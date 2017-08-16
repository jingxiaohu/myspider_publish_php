<?php

/**
 * log class
 */
class L_Log {
	private static $is_enable = false;
	public static $log_file = '';

	public static function init( $status ) {
		self::$is_enable = ( '1' == $status ) ? true : false;
		self::clear();
	}

	public static function clear() {
		self::$log_file = plugin_dir_path( __FILE__ ) . '/log.txt';
		$msg            = '';
		file_put_contents( self::$log_file, $msg );
	}

	public static function info( $msg ) {
		self::msg( $msg, ': [INFO] ' );
	}

	public static function note( $msg ) {
		self::msg( $msg, ': [NOTE] ' );
	}

	public static function warn( $msg ) {
		self::msg( $msg, ': [WARN] ' );
	}

	public static function error( $msg ) {
		self::msg( $msg, ': [ERROR] ' );
	}

	public static function debug( $msg ) {
		self::msg( $msg, ': [DEBUG] ' );
	}

	public static function msg( $msg, $log_type ) {
		if ( false == self::$is_enable ) {
			return;
		}
		self::$log_file = plugin_dir_path( __FILE__ ) . '/log.txt';

		$msg = date( 'Y-m-d H:i:s' ) . $log_type . $msg . PHP_EOL;
		file_put_contents( self::$log_file, $msg, FILE_APPEND | LOCK_EX );
	}
}
