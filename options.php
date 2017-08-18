<?php

//首先查看请求参数
$cr_type = $_REQUEST['cr_type'];
 echo "$cr_type=".$cr_type;
if ( empty( $cr_type ) ) :
  echo "未获取到分类ID";
endif;
 if($cr_type == 1){
	 //表示发布
	 $ids = $_REQUEST['cr_ids'];
	 echo '$ids='.$ids;
	 //执行迁移语句
	// $sql_insert = 'INSERT INTO `wp_posts`(post_author,post_content,post_title,) 
	// VALUES ('65', '1', '2017-08-11 17:07:21', '2017-08-11 09:07:21', '', '股讯', '', 'inherit', 'closed', 'closed', '', '62-revision-v1', '', '', '2017-08-11 17:07:21', '2017-08-11 09:07:21', '', '62', 'http://wp.51pyb.com/%e8%b5%84%e8%ae%af/6520170811.html', '0', 'revision', '', '0');'
	 if ( empty( $ids ) ){
		 echo '$ids===is null'; 		
		redirect('admin.php?page=cr-admin-menu');
	 }
	 
	 $sql_str11 = 'select * from wp_post_jxh  where id in ('.$ids.') and  post_id=0 and url_status < 2';
     echo '$sql_str11='.$sql_str11;      
     $results = $wpdb->get_results( $sql_str11 );
	 foreach ($results as $oob){
		    $content = auto_save_image( 1, $oob->content );
			//echo '$content='.$content[ 'content' ];
			if(empty( $content ) ){
				exit;
			}
			$my_post = array(

				'post_title' => $oob->title,

				'post_content' => $content[ 'content' ],

				'post_status' => 'publish',

				'post_author' => 1,

				'post_category' => array($oob->category_id) //分类ID 可以是一个数组
			);
			
		//入库
		$result = 	wp_insert_post( $my_post );
		//echo '$result='.$result;
		//进行更新本地对应ID 的发布状态 并重定向到展示页面
		$sql_up = 'update  wp_post_jxh set url_status=1,post_id='.$result.' where id = '.$oob->id;
		$wpdb->query($sql_up);
     }
	 		
	 redirect('admin.php?page=cr-admin-menu');
	 

	 //$result = wp_insert_post( $post, $wp_error );
	 // 创建一个文章对象 http://www.cnblogs.com/xbdeng/p/5545180.html
 }else if($cr_type == 100){
	 //发布所有
	 $sql_str11 = 'select * from wp_post_jxh  where post_id=0 and url_status < 2';
     echo '$sql_str11='.$sql_str11;      
     $results = $wpdb->get_results( $sql_str11 );
	 foreach ($results as $oob){
			$my_post = array(

				'post_title' => $oob->title,

				'post_content' => $oob->content,

				'post_status' => 'publish',

				'post_author' => 1,

				'post_category' => array($oob->category_id) //分类ID 可以是一个数组
			);
		//入库
		$result = 	wp_insert_post( $my_post );
		//echo '$result='.$result;
		//进行更新本地对应ID 的发布状态 并重定向到展示页面
		$sql_up = 'update  wp_post_jxh set url_status=1,post_id='.$result.' where id = '.$oob->id;
		$wpdb->query($sql_up);
     }
	 		
	 redirect('admin.php?page=cr-admin-menu');
	 
 }

 
 //url重定向2  
function redirect($url) {  
  
    echo "<script>".  
    "function redirect() {window.location.replace('$url');}\n".  
    "setTimeout('redirect();', 1000);\n".  
    "</script>";  
    exit();  
  
}  
 function do_post($url, $data) {
	 $ch = curl_init ();
	 curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, TRUE );
	 curl_setopt ( $ch, CURLOPT_POST, TRUE );
	 curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data );
	 curl_setopt ( $ch, CURLOPT_URL, $url );
	 curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	 $ret = curl_exec ( $ch );
	 curl_close ( $ch );
	 return $ret;
}
?>
