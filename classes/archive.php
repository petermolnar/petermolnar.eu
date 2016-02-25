<?php

class pmlnr_archive extends pmlnr_base {

	public function __construct () {
	}

	/**
	 *
	 */
	public static function template_vars ( $prefix = '' ) {

		$r = array();


		if ( !is_archive() && !is_home())
			return $r;

		$curr_url = static::fix_url ( $_SERVER['REQUEST_URI'] );

		if ( $cached = wp_cache_get ( $curr_url . $prefix, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$name = get_bloginfo('name');
		$taxonomy = false;

		if ( is_archive() ) {
			global $wp_query;
			$term = $wp_query->get_queried_object();
			$name = $term->name;
			$taxonomy = $term->taxonomy;
		}

		$r = array (
			'name' => $name,
			'taxonomy' => $taxonomy,
			'url' => $curr_url,
			'feed' => rtrim( $curr_url, '/' ) . '/feed',
		);

		$r = static::prefix_array ( $r, $prefix );

		wp_cache_set ( $curr_url . $prefix, $r, __CLASS__ . __FUNCTION__, static::expire );

		return $r;
	}
}

