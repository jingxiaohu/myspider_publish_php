<?php

require_once( 'log.php' );

define( 'QINIU_FETCH', 'http://iovip.qbox.me/fetch/' );

/*
 *
 * @desc URL安全形式的base64编码
 * @param string $str
 * @return string
 */
function urlsafe_base64_encode( $str ) {
	$find    = array( '+', '/' );
	$replace = array( '-', '_' );

	return str_replace( $find, $replace, base64_encode( $str ) );
}

/**
 * generate_access_token
 *
 * @desc 签名运算
 *
 * @param string $access_key
 * @param string $secret_key
 * @param string $url
 * @param array $params
 *
 * @return string
 */
function generate_access_token( $access_key, $secret_key, $url, $params = '' ) {
	$parsed_url = parse_url( $url );
	$path       = $parsed_url[ 'path' ];
	$access     = $path;
	if ( isset( $parsed_url[ 'query' ] ) ) {
		$access .= '?' . $parsed_url[ 'query' ];
	}
	$access .= "\n";
	if ( $params ) {
		if ( is_array( $params ) ) {
			$params = http_build_query( $params );
		}
		$access .= $params;
	}
	$digest = hash_hmac( 'sha1', $access, $secret_key, true );

	return $access_key . ':' . urlsafe_base64_encode( $digest );
}

/**
 * Post 请求
 */
function send( $url, $header = '' ) {
	$ch = curl_init( $url );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $ch, CURLOPT_HEADER, 1 );
	curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );
	curl_setopt( $ch, CURLOPT_POST, 1 );

	$con  = curl_exec( $ch );
	$info = curl_getinfo( $ch );

	if ( false == $con ) {
		L_Log::error( '七牛云上传失败，错误码: ' . $info[ 'http_code' ] );

		return false;
	} elseif ( 200 == $info[ 'http_code' ] ) {
		$index_l         = strpos( $con, '{"' ) - 1;
		$request_content = substr( $con, $index_l, strlen( $con ) - $index_l );

		return json_decode( $request_content, true );
	} else {
		L_Log::error( '七牛云上传失败，错误码: ' . $info[ 'http_code' ] );

		return false;
	}
}


class L_QiNiu {
	private static $_access_key = '';
	private static $_secret_key = '';
	private static $_space = '';
	private static $_prefix = '';
	private static $_host = '';

	public static function qiniu_set_key( $access_key, $secret_key ) {
		self::$_access_key = $access_key;
		self::$_secret_key = $secret_key;
	}

	public static function qiniu_set_space( $space, $prefix ) {
		self::$_space  = $space;
		self::$_prefix = $prefix;
	}

	public static function qiniu_set_host( $host ) {
		self::$_host = $host;
	}

	public static function qiniu_get_host() {
		return self::$_host;
	}

	public static function qiniu_fetch( $src, $dst ) {
		$fetch    = urlsafe_base64_encode( $src );
		$to       = urlsafe_base64_encode( self::$_space . ':' . self::$_prefix . date( 'Ymd-' ) . $dst );

		$url          = QINIU_FETCH . $fetch . '/to/' . $to;
		$access_token = generate_access_token( self::$_access_key, self::$_secret_key, $url );

		$header[] = 'Content-Type: application/json';
		$header[] = 'Authorization: QBox ' . $access_token;

		$con = send( QINIU_FETCH . $fetch . '/to/' . $to, $header );

		return $con;
	}

}
