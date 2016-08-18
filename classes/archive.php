<?php

class pmlnr_archive extends pmlnr_base {

	public function __construct () {
	}

	/**
	 *
	 */
	public static function template_vars ( $qstr = false ) {

		$r = array();

		if ( ! is_archive() && ! is_home() )
			return $r;

		$url = site_url ( $_SERVER['REQUEST_URI'] );


		global $wp_query;
		global $query_string;

		if ( false !== $qstr ) {
			query_posts( $qstr );
		}

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
			$description = "";
			//$term->description;
		}

		$feed = rtrim( $url, '/' ) . '/feed';
		$base_url = preg_replace( '/\/page\/[0-9]+\/?$/', '', $url );

		$r = array (
			'name' => $name,
			'taxonomy' => $taxonomy,
			'url' => $url,
			'pagination_base' => $base_url,
			'feed' => $feed,
			'description' => $description,
			'paged' => $paged,
			'total' => $total,
			'perpage' => $perpage,
		);

		return $r;
	}
}

