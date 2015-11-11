<?php

class pmlnr_base {
	const expire = 300;

	public function __construct () {
	}

	/**
	 *
	 */
	public static function error( $message) {
		if (is_object($message) || is_array($message))
			$message = json_encode($message);

		error_log ( 'PMLNR DEBUG => ' . $message );
	}

	/**
	 *
	 */
	public static function debug( $message) {
		if (is_object($message) || is_array($message))
			$message = json_encode($message);

		if ( defined('WP_DEBUG') && WP_DEBUG == true )
			error_log ( 'PMLNR DEBUG => ' . $message);
	}

	/**
	 *
	 */
	public static function livedebug( $message) {
		if ( function_exists('is_user_logged_in') && is_user_logged_in() )
			print_r ($message);
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
	public static function icon4url ( &$url ) {
		return 'icon-' . strtolower(substr(parse_url($url, PHP_URL_HOST), 0 , (strrpos(parse_url($url, PHP_URL_HOST), "."))));
	}

	/**
	 *
	 */
	public static function is_localhost() {
		if ( $_SERVER['SERVER_ADDR'] == $_SERVER['REMOTE_ADDR'] || $_SERVER['REMOTE_ADDR'] == '127.0.0.1' )
			return true;

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
	 *
	 */
	public static function is_amp () {
		global $wp_query;

		if ( is_object($wp_query) && isset( $wp_query->query_vars[ 'amp' ]) )
			return true;

		return false;
	}

	/**
	 * detect if the post is a photo made by me
	 */
	public static function is_photo (&$thid) {
		if ( empty($thid))
			return false;

		if ( $cached = wp_cache_get ( $thid, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$return = false;

		$rawmeta = wp_get_attachment_metadata( $thid );

		if ( isset( $rawmeta['image_meta'] ) && !empty($rawmeta['image_meta'])) {

			if (isset($rawmeta['image_meta']['copyright']) && !empty($rawmeta['image_meta']['copyright']) && ( stristr($rawmeta['image_meta']['copyright'], 'Peter Molnar') || stristr($rawmeta['image_meta']['copyright'], 'petermolnar.eu'))) {
				$return = true;
			}

			$my_devs = array ( 'PENTAX K-5 II s', 'NIKON D80' );
			if ( isset($rawmeta['image_meta']['camera']) && !empty($rawmeta['image_meta']['camera']) && in_array(trim($rawmeta['image_meta']['camera']), $my_devs)) {
				$return = true;
			}
		}

		wp_cache_set ( $thid, $return, __CLASS__ . __FUNCTION__, self::expire );

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

		//$thid = get_post_thumbnail_id( $post->ID );
		//if ( ! $thid )
			//return false;

		//$post_length = strlen( $post->post_content );
		//$is_photo = self::is_photo($thid);

		//if ( $post_length > ARTICLE_MIN_LENGTH )
			//return false;

		//if ( $is_photo || $post_length < ARTICLE_MIN_LENGTH )
			//return true;

		return true;
	}

	/**
	 *
	 */
	public static function is_imported( &$post ) {
		$post = static::fix_post($post);

		if ($post === false )
			return false;

		if ( $cached = wp_cache_get ( $post->ID, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$return = false;
		$raw_import_data = get_post_meta ($post->ID, 'raw_import_data', true);
		if (!empty($raw_import_data)) {
			$return = true;

			$raw_import_data = json_decode($raw_import_data);

			if (isset($raw_import_data['source']) && !empty($raw_import_data['source'])) {
				if (stristr($raw_import_data['source'], 'twitter'))
					$return = 'twitter';
			}
		}

		wp_cache_set ( $post->ID, $return, __CLASS__ . __FUNCTION__, self::expire );

		return $return;
	}

	/**
	 *
	 */
	public static function is_twitter_reply( &$post ) {
		$post = static::fix_post($post);

		if ($post === false )
			return false;

		if ( $cached = wp_cache_get ( $post->ID, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$r = false;

		$twitter_in_reply_to_screen_name = get_post_meta ( $post->ID, 'twitter_in_reply_to_screen_name', true);
		if (!empty($twitter_in_reply_to_screen_name)) {
				$r = true;
		}

		$twitter_reply_id = get_post_meta ($post->ID, 'twitter_reply_id', true);
		if (!empty($twitter_reply_id)) {
			$r = true;
		}

		wp_cache_set ( $post->ID, $r, __CLASS__ . __FUNCTION__, self::expire );
		return $r;
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

		$return = 'article';
		$kind = wp_get_post_terms( $post->ID, 'kind', array( 'fields' => 'all' ) );

		if (is_wp_error($kind))
			return false;

		if(is_array($kind))
			$kind = array_pop( $kind );

		if (is_object($kind) && isset($kind->slug))
			$return = $kind->slug;

		wp_cache_set ( $post->ID, $return, __CLASS__ . __FUNCTION__, self::expire );

		return $return;
	}


	/**
	 *
	 */
	public static function get_the_content( &$_post = null ){
		global $post;
		$prevpost = $post;

		$post = static::fix_post($_post);

		if ($post === false ) {
			$post = $prevpost;
			return false;
		}

		if ( $cached = wp_cache_get ( $post->ID, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		setup_postdata( $post );
		ob_start();
		the_content();
		$r = ob_get_clean();
		wp_reset_postdata( $post );
		$post = $prevpost;

		wp_cache_set ( $post->ID, $r, __CLASS__ . __FUNCTION__, self::expire );

		return $r;
	}

	/**
	 *
	 */
	public static function get_the_excerpt( &$_post = null ){
		global $post;
		$prevpost = $post;

		$post = static::fix_post($_post);

		if ($post === false ) {
			$post = $prevpost;
			return false;
		}

		if ( $cached = wp_cache_get ( $post->ID, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		setup_postdata( $post );
		ob_start();
		the_excerpt();
		$r = ob_get_clean();
		wp_reset_postdata( $post );
		$post = $prevpost;

		wp_cache_set ( $post->ID, $r, __CLASS__ . __FUNCTION__, self::expire );

		return $r;
	}

	/**
	 * decode short string and covert it back to UNIX EPOCH
	 *
	 */
	public static function url2epoch( $num, $b=36) {
		/* this is the potential 1 I chopped off */
		if ( !is_numeric($num[0]) || $num[0] != '1' )
			$num = '1' . $num;

		if ($b == 36)
			$base='0123456789abcdefghijklmnopqrstuvwxyz';
		else
			$base='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

		$limit = strlen($num);
		$res=strpos($base,$num[0]);
		for($i=1;$i<$limit;$i++) {
			$res = $b * $res + strpos($base,$num[$i]);
		}

		return $res;
	}

	/**
	 * convert UNIX EPOCH to short string
	 *
	* thanks to https://stackoverflow.com/questions/4964197/converting-a-number-base-10-to-base-62-a-za-z0-9
	*/
	public static function epoch2url($num, $b=36) {
		if ($b == 36)
			$base='0123456789abcdefghijklmnopqrstuvwxyz';
		else
			$base='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

		$r = $num  % $b ;
		$res = $base[$r];
		$q = floor($num/$b);
		while ($q) {
			$r = $q % $b;
			$q =floor($q/$b);
			$res = $base[$r].$res;
		}
		/* most of the posts I'll make in my life will start with 1
		 * so we can save a char by popping it off and re-adding them in
		 * the decode function
		 */
		$res = ltrim($res,'1');
		return $res;
	}

	///**
	 //* decode short string and covert it back to UNIX EPOCH
	 //*
	 //*/
	//public static function url2epoch( $num, $b=62) {
		//// this is the potential 1 I chopped off
		//if ( !is_numeric($num[0]) || $num[0] != '1' )
			//$num = '1' . $num;

		//$base='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		//$limit = strlen($num);
		//$res=strpos($base,$num[0]);
		//for($i=1;$i<$limit;$i++) {
			//$res = $b * $res + strpos($base,$num[$i]);
		//}

		//return $res;
	//}

	///**
	 //* convert UNIX EPOCH to short string
	 //*
	//* thanks to https://stackoverflow.com/questions/4964197/converting-a-number-base-10-to-base-62-a-za-z0-9
	//*/
	//public static function epoch2url($num, $b=62) {
		//$base='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		//$r = $num  % $b ;
		//$res = $base[$r];
		//$q = floor($num/$b);
		//while ($q) {
			//$r = $q % $b;
			//$q =floor($q/$b);
			//$res = $base[$r].$res;
		//}
		///* most of the posts I'll make in my life will start with 1
		 //* so we can save a char by popping it off and re-adding them in
		 //* the decode function
		 //*/
		//$res = ltrim($res,'1');
		//return $res;
	//}

	/**
	 *
	 */
	public static function extract_urls( &$text ) {
		$matches = array();
		preg_match_all("/\b(?:http|https)\:\/\/?[a-zA-Z0-9\.\/\?\:@\-_=#]+\.[a-zA-Z0-9\.\/\?\:@\-_=#]*/i", $text, $matches);

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
	public static function is_url_external ( &$url ) {
		if (!stristr($url, 'http://') || !stristr($url, 'https://'))
			return false;

		$domain = parse_url(get_bloginfo('url'), PHP_URL_HOST);
		if (stristr($url, $domain))
			return false;

		return true;
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

		//if (!defined('PMLNR_UPDATE_TYPES')) {
			//$type = static::get_type($post);
			//if ($type != false )
				//return $type;
		//}

		$slug = 'article';
		$name = __('Article', 'petermolnareu');

		$post_length = strlen( $post->post_content );

		$webmention_url = get_post_meta( $post->ID, 'webmention_url', true );
		$webmention_type = get_post_meta( $post->ID, 'webmention_type', true );
		$webmention_rsvp = get_post_meta( $post->ID, 'webmention_rsvp', true );

		$links = static::extract_urls($post->post_content);
		$content = $post->post_content;
		// one single link in the post, so it's most probably a bookmark
		if (!empty($links) && count($links) == 1) {
			$webmention_url = $links[0];
			$content = str_replace($webmention_url, '', $content);
			$content = trim($content);
		}

		$is_twitter_reply = static::is_twitter_reply($post);

		// /m for multiline, so ^ means beginning of line
		$has_quote = preg_match("/^> /m", $post->post_content);

		// /m for multiline, so ^ means beginning of line
		$has_code = preg_match("/^```(?:[a-z]+)?/m", $post->post_content);

		$diff = 0;
		$has_thumbnail = get_post_thumbnail_id( $post->ID );
		if ( $has_thumbnail ) {
			$thumbnail_meta = pmlnr_image::get_extended_meta( $has_thumbnail );
			if (isset($thumbnail_meta['image_meta']['caption']) && !empty($thumbnail_meta['image_meta']['caption'])) {
				similar_text( $post->post_content, $thumbnail_meta['image_meta']['caption'], $diff);
			}
		}

		$has_youtube = preg_match("/(?:www\.)?youtube\.com\/watch\?v=[a-zA-Z0-9_-]+/", $post->post_content);

		$has_audio = preg_match("/\[audio.*\]/", $post->post_content);

		$has_cblips = has_category( 'blips', $post );
		$has_tblips = has_tag( 'blips', $post );

		$has_blips = ( $has_cblips != false || $has_tblips != false ) ? true : false;


		/**
		 * Actual discovery
		 */
		if ( !empty($webmention_url) && !empty($webmention_type) && $webmention_type == 'u-in-reply-to' && !empty($webmention_rsvp) ) {
			$slug = 'rsvp';
			$name =  __('Response to event','petermolnareu');
		}
		elseif ( (!empty($webmention_url) && !empty($webmention_type) && $webmention_type == 'u-in-reply-to') || $is_twitter_reply ) {
			$slug = 'reply';
			$name = __('Reply','petermolnareu');
		}
		elseif ( $has_code ) {
			$slug = 'article';
			$name = __('Article', 'petermolnareu');
		}
		/*
		elseif ( !empty($webmention_type) && ($webmention_type == 'u-like-of') ) {
			$slug = 'like';
			$name = __('Like','petermolnareu');
		}
		elseif ( !empty($webmention_type) && ($webmention_type == 'u-repost-of') ) {
			$slug = 'repost';
			$name = __('Repost','petermolnareu');
		}
		*/
		elseif ( $has_thumbnail && static::is_photo($has_thumbnail) && $diff > 90 ) {
			$slug = 'photo';
			$name =  __('Photo','petermolnareu');
		}
		elseif ( $post_length < ARTICLE_MIN_LENGTH && $has_thumbnail ) {
			$slug = 'image';
			$name = __('Image','petermolnareu');
		}
		elseif ( !empty($webmention_url) && empty($content)) {
			$slug = 'bookmark';
			$name = __('Bookmark','petermolnareu');
		}
		elseif ( $post_length < ARTICLE_MIN_LENGTH && $has_youtube ) {
			$slug = 'video';
			$name = __('Video','petermolnareu');
		}
		elseif ( $post_length < ARTICLE_MIN_LENGTH && $has_audio ) {
			$slug = 'audio';
			$name = __('Audio','petermolnareu');
		}
		elseif ( $post_length < ARTICLE_MIN_LENGTH && $has_quote ) {
			$slug = 'quote';
			$name = __('Quote','petermolnareu');
		}
		elseif ( strlen($post->post_title) == 0 || ($post_length < ARTICLE_MIN_LENGTH && $has_blips) ) {
			$slug = 'note';
			$name = __('Note','petermolnareu');
		}

		if ($id = term_exists( $slug, 'kind')) {
			$current = static::get_type( $post );
			//static::debug(sprintf('post type refresh for %s: kind is "%s", automatic says "%s"', $post->ID,$current, $slug));
			if ($current != $slug ) {
				static::debug(sprintf('post type refresh for %s: kind is "%s", automatic says "%s"', $post->ID,$current, $slug));
				wp_set_post_terms( $post->ID, $id, 'kind', false );
			}
		}

		/*
		if ( $slug == 'note' && strlen($post->post_title) != 0) {
			static::post_replace_title($post);
		}

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

		wp_cache_set ( $post->ID, $slug, __CLASS__ . __FUNCTION__, self::expire );
		return $slug;
	}

}
