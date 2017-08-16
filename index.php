<?php
/*
Plugin Name: Crawling
Plugin URI: http://crawling.cn/
Description: 一个简洁、直观、强悍的Wordpress自动发布插件，迅速、简单、高效。
Version: 1.0
Author: 爬虫漫步
Author URI: http://crawling.cn/
License: GPL2
*/

//require_once( 'task.php' );
//require_once( 'log.php' );
require_once( 'pages.php' );
require_once( 'db.php' );
require_once( 'exec.php' );
//
function cr_schedule_time() {
	return array(
		'second' => array( 'interval' => 60 * 60, 'display' => 'CR Custom' ),
	);
}

add_filter( 'cron_schedules', 'cr_schedule_time' );
//

register_activation_hook( __FILE__, 'cr_activation' );
register_deactivation_hook( __FILE__, 'cr_deactivation' );
register_uninstall_hook( __FILE__, 'cr_uninstall' );
add_action( 'cr_hourly_event', 'do_this_hourly' );

function cr_activation() {
	L_Log::info( '启用插件' );
	//wp_schedule_event( time(), 'hourly', 'cr_hourly_event' );
	wp_schedule_event( time(), 'second', 'cr_hourly_event' );
	cr_db_creat_table();
}

function cr_deactivation() {
	L_Log::info( '禁用插件' );
	wp_clear_scheduled_hook( 'cr_hourly_event' );
}

function cr_uninstall() {
	L_Log::info( '删除插件' );
	cr_db_delete_table();
}

if ( is_admin() ) {
	add_action( 'admin_menu', 'display_cr_menu' );
	add_action( 'admin_enqueue_scripts', 'register_cr_scripts_css' );
	add_action( 'admin_init', 'register_cr_settings' );
}

function display_cr_menu() {
	add_menu_page( 'Crawling选项', 'Crawling选项', 'administrator', 'cr-admin-menu', 'cr_function_submenu3', '', 100 );
	//add_submenu_page( 'cr-admin-menu', '任务管理', '任务管理', 'administrator', 'cr-admin-menu', 'cr_function_submenu1' );
	//add_submenu_page( 'cr-admin-menu', '全局设置', '全局设置', 'administrator', 'cr-admin-sub-menu1', 'cr_function_submenu2' );
	add_submenu_page( 'cr-admin-menu', '采集历史', '采集历史', 'administrator', 'cr-admin-sub-menu2', 'cr_function_submenu3' );
	//add_submenu_page( 'cr-admin-menu', '日志', '日志', 'administrator', 'cr-admin-sub-menu3', 'cr_function_submenu4' );
	//add_submenu_page( 'cr-admin-menu', '关于', '关于', 'administrator', 'cr-admin-sub-menu4', 'cr_function_submenu5' );
	//add_submenu_page( 'cr-admin-menu', '关于', '关于', 'administrator', 'cr-admin-sub-menu4', 'cr_function_submenu5' );
	add_submenu_page('crawling/options.php', '选项执行', '选项执行',  'administrator', 'crawling/options.php');
}


function register_cr_scripts_css() {
	wp_register_script( 'jquery', plugins_url( 'jquery/jquery.min.js', __FILE__ ) );
	wp_register_script( 'bootstrap_js', plugins_url( 'bootstrap/js/bootstrap.min.js', __FILE__ ) );
	wp_register_style( 'bootstrap_css', plugins_url( 'bootstrap/css/bootstrap.min.css', __FILE__ ) );
	wp_register_script( 'cr_js', plugins_url( 'script.js', __FILE__ ) );
	wp_register_style( 'cr_css', plugins_url( 'style.css', __FILE__ ) );

	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'bootstrap_js' );
	wp_enqueue_style( 'bootstrap_css' );
	wp_enqueue_script( 'bootstrap_treeview_js' );
	wp_enqueue_style( 'bootstrap_treeview_css' );
	wp_enqueue_script( 'cr_js' );
	wp_enqueue_style( 'cr_css' );
}

function register_cr_settings() {
	register_setting( 'cr_settings', 'cr_tasklist' );
	register_setting( 'cr_settings', 'cr_run_index' );
	register_setting( 'cr_settings_run', 'cr_running' );
	register_setting( 'cr_settings_more', 'cr_log_enable' );
	register_setting( 'cr_settings_more', 'cr_max_thread' );
	register_setting( 'cr_settings_more', 'cr_delay' );
	register_setting( 'cr_settings_more', 'cr_qiniu_ak' );
	register_setting( 'cr_settings_more', 'cr_qiniu_sk' );
	register_setting( 'cr_settings_more', 'cr_qiniu_space' );
	register_setting( 'cr_settings_more', 'cr_qiniu_host' );
	register_setting( 'cr_settings_more', 'cr_qiniu_prefix' );
	register_setting( 'cr_history', 'cr_delete_ids' );
	register_setting( 'cr_history', 'cr_show_page_no' );
	register_setting( 'cr_history', 'cr_show_item_num' );
}

add_filter( 'pre_update_option', 'cr_pre_update_option_cache', 10, 2 );

function cr_pre_update_option_cache( $value, $option ) {
	wp_cache_delete( 'notoptions', 'options' );
	wp_cache_delete( 'alloptions', 'options' );
	wp_cache_delete( $option, 'options' );

	return $value;
}
