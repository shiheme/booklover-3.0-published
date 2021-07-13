<?php
/**
 * @package   Admin Settings
 */
if ( !defined( 'ABSPATH' ) ) exit;
include( MINI_PROGRAM_REST_API. 'admin/about.php' );
include( MINI_PROGRAM_REST_API. 'admin/options.php' );
include( MINI_PROGRAM_REST_API. 'admin/core/menu.php');
include( MINI_PROGRAM_REST_API. 'admin/core/meta.php');
include( MINI_PROGRAM_REST_API. 'admin/core/terms.php' );
include( MINI_PROGRAM_REST_API. 'admin/core/interface.php' );
include( MINI_PROGRAM_REST_API. 'admin/core/sanitization.php' );
include( MINI_PROGRAM_REST_API. 'admin/page/subscribe.php' );
add_action( 'init', 'creat_miniprogram_terms_meta_box' );
add_action( 'admin_menu', function() {
	register_miniprogram_manage_menu();
	mp_install_subscribe_message_table();
});
add_action( 'admin_enqueue_scripts', function () {
	wp_enqueue_style('miniprogram', MINI_PROGRAM_API_URL.'static/style.css', array(), get_bloginfo('version') );
} );
add_action( 'admin_enqueue_scripts', function () {
	wp_enqueue_script( 'miniprogram', MINI_PROGRAM_API_URL.'static/script.js', array( 'jquery' ), get_bloginfo('version') );
	wp_enqueue_script( 'mini-adv', MINI_PROGRAM_API_URL.'static/mini.adv.js', array( 'jquery' ), get_bloginfo('version') );
	wp_enqueue_script( 'automsg', MINI_PROGRAM_API_URL.'static/automsg.js', array( 'jquery' ), get_bloginfo('version') ); //小鱼哥 注册脚本
	if ( function_exists( 'wp_enqueue_media' ) ) {
		wp_enqueue_media();
	}
} );

add_action( 'admin_init', function() {
	register_setting( "minapp-group", "minapp", array( 'sanitize_callback' => 'validate_sanitize_miniprogram_options' ) );
});

//小鱼哥 开始增加自动获取豆瓣书影音脚本
add_action('wp_ajax_nopriv_automsglibrary', 'automsglibrary_callback_new');
add_action('wp_ajax_automsglibrary', 'automsglibrary_callback_new');
add_action('wp_ajax_nopriv_automsgmovie', 'automsgmovie_callback_new');
add_action('wp_ajax_automsgmovie', 'automsgmovie_callback_new');


    //剪切
    function cutautomsg($content, $start, $end){
        $r = explode($start, $content);
        if (isset($r[1])) {
            $r = explode($end, $r[1]);
            return $r[0];
        }
        return '';
    }
    //模拟get请求
    function getautomsg($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.7 (KHTML, like Gecko) Chrome/20.0.1099.0 Safari/536.7 QQBrowser/6.14.15493.201');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }


    function automsglibrary_callback_new(){
        $isbn = $_POST['isbn'];
        $surl = 'https://book.douban.com/isbn/' . $isbn . '/';
        $headers = json_encode(get_headers($surl), true);
        $headers = json_encode($headers, true);
        $surl  = cutautomsg($headers, 'Location: ', '"');
        $surl  = str_replace('\\', '', $surl); //302地址
        $search = array(" ", "　", "\n", "\r", "\t");
        $replace = array("", "", "", "", "");
        $data = getautomsg($surl);
        $data_1 = cutautomsg($data, 'application/ld+json">', '</script>');
        $data_1 = json_decode($data_1, true);
        $res['isbn'] = $isbn;
        $res['title'] = $data_1['name']; //书名

        $authors = $data_1['author'];
        if (!empty($authors)) {
            $__authors = '';
            foreach ($authors as $author) {
                $__authors .= sprintf(
                    '%1$s ',
                    $author['name']
                );
            } 
            $res['author'] = $__authors; //作者
        }

        $res['url'] = $data_1['url']; //书名

        $res['id'] = cutautomsg($res['url'], 'subject/', '/');

        $res['logo'] = cutautomsg($data, 'data-pic="', '"'); //图标

        $publisher_txt = cutautomsg($data, '出版社:</span>', '<br/>');
        $publisher = str_replace($search, $replace, $publisher_txt);
        $res['publisher'] = $publisher; //出版社

        $published_txt = cutautomsg($data, '出版年:</span>', '<br/>');
        $published = str_replace($search, $replace, $published_txt);
        $res['published'] = $published; //出版年

        $page_txt = cutautomsg($data, '页数:</span>', '<br/>');
        $page = str_replace($search, $replace, $page_txt);
        $res['page'] = $page; //页数

        $translator_html = cutautomsg($data, '译者</span>:', '</span><br/>');
        $translator_txt = strip_tags($translator_html);
        $translator = str_replace($search, $replace, $translator_txt);
        $res['translator'] = $translator; //译者

        $price_txt = cutautomsg($data, '定价:</span>', '<br/>');
        $price = str_replace($search, $replace, $price_txt);
        if ($price == '') {
            $price = '未知';
        }
        $res['price'] = $price; //定价

        $designed_txt = cutautomsg($data, '装帧:</span>', '<br/>');
        $designed = str_replace($search, $replace, $designed_txt);
        $res['designed'] = $designed; //装帧

        // $description = cutautomsg($data,'class="intro">','</p>');
        // $description = explode('<p>',$description)[1];
        // if($description==''){
        //   $description ='未知';
        // }
        // $res['description'] =$description;//简介


        $res = json_encode($res, true);
        echo $res;

        exit;
    }
    function automsgmovie_callback_new() {
        $dbid = $_POST['dbid'];
        $surl = 'https://movie.douban.com/subject/' . $dbid . '/';
        $search = array(" ", "　", "\n", "\r", "\t");
        $replace = array("", "", "", "", "");
        $data = getautomsg($surl);
        $data_1 = cutautomsg($data, 'application/ld+json">', '</script>');
        //$data_1 = preg_replace("(?<=\"description\"\: \"){.*}(?=\".*\.contacts)","",$data_1);
        //$data_1 = JSON.parse($data_1);
        //$data_1 = json_encode($data_1, true);
        $data_1 = preg_replace("/\"description\"(.*)\"([\s\S]*?)\"\,/","\"description\": \"\",",$data_1);
        $data_1 = json_decode($data_1, true);
        $res['data_1'] = $data_1;
        $res['title'] = $data_1['name']; //影视名称

        $directors = $data_1['director'];
        if (!empty($directors)) {
            $__directors = '';
            foreach ($directors as $director) {
                if ($director != end($directors)) {
                    // 不是最后一项
                    $__directors .= sprintf(
                        '%1$s / ',
                        $director['name']
                    );
                } else {
                    // 最后一项
                    $__directors .= sprintf(
                        '%1$s',
                        $director['name']
                    );
                }
            }
            $res['director'] = $__directors; //导演
        }

        $authors = $data_1['author'];
        if (!empty($authors)) {
            $__authors = '';
            foreach ($authors as $author) {
                if ($author != end($authors)) {
                    // 不是最后一项
                    $__authors .= sprintf(
                        '%1$s / ',
                        $author['name']
                    );
                } else {
                    // 最后一项
                    $__authors .= sprintf(
                        '%1$s',
                        $author['name']
                    );
                }
            }
            $res['writer'] = $__authors; //编剧
        }

        $actors = $data_1['actor'];
        if (!empty($actors)) {
            $__actors = '';
            foreach ($actors as $actor) {
                if ($actor != end($actors)) {
                    // 不是最后一项
                    $__actors .= sprintf(
                        '%1$s / ',
                        $actor['name']
                    );
                } else {
                    // 最后一项
                    $__actors .= sprintf(
                        '%1$s',
                        $actor['name']
                    );
                }
            }
            $res['star'] = $__actors; //演员
        }
        $res['url'] = $data_1['url'];
        $res['id'] = cutautomsg($res['url'], 'subject/', '/');

        $res['logo'] = $data_1['image']; //图标

        $res['datePublished'] = $data_1['datePublished']; //上映时间

        $res['type'] = $data_1['genre']; //类型

        $aggregateRating = $data_1['aggregateRating'];
        $res['dbscore'] = $aggregateRating['ratingValue']; //评分

        $area_txt = cutautomsg($data, '地区:</span>', '<br/>');
        $area = str_replace($search, $replace, $area_txt);
        $res['area'] = $area; //制片国家
        $opened_txt = cutautomsg($data, '"year">(', ')');
        $opened = str_replace($search, $replace, $opened_txt);
        $res['opened'] = $opened; //上映年份

        $language_txt = cutautomsg($data, '语言:</span>', '<br/>');
        $language = str_replace($search, $replace, $language_txt);
        $res['language'] = $language; //语言

        $alias_txt = cutautomsg($data, '又名:</span>', '<br/>');
        $alias = str_replace($search, $replace, $alias_txt);
        $res['alias'] = $alias; //又名

        $time_html = cutautomsg($data, '片长:</span>', '<br/>');
        $time_txt = strip_tags($time_html);
        $time = str_replace($search, $replace, $time_txt);
        $res['time'] = $time; //片长

        $imdb_html = cutautomsg($data, 'IMDb链接:</span>', '<br>');
        $imdb_txt = strip_tags($imdb_html);
        $imdb = str_replace($search, $replace, $imdb_txt);
        $res['imdb'] = $imdb; //片长


        // $description = cutautomsg($data,'class="intro">','</p>');
        // $description = explode('<p>',$description)[1];
        // if($description==''){
        //   $description ='未知';
        // }
        // $res['description'] =$description;//简介


        $res = json_encode($res, true);
        echo $res;

        exit;
    }
	//小鱼哥 结束增加自动获取豆瓣书影音脚本
	
// Menu
if(is_admin()) {
	add_filter( 'miniprogram_manage_menus', function( $admin_menu ) {
		$submenu = array();
		$submenu[] = ['page_title' => '小程序设置','menu_title' => '基本设置', 'option_name' => 'miniprogram','slug' => 'miniprogram', 'function' => 'miniprogram_options_manage_page'];
		$submenu[] = ['page_title' => '小程序订阅消息统计','menu_title' => '订阅统计', 'option_name' => 'miniprogram','slug' => 'subscribe', 'function' => 'miniprogram_subscribe_message_count'];
		$submenu[] = ['page_title' => '小程序历史推送任务','menu_title' => '任务列表', 'option_name' => 'miniprogram','slug' => 'task', 'function' => 'miniprogram_subscribe_message_task_table'];
		$submenu[] = ['page_title' => 'Mini Program API 使用指南','menu_title' => '使用指南', 'option_name' => 'miniprogram','slug' => 'guide', 'function' => 'miniprogram_api_guide'];
		$admin_menu[] = array(
			'menu' => [
				'page_title' => '小程序设置','menu_title' => '小程序', 'option_name' => 'miniprogram', 'function' => 'miniprogram_options_manage_page', 'icon' => 'dashicons-editor-code', 'position' => 2
			],
			'submenu'	=> $submenu
		);
		return $admin_menu;
	});
}
// Pages
function miniprogram_options_manage_page( ) {
	$option = array(
		'id' 		=> 'minapp-form',
		'options'	=> 'minapp',
		"group"		=> "minapp-group"
	);
	$options = apply_filters( 'miniprogram_setting_options', $options = array() );
	require_once( MINI_PROGRAM_REST_API. 'admin/core/settings.php' );
}
add_action('admin_footer', function () {
	echo '<script type="text/html" id="tmpl-mp-del-item">
	<a href="javascript:;" class="button del-item">删除</a> <span class="dashicons dashicons-menu"></span>
</script>';
});