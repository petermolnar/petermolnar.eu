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

		$name = get_bloginfo('name');
		$taxonomy = $description = false;

		if ( is_archive() ) {
			global $wp_query;
			$term = $wp_query->get_queried_object();
			$name = $term->name;
			$taxonomy = $term->taxonomy;
			$description = pmlnr_markdown::parsedown( $term->description );
		}

		$curr_feed = rtrim( $curr_url, '/' ) . '/feed';

		$subscribe = array (
			'resource' => $curr_url,
			'feeds' => $curr_feed,
			'suggestedUrl' => 'http://blogtrottr.com/?subscribe={feed}',
			'suggestedName' => 'Blogtrottr',
		);

		$r = array (
			'name' => $name,
			'taxonomy' => $taxonomy,
			'url' => $curr_url,
			'feed' => $curr_feed,
			'description' => $description,
			'subscribe' => 'https://www.subtome.com/?subs/#/subscribe?'
				. http_build_query ( $subscribe ),
		);

		wp_cache_set ( $curr_url, $r,
			__CLASS__ . __FUNCTION__, static::expire );

		return $r;
	}
}

