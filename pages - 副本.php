<?php

require_once( 'exec.php' );

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

function cr_function_submenu1() {
	do_task_now();
	?>

    <div class="col-md-12">
        <h1 class="text-left">任务管理</h1>
        <form method="post" action="options.php" id="my_plugin_test_form" class="form-horizontal">
			<?php
			settings_fields( 'cr_settings_run' );
			$running = get_option( 'cr_running' );
			if ( 0 == $running ) {
				?>
                <p class="running-state">插件运行状态: 停止</p>
                <select title="cr_running" name="cr_running">
                    <option value="0" selected="selected">停止</option>
                    <option value="1">运行</option>
                </select>
				<?php
			} else {
				?>
                <p class="running-state">插件运行状态: 运行</p>
                <select title="cr_running" name="cr_running">
                    <option value="0">停止</option>
                    <option value="1" selected="selected">运行</option>
                </select>
				<?php
			}
			?>
            <div class="running-button">
				<?php submit_button( $text = '切换' ); ?>
            </div>
        </form>
        <table id="table_list" class="table table-bordered table-striped table-hover table-condensed">
            <caption>任务列表</caption>
            <thead>
            <tr>
                <th>任务编号</th>
                <th>任务名称</th>
                <th>入口网址</th>
                <th>抓取间隔（小时）</th>
                <th>上次抓取时间</th>
                <th>采集页面</th>
                <th class="text-center">操作</th>
            </tr>
            </thead>
            <tbody id="table_list_body">

            </tbody>
        </table>

        <div>
            <input class="button" type="button" onclick="show_options()" value="新建任务"/>
        </div>

        <div id="option_detail" style="margin-top: 30px;">
            <div>
                <input class="button" type="button" onclick="hide_options()" value="取消"/>
            </div>
            <h4>任务详细设置</h4>
            <div class="table-bordered" style="padding-top:20px;">
                <form class="form-horizontal" role="form">
                    <div class="form-group">
                        <label id="option_task_text" class="col-sm-2 control-label"
                               style="font-weight: bold;">任务类型</label>
                        <div class="col-sm-10">
                            <label id="option_task_id" class="control-label" style="font-weight: bold;">New</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">任务名称</label>
                        <div class="col-sm-10">
                            <input title="option_task_name" class="col-sm-3" type="text" id="option_task_name"/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">入口网址</label>
                        <div class="col-sm-10">
                            <input title="option_start_url" class="col-sm-3" type="text" id="option_start_url"/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">爬取间隔时间</label>
                        <div class="col-sm-10">
                            <input title="option_skip_time" class="col-sm-3" type="text" id="option_skip_time"/>
                            <label class="control-label end-text">小时</label>
                        </div>
                    </div>
                    <hr>
                    <div class="form-group">
                        <label class="col-sm-2 control-label"></label>
                        <h5 class="col-sm-10">
                            【===============<strong>必选设置</strong>==================】
                        </h5>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">文章链接（xpath规则）</label>
                        <div class="col-sm-10">
                            <input title="option_page_content" class="col-sm-3" type="text" id="option_page_content"/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">文章标题（xpath规则）</label>
                        <div class="col-sm-10">
                            <input title="option_post_title" class="col-sm-3" type="text" id="option_post_title"/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">文章内容（xpath规则）</label>
                        <div class="col-sm-10">
                            <input title="option_post_content" class="col-sm-3" type="text" id="option_post_content"/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">内容是否分页</label>
                        <div class="col-sm-10">
                            <select title="option_post_content_cut" id="option_post_content_cut"
                                    onchange="pageCutChange()">
                                <option value="0" selected="selected">不分页</option>
                                <option value="1">循环匹配下一页</option>
                                <option value="2">一次匹配所有分页</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" id="option_post_content_cutrule_div">
                        <label class="col-sm-2 control-label">文章内容分页（xpath规则）</label>
                        <div class="col-sm-10">
                            <input title="option_post_content_cutrule" class="col-sm-3" type="text"
                                   id="option_post_content_cutrule"/>
                        </div>
                    </div>
                    <hr>
                    <div class="form-group">
                        <label class="col-sm-2 control-label"></label>
                        <h5 class="col-sm-10">
                            【===============<strong>可选设置</strong>==================】
                        </h5>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">文章图片</label>
                        <div class="col-sm-10">
                            <select title="option_post_content_image" id="option_post_content_image">
                                <option value="0">不做处理</option>
                                <option value="1" selected="selected">保存到本地</option>
                                <option value="2" selected="selected">保存到七牛</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">文章分类</label>
                        <div class="col-sm-10">
                            <p id="option_post_categray_names"
                               style="display: inline-block; margin-top: 4px; text-align: left;">*未选择</p>
                            <p id="option_post_categray_ids"
                               style="display: inline-block; margin-top: 4px; text-align: left;"></p>
                            <a href="#" style="display: inline-block; margin-top: 4px; text-align: center;"
                               onclick="categoryShow()">选择分类</a>
                            <div id="category_list" class="category_list">
								<?php cr_category_list(); ?>
                                <a href="#" style="display: inline-block; margin-top: 4px; text-align: left;"
                                   onclick="categorySelect()">确定</a>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">文章标签</label>
                        <div class="col-sm-10">
                            <input title="option_post_tag" class="col-sm-3" type="text" id="option_post_tag"/>
                            <label class="control-label end-text">（请以|隔开，可选，留空则自动匹配）</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">文章作者</label>
                        <div class="col-sm-10">
							<?php wp_dropdown_users( array( 'name' => 'option_post_writer' ) ); ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">发布方式</label>
                        <div class="col-sm-10">
                            <select title="option_post_publish" id="option_post_publish">
                                <option value="0">放入草稿箱</option>
                                <option value="1" selected="selected">直接发布</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">翻页链接（xpath规则）</label>
                        <div class="col-sm-10">
                            <input title="option_page_list" class="col-sm-3" type="text" id="option_page_list"/>
                            <label class="control-label end-text">（可选）</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">翻页次数（采集页数）</label>
                        <div class="col-sm-10">
                            <input title="option_page_list_times" class="col-sm-3" type="text"
                                   id="option_page_list_times"/>
                            <label class="control-label end-text">（可选）</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">内容过滤（开始）字符串</label>
                        <div class="col-sm-10">
                            <input title="option_post_content_start" class="col-sm-3" type="text"
                                   id="option_post_content_start"/>
                            <label class="control-label end-text">（可选，从此字符串之后开始截取）</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">内容过滤（结束）字符串</label>
                        <div class="col-sm-10">
                            <input title="option_post_content_end" class="col-sm-3" type="text"
                                   id="option_post_content_end"/>
                            <label class="control-label end-text">（可选，截取到次字符串之前）</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">自定义添加内容</label>
                        <div class="col-sm-10">
                            <input title="option_post_content_add" class="col-sm-3" type="text"
                                   id="option_post_content_add"/>
                            <label class="control-label end-text">（可选，添加到内容之后）</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">标题内容关键字替换</label>
                        <div class="col-sm-10">
                            <textarea class="col-sm-3" type="text" id="option_post_content_replace"></textarea>
                            <label class="control-label end-text">（可选，格式：我的>你的，以|隔开）</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="form-group">
                <input class="button" style="margin-top: 20px;" type="button" onClick="saveRow();" value="保存任务"/>
            </div>
        </div>

        <div style="font-weight: bold; margin-top: 50px; padding-bottom: 5px;"></div>
        <hr>
        <form method="post" action="options.php" id="my_plugin_test_form1">
			<?php settings_fields( 'cr_settings' ); ?>
            <input title="cr_run_index" type="text" id="option_run_index" name="cr_run_index"/>
            <br>
            <textarea title="cr_tasklist" id="task_list" name="cr_tasklist" rows="5"
                      cols="120"><?php echo esc_attr( get_option( 'cr_tasklist' ) ); ?></textarea>
			<?php
			submit_button( '保存更改', 'primary', 'save-submit-button' );
			?>
        </form>
    </div>
	<?php
}

//全局设置
function cr_function_submenu2() {
	?>
    <div class="col-md-12">
        <h1 class="text-left">全局设置</h1>
        <form method="post" action="options.php" id="my_plugin_test_form2" class="form-horizontal">
			<?php settings_fields( 'cr_settings_more' ); ?>
            <p class="button-text">是否记录日志</p>
            <select title="cr_log_enable" name="cr_log_enable">
                <option value="0" <?php if ( 0 == esc_attr( get_option( 'cr_log_enable' ) ) ) {
					echo 'selected="selected"';
				} ?>>关闭
                </option>
                <option value="1" <?php if ( 1 == esc_attr( get_option( 'cr_log_enable' ) ) ) {
					echo 'selected="selected"';
				} ?>>打开
                </option>
            </select>
            <p class="button-text">最大抓取线程数</p>
            <input title="cr_max_thread" type="text" name="cr_max_thread"
                   value="<?php echo esc_attr( get_option( 'cr_max_thread' ) ); ?>"/>
            <p class="button-text">抓取延时</p>
            <input title="cr_delay" type="text" name="cr_delay"
                   value="<?php echo esc_attr( get_option( 'cr_delay' ) ); ?>"/>
            <br><br>
            <h3>七牛云
                <small>（可选）</small>
            </h3>
            <hr>
            <p class="button-text">空间名</p>
            <input title="cr_qiniu_space" type="text" name="cr_qiniu_space"
                   value="<?php echo esc_attr( get_option( 'cr_qiniu_space' ) ); ?>"/>
            <p class="button-text">Access Key</p>
            <input title="cr_qiniu_ak" type="text" name="cr_qiniu_ak"
                   value="<?php echo esc_attr( get_option( 'cr_qiniu_ak' ) ); ?>"/>
            <p class="button-text">Secret Key</p>
            <input title="cr_qiniu_sk" type="text" name="cr_qiniu_sk"
                   value="<?php echo esc_attr( get_option( 'cr_qiniu_sk' ) ); ?>"/>
            <p class="button-text">URL前缀[域名]</p>
            <input title="cr_qiniu_host" type="text" name="cr_qiniu_host"
                   value="<?php echo esc_attr( get_option( 'cr_qiniu_host' ) ); ?>"/>
            <p class="button-text">文件名前缀</p>
            <input title="cr_qiniu_prefix" type="text" name="cr_qiniu_prefix"
                   value="<?php echo esc_attr( get_option( 'cr_qiniu_prefix' ) ); ?>"/>
            <br><br>
			<?php submit_button(); ?>
        </form>
    </div>
	<?php
}

//日志
function cr_function_submenu4() {
	?>
    <div class="col-md-12">
        <h2 class="text-left">日志</h2>
        <div>
		<textarea title="log-content" class="col-md-12" rows="25"><?php
			$log_file = plugin_dir_path( __FILE__ ) . 'log.txt';
			echo file_get_contents( $log_file );
			?></textarea>
        </div>
        <a style="margin-top: 10px;" class="button" role="button"
           href="<?php echo plugin_dir_url( __FILE__ ) . 'log.txt'; ?>" target="_blank">下载日志</a>
    </div>
	<?php
}

//关于
function cr_function_submenu5() {
	?>
    <div class="col-md-12">
    <h2 class="text-left">关于</h2>
    <div>
        <div class="list-title">
			<?php
			$local_version_file  = plugin_dir_path( __FILE__ ) . 'README.txt';
			$remote_version_file = 'http://crawling.cn/release.txt';
			$local_version       = null;
			$remote_version      = null;
			$handle              = @fopen( $local_version_file, 'r' );
			if ( $handle ) {
				$local_version = fgets( $handle, 4096 );
				fclose( $handle );
			}
			$handle = @fopen( $remote_version_file, 'r' );
			if ( $handle ) {
				$remote_version = fgets( $handle, 4096 );
				fclose( $handle );
			}
			echo '当前本地版本：' . $local_version . '<br>';
			echo '官网最新版本：' . $remote_version . '<br>';
			?>
        </div>
        <hr>
        <div class="list-title">项目主页：<a href="http://crawling.cn">http://crawling.cn</a></div>
        <div class="list-title">技术交流QQ群： 559609792</div>
        <div class="list-title">联系邮箱： <a href="mailto:820169199@qq.com">820169199@qq.com</a></div>
        <p class="list-p">如果您有什么好的需求和建议，欢迎提出来。我会在后续版本中增加这些功能，将Crawling插件做得更好更好用。</p>
    </div>
    <h4 class="text-left" style="margin-top: 30px;">更新历史</h4>
	<?php
	$str='';
    $handle = @fopen( $remote_version_file, 'r' );
	if ( $handle ) {
        do{
            $str = fgets( $handle, 4096 );
            echo $str . '<br>';
        }while(!empty($str));
	fclose( $handle );
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
    <div class="form-group pull-right">
		<?php submit_button( '删除所选', 'primary', 'delete_history_btn', true, array( 'onclick' => 'delete_history()' ) ); ?>
    </div>
    </div>
    </div>
<?php
}