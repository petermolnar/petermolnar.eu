<?php

class pmlnr_post extends pmlnr_base {

	public function __construct () {
	}

	public static function twig_c_exif ( $path ) {
		if ( ! defined( 'WP_EXTRAEXIF\CACHEDIR' ) )
			return false;

		$path = realpath( $path );

		$hash = md5 ( $path );
		$cached = WP_EXTRAEXIF\CACHEDIR . $hash;

		if ( ! is_file( $cached ) ) {
			WP_EXTRAEXIF\exif_cache( $path );
		}

		if ( ! is_file( $cached ) ) {
			return false;
		}

		return json_decode( file_get_contents( $cached ), true );
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

	public static function post_reactions ( $post ) {

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

		if ( empty( $reactions) )
			return false;
		else
			return $reactions;
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

		/**/
		$syndications = explode( "\n", get_post_meta( $post->ID, 'syndication_urls', true ) );
		$o = count( $syndications );
		if ( ! empty( $syndications ) ) {
			foreach ( $syndications as $c => $url  ) {
				if ( preg_match( '/flickr.*petermolnareu/', $url )) {
					unset ( $syndications[ $c ] );
				}
				else {
					$syndications[ $c ] = trim( $syndications[ $c ] );
				}
			}
			if ( !empty( $syndications ) && count( $syndications ) != $o ) {
				static::debug ( join( "\n", $syndications ) );
				update_post_meta ( $post->ID, 'syndication_urls', join( "\n", $syndications ) );
			}
		}
		/**/

		$r = array (
			//'id' => $post->ID,
			'title' => trim(get_the_title( $post->ID )),
			'url' => get_permalink( $post->ID ),
			'shorturl' => wp_get_shortlink( $post->ID ),
			'published' => strtotime( $post->post_date_gmt ),
			'tags' => static::post_get_tags_array($post),
			'format' => static::post_format( $post ),
			'author' => pmlnr_author::template_vars( $post->post_author ),
			'syndications' => explode( "\n",
				get_post_meta( $post->ID, 'syndication_urls', true ) ),

			'content' => static::get_the_content($post, 'clean'),

		);

		// updated
		$published = \get_the_time( 'U', $post->ID );
		$modified = \get_the_modified_time( 'U', $post->ID );
		if ( $published != $modified && $modified > $published )
			$r['updated'] = date( 'Y-m-d H:i:s P', $modified );

		// look for embedded images; if there's only one, try to get the exif for it
		preg_match_all( '/\!\[([^\]]+)\]\(([^\)]+)\)(?:\{([^\}]+)\})?/i',
			$post->post_content, $img );
		if ( count( $img[0] ) == 1 ) {
			$wp_upload_dir = wp_upload_dir();
			$path = parse_url( $img[2][0] );
			$fname = pathinfo( $path['path'], PATHINFO_FILENAME ) . '.'
				. pathinfo( $path['path'], PATHINFO_EXTENSION );

			$fpath = $wp_upload_dir['basedir'] . DIRECTORY_SEPARATOR . $fname;
			$texif = static::twig_c_exif( $fpath );
			$r['exif'] = $texif;
		}

		// thumbnail
		if ( has_post_thumbnail ( $post->ID ) ) {
			$r['thumbnail'] = static::post_thumbnail ($post);
			//$r['exif'] = pmlnr_image::twig_exif( $post->ID );
		}

		// excerpt
		if ( ! empty( $post->post_excerpt ) )
			$r['excerpt'] = static::get_the_excerpt($post);

		// reactions
		if ( $reactions = static::post_reactions( $post ) )
			$r['reactions'] = $reactions;

		return $r;
	}
}
