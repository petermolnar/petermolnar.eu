<?php

class pmlnr_post extends pmlnr_base {

	public function __construct () {
	}

	/**
	 *
	 */
	public static function remove_reaction ( &$content, $reaction = false ) {
		if ( false === $reaction )
			$reaction = static::has_reaction( $content );

		if ( false == $reaction )
			return $content;

		$r = $content;
		foreach ( $reaction[0] as $cntr => $replace )  {
			$pattern = '/(?:^|\s)' . preg_quote($replace, '/' ) . '(:?\s|$|\n|\r)/';
			$r = preg_replace( $pattern, '', $r );
		}

		return trim ( $r );
	}

	/**
	 *
	 */
	public static function get_the_content( &$post = null, $clean = false ){

		$post = static::fix_post ( $post );

		if ( false === $post )
			return false;

		$r = $post->post_content;

		$r = static::remove_reaction ( $r );
		$r = apply_filters('the_content', $r);

		return $r;
	}

	/**
	 *
	 */
	public static function get_the_excerpt( &$post = null ){
		$post = static::fix_post($post);

		$r = apply_filters('the_excerpt', $post->post_excerpt);

		return $r;
	}

	/**
	 *
	 */
	public static function post_get_tags_array ( &$post = null ) {
		$post = static::fix_post($post);

		if ($post === false)
			return false;

		$format = static::post_format( $post );

		$r = [];

		// backfill photo tag from format, in case it's forgotten
		if ( $format == 'photo' && ! has_term ( 'photo', 'post_tag', $post ) )
			wp_set_object_terms( $post->ID, 'photo', 'post_tag', true );


		$taxonomies = array ( 'post_tag', 'category' );
		$skip = array ('Photo'); // Photo category is skipped, the tag will be used; that is lowercase photo

		foreach ( $taxonomies as $taxonomy ) {
			$t = wp_get_post_terms( $post->ID, $taxonomy );
			if ( $t && is_array ( $t ) ) {
				foreach( $t as $tax ) {
					if ( ! in_array ( $tax->name, $skip ) ) {
						$r[ $tax->name ] = get_term_link( $tax->term_id, $taxonomy );
					}
				}
			}
		}

		return $r;
	}

	/**
	 *
	 */
	public static function post_thumbnail ( &$post = null ) {
		$post = static::fix_post($post);

		if ($post === false)
			return false;

		$r = false;
		$thid = get_post_thumbnail_id( $post->ID );
		if ( $thid ) {
			$thumbnail = wp_get_attachment_image_src($thid,'thumbnail');
			if ( isset($thumbnail[1]) && $thumbnail[3] != false )
				$r = site_url($thumbnail[0]);
		}

		return $r;
	}

	/**
	 *
	 */
	public static function template_vars ( &$post = null ) {
		$r = array();
		$post = static::fix_post($post);

		if ($post === false)
			return $r;

		$r = array (
			'id' => $post->ID,
			'url' => get_permalink( $post->ID ),
			'title' => trim(get_the_title( $post->ID )),
			'shorturl' => wp_get_shortlink( $post->ID ),
			'thumbnail' => static::post_thumbnail ($post),
			'content' => static::get_the_content($post, 'clean'),
			'excerpt' => static::get_the_excerpt($post),
			'published' => strtotime( $post->post_date_gmt ),
			'modified' => strtotime( $post->post_modified_gmt ),
			'tags' => static::post_get_tags_array($post),
			'format' => static::post_format( $post ),
			//'show_author' => $show_author,
			//'singular' => is_singular(),
			'uuid' => hash ( 'md5', (int)$post->ID + (int) get_post_time('U', true, $post->ID ) ),
			'author' => pmlnr_author::template_vars( $post->post_author ),
			'exif' => pmlnr_image::twig_exif( $post->ID ),
			'syndications' => explode( "\n", get_post_meta( $post->ID, 'syndication_urls', true ) ),
		);

		$reactions = array();
		$reaction = pmlnr_base::has_reaction( $post->post_content );

		if ( ! empty( $reaction[0] ) ) {
			foreach ( $reaction[0] as $cntr => $replace ) {
				$url = trim($reaction[2][$cntr]);
				if ( empty( $url ) )
					continue;

				$react = [
					'url' => $url,
					'type' => trim( $reaction[1][$cntr] ),
				];

				if ( ! empty( trim( $reaction[3][$cntr] ) ) )
					$react['rsvp'] = trim( $reaction[3][$cntr] );

				array_push( $reactions, $react );
			}
		}

		if ( ! empty( $reactions ) )
			$r['reactions'] = $reactions;

		return $r;
	}
}
