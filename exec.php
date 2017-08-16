<?php

function do_this_hourly() {
	date_default_timezone_set( 'PRC' );
	$cr_running = get_option( 'cr_running', 0 );
	if ( 1 != $cr_running ) {
		L_Log::warn( '插件未运行' );

		return;
	}

	$log_enable = get_option( 'cr_log_enable', 0 );
	$max_thread = get_option( 'cr_max_thread', 1 );
	$skip_time  = get_option( 'cr_delay', 1 );
	$task_list  = get_option( 'cr_tasklist', '' );
	$task_objs  = json_decode( $task_list, true );

	L_Log::init( $log_enable );

	try {
		set_time_limit( 0 );
	} catch ( Exception $e ) {
		L_Log::warn( '服务器不支持set_time_limit()函数，插件单个任务运行时间过长会被强制停掉' );
	}

	for ( $i = 0; $i < count( $task_objs ); $i ++ ) {
		$task = $task_objs[ $i ];
		$doit = false;
		L_Log::info( '任务名称: ' . $task[ 'task_name' ] . ' 上次执行时间: ' . $task[ 'last_time' ] );
		if ( '--' == $task[ 'last_time' ] ) {
			$doit = true;
		} elseif ( 0 < $task[ 'skip_time' ] ) {
			$last_timesecond = strtotime( $task[ 'last_time' ] );
			if ( ( time() - $last_timesecond ) > $task[ 'skip_time' ] * 60 * 60 ) {
				$doit = true;
			}
		}

		if ( true == $doit ) {
			L_Log::info( '执行任务: ' . $task[ 'task_name' ] );

			$new_task = new L_Task_Run();
			$new_task->init();
			$new_task->set_task_param( $task );
			$new_task->set_multi_request_num( $max_thread );
			$new_task->set_delay_times( $skip_time );

			$task[ 'catch_nums' ] + $new_task->run();
			global $wpdb;
			$sql_str = "select COUNT(*) AS catch_num from wp_cr_table inner join wp_posts on wp_cr_table.postid = wp_posts.ID
				WHERE (wp_posts.post_date BETWEEN '" . $task_objs[ $i ][ 'last_time' ] . "' AND '2099-01-01 00:00:00') AND taskid=" . $task['task_id'];
			$results = $wpdb->get_results( $sql_str );
			$catch_count = $results[0]->catch_num;
			$task_objs[ $i ][ 'catch_nums' ] += $catch_count;
			$task_objs[ $i ][ 'last_time' ]  = date( 'Y-m-d H:i:s', time() );
			L_Log::info( '任务执行结束' );
		}
	}
	update_option( 'cr_tasklist', json_encode( $task_objs ), true );
}

function do_task_now() {
	date_default_timezone_set( 'PRC' );
	$run_index = get_option( 'cr_run_index' );
	update_option( 'cr_run_index', '', true );
	if ( false == is_numeric( $run_index ) ) {
		return;
	}

	$log_enable = get_option( 'cr_log_enable', 0 );
	L_Log::init( $log_enable );
	$cr_running = get_option( 'cr_running', 0 );
	if ( 1 != $cr_running ) {
		L_Log::warn( '插件未运行' );

		return;
	}

	$max_thread = get_option( 'cr_max_thread', 1 );
	$skip_time  = get_option( 'cr_delay', 1 );
	$task_list  = get_option( 'cr_tasklist', '' );
	$task_objs  = json_decode( $task_list, true );
	if ( $run_index >= count( $task_objs ) ) {
		L_Log::error( '任务 ' . $run_index . '出错' );

		return;
	}

	try {
		set_time_limit( 0 );
	} catch ( Exception $e ) {
		L_Log::warn( '服务器不支持set_time_limit()函数，插件单个任务运行时间过长会被强制停掉' );
	}

	$task = $task_objs[ $run_index ];

	L_Log::info( '执行任务: ' . $task[ 'task_name' ] );

	$new_task = new L_Task_Run();
	$new_task->init();
	$new_task->set_task_param( $task );
	$new_task->set_multi_request_num( $max_thread );
	$new_task->set_delay_times( $skip_time );

	$task[ 'catch_nums' ] + $new_task->run();
	global $wpdb;
	$sql_str = "select COUNT(*) AS catch_num from wp_cr_table inner join wp_posts on wp_cr_table.postid = wp_posts.ID
				WHERE (wp_posts.post_date BETWEEN '" . $task_objs[ $run_index ][ 'last_time' ] . "' AND '2099-01-01 00:00:00') AND taskid=" . $task['task_id'];
	$results = $wpdb->get_results( $sql_str );
	$catch_count = $results[0]->catch_num;
	$task_objs[ $run_index ][ 'catch_nums' ] += $catch_count;
	$task_objs[ $run_index ][ 'last_time' ]  = date( 'Y-m-d H:i:s', time() );
	L_Log::info( '任务执行结束' );

	update_option( 'cr_tasklist', json_encode( $task_objs ), true );
}
