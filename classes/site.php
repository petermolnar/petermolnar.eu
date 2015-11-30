<?php

class pmlnr_site extends pmlnr_base {

	public function __construct () {
	}


	/**
	 *
	 */
	public static function get_the_header() {

		if ( $cached = wp_cache_get ( __FUNCTION__, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		ob_start();
		wp_head();
		$r = ob_get_clean();

		wp_cache_set ( __FUNCTION__, $r, __CLASS__ . __FUNCTION__, self::expire );

		$r = str_replace("'", '"', $r);
		$r = preg_replace('/\?ver=.*?"/', '"', $r);

		return $r;
	}

	/**
	 *
	 */
	public static function get_the_sidebar($name='') {

		if (empty($name))
			return false;

		if ( $cached = wp_cache_get ( $name, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		ob_start();
		dynamic_sidebar( $name );
		$r = ob_get_clean();

		wp_cache_set ( $name, $r, __CLASS__ . __FUNCTION__, self::expire );

		return $r;
	}

	/**
	 *
	 */
	public static function get_the_footer() {

		if ( $cached = wp_cache_get ( __FUNCTION__, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		ob_start();
		wp_footer();
		$r = ob_get_clean();

		$r = preg_replace('/\?ver=.*?"/', '"', $r);

		wp_cache_set ( __FUNCTION__, $r, __CLASS__ . __FUNCTION__, self::expire );

		return $r;
	}

	/**
	 *
	 */
	public static function template_vars ( $prefix = 'site_' ) {

		//if ( $cached = wp_cache_get ( __FUNCTION__ . $prefix, __CLASS__ . __FUNCTION__ ) )
		//	return $cached;

		$terms = $menus = array();
		$author_id = 1;

		if (is_singular()) {
			$post = static::fix_post();

			$terms[] = $post->ID;

			$categories = get_the_category( $post->ID );
			if (!empty($categories) && is_array($categories))
				foreach ($categories as $category)
					$terms[] = $category->term_id;

			$tags = get_the_tags ($post->ID );
			if (!empty($tags) && is_array($tags))
				foreach ($tags as $tag)
					$terms[] = $tag->term_id;
		}
		elseif (is_archive()) {
			global $wp_query;
			$term = $wp_query->get_queried_object();
			$terms[] = $term->term_id;
		}

		$r = array (
			'url' => static::fix_url(get_bloginfo('url')),
			'charset' => get_bloginfo('charset'),
			'name' => get_bloginfo('name'),
			'description' => get_bloginfo('description'),
			'language' => get_bloginfo('language'),
			'content_dir' => WP_CONTENT_DIR,
			'content_url' => WP_CONTENT_URL,
			'theme_url' => get_bloginfo('stylesheet_directory'),
			'pingback_url' => get_bloginfo('pingback_url'),
			'rss_url' => get_bloginfo('rss2_url'),
			'favicon' => get_bloginfo('template_directory') . '/images/favicon.png',
			'user_lang' => isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : '',
			'author' => pmlnr_author::template_vars( $author_id ),
			'header' => static::get_the_header(),
			'footer' => static::get_the_footer(),
			//'subscribe_sidebar' => static::get_the_sidebar('subscribe'),
		);

		// menu vars
		$menu_name = petermolnareu::menu_header;
		if ( ( $locations = get_nav_menu_locations() ) && isset( $locations[ $menu_name ] ) ) {

			$menu = wp_get_nav_menu_object( $locations[ $menu_name ] );
			$items = wp_get_nav_menu_items($menu->term_id);

			foreach ( (array) $items as $key => $item ) {

				$active = false;

				$_url = parse_url($item->url);
				$_siteurl = parse_url($r['url']);

				if ( in_array( $item->object_id, $terms) )
					$active = true;
				elseif ( is_home() && ($_url['path'] == '/' || empty($_url['path'])) && $_url['host'] == $_siteurl['host'])
					$active = true;

				$e = array (
					'title' => $item->title,
					'url' => $item->url,
					'active' => $active,
				);

				$r['menu'][ $item->ID ] = $e;
			}
		}



		if (!empty($prefix)) {
			foreach ($r as $key => $value ) {
				$r[ $prefix . $key ] = $value;
				unset($r[$key]);
			}
		}

		//wp_cache_set ( __FUNCTION__ . $prefix , $r, __CLASS__ . __FUNCTION__, self::expire );

		return $r;
	}
}
