<?php

class pmlnr_base {
	const expire = 10;

	public function __construct () {
	}

	/**
	 *
	 */
	public static function has_reaction ( &$content ) {
		$pattern = "/---[\n\r]+(?:(.*?):\s+)?+\b((?:http|https)\:\/\/?[a-zA-Z0-9\.\/\?\:@\-_=#]+\.[a-zA-Z0-9\.\/\?\:@\-_=#&]*)(?:[\n\r]+((?!---).*))?[\n\r]+---/mi";

		$matches = array();
		preg_match_all( $pattern, $content, $matches);

		if ( ! empty( $matches ) && isset( $matches[0] ) && ! empty( $matches[0] ) )
			return $matches;

		return false;

	}

	/**
	 *
	 */
	public static function extract_reaction ( &$content, $parsedown = false ) {

		$matches = static::has_reaction( $content );
		if ( false == $matches )
			return false;

		$replace = false;
		$r = false;
		$type = false;
		$rsvp = '';

		$rsvps = array (
			'no' => __("Sorry, can't make it."),
			'yes' => __("I'll be there."),
			'maybe' => __("I'll do my best, but don't count on me for sure."),
		);

		$replace = $matches[0][0];
		$type = trim($matches[1][0]);
		$url = trim($matches[2][0]);
		$data = trim($matches[3][0]);

		if ( $type == 're' && !empty( $data ) )
			$rsvp = '<data class="p-rsvp" value="' . $rsvp .'">'. $rsvps[ $rsvp ] .'</data>';

		switch ($type) {
			case 'like':
			case 'fav':
				$cl = 'u-like-of';
				$prefix = '';
				break;
			case 'from':
				$cl = 'u-repost-of';
				$prefix = '*reposted from:* ';
				break;
			case 're':
				$cl = 'u-in-reply-to';
				$prefix = '**RE:** ';
				break;
			default:
				$cl = 'u-url';
				$prefix = '**URL:** ';
				break;
		}

		$title = str_replace ( parse_url( $url, PHP_URL_SCHEME) .'://', '', $url);
		$r = "\n{$prefix}[{$title}]({$url}){.{$cl}}\n{$rsvp}";

		if ($parsedown)
			$r = pmlnr_markdown::parsedown($r);

		return $r;
	}

	/**
	 *
	 */
	public static function remove_reaction ( &$content ) {

		$matches = static::has_reaction( $content );
		if ( false == $matches )
			return $content;

		return str_replace ( $matches[0][0], '', $content );
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
	public static function preg_value ( $string, $pattern, $index = 1 ) {
		preg_match( $pattern, $string, $results );
		if ( isset ( $results[ $index ] ) && !empty ( $results [ $index ] ) )
			return $results [ $index ];
		else
			return false;
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
	public static function extract_wp_images( &$text ) {
		$matches = array();
		preg_match_all("/<img.*wp-image-(\d*)[^\>]*>/", $text, $matches);

		return $matches;
	}

	/**
	 *
	 */
	public static function extract_md_images( &$text ) {
		$matches = array();
		preg_match_all('/\!\[(.*?)\]\((.*?) ?"?(.*?)"?\)\{(.*?)\}/', $text, $matches);

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

		//if ( $post->post_status == 'publish' ) {
			$current = static::get_type($post);
			//if ($current != false ) {
				//return $current;
			//}
		//}

		$post_length = strlen( $post->post_content );
		$content = $post->post_content;

		$matches = static::has_reaction( $content );

		if ( ! empty( $matches ) && is_array( $matches ) && isset( $matches[0] ) && ! empty( $matches[0] ) ) {
			$replace = $matches[0][0];
			$type = trim($matches[1][0]);
			$webmention_url = trim($matches[2][0]);
			$webmention_rsvp = trim($matches[3][0]);

			switch ($type) {
				case 'like':
					$webmention_type = 'u-like-of';
					break;
				case 'from':
					$webmention_type= 'u-repost-of';
					break;
				case 're':
					$webmention_type = 'u-in-reply-to';
					break;
			}

			$content = trim ( str_replace( $replace, '', $content ) );
		}
		else {
			$webmention_url = get_post_meta( $post->ID, 'webmention_url', true );
			$webmention_type = get_post_meta( $post->ID, 'webmention_type', true );
			$webmention_rsvp = get_post_meta( $post->ID, 'webmention_rsvp', true );
			$single_url = $webmention_url;
			$links = static::extract_urls($post->post_content);

			// one single link in the post, so it's most probably a bookmark
			if (!empty($links) && count($links) == 1) {
				$single_url = $links[0];
				$content = str_replace($single_url, '', $content);
				$content = trim($content);
			}
		}

		// /m for multiline, so ^ means beginning of line
		$has_quote = preg_match("/^> /m", $post->post_content);

		// /m for multiline, so ^ means beginning of line
		$has_code = preg_match("/^```(?:[a-z]+)?/m", $post->post_content);

		$diff = 0;
		$has_thumbnail = get_post_thumbnail_id( $post->ID );
		if ( $has_thumbnail ) {
			$thumbnail_meta = static::get_extended_thumbnail_meta( $has_thumbnail );
			if (isset($thumbnail_meta['image_meta']['caption']) && !empty($thumbnail_meta['image_meta']['caption'])) {
				similar_text( $post->post_content, $thumbnail_meta['image_meta']['caption'], $diff);
			}
		}

		$has_youtube = preg_match("/(?:www\.)?youtube\.com\/watch\?v=[a-zA-Z0-9_-]+/", $post->post_content);

		$has_audio = preg_match("/\[audio.*\]/", $post->post_content);

		//$has_cblips = has_category( 'blips', $post );
		//$has_tblips = has_tag( 'blips', $post );

		//$has_blips = ( $has_cblips != false || $has_tblips != false ) ? true : false;


		$taxonomy = 'post_tag';
		$has_ltc = has_term( 'linux-tech-coding', $taxonomy, $post );
		$has_j = has_term( 'journal', $taxonomy, $post );
		$has_p = has_term( 'photo', $taxonomy, $post );
		$has_longcat = ( $has_ltc != false || $has_j != false ) ? true : false;

		$slug = 'article';
		//$name = __('Article', 'petermolnareu');

		/**
		 * Actual discovery
		 */
		if ( $has_longcat ) {
			$slug = 'article';
			//$name = __('Article', 'petermolnareu');
		}
		elseif ( !empty($webmention_url) && !empty($webmention_type) && $webmention_type == 'u-in-reply-to' && !empty($webmention_rsvp) ) {
			$slug = 'rsvp';
			//$name =  __('Response to event','petermolnareu');
		}
		elseif ( (!empty($webmention_url) && !empty($webmention_type) && $webmention_type == 'u-in-reply-to') ) {
			$slug = 'reply';
			//$name = __('Reply','petermolnareu');
		}
		elseif ( $has_code ) {
			$slug = 'article';
			//$name = __('Article', 'petermolnareu');
		}
		elseif ( !empty($webmention_type) && ($webmention_type == 'u-like-of') ) {
			$slug = 'bookmark';
			//$name = __('Favourite','petermolnareu');
		}
		elseif ( !empty($webmention_type) && ($webmention_type == 'u-repost-of') ) {
			$slug = 'repost';
			//$name = __('Repost','petermolnareu');
		}
		elseif ( ( $has_thumbnail && static::is_photo($has_thumbnail) ) || $has_p ) {
			$slug = 'photo';
			//$name =  __('Photo','petermolnareu');
			/* && $diff > 50 */
		}
		elseif ( $post_length < ARTICLE_MIN_LENGTH && $has_thumbnail ) {
			$slug = 'image';
			//$name = __('Image','petermolnareu');
		}
		elseif ( $post_length < ARTICLE_MIN_LENGTH && $has_youtube ) {
			$slug = 'video';
			//$name = __('Video','petermolnareu');
		}
		elseif ( $post_length < ARTICLE_MIN_LENGTH && $has_audio ) {
			$slug = 'audio';
			//$name = __('Audio','petermolnareu');
		}
		elseif ( !empty($webmention_url) ) {
			$slug = 'bookmark';
			//$name = __('Bookmark','petermolnareu');
		}
		elseif ( $post_length < ARTICLE_MIN_LENGTH && $has_quote ) {
			$slug = 'quote';
			//$name = __('Quote','petermolnareu');
		}
		elseif ( strlen($post->post_title) == 0 || ($post_length < ARTICLE_MIN_LENGTH) ) {
			$slug = 'note';
			//$name = __('Note','petermolnareu');
		}

		$id = term_exists( $slug, 'category');
		if ($id !== 0 && $id !== null) {
			//$current = static::get_type( $post );
			//static::debug(sprintf('post type refresh for %s: kind is "%s", automatic says "%s"', $post->ID,$current, $slug));
			if ($current != $slug ) {
				static::debug(sprintf('post type refresh for %s: category is "%s", automatic says "%s"', $post->ID,$current, $slug));
				wp_set_post_terms( $post->ID, $id, 'category', false );
			}
		}

		/*
		if ( $slug == 'note' && strlen($post->post_title) != 0) {
			static::post_replace_title($post);
		}
		*/

		/*
		if ( strlen($post->post_title) == 0 ) {
			$current_slug = $post->post_name;
			$epoch = get_the_time('U', $post->ID);
			$url = static::epoch2url($epoch);
			$fucked = strtolower($url);

			if ( $current_slug == $fucked ) {
				static::livedebug('phase 2');
				$reset = $epoch;

					$_post = array(
						'ID' => $post->ID,
						'post_name' => $reset,
					);

				$r = wp_update_post( $_post );
			}
		}*/

		wp_cache_set ( $post->ID, $slug, __CLASS__ . __FUNCTION__, static::expire );
		return $slug;
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
				$meta['image_meta']['geo_latitude'] = get_post_meta( $parent->ID, 'geo_latitude', true );
				$meta['image_meta']['geo_longitude'] = get_post_meta( $parent->ID, 'geo_longitude', true );
			}

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


			/*
			if ( static::is_photo ($thid ) ) {
				if ( !isset ( $meta['image_meta']['lens'] ) || strstr ( $meta['image_meta']['lens'], 'Unknown') ) {
					require_once(ABSPATH . "wp-admin" . '/includes/image.php');
					$m = wp_read_image_metadata( $wp_upload_dir['basedir'] . DIRECTORY_SEPARATOR . $meta['file'] );
					static::debug ( " #{$thid} got exif as: " . json_encode($m) );
					$_meta['image_meta'] = $m;
					update_post_meta ( $thid, '_wp_attachment_metadata', $_meta );
				}
			}
			*/
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

		$r = yaml_parse_file ( __DIR__ . '/../data.yaml' );

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

}
