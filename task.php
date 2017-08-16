<?php
/*
 * task
*/

require_once( 'request.php' );
require_once( 'image.php' );
require_once( 'qiniu.php' );
require_once( 'db.php' );
require_once( ABSPATH . 'wp-admin/includes/image.php' );

class L_Task_Run {

	private $_curl_request;
	private $_curl_multi_request_num = 1;
	private $_curl_delay_time = 1;
	private $_history_ok = array();
	private $_list_page_list = array();
	private $_list_page_content = array();
	private $_start_url = array();
	private $_input_encoding = null;
	private $_task_info = null;
	private $_post_category = array();
	private $_post_tag = array();
	private $_content_replace = array();

	public function init() {
		$this->_curl_request = new L_Curl_Request();
		$this->_curl_request->register_callback( $this, 'process_response' );
	}

	public function set_task_param( $task ) {
		$this->_start_url[ 'type' ] = 'LIST';
		$this->_start_url[ 'url' ]  = $task[ 'start_url' ];
		$this->_task_info           = $task;
		$this->_post_category       = empty( $task[ 'post_categray' ] ) ? array() : explode( '|',
			$task[ 'post_categray' ] );
		$this->_post_tag            = empty( $task[ 'post_tag' ] ) ? array() : explode( '|', $task[ 'post_tag' ] );
		$replace_strgroup           = empty( $task[ 'post_content_replace' ] ) ? array() : explode( '|',
			$task[ 'post_content_replace' ] );
		foreach ( $replace_strgroup as $replace_str ) {
			$str_group = explode( '>', $replace_str );
			if ( 2 == sizeof( $str_group ) ) {
				array_push( $this->_content_replace, $str_group );
			}
		}


		$url_info = parse_url( $this->_start_url[ 'url' ] );
		$this->_curl_request->set_host( $url_info[ 'host' ] );

		if ( 2 == $this->_task_info[ 'post_content_image' ] ) {
			$qiniu_ak     = get_option( 'cr_qiniu_ak', '' );
			$qiniu_sk     = get_option( 'cr_qiniu_sk', '' );
			$qiniu_space  = get_option( 'cr_qiniu_space', '' );
			$qiniu_host   = get_option( 'cr_qiniu_host', '' );
			$qiniu_prefix = get_option( 'cr_qiniu_prefix', '' );

			if ( ( empty( $qiniu_ak ) ) || ( empty( $qiniu_sk ) ) ) {
				L_Log::error( '七牛AccessKey SecurityKey未设置，不上传' );
				$this->_task_info[ 'post_content_image' ] = 0;
			} else if ( empty( $qiniu_space ) ) {
				L_Log::error( '七牛空间名未设置，不上传' );
				$this->_task_info[ 'post_content_image' ] = 0;
			} else if ( empty( $qiniu_host ) ) {
				L_Log::error( '七牛域名未设置，不上传' );
				$this->_task_info[ 'post_content_image' ] = 0;
			} else {
				L_QiNiu::qiniu_set_key( $qiniu_ak, $qiniu_sk );
				L_QiNiu::qiniu_set_space( $qiniu_space, $qiniu_prefix );
				L_QiNiu::qiniu_set_host( $qiniu_host );
			}
		}
	}

	public function set_multi_request_num( $num ) {
		$this->_curl_multi_request_num = $num;
		$this->_curl_request->set_multi_request_num( $num );
	}

	public function set_delay_times( $second ) {
		$this->_curl_delay_time = $second;
	}

	public function set_max_failure_times() {
		;
	}

	public function run() {
		remove_filter( 'content_save_pre', 'wp_filter_post_kses' );
		remove_filter( 'content_filtered_save_pre', 'wp_filter_post_kses' );

		L_Log::info( '[运行]最大线程数：' . $this->_curl_multi_request_num );

		if ( empty( $this->_task_info[ 'page_content' ] ) ) {
			L_Log::error( '内容页面的xpath规则未设置' );

			return 0;
		}

		L_Log::warn( '进入起始页：' . $this->_start_url[ 'url' ] );
		$this->_curl_request->do_request( array( 0 => $this->_start_url ) );

		$page_number  = 0;
		$catch_number = 0;
		while ( 1 ) {
			$cr_running = get_option( 'cr_running', 0 );
			if ( 1 != $cr_running ) {
				L_Log::warn( '插件被停止，退出' );
				break;
			}

			if ( ! empty( $this->_list_page_content ) ) {
				$requests = array_splice( $this->_list_page_content, 0, $this->_curl_multi_request_num );
				$this->_curl_request->do_request( $requests );
				$catch_number = $catch_number + $this->_curl_multi_request_num;

			} elseif ( ! empty( $this->_list_page_list ) ) {
				if ( 0 >= $this->_task_info[ 'page_list_times' ] ) {
					L_Log::warn( '翻页次数为：' . $this->_task_info[ 'page_list_times' ] . '不翻页' );
					break;
				}
				$page_number ++;
				if ( $page_number > $this->_task_info[ 'page_list_times' ] ) {
					L_Log::warn( '翻页次数为：' . $this->_task_info[ 'page_list_times' ] . '，下一页面：' . $page_number . '，不再翻页' );
					break;
				}
				L_Log::warn( '准备进入第' . $page_number . '页' );
				$requests = array_splice( $this->_list_page_list, 0, 1 );
				$this->_curl_request->do_request( $requests );
			} else {
				break;
			}

			if ( function_exists( 'sleep' ) ) {
				sleep( $this->_curl_delay_time );
			}
		}

		add_filter( 'content_save_pre', 'wp_filter_post_kses' );
		add_filter( 'content_filtered_save_pre', 'wp_filter_post_kses' );

		return $catch_number;
	}

	public function process_response( $info, $body, $request, $error ) {
		// L_Log::info( "body: $body" );
		if ( empty( $error ) ) {
			if ( false == in_array( $request[ 'url' ], $this->_history_ok ) ) {
				array_push( $this->_history_ok, $request[ 'url' ] );
			}

			$body = $this->get_response_body( $info, $body );
			if ( 'CONTENT' == $request[ 'type' ] ) {
				$this->process_content_page( $request[ 'url' ], $body );
			} else { //if ( 'LIST' == $request['type'] ) {
				$this->process_list_page( $request[ 'url' ], $body );
			}
		} else {
			L_Log::error( '请求页面失败：' . $request[ 'url' ] );// TO DO: curl failed
		}
	}

	private function get_response_body( $info, $body ) {
		// get encoding type
		if ( null == $this->_input_encoding ) {
			preg_match( '/charset=([^\s]*)/i', $info[ 'content_type' ], $out );
			$encoding = empty( $out[ 1 ] ) ? '' : str_replace( array( '"', '\'' ), '',
				strtolower( trim( $out[ 1 ] ) ) );
			if ( empty( $encoding ) ) {
				// get from header
				preg_match( '/charset=([^\s]*)/i', $body, $out );
				if(empty( $out[ 1 ] )) {
					$encoding = '';
				}
				else {
					$encoding = str_replace( array( '"', '\'', '>' ), '', strtolower( trim( $out[ 1 ] ) ) );
				}
				if ( empty( $encoding ) ) {
					L_Log::error( '不能获取页面编码方式' );
				}
			}

			$this->_input_encoding = $encoding;
		}

		// convert to UTF-8
		if ( 'UTF-8' != $this->_input_encoding && 'utf-8' != $this->_input_encoding && null != $this->_input_encoding ) {
			$body = mb_convert_encoding( $body, 'UTF-8', $this->_input_encoding );
			// replace the meta info
			$body = str_replace( $this->_input_encoding, 'UTF-8', $body );
			if(($this->_input_encoding == 'gb2312') || ($this->_input_encoding == 'gbk')) {
				$body = str_replace( 'gb2312', 'UTF-8', $body );
				$body = str_replace( 'gbk', 'UTF-8', $body );
			}
		}

		return $body;
	}

	public function process_list_page( $url, $body ) {
		$document           = new DOMDocument();
		$document->encoding = 'UTF-8';
		@$document->loadHTML( '<?xml encoding="UTF-8">' . $body );
		$document->normalize();
		$xpath = new DOMXPath( $document );

		if ( empty( $this->_task_info[ 'page_content' ] ) ) {
			L_Log::warn( '内容页面xpath未设置，无法匹配要采集文章' );

			return;
		}

		$links_content = $xpath->query( $this->_task_info[ 'page_content' ] );
		if ( false === $links_content ) {
			L_Log::warn( '列表页面xpath匹配内容页面url错误' );
		}
		if ( null === $links_content ) {
			L_Log::warn( '列表页面xpath匹配内容页面url失败' );
		}
		if ( empty( $links_content ) ) {
			L_Log::warn( '没有匹配到内容页面链接' );
		}
		foreach ( $links_content as $link ) {
			$match_url = $link->getAttribute( 'href' );
			if ( empty( $match_url ) ) {
				continue;
			}
			if ( true == strpos( $url, $match_url ) ) {
				continue;
			}

			if ( true == in_array( $match_url, $this->_history_ok ) ) {
				continue;
			}

			$new_request = array( 'url' => $match_url, 'type' => 'CONTENT' );
			if ( false == in_array( $new_request, $this->_list_page_content ) ) {
				if ( false == cr_db_is_new_post( $match_url ) ) {
					L_Log::info( '不是新页面：' . $match_url );
				} else {
					array_push( $this->_list_page_content, $new_request );

					L_Log::info( '发现内容页面：' . $new_request[ 'url' ] );
				}
			}
		}

		if ( empty( $this->_task_info[ 'page_list' ] ) ) {
			L_Log::warn( '下一页xpath未设置，不采集下一页' );
		} else {
			$links_list = $xpath->query( $this->_task_info[ 'page_list' ] );
			if ( false === $links_list ) {
				L_Log::warn( '列表页面xpath匹配列表页面url错误' );
			}
			if ( null === $links_list ) {
				L_Log::warn( '列表页面xpath匹配列表页面url失败' );
			}
			if ( empty( $links_list ) ) {
				L_Log::warn( '没有匹配到列表页面链接' );
			}
			foreach ( $links_list as $link ) {
				$match_url = $link->getAttribute( 'href' );
				if ( empty( $match_url ) ) {
					continue;
				}
				if ( true == strpos( $url, $match_url ) ) {
					continue;
				}

				if ( true == in_array( $match_url, $this->_history_ok ) ) {
					continue;
				}

				$new_request = array( 'url' => $match_url, 'type' => 'LIST' );
				if ( false == in_array( $new_request, $this->_list_page_list ) ) {
					array_push( $this->_list_page_list, $new_request );

					L_Log::info( '发现列表页：' . $new_request[ 'url' ] );
				}
			}
		}

	}

	public function process_content_page( $url, $body ) {
		$document           = new DOMDocument();
		$document->encoding = 'UTF-8';
		@$document->loadHTML( '<?xml encoding="UTF-8">' . $body );
		$document->normalize();
		$xpath = new DOMXPath( $document );

		L_Log::info( '开始采集文章url：' . $url );

		$title = $xpath->query( $this->_task_info[ 'post_title' ] );
		if ( 0 == $title->length ) {
			L_Log::error( '页面标题xpath匹配失败：' . $url );

			return;
		}
		$title = trim( $title->item( 0 )->textContent );
		L_Log::info( '[标题]：' . $title );

		$content_list = $xpath->query( $this->_task_info[ 'post_content' ] );
		if ( 0 == $content_list->length ) {
			L_Log::error( '页面内容xpath匹配失败：' . $url );

			return;
		}

		$content = '';
		foreach ( $content_list as $content_item ) {
			if ( version_compare( PHP_VERSION, '5.3.0', 'ge' ) ) {
				$content .= $document->saveHTML( $content_item );
			} else {
				$content .= $document->saveXML( $content_item );
			}
		}

		if ( $this->_task_info[ 'post_content_cut' ] > 0 )
		{
			if ( $this->_task_info[ 'post_content_cut' ] == 1 ) {
				$content .= $this->get_all_cutpage_content1( $url, $xpath );
			} else if ( $this->_task_info[ 'post_content_cut' ] == 2 ) {
				$content .= $this->get_all_cutpage_content2( $url, $xpath );
			}
		}

		if ( '' != $this->_task_info[ 'post_content_start' ] ) {
			$pos = strrpos( $content, $this->_task_info[ 'post_content_start' ] );
			if ( false != $pos ) {
				$content = substr( $content, $pos + strlen( $this->_task_info[ 'post_content_start' ] ));
			}
		}

		if ( '' != $this->_task_info[ 'post_content_end' ] ) {
			$pos = strrpos( $content, $this->_task_info[ 'post_content_end' ] );
			if ( false != $pos ) {
				$content = substr( $content, 0, $pos );
			}
		}
		$this->insert_post( $url, $title, $content );
	}

	public function insert_post( $url, $title, $content ) {
		$result_value = '';
		//L_Log::info( '[content]:' . $content );
		if ( 0 != $this->_task_info[ 'post_content_image' ] ) {
			$result_value = auto_save_image( $this->_task_info[ 'post_content_image' ], $content );
			$content      = $result_value[ 'content' ];
		}
		//L_Log::info( '[content replace]:' . $content );

		$id = $this->insert_page( $title, $content );
		if ( false == $id ) {
			L_Log::info( '插入文章失败：' . $title );

			return;
		}

		if ( 1 == $this->_task_info[ 'post_content_image' ] ) {
			foreach ( $result_value[ 'images' ] as $image_info ) {
				$attachment  = array(
					'post_type'      => 'attachment',
					'guid'           => $image_info[ 'url' ],
					'post_mime_type' => $image_info[ 'type' ],
					'post_title'     => $title,
					'post_content'   => '',
					'post_status'    => 'inherit',
					'post_author'    => $this->_task_info[ 'post_writer' ],
				);
				$attach_id   = wp_insert_attachment( $attachment, $image_info[ 'file' ], $id );
				$attach_data = wp_generate_attachment_metadata( $attach_id, $image_info[ 'file' ] );
				wp_update_attachment_metadata( $attach_id, $attach_data );

				set_post_thumbnail( $id, $attach_id );
			}
		}

		if ( 48 < strlen( $url ) ) {
			$md5_str = md5( $url );
			cr_db_insert_record( $this->_task_info[ 'task_id' ], $id, $md5_str );
		} else {
			cr_db_insert_record( $this->_task_info[ 'task_id' ], $id, $url );
		}
	}

	public function insert_page( $title, $content ) {
		$publish = ( 1 == $this->_task_info[ 'post_publish' ] ) ? 'publish' : 'draft';

		$category = $this->_post_category;
		if ( empty( $category ) ) {
			L_Log::info( '未设置分类，在标题中自动匹配' );
			$categories = get_categories( array( 'hide_empty' => 0 ) );
			foreach ( $categories as $cat_item ) {
				$tmp = strstr( $title, $cat_item->name );
				if ( ! empty( $tmp ) ) {
					L_Log::info( '匹配到：' . $cat_item->name );
					array_push( $category, $cat_item->term_id );
				}
			}
		}

		if ( empty( $category ) ) {
			L_Log::info( '未设置分类，在内容中自动匹配' );
			$categories = get_categories( array( 'hide_empty' => 0 ) );
			foreach ( $categories as $cat_item ) {
				$tmp = strstr( $content, $cat_item->name );
				if ( ! empty( $tmp ) ) {
					L_Log::info( '匹配到：' . $cat_item->name );
					array_push( $category, $cat_item->term_id );
				}
			}
		}

		$tag = $this->_post_tag;
		if ( empty( $tag ) ) {
			L_Log::info( '未设置标签，在标题和内容中自动匹配' );
			$tags = get_tags( array( 'hide_empty' => 0 ) );
			foreach ( $tags as $tag_item ) {
				$tmp1 = strstr( $title, $tag_item->name );
				$tmp2 = strstr( $content, $tag_item->name );
				if ( ! empty( $tmp1 ) || ! empty( $tmp2 ) ) {
					L_Log::info( '匹配到：' . $tag_item->name );
					array_push( $tag, $tag_item->name );
				}
			}
		}

		foreach ( $this->_content_replace as $replace_str ) {
			L_Log::info( '关键字替换：' . $replace_str[ 0 ] . '--' . $replace_str[ 1 ] );
			$title = str_replace( $replace_str[ 0 ], $replace_str[ 1 ], $title );
			$content = str_replace( $replace_str[ 0 ], $replace_str[ 1 ], $content );
		}

		if((!empty($this->_task_info[ 'post_content_add' ])) && ($this->_task_info[ 'post_content_add' ] != "undefined"))
		{
			$content .= $this->_task_info[ 'post_content_add' ];
		}

		$post     = array(
			'post_title'    => $title,
			'post_content'  => $content,
			'post_category' => $category,
			'tags_input'    => $tag,
			'post_status'   => $publish,
			'post_author'   => $this->_task_info[ 'post_writer' ],
		);
		$wp_error = '';
		$ret      = wp_insert_post( $post, $wp_error );

		//L_Log::error( 'insert error: ' . $error );
		return $ret;
	}

	private function get_all_cutpage_content1( $url, $xpath ) {
		$content = '';
		$times   = 0;
		while ( 1 ) {
			$link_cut = $xpath->query( $this->_task_info[ 'post_content_cutrule' ] );
			if ( 0 == $link_cut->length ) {
				L_Log::info( '页面分页翻页' . $times . '次，完成：' . $url );

				return $content;
			}

			$next_url = $link_cut[ 0 ]->getAttribute( 'href' );
			$body     = $this->_curl_request->single_curl_page_request( $next_url );

			if ( null != $body ) {
				$document           = new DOMDocument();
				$document->encoding = 'UTF-8';
				@$document->loadHTML( '<?xml encoding="UTF-8">' . $body );
				$document->normalize();
				$xpath = new DOMXPath( $document );

				$content_list = $xpath->query( $this->_task_info[ 'post_content' ] );
				if ( 0 == $content_list->length ) {
					L_Log::error( '分页页面内容xpath匹配失败：' . $url );

					return $content;
				}

				foreach ( $content_list as $content_item ) {
					if ( version_compare( PHP_VERSION, '5.3.0', 'ge' ) ) {
						$content .= $document->saveHTML( $content_item );
					} else {
						$content .= $document->saveXML( $content_item );
					}
				}
				$times ++;
			} else {
				return $content;
			}
		}

		return $content;
	}

	private function get_all_cutpage_content2( $url, $xpath ) {
		$content   = '';
		$links_cut = $xpath->query( $this->_task_info[ 'post_content_cutrule' ] );
		if ( 0 == $links_cut->length ) {
			L_Log::error( '页面分页下一页链接xpath匹配失败：' . $url );

			return $content;
		}

		foreach ( $links_cut as $url ) {
			$next_url = $url->getAttribute( 'href' );
			$body     = $this->_curl_request->single_curl_page_request( $next_url );
			if ( null != $body ) {
				$document           = new DOMDocument();
				$document->encoding = 'UTF-8';
				@$document->loadHTML( '<?xml encoding="UTF-8">' . $body );
				$document->normalize();
				$xpath = new DOMXPath( $document );

				$content_list = $xpath->query( $this->_task_info[ 'post_content' ] );
				if ( 0 == $content_list->length ) {
					L_Log::error( '分页页面内容xpath匹配失败：' . $url );

					return $content;
				}

				foreach ( $content_list as $content_item ) {
					if ( version_compare( PHP_VERSION, '5.3.0', 'ge' ) ) {
						$content .= $document->saveHTML( $content_item );
					} else {
						$content .= $document->saveXML( $content_item );
					}
				}
			} else {
				return $content;
			}
		}

		return $content;
	}
}
