<?php




function cr_category_list() {
	$categories       = get_terms( 'category', 'hide_empty=0&hierarchical=true' );
	$categories_order = array();
	while ( 0 < count( $categories ) ) {
		$categories     = array_merge( $categories );
		$categories_tmp = $categories;
		for ( $i = 0; $i < count( $categories_tmp ); $i ++ ) {
			if ( 0 == $categories_tmp[ $i ]->parent ) {
				array_push( $categories_order, $categories_tmp[ $i ] );
				unset( $categories[ $i ] );
			} else {
				$parent_id = $categories_tmp[ $i ]->parent;
				for ( $j = 0; $j < count( $categories_order ); $j ++ ) {
					if ( $parent_id == $categories_order[ $j ]->term_id ) {
						array_splice( $categories_order, $j + 1, 0, '' );
						$categories_order[ $j + 1 ] = $categories[ $i ];
						unset( $categories[ $i ] );
						break;
					}
				}
			}
		}
	}

	foreach ( $categories_order as $category ) {
		$depth  = 0;
		$parent = $category->parent;
		$run    = true;
		while ( true == $run ) {
			$run = false;
			for ( $i = 0; $i < count( $categories_order ); $i ++ ) {
				if ( $parent == $categories_order[ $i ]->term_id ) {
					$parent = $categories_order[ $i ]->parent;
					$depth ++;
					$run = true;
					break;
				}
			}
		}
		$start_tag = '';
		for ( $i = 0; $i < $depth; $i ++ ) {
			$start_tag .= '&nbsp;&nbsp;&nbsp;&nbsp;';
		}
		echo '<p>' . $start_tag . '<input type="checkbox" name="category_item" value="' . $category->term_id . '" />' . $category->name . '</p>';
	}
}



//采集历史
function cr_function_submenu3() {
	global $wpdb;
	$delete_ids = get_option( 'cr_delete_ids' );
	if ( !empty( $delete_ids ) ) {
		$ids = explode( '|', $delete_ids );
		foreach( $ids as $id ) {
			$sql_str = 'DELETE FROM wp_posts WHERE ID = ' . $id;
			$wpdb->query( $sql_str );
			$sql_str = 'DELETE FROM wp_cr_table WHERE postid = ' . $id;
			$wpdb->query( $sql_str );
        }
	}

	update_option( 'cr_delete_ids', '', true );

	$show_page = get_option( 'cr_show_page_no', 1 );
	$show_num = get_option( 'cr_show_item_num', 20 );
	if(empty($show_page))
		$show_page = 1;
	if(empty($show_num))
		$show_num = 20;
?>
    <div class="col-md-12">
    <h2 class="text-left">采集历史</h2>
    <div class="row">
    <div class="col-md-6">
    <?php
        $sql_str = 'SELECT count(*) AS list_num FROM wp_post_jxh';
		$results = $wpdb->get_results( $sql_str );
		$history_list_count = $results[0]->list_num;
        $page_nums = (int)($history_list_count / $show_num) + 1;
	echo $history_list_count . '条记录，共' . $page_nums . '页，当前显示第' . $show_page . '页';
	?>

	</div>
    <div class="col-md-6">
    <p class=" pull-right">
    每页：
        <?php
        $nums = array(10, 20, 50, 100);
        foreach ($nums as $num){
            if($show_num == $num){
                echo $num;
            }else{
                echo '<u><a href="#" onclick="history_show_num(' . $num . ')">' . $num . '</a></u>';
            }
            echo ' ';
        }
        ?>
        条
    </p>
    </div>
    </div>
    <div>
	<table id="history_table" class="table table-bordered table-striped table-hover table-condensed">
        <thead>
        <tr>
            <th>文章ID</th>
			<th>发布(postid)</th>
            <th>目录编号</th>
            <th>文章标题</th>
			<th>文章url</th>
            <th>采集时间</th>
			<th>发布状态</th>
            <th style="width: 60px;"><input style="margin-top: -3px; margin-right: 3px;" id="button_choose_all" type="checkbox" onclick="history_choose_all()"/>全选</th>
        </tr>
        </thead>
        <tbody id="table_list_body">
        <?php
            //$sql_str = 'select 
            //wp_cr_table.postid,wp_cr_table.taskid,wp_posts.post_title,wp_posts.post_date 
            //from wp_cr_table 
            //inner join wp_posts 
            //on wp_cr_table.postid = wp_posts.ID LIMIT ' . ($show_page - 1) * $show_num . ',' . $show_num;
			
			
		$sql_str = 'select * from wp_post_jxh   LIMIT ' . ($show_page - 1) * $show_num . ',' . $show_num;
            
        $results = $wpdb->get_results( $sql_str );
        $i = count( $results ) - 1;
        while ( $i >= 0 ) {
            echo '<tr>';
            echo '<td class="history_id">' . $results[$i]->id . '</td>';
			echo '<td>' . $results[$i]->post_id . '</td>';
            echo '<td>' . $results[$i]->category_code . '</td>';

            //$task_list  = get_option( 'cr_tasklist', '' );
            //$task_objs  = json_decode( $task_list, true );
            //$task_name = "未定义";
            //foreach( $task_objs as $task )
            //{
               // if($task['task_id'] == $results[$i]->taskid)
               // {
                   // $task_name = $task['task_name'];
               // }
            // }
            echo '<td>' . $results[$i]->title . '</td>';
            echo '<td>' . $results[$i]->url . '</td>';
            echo '<td>' . $results[$i]->date_time . '</td>';
			echo '<td>' . $results[$i]->url_status . '</td>';
	        echo '<td style="text-align: center;"><input type="checkbox" class="check_box"></td>';
            echo '</tr>';
            $i--;
        }
        ?>
        </tbody>
    </table>
    <div class="center-block">
        <?php
        if($show_page > 2){
            echo "<a href=\"#\" onclick=\"history_show_page(1)\">[首页]</a>&nbsp&nbsp&nbsp&nbsp";
        }
        if($show_page > 1){
            echo "<a href=\"#\" onclick=\"history_show_page(" . ($show_page - 1) . ")\">[上一页]</a>&nbsp&nbsp&nbsp&nbsp";
        }

        if($show_page < $page_nums - 1) {
            echo "<a href=\"#\" onclick=\"history_show_page(" . ($show_page + 1) . ")\">[下一页]</a>&nbsp&nbsp&nbsp&nbsp";
        }
        if($show_page < $page_nums) {
            echo "<a href=\"#\" onclick=\"history_show_page(" . $page_nums . ")\">[尾页]</a>";
        }
        ?>
    </div>
    <form method="post" action="options.php" style="display: none;" id="vir_history_form">
	    <?php settings_fields( 'cr_history' ); ?>
        <input name="cr_delete_ids" id="delete_ids" value=""/>
        <input name="cr_show_page_no" id="show_page_no" value="<?php echo esc_attr( get_option( 'cr_show_page_no' ) ); ?>"/>
        <input name="cr_show_item_num" id="show_item_num" value="<?php echo esc_attr( get_option( 'cr_show_item_num' ) ); ?>"/>
    </form>
	<form method="post" action="admin.php?page=crawling/options.php" style="display: none;" id="vir_fabu_form">
	    <?php settings_fields( 'cr_history' ); ?>
		<input name="cr_type" id="cr_type" value="1"/>
        <input name="cr_ids" id="cr_ids" value=""/>
        <input name="cr_show_page_no" id="show_page_no" value="<?php echo esc_attr( get_option( 'cr_show_page_no' ) ); ?>"/>
        <input name="cr_show_item_num" id="show_item_num" value="<?php echo esc_attr( get_option( 'cr_show_item_num' ) ); ?>"/>
    </form>
    <div class="form-group pull-right">
		<?php submit_button( '发布所选', 'primary', 'delete_history_btn', true, array( 'onclick' => 'fabu_history()' ) ); ?>
    </div>
	<div class="form-group pull-right">
		<?php submit_button( '删除所选', 'primary', 'delete_history_btn', true, array( 'onclick' => 'delete_history()' ) ); ?>
    </div>
	
    </div>
    </div>
<?php
}