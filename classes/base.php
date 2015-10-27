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

		error_log ( __FILE__ . '::' . __CLASS__ . '::' . __FUNCTION__ . ' => ' . $message);
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

		$thid = get_post_thumbnail_id( $post->ID );
		if ( ! $thid )
			return false;

		$post_length = strlen( $post->post_content );
		$is_photo = self::is_photo($thid);

		if ( $post_length > ARTICLE_MIN_LENGTH )
			return false;

		if ( $is_photo || $post_length < ARTICLE_MIN_LENGTH )
			return true;

		return false;
	}

	/**
	 *
	 */
	public static function is_imported( &$post ) {
		$post = static::fix_post($post);

		if ($post === false )
			return false;

		$return = false;
		$raw_import_data = get_post_meta ('raw_import_data', $post->ID, true);
		if (!empty($raw_import_data)) {
			$return = true;

			$raw_import_data = json_decode($raw_import_data);

			if (isset($raw_import_data['source']) && !empty($raw_import_data['source'])) {
				if (stristr($raw_import_data['source'], 'twitter'))
					$return = 'twitter';
			}
		}

		return $return;
	}

	/**
	 *
	 */
	public static function is_twitter_reply( &$post ) {
		$post = static::fix_post($post);

		if ($post === false )
			return false;

		$return = false;

		if ( 'twitter' == static::is_imported($post)) {
			$twitter_in_reply_to_screen_name = get_post_meta ('twitter_in_reply_to_screen_name', $post->ID, true);
			if (!empty($twitter_in_reply_to_screen_name))
				$return = true;
		}
		else {
			$twitter_reply_id = get_post_meta ('twitter_reply_id', $post->ID, true);
			if (!empty($twitter_reply_id))
				$return = true;
		}

		return $return;
	}

	/**
	 * my own format manager because the built-in sucks
	 */
	public static function get_type ( &$post ) {
		$post = static::fix_post($post);

		if ($post === false )
			return false;

		$return = 'article';
		$kind = wp_get_post_terms( $post->ID, 'kind', array( 'fields' => 'all' ) );

		if (is_wp_error($kind))
			return false;

		if(is_array($kind))
			$kind = array_pop( $kind );

		if (is_object($kind) && isset($kind->slug))
			$return = $kind->slug;

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
	public static function url2epoch( $num, $b=62) {
		/* this is the potential 1 I chopped off */
		if ( !is_numeric($num[0]) || $num[0] != '1' )
			$num = '1' . $num;

		$base='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$limit = strlen($num);
		$res=strpos($base,$num[0]);
		for($i=1;$i<$limit;$i++) {
			$res = $b * $res + strpos($base,$num[$i]);
		}
		$res = '1' . $res;
		return $res;
	}

	/**
	 * convert UNIX EPOCH to short string
	 *
	* thanks to https://stackoverflow.com/questions/4964197/converting-a-number-base-10-to-base-62-a-za-z0-9
	*/
	public static function epoch2url($num, $b=62) {
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

	/**
	 *
	 */
	public static function extract_urls( &$text ) {
		$matches = array();
		preg_match_all("/\b(?:http|https)\:\/\/?[a-zA-Z0-9\.\/\?\:@\-_=#]+\.[a-zA-Z0-9\.\/\?\:@\-_=#]*/i", $text, $matches);

		return $matches;
	}

	public static function is_url_external ( &$url ) {
		if (!stristr($url, 'http://') || !stristr($url, 'https://'))
			return false;

		$domain = parse_url(get_bloginfo('url'), PHP_URL_HOST);
		if (stristr($url, $domain))
			return false;

		return true;
	}
}
