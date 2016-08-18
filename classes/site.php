<?php

class pmlnr_site extends pmlnr_base {

	public function __construct () {
	}


	/**
	 *
	 *
	public static function get_the_header() {

		ob_start();
		wp_head();
		$r = ob_get_clean();

		$r = str_replace("'", '"', $r);
		$r = preg_replace('/\?ver=.*?"/', '"', $r);

		return $r;
	}

	/**
	 *
	 *
	public static function get_the_footer() {

		ob_start();
		wp_footer();
		$r = ob_get_clean();

		$r = preg_replace('/\?ver=.*?"/', '"', $r);

		return $r;
	}

	/**
	 *
	 *
	public static function get_css( $file = 'style' ) {

		$base = get_stylesheet_directory();
		$r = file_get_contents( "{$base}/{$file}.css" );

		return $r;
	}

	/**
	 *
	 *
	public static function get_the_pagination() {
		global $wp_query;

		$current = 1;
		if ( isset( $wp_query->query_vars['paged'] ) && $wp_query->query_vars['paged'] > 1 )
			$current = $wp_query->query_vars['paged'];

		$pargs = array(
			'format' => 'page/%#%',
			'current' => $current,
			'end_size' => 1,
			'mid_size' => 2,
			'prev_next' => True,
			'prev_text' => __('«'),
			'next_text' => __('»'),
			'type' => 'array',
			'total' => $wp_query->max_num_pages,
		);
		$r = paginate_links( $pargs );


		if ( ! empty( $r ) && is_array ( $r ) ) {
			foreach ( $r as $k => $l ) {
				if (strstr( $l, '»'))
					$r[ $k ] = str_replace( 'a class', 'a rel="next" class', $l );
				elseif (strstr( $l, '«'))
					$r[ $k ] = str_replace( 'a class', 'a rel="prev" class', $l );
				else
					continue;
			}
		}

		return $r;
	}


	/**
	 *
	 */
	public static function template_vars ( ) {

		$terms = $menus = array();
		$author_id = 1;
		$atitle = false;

		if (is_page()) {
			$post = static::fix_post();
			$terms[] = $post->ID;
		}
		elseif (is_singular()) {
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
			$atitle = $term->name;
		}

		$r = array (
			'url' => rtrim( site_url() , '/' ),
			'domain' => parse_url( site_url(), PHP_URL_HOST ),
			'charset' => get_bloginfo('charset'),
			'name' => get_bloginfo('name'),
			'description' => get_bloginfo('description'),
			'theme_url' => rtrim( get_bloginfo('stylesheet_directory'), '/'),
			'pingback_url' => get_bloginfo('pingback_url'),
			'rss_url' => get_bloginfo('rss2_url'),
			'favicon' => get_bloginfo('template_directory') . '/images/favicon.png',
			'author' => pmlnr_author::template_vars( 1 ),
		);

		return $r;
	}
}

