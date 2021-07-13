<?php

if ( !defined( 'ABSPATH' ) ) exit;

class WP_REST_Menu_Router extends WP_REST_Controller {
	
	public function __construct( ) {
		$this->namespace     = 'mp/v1';
        $this->resource_name = 'menu';
	}

	public function register_routes() {
		
		register_rest_route( $this->namespace, '/'.$this->resource_name, array(
			array(
				'methods'             	=> WP_REST_Server::READABLE,
				'callback'            	=> array( $this, 'get_minapp_menu' ),
				'permission_callback' 	=> array( $this, 'wp_menu_permissions_check' ),
				'args'                	=> array(
					'context' => $this->get_context_param( array( 'default' => 'view' ) )
				)
			)
		));
		
	}

	public function wp_menu_permissions_check( $request ) {
		return true;
	}
	
	public function get_minapp_menu( ) {
		
		$data = array();
		
		if ( ( $locations = get_nav_menu_locations() ) && isset( $locations[ 'minapp-menu' ] ) ) {
			$menu = wp_get_nav_menu_object( $locations[ 'minapp-menu' ] );
			$navigation = wp_get_nav_menu_items($menu->term_id);
			foreach($navigation as $nav) {
				$_data = array();
				if($nav->type == 'taxonomy' && $nav->object == 'category') {
					$_data['id'] = $nav->menu_order;
					$_data['name'] = $nav->title;
					$_data['type'] = 'page';
					$_data['class'] = $nav->classes;
					$_data['icon'] = $nav->description;
					$_data['url'] = '/pages/list/list?id='.$nav->object_id;
				} elseif($nav->type == 'post_type' && $nav->object == 'post') {
					$_data['id'] = $nav->menu_order;
					$_data['name'] = $nav->title;
					$_data['type'] = 'page';
					$_data['class'] = $nav->classes;
					$_data['icon'] = $nav->description;
					$_data['url'] = '/pages/detail/detail?id='.$nav->object_id;
				} elseif($nav->type == 'post_type' && $nav->object == 'page') {
					$_data['id'] = $nav->menu_order;
					$_data['name'] = $nav->title;
					$_data['type'] = 'page';
					$_data['class'] = $nav->classes;
					$_data['icon'] = $nav->description;
					$_data['url'] = '/pages/page/page?id='.$nav->object_id;
				} elseif($nav->xfn == 'app' || $nav->xfn == 'weapp' || $nav->xfn == 'qq' || $nav->xfn == 'baidu' || $nav->xfn == 'toutiao') {
					if( is_wechat_miniprogram() && ( $nav->xfn == 'qq' || $nav->xfn == 'baidu' || $nav->xfn == 'toutiao' ) ) {
						continue;
					} else if( is_tencent_miniprogram() && $nav->xfn != 'qq' ) {
						continue;
					} else if( is_smart_miniprogram() && $nav->xfn != 'baidu' ) {
						continue;
					} else if( is_toutiao_miniprogram() && $nav->xfn != 'toutiao' ) {
						continue;
					} else {
						$_data['id'] = $nav->menu_order;
						$_data['name'] = $nav->title;
						$_data['type'] = $nav->xfn;
						$_data['class'] = $nav->classes;
						$_data['icon'] = $nav->description;
						$_data['appid'] = str_replace('https://','',str_replace('http://','',$nav->url));
					}
				} elseif($nav->xfn == 'tel') {
					$_data['id'] = $nav->menu_order;
					$_data['name'] = $nav->title;
					$_data['type'] = $nav->xfn;
					$_data['class'] = $nav->classes;
					$_data['icon'] = $nav->description;
					$_data['url'] = str_replace('https://','',str_replace('http://','',$nav->url));
				} elseif($nav->xfn == 'page') {
					$_data['id'] = $nav->menu_order;
					$_data['name'] = $nav->title;
					$_data['type'] = $nav->xfn;
					$_data['class'] = $nav->classes;
					$_data['icon'] = $nav->description;
					$_data['url'] = str_replace('https://','',str_replace('http://','',$nav->url));
				} elseif($nav->xfn == 'contact') {
					$_data['id'] = $nav->menu_order;
					$_data['name'] = $nav->title;
					$_data['type'] = $nav->xfn;
					$_data['class'] = $nav->classes;
					$_data['icon'] = $nav->description;
					$_data['url'] = str_replace('https://','',str_replace('http://','',$nav->url));
				} else {
					$_data['id'] = $nav->menu_order;
					$_data['name'] = $nav->title;
					$_data['type'] = $nav->xfn;
					$_data['class'] = $nav->classes;
					$_data['icon'] = $nav->description;
					$_data['url'] = '/pages/view/view?url='.$nav->url;
				}
				$data[] = $_data;
			}
		}

		if($data) {
			$result = array(
				'status'	=> 200,
				'success' 	=> true ,
				'message'	=> 'miniprogram menu setting success',
				'data'		=> $data
			);
		} else {
			$result = array(
				'status'	=> 400,
				'success' 	=> false ,
				'message'	=> 'miniprogram menu setting failure'
			);
		}
		
		$response = rest_ensure_response( $result );
		
		return $response;
		
	}
	
}