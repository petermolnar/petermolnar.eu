<?php

class pmlnr_base {
	const expire = 10;

	public function __construct () {
	}

	/**
	 *
	 */
	public static function add_tags ( &$post, &$keywords, $taxonomy = 'post_tag' ) {

		$keywords = array_unique($keywords);
		foreach ( $keywords as $tag ) {
			$tag = trim( $tag );

			if ( empty( $tag ) )
				continue;

			if ( !term_exists( $tag, $taxonomy ))
				wp_insert_term ( $tag, $taxonomy );

			if ( !has_term( $tag, $taxonomy, $post ) ) {
				pmlnr_base::debug ( "appending post #{$post->ID} {$taxonomy} taxonomy with: {$tag}", 5 );
				wp_set_post_terms( $post->ID, $tag, $taxonomy, true );
			}

		}

	}

	/**
	 *
	 * debug messages; will only work if WP_DEBUG is on
	 * or if the level is LOG_ERR, but that will kill the process
	 *
	 * @param string $message
	 * @param int $level
	 *
	 * @output log to syslog | wp_die on high level
	 * @return false on not taking action, true on log sent
	 */
	public static function debug( $message, $level = LOG_NOTICE ) {
		if ( empty( $message ) )
			return false;

		if ( @is_array( $message ) || @is_object ( $message ) )
			$message = json_encode($message);

		$levels = array (
			LOG_EMERG => 0, // system is unusable
			LOG_ALERT => 1, // Alert 	action must be taken immediately
			LOG_CRIT => 2, // Critical 	critical conditions
			LOG_ERR => 3, // Error 	error conditions
			LOG_WARNING => 4, // Warning 	warning conditions
			LOG_NOTICE => 5, // Notice 	normal but significant condition
			LOG_INFO => 6, // Informational 	informational messages
			LOG_DEBUG => 7, // Debug 	debug-level messages
		);

		// number for number based comparison
		// should work with the defines only, this is just a make-it-sure step
		$level_ = $levels [ $level ];

		// in case WordPress debug log has a minimum level
		if ( defined ( 'WP_DEBUG_LEVEL' ) ) {
			$wp_level = $levels [ WP_DEBUG_LEVEL ];
			if ( $level_ > $wp_level ) {
				return false;
			}
		}

		// ERR, CRIT, ALERT and EMERG
		if ( 3 >= $level_ ) {
			wp_die( '<h1>Error:</h1>' . '<p>' . $message . '</p>' );
			exit;
		}

		$trace = debug_backtrace();
		$caller = $trace[1];
		$parent = $caller['function'];

		if (isset($caller['class']))
			$parent = $caller['class'] . '::' . $parent;

		return error_log( "{$parent}: {$message}" );
	}

	/**
	 *
	 */
	public static function livedebug( $message) {
		if ( function_exists('is_user_logged_in') && is_user_logged_in() )
			var_dump ($message);
	}

	/**
	 *
	 */
	public static function fix_url ( $url, $absolute = true ) {
		// move to generic scheme
		$url = str_replace ( array('http://', 'https://'), 'https://', $url );

		$domain = parse_url(get_bloginfo('url'), PHP_URL_HOST);
		// relative to absolute
		if ($absolute && !stristr($url, $domain)) {
			$url = 'https://' . $domain . '/' . ltrim($url, '/');
		}

		return $url;
	}

	/**
	 * do everything we can to find the currently active post
	 */
	public static function fix_post ( &$post = null ) {
		if ($post === null || !static::is_post($post))
			global $post;

		if (static::is_post($post))
			return $post;

		return false;
	}

	/**
	 *
	 */
	public static function is_post ( &$post ) {
		if ( !empty($post) && is_object($post) && isset($post->ID) && !empty($post->ID) )
			return true;

		return false;
	}

	/**
	 * validates a comment object if it really is a comment
	 *
	 * @param $post object Wordpress comment Object to check
	 *
	 * @return bool true if it's a post, false if not
	 */
	public static function is_comment ( &$comment ) {
		if ( !empty($comment) && is_object($comment) && isset($comment->comment_ID) && !empty($comment->comment_ID) )
			return true;

		return false;
	}

	/**
	 * detect if the post is a photo made by me
	 */
	public static function is_photo (&$thid) {
		if ( empty($thid))
			return false;

		if (!is_string($thid) && !is_numeric($thid))
			return false;

		if ( $cached = wp_cache_get ( $thid, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$return = false;

		$rawmeta = wp_get_attachment_metadata( $thid );

		$yaml = static::get_yaml();

		if ( isset( $rawmeta['image_meta'] ) && !empty($rawmeta['image_meta'])) {

			if (isset($rawmeta['image_meta']['copyright']) && !empty($rawmeta['image_meta']['copyright']) ) {
				foreach ( $yaml['copyright'] as $str ) {
					if ( stristr($rawmeta['image_meta']['copyright'], $str) ) {
						return true;
					}
				}
			}

			if ( isset($rawmeta['image_meta']['camera']) && !empty($rawmeta['image_meta']['camera']) && in_array(trim($rawmeta['image_meta']['camera']), $yaml['cameras'])) {
				$return = true;
			}
		}

		wp_cache_set ( $thid, $return, __CLASS__ . __FUNCTION__, static::expire );

		return $return;
	}

	/**
	 * detect if the post is either a photo or a short post with a featured image
	 */
	public static function is_u_photo ( &$post ) {
		$post = static::fix_post($post);

		if ($post === false )
			return false;

		$format = static::post_format($post);

		if ( in_array($format, array('article')))
			return false;

		return true;
	}

	/**
	 * my own format manager because the built-in sucks
	 */
	public static function get_type ( &$post ) {
		$post = static::fix_post($post);

		if ($post === false )
			return false;

		if ( $cached = wp_cache_get ( $post->ID, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$return = false;

		$kind = wp_get_post_terms( $post->ID, 'category', array( 'fields' => 'all' ) );

		if (is_wp_error($kind))
			return false;

		if(is_array($kind) && count($kind) > 1 )
			return false;

		if(is_array($kind))
			$kind = array_pop( $kind );

		if (is_object($kind) && isset($kind->slug))
			$return = $kind->slug;

		wp_cache_set ( $post->ID, $return, __CLASS__ . __FUNCTION__, static::expire );

		return $return;
	}

	/**
	 *
	 */
	public static function extract_urls( &$text ) {
		$matches = array();
		preg_match_all("/\b(?:http|https)\:\/\/?[a-zA-Z0-9\.\/\?\:@\-_=#]+\.[a-zA-Z0-9\.\/\?\:@\-_=#&]*/i", $text, $matches);

		$matches = $matches[0];
		return $matches;
	}

	/**
	 *
	 */
	public static function post_format ( &$post ) {
		$post = static::fix_post($post);

		if ($post === false )
			return false;

		if ( $cached = wp_cache_get ( $post->ID, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$current = static::get_type( $post );

		$post_length = strlen( $post->post_content );
		$content = $post->post_content;

		$reaction = static::has_reaction( $post->post_content );
		$webmention_url = trim( $reaction[2][0] );
		$webmention_type = trim( $reaction[1][0] );
		$webmention_rsvp = trim( $reaction[3][0] );

		$links = static::extract_urls( $post->post_content );

		// one single link in the post, so it's most probably an old bookmark
		if (!empty($links) && count($links) == 1) {
			$content = trim ( str_replace($links[0], '', $content) );

			if ( empty( $content ) ) {
				//if ( empty ( $webmention_url ) )
					//add_post_meta( $post->ID, 'webmention_url', $links[0], true );

				//if ( empty ( $webmention_type ) )
					//add_post_meta( $post->ID, 'webmention_type', 'fav', true );

				$webmention_url = $links[0];
				$webmention_type = 'fav';
			}

		}

		$has_thumbnail = get_post_thumbnail_id( $post->ID );
		$has_quote = preg_match("/^> /m", $post->post_content);
		$has_code = preg_match("/^```(?:[a-z]+)?/m", $post->post_content);
		$has_youtube = preg_match("/(?:www\.)?youtube\.com\/watch\?v=[a-zA-Z0-9_-]+/", $post->post_content);
		$has_audio = preg_match("/\[audio.*\]/", $post->post_content);
		$has_webmention = empty( $webmention_url ) ? false : true;

		$taxonomy = 'post_tag';
		$tag_it = has_term( 'it', $taxonomy, $post );
		$tag_journal = has_term( 'journal', $taxonomy, $post );
		$tag_photo = has_term( 'photo', $taxonomy, $post );
		$is_long = ( $tag_it != false || $tag_journal != false ) ? true : false;

		$type = 'article';

		// --- Actual discovery ---
		if ( $is_long ) {
			$type = 'article';
		}
		elseif ( ! empty( $webmention_type ) ) {
			switch ( $webmention_type ) {
				case 'reply':
				case 're':
				case 'u-in-reply-to':
					$type = 'reply';
					break;
				case 'from':
				case 'repost':
				case 'u-repost-of':
					$type = 'repost';
					break;
				default:
					$type = 'bookmark';
					break;
			}
		}
		elseif ( $has_code ) {
			$type = 'article';
		}
		elseif ( ( $has_thumbnail && static::is_photo( $has_thumbnail ) ) || $tag_photo ) {
			$type = 'photo';
		}
		elseif ( $post_length < ARTICLE_MIN_LENGTH ) {
			$type = 'note';

			if ( $has_thumbnail ) {
				$type = 'image';
			}
			elseif ( $has_youtube ) {
				$type = 'video';
			}
			elseif ( $has_audio ) {
				$type = 'audio';
			}
			elseif ( $has_quote ) {
				$type = 'quote';
			}

		}
		elseif ( strlen ($post->post_title ) == 0 ) {
			$type = 'note';
		}

		$id = term_exists( $type, 'category');
		if ($id !== 0 && $id !== null) {
			if ($current != $type ) {
				static::debug(sprintf('post type refresh for %s: category is "%s", automatic says "%s"', $post->ID, $current, $type));
				wp_set_post_terms( $post->ID, $id, 'category', false );
			}
		}

		wp_cache_set ( $post->ID, $type, __CLASS__ . __FUNCTION__, static::expire );
		return $type;
	}

	/**
	 *
	 */
	public static function get_extended_thumbnail_meta ( &$thid ) {
		if ( empty ( $thid ) )
			return false;

		if ( $cached = wp_cache_get ( $thid, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$attachment = get_post( $thid );

		$meta = array();
		if ( static::is_post($attachment)) {
			$meta = $_meta = wp_get_attachment_metadata($thid);
			$wp_upload_dir = wp_upload_dir();

			if ( !empty ( $attachment->post_parent ) ) {
				$parent = get_post( $attachment->post_parent );
				$meta['parent'] = $parent->ID;
			}

			$try = array ( 'geo_latitude', 'geo_longitude', 'geo_altitude' );

			foreach ( $try as $kw )
				if ( empty ( $meta['image_meta'][ $kw ] ) )
					$meta['image_meta'][ $kw ] = get_post_meta( $parent->ID, $kw, true );

			$src = wp_get_attachment_image_src ($thid, 'full');
			$meta['src'] = static::fix_url($src[0]);

			if (isset($meta['sizes']) && !empty($meta['sizes'])) {
				foreach ( $meta['sizes'] as $size => $data ) {
					$src = wp_get_attachment_image_src ($thid, $size);
					$src = static::fix_url($src[0]);
					$meta['sizes'][$size]['src'] = $src;
					$meta['sizes'][$size]['path'] = $wp_upload_dir['basedir'] . DIRECTORY_SEPARATOR . $data['file'];
				}
			}

			if ( empty($meta['image_meta']['title']))
				$meta['image_meta']['title'] = esc_attr($attachment->post_title);

			$slug = sanitize_title ( $meta['image_meta']['title'] , $thid );
			if ( is_numeric( substr( $slug, 0, 1) ) )
				$slug = 'img-' . $slug;
			$meta['image_meta']['slug'] = $slug;

			$meta['image_meta']['alt'] = '';
			$alt = get_post_meta($thid, '_wp_attachment_image_alt', true);
			if ( !empty($alt))
				$meta['image_meta']['alt'] = strip_tags($alt);

		}

		wp_cache_set ( $thid, $meta, __CLASS__ . __FUNCTION__, static::expire );

		return $meta;
	}

	/**
	 * non-git safe data
	 */
	public static function get_yaml () {

		if ( $cached = wp_cache_get ( __FUNCTION__ , __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$r = parse_ini_file ( __DIR__ . '/../data.ini' );

		wp_cache_set ( __FUNCTION__, $r, __CLASS__ . __FUNCTION__, static::expire );


		return $r;
	}

	/**
	 *
	 */
	public static function prefix_array ( &$r, $prefix = '' ) {
		if (!empty($prefix)) {
			foreach ($r as $key => $value ) {
				$r[ $prefix . $key ] = $value;
				unset($r[$key]);
			}
		}

		return $r;

	}

	/**
	 *
	 */
	public static function replace_content ( &$post, &$content ) {

		$post = static::fix_post ( $post );

		if ( false === $post )
			return false;

		//if ( empty ( $content ) )
			//return false;

		global $wpdb;
		$dbname = "{$wpdb->prefix}posts";
		$req = false;

		static::debug("Updating post content for #{$post->ID}", 5);

		$q = $wpdb->prepare( "UPDATE `{$dbname}` SET `post_content`='%s' WHERE `ID`='{$post->ID}'", $content );

		try {
			$req = $wpdb->query( $q );
		}
		catch (Exception $e) {
			pmlnr_base::debug('Something went wrong: ' . $e->getMessage(), 4);
		}
	}

	/**
	 *
	 */
	public static function replace_title ( &$post, &$title ) {

		$post = static::fix_post ( $post );

		if ( false === $post )
			return false;

		if ( empty ( $title ) )
			return false;

		global $wpdb;
		$dbname = "{$wpdb->prefix}posts";
		$req = false;

		static::debug("Updating post title for #{$post->ID}", 5);

		$q = $wpdb->prepare( "UPDATE `{$dbname}` SET `post_title`='%s' WHERE `ID`='{$post->ID}'", $title );

		try {
			$req = $wpdb->query( $q );
		}
		catch (Exception $e) {
			pmlnr_base::debug('Something went wrong: ' . $e->getMessage(), 4);
		}
	}

	/**
	 *
	 */
	public static function has_reaction ( &$content ) {

		$pattern = "/[\*\+]{3}\s+(reply|fav|repost|u-repost-of|u-in-reply-to|u-like-of):?\s+(https?\:\/\/?[a-zA-Z0-9\.\/\?\:@\-_=#]+\.[a-zA-Z0-9\.\/\?\:@\-_=#&]*)(?:\s+(yes|no|maybe))?/i";

		preg_match_all( $pattern, $content, $matches);

		if ( ! empty( $matches ) && isset( $matches[0] ) && ! empty( $matches[0] ) ) {
			foreach ( $matches[1] as $index => $value ) {
				if ( $value == 'u-repost-of' )
					$matches[1][$index] = 'repost';
				elseif ( $value == 'u-in-reply-to' )
					$matches[1][$index] = 'reply';
				elseif ( $value == 'u-like-of' )
					$matches[1][$index] = 'fav';
			}
			return $matches;
		}

		return false;

	}

	/**
	 * extract in-content location information
	 *
	 * Example:
	 * `*** loc 29.557872,103.386825@790.5`
	 *
	 *
	 *
	 */
	public static function extract_location ( &$content ) {
		$pattern = "/^[\*\+]{3}\s+loc:?\s+([0-9\.]+),([0-9\.]+)(@[0-9,\.]+)?$/mi";
		preg_match_all( $pattern, $content, $matches);
		return $matches;
	}


	/**
	 * extract in-content hashtag lines
	 *
	 * Example:
	 * `\#this, #is, #a, #line, #of various hashtags`
	 * `#this, #is, #another line`
	 *
	 *
	 */
	public static function extract_hashtags ( &$content ) {
		$pattern = "/\\\?\#(.*?)(?:,|$)(?![\n\r][=-])/mi";
		preg_match_all( $pattern, $content, $matches);
		return $matches;
	}


}
