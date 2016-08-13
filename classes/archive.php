<?php

class pmlnr_archive extends pmlnr_base {

	public function __construct () {
	}

	/**
	 *
	 */
	public static function template_vars () {

		$r = array();

		if ( ! is_archive() && ! is_home() )
			return $r;

		$curr_url = site_url ( $_SERVER['REQUEST_URI'] );

		if ( $cached = wp_cache_get ( $curr_url,
			__CLASS__ . __FUNCTION__ ) )
			return $cached;

		global $wp_query;

		$paged = 1;
		if ( isset( $wp_query->query_vars['paged'] )
			&& $wp_query->query_vars['paged'] > 1
		)
			$paged = $wp_query->query_vars['paged'];

		$perpage = 10;
		if ( isset( $wp_query->query_vars['posts_per_page'] ) )
			$perpage = $wp_query->query_vars['posts_per_page'];

		$total = 1;
		if ( isset( $wp_query->max_num_pages ) )
			$total = $wp_query->max_num_pages;

		$name = get_bloginfo('name');
		$taxonomy = $description = false;
		if ( is_archive() ) {
			$term = $wp_query->get_queried_object();
			$name = $term->name;
			$taxonomy = $term->taxonomy;
			$description = pmlnr_markdown::parsedown( $term->description );
		}

		$feed = rtrim( $url, '/' ) . '/feed';

		$r = array (
			'name' => $name,
			'taxonomy' => $taxonomy,
			'url' => $url,
			'feed' => $feed,
			'description' => $description,
			'paged' => $paged,
			'total' => $total,
			'perpage' => $perpage,
		);

		wp_cache_set ( $curr_url, $r,
			__CLASS__ . __FUNCTION__, static::expire );

		return $r;
	}
}

