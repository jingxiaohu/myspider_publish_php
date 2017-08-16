<?php

require_once( 'log.php' );

global $wpdb;
define( 'CR_WP_TABLE', $wpdb->prefix . 'cr_table' );

function cr_db_creat_table() {
	global $wpdb;
	/*
	* We'll set the default character set and collation for this table.
	* If we don't do this, some characters could end up being converted
	* to just ?'s when saved in our table.
	*/
	$charset_collate = '';

	if ( ! empty( $wpdb->charset ) ) {
		$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
	}

	if ( ! empty( $wpdb->collate ) ) {
		$charset_collate .= " COLLATE {$wpdb->collate}";
	}

	$sql = 'CREATE TABLE ' . CR_WP_TABLE . " (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		taskid int(16) unsigned NOT NULL,
		postid int(16) unsigned NOT NULL,
		url varchar(48) NOT NULL,
		PRIMARY KEY (id)
	) $charset_collate;";

	//L_Log::info( 'sql: ' . $sql );
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	//L_Log::info( 'create table' );
	dbDelta( $sql );
}

function cr_db_delete_table() {
	global $wpdb;
	$sql = 'DROP TABLE ' . CR_WP_TABLE;
	$wpdb->query( $sql );
}

function cr_db_insert_record( $task_id, $post_id, $url ) {
	global $wpdb;

	$data[ 'taskid' ] = $task_id;
	$data[ 'postid' ] = $post_id;
	$data[ 'url' ]    = $url;

	$wpdb->insert( CR_WP_TABLE, $data );
}

function cr_db_is_new_post( $url ) {
	global $wpdb;
	$str = $url;
	if ( 48 < strlen( $url ) ) {
		$str = md5( $url );
	}

	$sql_str = 'SELECT postid FROM ' . CR_WP_TABLE . " where url='$str';";
	$results = $wpdb->query( $sql_str );
	if ( 0 < $results ) {
		return false;
	} else {
		return true;
	}
}
