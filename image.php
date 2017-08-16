<?php

//require_once( ABSPATH . '/wp-includes/http.php' );
require_once( 'qiniu.php' );

function get_url_content( $url ) {
	//if ( function_exists( 'file_get_contents' ) ) {
	//	$file_contents = file_get_contents( $url );
	//} else {
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, $url );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5 );
	$file_contents = curl_exec( $ch );
	curl_close( $ch );

	//}

	return $file_contents;
}

function auto_save_image( $mode, $content ) {
	$return_value[ 'images' ] = array();
	//上传目录
	$date_str        = '/' . date( 'Y', time() ) . '/' . date( 'm', time() );
	$upload_path     = WP_CONTENT_DIR . '/uploads' . $date_str;
	$upload_url_path = home_url() . '/wp-content/uploads' . $date_str;

	mkdirs( $upload_path );

	$imgs = array();

	//以文章的标题作为图片的标题
	$text = stripslashes( $content );
	if ( get_magic_quotes_gpc() ) {
		$text = stripslashes( $text );
	}
	preg_match_all( "/ src=(\"|\'){0,}(http:\/\/(.+?))(\"|\'|\s)/is", $text, $imgs );

	$imgs = array_unique( dhtmlspecialchars( $imgs[ 2 ] ) );

	//$failed_host = '';
	foreach ( $imgs as $key => $value ) {
		/*
		if ( '' != $failed_host ) {
			if ( strpos( $value, $failed_host ) ) {
				L_Log::info( '跳过: ' . $value . '， 因为操作很可能会失败' );
				continue;
			}
		}
		*/

		if ( str_replace( home_url(), '', $value ) == $value && str_replace( home_url(), '', $value ) == $value ) {
			//判断是否是本地图片，如果不是，则保存到服务器
			$fileext = substr( strrchr( $value, '.' ), 1 );
			$fileext = strtolower( $fileext );
			if ( '' == $fileext || 4 < strlen( $fileext ) ) {
				$fileext = 'jpg';
			}
			$savefiletype = array( 'jpg', 'gif', 'png', 'bmp' );
			if ( in_array( $fileext, $savefiletype ) ) {
				$url      = '';
				list($t1, $t2) = explode(' ', microtime());
				$filename = date("Y-m-d-H-i-s-") . $t2*1000;
				if ( 1 == $mode ) {
					$url  = $upload_url_path . '/' . $filename . '.' . $fileext;
					$file = $upload_path . '/' . $filename . '.' . $fileext;

					$image_content = get_url_content( $value );
					if ( false == $image_content ) {
						//$tempu       = parse_url( $value );
						//$failed_host = $tempu[ 'host' ];
						L_Log::error( '保存图片: ' . $value . ' 失败' );
						$url = plugins_url( "crawling/404.jpg" );
						L_Log::error( '替换为: ' . $url );
						$text = str_replace( $value, $url, $text ); //替换文章里面的图片地址
						continue;
					}
					wp_upload_bits( basename( $file ), '', $image_content );
					$wp_filetype = wp_check_filetype( $file, false );

					$image_info[ 'url' ]      = $url;
					$image_info[ 'file' ]     = $file;
					$image_info[ 'type' ]     = $wp_filetype[ 'type' ];
					$image_info[ 'filename' ] = $filename;

					array_push( $return_value[ 'images' ], $image_info );
				} elseif ( 2 == $mode ) {
					$result = L_QiNiu::qiniu_fetch( $value, $filename . '.' . $fileext );
					if ( false == $result ) {
						//$tempu       = parse_url( $value );
						//$failed_host = $tempu[ 'host' ];
						L_Log::info( '七牛上传图片：' . $value . '失败' );
						$url = plugins_url( "404.jpg" );
						L_Log::error( '替换为: ' . $url );
						$text = str_replace( $value, $url, $text ); //替换文章里面的图片地址
						continue;
					} else {
						$url = L_QiNiu::qiniu_get_host() . '/' . $result[ 'key' ];
					}
				}

				$text = str_replace( $value, $url, $text ); //替换文章里面的图片地址
			}// End if().
		}// End if().
	}// End foreach().
	$return_value[ 'content' ] = AddSlashes( $text );

	return $return_value;
}

function mkdirs( $dir ) {
	if ( ! is_dir( $dir ) ) {
		mkdirs( dirname( $dir ) );
		mkdir( $dir );
	}

	return;
}

function dhtmlspecialchars( $string ) {
	if ( is_array( $string ) ) {
		foreach ( $string as $key => $val ) {
			$string[ $key ] = dhtmlspecialchars( $val );
		}
	} else {
		$string = str_replace( '&', '&', $string );
		$string = str_replace( '"', '"', $string );
		$string = str_replace( '<', '<', $string );
		$string = str_replace( '>', '>', $string );
		$string = preg_replace( '/&(#\d;)/', '&\1', $string );
	}

	return $string;
}
