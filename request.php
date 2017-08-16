<?php
/*
 * request
 */

require_once( 'log.php' );

class L_Curl_Request {

	private $_requests = array();
	private $_multi_request_num = 3;
	private $_callback_obj = null;
	private $_callback_fuc = null;
	private $_request_map = null;
	private $_timeout = 3;
	private $_host = '';

	private $_options = array(
		CURLOPT_SSL_VERIFYPEER => 0,
		CURLOPT_RETURNTRANSFER => 1,
		// 注意：TIMEOUT = CONNECTTIMEOUT + 数据获取时间，所以 TIMEOUT 一定要大于 CONNECTTIMEOUT，否则 CONNECTTIMEOUT 设置了就没意义
		// "Connection timed out after 30001 milliseconds"
		CURLOPT_CONNECTTIMEOUT => 20,
		CURLOPT_TIMEOUT        => 20,
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_HEADER         => 0,
		// 在多线程处理场景下使用超时选项时，会忽略signals对应的处理函数，但是无耐的是还有小概率的crash情况发生
		CURLOPT_NOSIGNAL       => 1,
		// CURLOPT_FOLLOWLOCATION => 1, //是否抓取跳转后的页面, 301/302
		CURLOPT_USERAGENT      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.89 Safari/537.36',
	);


	function set_multi_request_num( $num ) {
		if ( $num > 0 ) {
			$this->_multi_request_num = $num;
		}
	}

	function set_host( $host ) {
		if ( '' != $host ) {
			$this->_host = $host;
		}
	}

	function register_callback( $obj, $fuc ) {
		$this->_callback_obj = $obj;
		$this->_callback_fuc = $fuc;
	}

	function do_request( $request = array() ) {
		$this->_requests = $request;
		if ( ( count( $this->_requests ) > 1 ) && ( $this->_multi_request_num > 1 ) ) {
			$this->multi_curl_request();
		} else {
			while ( 1 ) {
				$request = array_pop( $this->_requests );
				if ( null != $request ) {
					//echo 'request: ' . $request['url'] . PHP_EOL;
					$this->single_curl_request( $request );
				} else {
					break;
				}
			}
		}
	}

	private function format_url_absolutly( $url ) {
		preg_match( '/^(http|https|ftp):\/\//i', $url, $protocol );
		if ( 0 < count( $protocol ) ) {
			return $url;
		}

		if ( '/' == substr( $url, 0, 1 ) ) {
			$url = $this->_host . $url;
		} else {
			$url = $this->_host . '/' . $url;
		}

		return $url;
	}

	public function single_curl_page_request( $url ) {
		$ch = curl_init();
		curl_setopt_array( $ch, $this->_options );
		$url = $this->format_url_absolutly( $url );
		curl_setopt( $ch, CURLOPT_URL, $url );
		$output = curl_exec( $ch );
		$error  = null;
		if ( false == $output ) {
			$error = curl_error( $ch );
			if ( ! empty( $error ) ) {
				return null;
			}
		}

		return $output;
	}

	private function single_curl_request( $request ) {
		$ch = curl_init();
		curl_setopt_array( $ch, $this->_options );
		$url = $this->format_url_absolutly( $request[ 'url' ] );
		curl_setopt( $ch, CURLOPT_URL, $url );
		$output = curl_exec( $ch );
		$info   = curl_getinfo( $ch );
		$error  = null;
		if ( false == $output ) {
			$error = curl_error( $ch );
		}

		if ( $this->_callback_obj ) {
			if ( is_callable( array( $this->_callback_obj, $this->_callback_fuc ) ) ) {
				call_user_func( array( $this->_callback_obj, $this->_callback_fuc ), $info, $output, $request, $error );
			} else {
				L_Log::error( 'request response callback is invalid' );
			}
		}
	}

	private function multi_curl_request() {
		// 初始化任务队列
		$multi_curl = curl_multi_init();

		// 开始第一批请求
		$multi_request_num = $this->_multi_request_num < count( $this->_requests ) ? $this->_multi_request_num : count( $this->_requests );
		for ( $i = 0; $i < $multi_request_num; $i ++ ) {
			$ch = curl_init();
			curl_setopt_array( $ch, $this->_options );
			$request = array_pop( $this->_requests );
			//echo 'request: ' . $request['url'] . PHP_EOL;
			$url = $this->format_url_absolutly( $request[ 'url' ] );
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_multi_add_handle( $multi_curl, $ch );

			// 添加到请求数组
			$key                        = (string) $ch;
			$this->_request_map[ $key ] = $request;
		}

		do {
			do {
				$execrun = curl_multi_exec( $multi_curl, $running );
			} while ( CURLM_CALL_MULTI_PERFORM == $execrun );

			// 如果
			if ( CURLM_OK != $execrun ) {
				break;
			}

			// 一旦有一个请求完成，找出来，因为curl底层是select，所以最大受限于1024
			while ( $done = curl_multi_info_read( $multi_curl ) ) {
				// 从请求中获取信息、内容、错误
				$info    = curl_getinfo( $done[ 'handle' ] );
				$output  = curl_multi_getcontent( $done[ 'handle' ] );
				$key     = (string) $done[ 'handle' ];
				$request = $this->_request_map[ $key ];
				$error   = null;

				if ( false == $output ) {
					$error = curl_error( $done[ 'handle' ] );
				}

				$ch = $this->_request_map[ $key ];
				unset( $this->_request_map[ $key ] );
				if ( $this->_callback_obj ) {
					if ( is_callable( array( $this->_callback_obj, $this->_callback_fuc ) ) ) {
						call_user_func( array( $this->_callback_obj, $this->_callback_fuc ), $info, $output, $request,
							$error );
					} else {
						L_Log::error( 'request response callback is invalid' );
					}
				}

				// 一个请求完了，就加一个进来，一直保证5个任务同时进行
				if ( ! empty( $this->_requests ) ) {
					$request = array_pop( $this->_requests );
					//echo 'request: ' . $request['url'] . PHP_EOL;
					$ch = curl_init();
					curl_setopt_array( $ch, $this->_options );
					curl_setopt( $ch, CURLOPT_URL, $request[ 'url' ] );
					curl_multi_add_handle( $multi_curl, $ch );

					// 添加到请求数组
					$key                        = (string) $ch;
					$this->_request_map[ $key ] = $request;
				}
				// 把请求已经完成了得 curl handle 删除
				curl_multi_remove_handle( $multi_curl, $done[ 'handle' ] );

				// sleep( 1 );
			} // End while().

			// 当没有数据的时候进行堵塞，把 CPU 使用权交出来，避免上面 do 死循环空跑数据导致 CPU 100%
			if ( $running ) {
				curl_multi_select( $multi_curl, $this->_timeout );
			}
		} while ( $running );
		// 关闭任务
		curl_multi_close( $multi_curl );
	}
}
