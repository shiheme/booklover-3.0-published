<?php
/*
 * WordPress Custom API Data Hooks
 */

if( !defined( 'ABSPATH' ) ) exit;

// 屏蔽不常用 REST
if(wp_miniprogram_option('gutenberg')) {
	add_filter( 'rest_endpoints', function( $endpoints ) {
		unset( $endpoints['/wp/v2/users'] );
		unset( $endpoints['/wp/v2/users/me'] );
		unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
		unset( $endpoints['/wp/v2/posts/(?P<parent>[\d]+)/revisions']);
		unset( $endpoints['/wp/v2/posts/(?P<parent>[\d]+)/revisions/(?P<id>[\d]+)']);
		unset( $endpoints['/wp/v2/posts/(?P<id>[\d]+)/autosaves']);
		unset( $endpoints['/wp/v2/posts/(?P<parent>[\d]+)/autosaves/(?P<id>[\d]+)']);
		unset( $endpoints['/wp/v2/pages/(?P<parent>[\d]+)/revisions']);
		unset( $endpoints['/wp/v2/pages/(?P<parent>[\d]+)/revisions/(?P<id>[\d]+)']);
		unset( $endpoints['/wp/v2/pages/(?P<id>[\d]+)/autosaves']);
		unset( $endpoints['/wp/v2/pages/(?P<parent>[\d]+)/autosaves/(?P<id>[\d]+)']);
		unset( $endpoints['/wp/v2/comments']);
		unset( $endpoints['/wp/v2/comments/(?P<id>[\d]+)']);
		unset( $endpoints['/wp/v2/statuses']);
		unset( $endpoints['/wp/v2/statuses/(?P<status>[\w-]+)']);
		unset( $endpoints['/wp/v2/settings']);
		unset( $endpoints['/wp/v2/themes']);
		return $endpoints;
	});
}

add_action( 'rest_api_init', function () {
	$controller = array();
	$controller[] = new WP_REST_Setting_Router();
	$controller[] = new WP_REST_Posts_Router();
	$controller[] = new WP_REST_Comments_Router();
	$controller[] = new WP_REST_Qrcode_Router();
	$controller[] = new WP_REST_Users_Router();
	$controller[] = new WP_REST_Auth_Router();
	$controller[] = new WP_REST_Subscribe_Router();
	$controller[] = new WP_REST_Advert_Router();
	$controller[] = new WP_REST_Menu_Router();
	foreach ( $controller as $control ) {
		$control->register_routes();
	}
} );
add_filter('rest_prepare_post', 'rest_prepare_fields', 10, 3);
add_filter('rest_prepare_topic', 'rest_prepare_fields', 10, 3);
add_filter('rest_prepare_quot', 'rest_prepare_fields', 10, 3);
add_filter('rest_prepare_faq', 'rest_prepare_fields', 10, 3);
add_filter('rest_prepare_act', 'rest_prepare_fields', 10, 3);
add_filter('rest_prepare_talk', 'rest_prepare_fields', 10, 3);
add_filter('rest_prepare_library', 'rest_prepare_fields_plus', 10, 3);
add_filter('rest_prepare_app', 'rest_prepare_fields_plus', 10, 3);
add_filter('rest_prepare_movie', 'rest_prepare_fields_plus', 10, 3);
add_filter('rest_prepare_films', 'rest_prepare_fields_plus', 10, 3);

function rest_prepare_fields($data, $post, $request){
	$_data = $data->data;
	$post_id = $post->ID;
	if( is_miniprogram() || is_debug() ) {
		$post_date = $post->post_date;
		$post_type = $post->post_type;
		$author_id = $post->post_author;
		$author_avatar = get_user_meta($author_id, 'avatar', true);
		$taxonomies = get_object_taxonomies($_data['type']);
		$post_title = $post->post_title;
		$post_views = (int)get_post_meta( $post_id, "views" ,true );
		$post_excerpt = $_data["excerpt"]["rendered"];
		$post_content = $_data["content"]["rendered"];
		$session = isset($request['access_token'])?$request['access_token']:'';
		if( $session ) {
			$access_token = base64_decode( $session );
			$users = MP_Auth::login( $access_token );
			if( $users ) {
				$user_id = $users->ID;
			} else {
				$user_id = 0;
			}
		} else {
			$user_id = 0;
		}
		$_data["id"]  = $post_id;
		$_data["date"] = $post_date;
		$_data["week"] = get_wp_post_week($post_date);
		unset($_data['author']);
		$_data["author"]["id"] = $author_id;
		$_data["author"]["name"] = get_the_author_meta('nickname',$author_id);
		if($author_avatar) {
			$_data["author"]["avatar"] = $author_avatar;
		} else {
			$_data["author"]["avatar"] = get_avatar_url($author_id);
		}
		$_data["author"]["description"] = get_the_author_meta('description',$author_id);
		if( get_post_meta( $post_id, "source" ,true ) ) {
			$_data["meta"]["source"] = get_post_meta( $post_id, "source" ,true );
		}
		$_data["meta"]["thumbnail"] = apply_filters( 'post_thumbnail', $post_id );
		$_data["meta"]["views"] = $post_views;
		$_data["meta"]["count"] = mp_count_post_content_text_length( wp_strip_all_tags( $post_content ) );
		$_data["comments"] = apply_filters( 'comment_type_count', $post_id, 'comment' );
		$_data["isfav"] = apply_filters( 'miniprogram_commented', $post_id, $user_id, 'fav' );
		// $_data["favs"] = apply_filters( 'comment_type_count', $post_id, 'fav' );
		$_data["islike"] = apply_filters( 'miniprogram_commented', $post_id, $user_id, 'like' );
		$_data["likes"] = apply_filters( 'comment_type_count', $post_id, 'like' );
		
		if($post_type == 'topic'){
		$_data["post_likes"] = apply_filters( 'comment_type_list', $post_id, 'like' );
	}

		// if ($taxonomies) {
		// 	foreach ( $taxonomies as $taxonomy ){
		// 		$terms = wp_get_post_terms($post_id, $taxonomy, array('orderby' => 'term_id', 'order' => 'ASC', 'fields' => 'all'));
		// 		foreach($terms as $term) {
		// 			$tax = array();
		// 			$term_cover = get_term_meta($term->term_id,'cover',true);
		// 			$tax["id"] = $term->term_id;
		// 			$tax["name"] = $term->name;
		// 			$tax["description"] = $term->description;
		// 			$tax["cover"] = $term_cover ? $term_cover : wp_miniprogram_option('thumbnail');
		// 			if ($taxonomy === 'post_tag') { $taxonomy = "tag"; }
		// 			$_data[$taxonomy][] = $tax;
		// 		}
		// 	}
		// }
		$_data["title"]["rendered"] = html_entity_decode( $post_title );
		$_data["excerpt"]["rendered"] = html_entity_decode( wp_strip_all_tags( $post_excerpt ) );
		// if( wp_miniprogram_option('mediaon') ) {
		// 	$_data["media"]['cover'] = get_post_meta( $post_id, 'cover', true ) ? get_post_meta( $post_id, 'cover' ,true ) : apply_filters( 'post_thumbnail', $post_id );
		// 	$_data["media"]['author'] = get_post_meta( $post_id, 'author', true );
		// 	$_data["media"]['title'] = get_post_meta( $post_id, 'title', true );
		// 	$_data["media"]['video'] = get_post_meta( $post_id, 'video', true );
		// 	$_data["media"]['audio'] = get_post_meta( $post_id, 'audio', true );
		// }
		if (isset($request['id'])) {
			if (!update_post_meta($post_id, 'views', ($post_views + 1))) {
				add_post_meta($post_id, 'views', 1, true);
			}
			if (is_smart_miniprogram()) {
				$custom_keywords = get_post_meta($post_id, "keywords", true);
				if (!$custom_keywords) {
					$custom_keywords = "";
					$tags = wp_get_post_tags($post_id);
					foreach ($tags as $tag) {
						$custom_keywords = $custom_keywords . $tag->name . ",";
					}
				}
				$_data["smartprogram"]["title"] = $_data["title"]["rendered"] . '-' . get_bloginfo('name');
				$_data["smartprogram"]["keywords"] = $custom_keywords;
				$_data["smartprogram"]["description"] = $_data["excerpt"]["rendered"];
				$_data["smartprogram"]["image"] = apply_filters('post_images', $post_id);
				$_data["smartprogram"]["visit"] = array('pv' => $post_views);
				$_data["smartprogram"]["comments"] =  apply_filters('comment_type_count', $post_id, 'comment');
				$_data["smartprogram"]["likes"] = apply_filters('comment_type_count', $post_id, 'like');
				$_data["smartprogram"]["collects"] = apply_filters('comment_type_count', $post_id, 'fav');
			}

			$face_emoji = get_post_meta($post->ID, 'talk_emoji');
				$__face_emoji = array();
				foreach ($face_emoji as $book_g) {
					$__face_emoji[]  = array(
					"emojitit" => get_the_title($book_g['ID']),
					"emojisrc" => pods_image($book_g,array(80,80), 'original'),
					);
				}
				// $__face_emojiarray =array($__face_emoji);
				//$__face_emojiarray[] = $__face_emoji;
				$__face_emoji = array_column($__face_emoji, 'emojisrc','emojitit');
				$_data["face_emoji"] = $__face_emoji;

			if (!$media_video) {
				$_data["content"]["rendered"] = apply_filters('the_video_content', $post_content);
			}
			$_data["post_likes"] = apply_filters('comment_type_list', $post_id, 'like');
			// $_data["post_favs"] = apply_filters( 'comment_type_list', $post_id, 'fav' );
			// $_data["post_likes"] = apply_filters( 'comment_type_list', $post_id, 'like' );
			if (wp_miniprogram_option("prevnext")) {
				$category = get_the_category($post_id);
				$next = get_next_post($category[0]->term_id, '', 'category');
				$previous = get_previous_post($category[0]->term_id, '', 'category');
				if (!empty($next->ID)) {
					$_data["next_post"]["id"] = $next->ID;
					$_data["next_post"]["title"]["rendered"] = $next->post_title;
					$_data["next_post"]["thumbnail"] = apply_filters('post_thumbnail', $next->ID);
					$_data["next_post"]["views"] = (int)get_post_meta($next->ID, "views", true);
				}
				if (!empty($previous->ID)) {
					$_data["prev_post"]["id"] = $previous->ID;
					$_data["prev_post"]["title"]["rendered"] = $previous->post_title;
					$_data["prev_post"]["thumbnail"] = apply_filters('post_thumbnail', $previous->ID);
					$_data["prev_post"]["views"] = (int)get_post_meta($previous->ID, "views", true);
				}
			}

			//小鱼哥 专题增加关联书籍字段(详情页)
			if ($post_type == 'topic') { 
				if(get_post_meta($post_id, 'book_choosetype')[0]=='1'){
					$book_tolibrary = get_post_meta($post_id, 'book_chooselibrary');
					$book_type = 'library';
				} else if(get_post_meta($post_id, 'book_choosetype')[0]=='2'){
					$book_tolibrary = get_post_meta($post_id, 'book_choosefilms');
					$book_type = 'films';
				} else if(get_post_meta($post_id, 'book_choosetype')[0]=='3'){
					$book_tolibrary = get_post_meta($post_id, 'book_choosemovie');
					$book_type = 'movie';
				} else if(get_post_meta($post_id, 'book_choosetype')[0]=='4'){
					$book_tolibrary = get_post_meta($post_id, 'book_chooseapp');
					$book_type = 'app';
				} else if(get_post_meta($post_id, 'book_choosetype')[0]=='5'){
					$book_tolibrary = get_post_meta($post_id, 'book_choosepro');
					$book_type = 'pro';
				}
				if ($book_tolibrary[0] != false) {
					$__book_tolibrary = array();
					foreach ($book_tolibrary as $app_k) {

						// $__meta[] = array(
						// 	"thumbnail" =>  wp_get_attachment_image_src( get_post_thumbnail_id($app_k['ID']), 'thumbnail')[0],
						// 	"views" => (int)get_post_meta( $app_k['ID'], "views" ,true ),
						// );

						$__book_tolibrary[] = array(
							"id" => pods_field_display($book_type, $app_k['ID'], 'ID'),
							"book_author" => pods_field_display($book_type, $app_k['ID'], 'book_author'),
							"book_title" => pods_field_display($book_type, $app_k['ID'], 'book_title'),
							"book_thumb" => wp_get_attachment_image_src(get_post_thumbnail_id($app_k['ID']), 'medium')[0],
							"book_isbn" => pods_field_display($book_type, $app_k['ID'], 'book_isbn'),
							"book_imdb" => pods_field_display($book_type, $app_k['ID'], 'book_imdb'),
							"book_dbid" => pods_field_display($book_type, $app_k['ID'], 'book_dbid'),
							"book_dbscore" => pods_field_display($book_type, $app_k['ID'], 'book_dbscore'),
							// "book_published" => pods_field_display('library', $app_k['ID'], 'book_published'),
							// "book_price" => pods_field_display('library', $app_k['ID'], 'book_price'),
							"book_pagestyle" => pods_field_display($book_type, $app_k['ID'], 'book_pagestyle'),
							"book_foreground" => pods_field_display($book_type, $app_k['ID'], 'book_foreground'),
							"likes" => apply_filters('comment_type_count', $app_k['ID'], 'like'),
							"views" => (int)get_post_meta($app_k['ID'], "views", true),
							// "comments" => apply_filters( 'comment_type_count', $app_k['ID'], 'comment' ),
							// "book_cat" =>$__quot_library_tax
							"category" => get_the_terms($app_k['ID'], $book_type.'_cats'),
							"state" => get_the_terms($app_k['ID'], $book_type.'_state'),
							"meta" => array(
								"thumbnail" =>  wp_get_attachment_image_src(get_post_thumbnail_id($app_k['ID']), 'medium')[0],
								"views" => (int)get_post_meta($app_k['ID'], "views", true),
							),
							"title" => array(
								"rendered" => html_entity_decode(get_the_title($app_k['ID'])),
							),
							"excerpt" => array(
								"rendered" => html_entity_decode(wp_strip_all_tags(get_the_excerpt($app_k['ID'])))
							),
							"app_type"=>pods_field_display($book_type, $app_k['ID'], 'app_type'),
							"type" => $book_type,
						);
					}
					$_data["book_tolibrary"] = $__book_tolibrary;
				}
			}

			//小鱼哥 语录增加关联书籍字段(详情页)
			if ($post_type == 'quot') {
				if(get_post_meta($post_id, 'quot_choosetype')[0]=='1'){
					$book_tolibrary = get_post_meta($post_id, 'quot_library');
					$book_type = 'library';
				} else if(get_post_meta($post_id, 'quot_choosetype')[0]=='2'){
					$book_tolibrary = get_post_meta($post_id, 'quot_films');
					$book_type = 'films';
				}
				if ($book_tolibrary[0] != false) {
					$__book_tolibrary = array();
					foreach ($book_tolibrary as $app_k) {
						$__book_tolibrary[] = array(
							"id" => pods_field_display($book_type, $app_k['ID'], 'ID'),
							"book_author" => pods_field_display($book_type, $app_k['ID'], 'book_author'),
							"book_title" => pods_field_display($book_type, $app_k['ID'], 'book_title'),
							"book_thumb" => wp_get_attachment_image_src(get_post_thumbnail_id($app_k['ID']), 'medium')[0],
							"book_isbn" => pods_field_display($book_type, $app_k['ID'], 'book_isbn'),
							"book_dbid" => pods_field_display($book_type, $app_k['ID'], 'book_dbid'),
							"book_dbscore" => pods_field_display($book_type, $app_k['ID'], 'book_dbscore'),
							// "book_published" => pods_field_display($book_type, $app_k['ID'], 'book_published'),
							// "book_price" => pods_field_display($book_type, $app_k['ID'], 'book_price'),
							"book_pagestyle" => pods_field_display($book_type, $app_k['ID'], 'book_pagestyle'),
							"book_foreground" => pods_field_display($book_type, $app_k['ID'], 'book_foreground'),
							"likes" => apply_filters('comment_type_count', $app_k['ID'], 'like'),
							"views" => (int)get_post_meta($app_k['ID'], "views", true),
							// "comments" => apply_filters( 'comment_type_count', $app_k['ID'], 'comment' ),
							// "book_cat" =>$__quot_library_tax
							"category" => get_the_terms($app_k['ID'], $book_type.'_cats'),
							"state" => get_the_terms($app_k['ID'], $book_type.'_state'),
							"meta" => array(
								"thumbnail" =>  wp_get_attachment_image_src(get_post_thumbnail_id($app_k['ID']), 'medium')[0],
								"views" => (int)get_post_meta($app_k['ID'], "views", true),
							),
							"title" => array(
								"rendered" => html_entity_decode(get_the_title($app_k['ID'])),
							),
							"excerpt" => array(
								"rendered" => html_entity_decode(wp_strip_all_tags(get_the_excerpt($app_k['ID'])))
							),
							"type" => $book_type,
						);
					}
					$_data["book_tolibrary"] = $__book_tolibrary;
				}
			}
		} else {
			//小鱼哥 专题增加关联书籍字段(列表页)
			if ($post_type == 'topic') {
				if(get_post_meta($post_id, 'book_choosetype')[0]=='1'){
					$book_tolibrary = get_post_meta($post_id, 'book_chooselibrary');
					$book_type = 'library';
				} else if(get_post_meta($post_id, 'book_choosetype')[0]=='2'){
					$book_tolibrary = get_post_meta($post_id, 'book_choosefilms');
					$book_type = 'films';
				} else if(get_post_meta($post_id, 'book_choosetype')[0]=='3'){
					$book_tolibrary = get_post_meta($post_id, 'book_choosemovie');
					$book_type = 'movie';
				} else if(get_post_meta($post_id, 'book_choosetype')[0]=='4'){
					$book_tolibrary = get_post_meta($post_id, 'book_chooseapp');
					$book_type = 'app';
				} else if(get_post_meta($post_id, 'book_choosetype')[0]=='5'){
					$book_tolibrary = get_post_meta($post_id, 'book_choosepro');
					$book_type = 'pro';
				}
				if ($book_tolibrary[0] != false) {
					$__book_tolibrary = array();
					foreach ($book_tolibrary as $key => $app_k) {
						if ($key >= 5) break;
						$__book_tolibrary[] = array(
							"id" => pods_field_display($book_type, $app_k['ID'], 'ID'),
							"book_thumb" => wp_get_attachment_image_src(get_post_thumbnail_id($app_k['ID']), 'medium')[0],
							"book_pagestyle" => pods_field_display($book_type, $app_k['ID'], 'book_pagestyle'),
							"book_foreground" => pods_field_display($book_type, $app_k['ID'], 'book_foreground'),
							"app_type"=>pods_field_display($book_type, $app_k['ID'], 'app_type'),
							"type" => $book_type,
						);
					}
					$_data["book_tolibrary"] = $__book_tolibrary;
				}
			}
			// 小鱼哥 语录增加关联书籍字段
			if ($post_type == 'quot') {
				if(get_post_meta($post_id, 'quot_choosetype')[0]=='1'){
					$book_tolibrary = get_post_meta($post_id, 'quot_library');
					$book_type = 'library';
				} else if(get_post_meta($post_id, 'quot_choosetype')[0]=='2'){
					$book_tolibrary = get_post_meta($post_id, 'quot_films');
					$book_type = 'films';
				}
				if ($book_tolibrary[0] != false) {
					$__book_tolibrary = array();
					foreach ($book_tolibrary as $key => $app_k) {
						if ($key >= 5) break;
						$__book_tolibrary[] = array(
							"id" => pods_field_display($book_type, $app_k['ID'], 'ID'),
							"book_author" => pods_field_display($book_type, $app_k['ID'], 'book_author'),
							"book_director" => pods_field_display($book_type, $app_k['ID'], 'book_director'),
							"book_title" => pods_field_display($book_type, $app_k['ID'], 'book_title'),
							"book_thumb" => wp_get_attachment_image_src(get_post_thumbnail_id($app_k['ID']), 'medium')[0],
							"book_pagestyle" => pods_field_display($book_type, $app_k['ID'], 'book_pagestyle'),
							"book_foreground" => pods_field_display($book_type, $app_k['ID'], 'book_foreground'),
							"type" => $book_type,
						);
					}
					$_data["book_tolibrary"] = $__book_tolibrary;
				}
			}
			if (!wp_miniprogram_option("post_content")) {
				unset($_data['content']);
			}
			if (wp_miniprogram_option("post_picture")) {
				$_data["pictures"] = apply_filters('post_images', $post_id);
			}
		}
	}
	if (is_miniprogram()) {
		unset($_data['book_chooselibrary']);
		unset($_data['book_choosefilms']);
		unset($_data['book_choosemovie']);
		unset($_data['book_chooseapp']);
		unset($_data['book_choosepro']);
		unset($_data['quot_library']);
		unset($_data['quot_films']);
		unset($_data['quot_audio']);
		unset($_data['categories']);
		unset($_data['tags']);
		unset($_data["_edit_lock"]);
		unset($_data["_edit_last"]);
		unset($_data['featured_media']);
		unset($_data['ping_status']);
		unset($_data['template']);
		unset($_data['slug']);
		unset($_data['status']);
		unset($_data['modified_gmt']);
		unset($_data['post_format']);
		unset($_data['date_gmt']);
		unset($_data['guid']);
		unset($_data['curies']);
		unset($_data['modified']);
		unset($_data['status']);
		unset($_data['comment_status']);
		unset($_data['sticky']);
		unset($_data['_links']);
	}
	$data->data = $_data;
	return $data;
}

function rest_prepare_fields_plus($data, $post, $request)
{
	$_data = $data->data;

	$post_id = $post->ID;
	if (is_miniprogram() || is_debug()) {
		$post_date = $post->post_date;
		$post_type = $post->post_type;
		$author_id = $post->post_author;
		$taxonomies = get_object_taxonomies($_data['type']);
		$post_title = $post->post_title;
		$post_views = (int)get_post_meta($post_id, "views", true);
		$post_excerpt = $_data["excerpt"]["rendered"];
		$post_content = $_data["content"]["rendered"];
		$session = isset($request['access_token']) ? $request['access_token'] : '';
		if ($session) {
			$access_token = base64_decode($session);
			$users = MP_Auth::login($access_token);
			if ($users) {
				$user_id = $users->ID;
			} else {
				$user_id = 0;
			}
		} else {
			$user_id = 0;
		}
		$_data["id"]  = $post_id;
		$_data["date"] = $post_date;
		$_data["week"] = get_wp_post_week($post_date);
		unset($_data['author']);
		
		$_data["meta"]["thumbnail"] = apply_filters('post_thumbnail', $post_id);
		$_data["meta"]["views"] = $post_views;
		$_data["meta"]["count"] = mp_count_post_content_text_length($post_content);
		$_data["comments"] = apply_filters('comment_type_count', $post_id, 'comment');
		$_data["likes"] = apply_filters('comment_type_count', $post_id, 'like');

		if ($taxonomies) {
			foreach ($taxonomies as $taxonomy) {
				$terms = wp_get_post_terms($post_id, $taxonomy, array('orderby' => 'term_id', 'order' => 'ASC', 'fields' => 'all'));
				foreach ($terms as $term) {
					$tax = array();
					$term_cover = get_term_meta($term->term_id, 'cover', true);
					$tax["id"] = $term->term_id;
					$tax["name"] = $term->name;
					$tax["description"] = $term->description;
					$tax["cover"] = $term_cover ? $term_cover : wp_miniprogram_option('thumbnail');
					if ($taxonomy === 'post_tag') {
						$taxonomy = "tag";
					}
					$_data[$taxonomy][] = $tax;
				}
			}
		}
		$_data["title"]["rendered"] = html_entity_decode($post_title);
		$_data["excerpt"]["rendered"] = html_entity_decode(wp_strip_all_tags($post_excerpt));

		//小鱼哥
		if ($post_type == 'library') {
			$category = get_the_terms($post_id, 'library_cats');
			$_data["category"] = $category;
			$state = get_the_terms($post_id, 'library_state');
			$_data["state"] = $state;
		} else {
			$category = get_the_terms($post_id, $post_type.'_cats');
			$_data["category"] = $category;
		}

		if (wp_miniprogram_option('mediaon')) {
			$_data["media"]['cover'] = get_post_meta($post_id, 'cover', true) ? get_post_meta($post_id, 'cover', true) : apply_filters('post_thumbnail', $post_id);
			$_data["media"]['author'] = get_post_meta($post_id, 'author', true);
			$_data["media"]['title'] = get_post_meta($post_id, 'title', true);
			$_data["media"]['video'] = get_post_meta($post_id, 'video', true);
			$_data["media"]['audio'] = get_post_meta($post_id, 'audio', true);
		}
		if (isset($request['id'])) {
			if (!update_post_meta($post_id, 'views', ($post_views + 1))) {
				add_post_meta($post_id, 'views', 1, true);
			}
			if (is_smart_miniprogram()) {
				$custom_keywords = get_post_meta($post_id, "keywords", true);
				if (!$custom_keywords) {
					$custom_keywords = "";
					$tags = wp_get_post_tags($post_id);
					foreach ($tags as $tag) {
						$custom_keywords = $custom_keywords . $tag->name . ",";
					}
				}
				$_data["smartprogram"]["title"] = $_data["title"]["rendered"] . '-' . get_bloginfo('name');
				$_data["smartprogram"]["keywords"] = $custom_keywords;
				$_data["smartprogram"]["description"] = $_data["excerpt"]["rendered"];
				$_data["smartprogram"]["image"] = apply_filters('post_images', $post_id);
				$_data["smartprogram"]["visit"] = array('pv' => $post_views);
				$_data["smartprogram"]["comments"] =  apply_filters('comment_type_count', $post_id, 'comment');
				$_data["smartprogram"]["likes"] = apply_filters('comment_type_count', $post_id, 'like');
				$_data["smartprogram"]["collects"] = apply_filters('comment_type_count', $post_id, 'fav');
			}
			if (!$media_video) {
				$_data["content"]["rendered"] = apply_filters('the_video_content', $post_content);
			}
			// 小鱼哥 增加 评分字段
			$args = array(
				'post_id' => get_the_ID(),
				'status'  => 'approve'
			);

			$comments = get_comments($args);
			$ratings  = array();
			$count    = 0;
			$count_rating_5    = 0;
			$count_rating_4    = 0;
			$count_rating_3    = 0;
			$count_rating_2    = 0;
			$count_rating_1    = 0;

			foreach ($comments as $comment) {

				$rating = get_comment_meta($comment->comment_ID, 'rating', true);

				if (!empty($rating)) {
					$ratings[] = absint($rating);
					$count++;
				}
				if ($rating == '5') {
					$count_rating_5++;
				}
				if ($rating == '4') {
					$count_rating_4++;
				}
				if ($rating == '3') {
					$count_rating_3++;
				}
				if ($rating == '2') {
					$count_rating_2++;
				}
				if ($rating == '1') {
					$count_rating_1++;
				}
			}

			if (0 != count($ratings)) {

				$avg = (array_sum($ratings) / count($ratings));

				$rating_avg = array(
					'avg'   => round($avg, 1),
					'count' => $count,
					'count5' => $count_rating_5,
					'count4' => $count_rating_4,
					'count3' => $count_rating_3,
					'count2' => $count_rating_2,
					'count1' => $count_rating_1
				);
			}
			$_data['rating_avg'] = $rating_avg;
			// $_data["post_favs"] = apply_filters( 'comment_type_list', $post_id, 'fav' );
			$_data["isfav"] = apply_filters('miniprogram_commented', $post_id, $user_id, 'fav');
			$_data["islike"] = apply_filters('miniprogram_commented', $post_id, $user_id, 'like');
			$_data["post_likes"] = apply_filters('comment_type_list', $post_id, 'like');
			if (wp_miniprogram_option("prevnext")) {
				$category = get_the_category($post_id);
				$next = get_next_post($category[0]->term_id, '', 'category');
				$previous = get_previous_post($category[0]->term_id, '', 'category');
				if (!empty($next->ID)) {
					$_data["next_post"]["id"] = $next->ID;
					$_data["next_post"]["title"]["rendered"] = $next->post_title;
					$_data["next_post"]["thumbnail"] = apply_filters('post_thumbnail', $next->ID);
					$_data["next_post"]["views"] = (int)get_post_meta($next->ID, "views", true);
				}
				if (!empty($previous->ID)) {
					$_data["prev_post"]["id"] = $previous->ID;
					$_data["prev_post"]["title"]["rendered"] = $previous->post_title;
					$_data["prev_post"]["thumbnail"] = apply_filters('post_thumbnail', $previous->ID);
					$_data["prev_post"]["views"] = (int)get_post_meta($previous->ID, "views", true);
				}
			}
		} else {
			if (!wp_miniprogram_option("post_content")) {
				unset($_data['content']);
			}
			if (wp_miniprogram_option("post_picture")) {
				$_data["pictures"] = apply_filters('post_images', $post_id);
			}
		}
	}
	if (is_miniprogram()) {
		unset($_data['book_chooselibrary']);
		unset($_data['categories']);
		unset($_data['tags']);
		unset($_data["_edit_lock"]);
		unset($_data["_edit_last"]);
		unset($_data['featured_media']);
		unset($_data['ping_status']);
		unset($_data['template']);
		unset($_data['slug']);
		unset($_data['status']);
		unset($_data['modified_gmt']);
		unset($_data['post_format']);
		unset($_data['date_gmt']);
		unset($_data['guid']);
		unset($_data['curies']);
		unset($_data['modified']);
		unset($_data['status']);
		unset($_data['comment_status']);
		unset($_data['sticky']);
		unset($_data['_links']);
	}
	$data->data = $_data;
	return $data;
}

add_filter( 'rest_prepare_page',function ($data, $post, $request) {
	$_data = $data->data;
	$post_id = $post->ID;
	if( is_miniprogram() || is_debug() ) {
		$post_date = $post->post_date;
		$author_id = $post->post_author;
		$author_avatar = get_user_meta($author_id, 'avatar', true);
		$post_title = $post->post_title;
		$post_views = (int)get_post_meta( $post_id, "views" ,true );
		$post_excerpt = $_data["excerpt"]["rendered"];
		$post_content = $_data["content"]["rendered"];
		$_data["id"]  = $post_id;
		$_data["date"] = $post_date;
		$_data["except"] = get_post_meta( $post_id, "except" ,true )?true:false;
		unset($_data['author']);
		$_data["author"]["id"] = $author_id;
		$_data["author"]["name"] = get_the_author_meta('nickname',$author_id);
		if($author_avatar) {
			$_data["author"]["avatar"] = $author_avatar;
		} else {
			$_data["author"]["avatar"] = get_avatar_url($author_id);
		}
		$_data["author"]["description"] = get_the_author_meta('description',$author_id);
		$_data["menu"]["icon"] = get_post_meta( $post_id, "icon" ,true );
		$_data["menu"]["title"] = get_post_meta( $post_id, "title" ,true );
		$_data["meta"]["thumbnail"] = apply_filters( 'post_thumbnail', $post_id );
		$_data["meta"]["views"] = $post_views;
		$_data["comments"] = apply_filters( 'comment_type_count', $post_id, 'comment' );
		$_data["favs"] = apply_filters( 'comment_type_count', $post_id, 'fav' );
		$_data["likes"] = apply_filters( 'comment_type_count', $post_id, 'like' );
		$_data["title"]["rendered"] = html_entity_decode( $post_title );
		if( !$post_excerpt ) {
			$_data["excerpt"]["rendered"] = html_entity_decode( wp_trim_words( wp_strip_all_tags( $post_content ), 100, '...' ) ); 
		}
		if( !isset( $request['id'] ) ) {
			if(wp_miniprogram_option("post_content")) { unset($_data['content']); }
		} else {
			if( is_smart_miniprogram() ) {
				$custom_keywords = get_post_meta( $post_id, "keywords", true );
				if( !$custom_keywords ) {
					$custom_keywords = "";
					$tags = wp_get_post_tags( $post_id );
					foreach ($tags as $tag ) {
						$custom_keywords = $custom_keywords . $tag->name . ",";
					}
				}
				$_data["smartprogram"]["title"] = $_data["title"]["rendered"] .'-'.get_bloginfo('name');
				$_data["smartprogram"]["keywords"] = $custom_keywords;
				$_data["smartprogram"]["description"] = $post_excerpt ? $post_excerpt : html_entity_decode( wp_trim_words( wp_strip_all_tags( $post_content ), 100, '...' ) ); 
				$_data["smartprogram"]["image"] = apply_filters( 'post_images', $post_id );
				$_data["smartprogram"]["visit"] = array( 'pv' => $post_views );
				$_data["smartprogram"]["comments"] =  apply_filters( 'comment_type_count', $post_id, 'comment' );
				$_data["smartprogram"]["likes"] = apply_filters( 'comment_type_count', $post_id, 'like' );
				$_data["smartprogram"]["collects"] = apply_filters( 'comment_type_count', $post_id, 'fav' );
			}
			if( !update_post_meta( $post_id, 'views', ( $post_views + 1 ) ) ) {
				add_post_meta($post_id, 'views', 1, true);  
			}
		}
	}
	if( is_miniprogram() ) {
		unset($_data["_edit_lock"]);
		unset($_data["_edit_last"]);
		unset($_data['featured_media']);
		unset($_data['ping_status']);
		unset($_data['template']);
		unset($_data['slug']);
		unset($_data['modified_gmt']);
		unset($_data['post_format']);
		unset($_data['date_gmt']);
		unset($_data['guid']);
		unset($_data['curies']);
		unset($_data['modified']);
		unset($_data['status']);
		unset($_data['comment_status']);
		unset($_data['sticky']);    
		unset($_data['_links']);
	}
	$data->data = $_data;
	return $data;
}, 10, 3 );

add_filter( 'rest_prepare_category',function($data, $item, $request) {
	$cover = '';
	$term_id = $item->term_id;
	$args = array('category'=>$term_id,'numberposts' => 1);
	$posts = get_posts($args);
	if(!empty($posts)) {
		$recent_date = $posts[0]->post_date;
	} else {
		$recent_date = '无更新';
	}
	if(get_term_meta($item->term_id,'cover',true)) {
        $cover = get_term_meta($item->term_id,'cover',true);
	} else {
		$cover = wp_miniprogram_option('thumbnail');
	}
	if(get_term_meta($item->term_id,'except',true)) {
		$except = false;
	} else {
		$except = true;
	}
	if( isset($request['id']) ) {
		if( is_smart_miniprogram() ) {
			$smartprogram["title"] = $item->name .'-'.get_bloginfo('name');
			$smartprogram["keywords"] = $item->name;
			$smartprogram["description"] = $item->description;
			$data->data['smartprogram'] = $smartprogram;
		}
	}
	$data->data['cover'] = $cover;
	$data->data['date'] = $recent_date;
	$data->data['except'] = $except;
	return $data;
}, 10, 3 );
add_filter('rest_prepare_library_cats', 'rest_prepare_cats_plus', 10, 3);
add_filter('rest_prepare_app_cats', 'rest_prepare_cats_plus', 10, 3);
add_filter('rest_prepare_films_cats', 'rest_prepare_cats_plus', 10, 3);
add_filter('rest_prepare_pro_cats', 'rest_prepare_cats_plus', 10, 3);

function rest_prepare_cats_plus($data, $item, $request) {
	$cover = '';
	$term_id = $item->slug;
	$posttype = isset($request['posttype'])?$request['posttype']:'library';
	$args = array($posttype.'_cats' => $term_id,'numberposts' => 5, 'post_type' => $posttype);
	$posts = get_posts($args);
	
	if (!empty($posts)) {
		$recent_date = $posts[0]->post_date;
		
		
			foreach ($posts as $key => $post) {
				if ($key >= 5) break;
			
				$__posts[] = array(
					"id" => $post->ID,
					"book_pagestyle" => get_post_meta($post->ID, 'book_pagestyle'),
					"book_foreground" => get_post_meta($post->ID, 'book_foreground'),
					"app_type" => get_post_meta($post->ID, 'app_type'),
					"meta" => array(
						"thumbnail" =>  wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'medium')[0],
						"views" => (int)get_post_meta($post->ID, "views", true)
					),
				);
			}
			$posts = $__posts;
	} else {
		$recent_date = '无更新';
	}
	if (get_term_meta($item->term_id, 'cover', true)) {
		$cover = get_term_meta($item->term_id, 'cover', true);
	} else {
		$cover = wp_miniprogram_option('thumbnail');
	}
	if (get_term_meta($item->term_id, 'except', true)) {
		$except = false;
	} else {
		$except = true;
	}
	if (isset($request['id'])) {
		if (is_smart_miniprogram()) {
			$smartprogram["title"] = $item->name . '-' . get_bloginfo('name');
			$smartprogram["keywords"] = $item->name;
			$smartprogram["description"] = $item->description;
			$data->data['smartprogram'] = $smartprogram;
		}
	}
	$data->data['posts'] = $posts;
	$data->data['cover'] = $cover;
	$data->data['date'] = $recent_date;
	$data->data['except'] = $except;
	return $data;
}

add_filter( 'rest_prepare_post_tag', function($data, $item, $request) {
	$cover = '';
	$term_id = $item->term_id;
    if(get_term_meta($item->term_id,'cover',true)) {
        $cover = get_term_meta($item->term_id,'cover',true);
    } else {
		$cover = wp_miniprogram_option('thumbnail');
	}
	if(get_term_meta($item->term_id,'except',true)) {
		$except = false;
	} else {
		$except = true;
	}
	$data->data['cover'] = $cover;
	$data->data['except'] = $except;
	if( isset($request['id']) ) {
		if( is_smart_miniprogram() ) {
			$smartprogram["title"] = $item->name .'-'.get_bloginfo('name');
			$smartprogram["keywords"] = $item->name;
			$smartprogram["description"] = $item->description;
			$data->data['smartprogram'] = $smartprogram;
		}
	}
	return $data;
}, 10, 3 );

add_filter( 'the_content',function ($content) {
	$post_id = get_the_ID();
	if(wp_miniprogram_option('mediaon')) {
		if(get_post_meta( $post_id, 'cover' ,true )) {
			$cover_url = get_post_meta( $post_id, 'cover' ,true );
		} else {
			$cover_url = apply_filters( 'post_thumbnail', $post_id );
		}
		if(get_post_meta( $post_id, 'author' ,true )){
			$media_author = 'author="'.get_post_meta( $post_id, 'author' ,true ).'" ';
		} else {
			$media_author = '';
		}
		if(get_post_meta( $post_id, 'title' ,true )){
			$media_title = ' title="'.get_post_meta( $post_id, 'title' ,true ).'" ';
		} else {
			$media_title = '';
		}
		$video_id = get_post_meta($post_id,'video',true);
		$audio_id = get_post_meta($post_id,'audio',true);
		if(!empty($video_id) && wp_miniprogram_option('qvideo')) {
			$video = apply_filters( 'tencent_video', $video_id );
			if($video) {
				$video_code = '<p><video '.$media_author.$media_title.' controls="controls" poster="'.$cover_url.'" src="'.$video.'" width="100%"></video></p>';
			} else {
				$video_code = '<p><video '.$media_author.$media_title.' controls="controls" poster="'.$cover_url.'" src="'.$video_id.'" width="100%"></video></p>';
			}
			$content = $video_code.$content;
		}
		if(!empty($audio_id)) {
			$audio_code = '<p><audio '.$media_author.$media_title.' controls="controls" src="'.$audio_id.'" width="100%"></audio></p>';
			$content = $audio_code.$content;
		}
	}
	return $content;
});

add_filter('category_description', 'wp_strip_all_tags');

add_filter( 'user_contactmethods',function($userInfo) {
	$userInfo['gender'] 				= __( '性别' );
	$userInfo['openid'] 				= __( 'OpenID' );
	$userInfo['avatar'] 				= __( '微信头像' );
	$userInfo['city'] 					= __( '所在城市' );
	$userInfo['province'] 				= __( '所在省份' );
	$userInfo['country'] 				= __( '所在国家' );
	$userInfo['language'] 				= __( '系统语言' );
	$userInfo['expire_in'] 				= __( '缓存有效期' );
	return $userInfo;
});

add_action( 'personal_options_update', 'update_miniprogam_platform' );
add_action( 'edit_user_profile_update', 'update_miniprogam_platform' );

function update_miniprogam_platform( $user_id ) {
	if( !current_user_can( 'edit_user', $user_id ) )
    	return false;
	update_user_meta( $user_id, 'platform', $_POST['platform'] );
}

add_action( 'show_user_profile', 'add_miniprogam_platform_source' );
add_action( 'edit_user_profile', 'add_miniprogam_platform_source' );

function add_miniprogam_platform_source( $user ) { ?>
	<table class="form-table">
		<tr>
			<th><label for="dropdown">平台用户</label></th>
			<td>
			<?php $selected = get_the_author_meta( 'platform', $user->ID ); ?>
            <select name="platform" id="platform">
				<option value="website" <?php selected( $selected, 'website', true ) ?>>网站注册</option>
                <option value="wechat" <?php selected( $selected, 'wechat', true ) ?>>微信小程序</option>
				<option value="tencent" <?php selected( $selected, 'tencent', true ) ?>>QQ 小程序</option>
				<option value="baidu" <?php selected( $selected, 'baidu', true ) ?>>百度小程序</option>
				<option value="toutiao" <?php selected( $selected, 'toutiao', true ) ?>>头条小程序</option>
            </select>
            <span class="description">用户注册来源所属平台</span>
			</td>
		</tr>
	</table>
	<?php }

add_filter( 'manage_users_columns', function ( $columns ){ 
	$columns["registered"] = "注册时间";
	$columns["platform"] = "注册平台";
	return $columns;
});
add_action( 'manage_users_custom_column', function ( $value, $column_name, $user_id ) {
	$user = get_userdata( $user_id );
	if('registered' == $column_name){
		$value = get_date_from_gmt($user->user_registered);
	} else if('platform' == $column_name){
		$platform = get_user_meta($user->ID, 'platform', true);
		if($platform == 'wechat') {
			$value = '微信小程序';
		} elseif($platform == 'tencent') {
			$value = 'QQ 小程序';
		} elseif($platform == 'baidu') {
			$value = '百度小程序';
		} elseif($platform == 'toutiao') {
			$value = '头条小程序';
		} else {
			$value = '网站用户';
		}
	}
	return $value;
}, 10, 3 );

add_action('admin_head-edit-comments.php', function (){
	echo'<style type="text/css">
		.column-type { width:80px; }
		</style>';
});
add_filter( 'manage_edit-comments_columns', function ( $columns ){
	$columns[ 'type' ] = __( '类型' );
	return $columns;
});
add_action( 'manage_comments_custom_column',function  ( $column_name, $comment_id ){
	switch( $column_name ) {
		case "type":
			$type = get_comment_type();
			switch( $type ) {
				case 'fav' :
					echo "收藏";
					break;
				case 'like' :
					echo "点赞";
					break;
				case 'comment' :
					echo "评论";
					break;
				default :
					echo $commenttxt;
			}
	}
}, 10, 2 );

if(wp_miniprogram_option('reupload')) {
	add_filter('wp_handle_upload_prefilter',function ($file) {
		$time = date("YmdHis");
		$file['name'] = $time . "" . mt_rand(1, 100) . "." . pathinfo($file['name'], PATHINFO_EXTENSION);
		return $file;
	});
}

if( wp_miniprogram_option('gutenberg') || is_debug() ) {
	add_filter('use_block_editor_for_post_type', '__return_false');
}

add_shortcode('qvideo', function ($attr) {
	extract(
		shortcode_atts(
			array(
				'vid' => ''
			),
			$attr
		)
	);
	if(strpos($vid, 'v.qq.com') === false) {
		$url = 'https://v.qq.com/x/page/'.$vid.'.html';
	} else {
		$url =  $vid;
	}
	$video = apply_filters( 'tencent_video', $url );
	if( $video ) {
		$output = '<p><video controls="controls" poster="https://puui.qpic.cn/qqvideo_ori/0/'.$vid.'_496_280/0" src="'.$video.'" width="100%"></video></p>';
	} else {
		$output = '<p>腾讯视频参数不支持，请重新检查！</p>';
	}
	return $output;
});

add_action( 'admin_print_footer_scripts', function () {
	if( wp_script_is('quicktags') ) {
?>
    <script type="text/javascript">
    QTags.addButton( 'qvideo', '腾讯视频', '[qvideo vid="腾讯视频 vid 或 url"]','' );
    </script>
<?php
    }
} );