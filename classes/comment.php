<?php

class pmlnr_comment extends pmlnr_base {

	public function __construct () {
		// init all the things!
		add_action( 'init', array( &$this, 'init'));
	}

	public function init() {

	}

	/**
	 *
	 */
	public static function comment_endpoint () {
		return 'webmention_response';
	}

	/**
	 *
	 */
	public static function get_permalink( $comment_ID ) {
		if ( empty( $comment_ID ) )
			return false;

		return rtrim(get_bloginfo('url'),'/') . '/' . static::comment_endpoint() . '/' . $comment_ID;
	}

	/**
	 *
	 */
	public static function is_a_reply ( $comment_ID ) {

		if ( empty( $comment_ID ) )
			return false;

		$comment = get_comment( $comment_ID );

		if ( ! pmlnr_base::is_comment ( $comment ) ) {
			pmlnr_base::debug ( "comment #{$comment_ID} is not a comment" );
			return false;
		}

		if ( empty( $comment->comment_parent ) ) {
			pmlnr_base::debug ( "comment #{$comment_ID} doesn't have a parent" );
			return false;
		}

		$parent = get_comment( $comment->comment_parent );

		if ( ! pmlnr_base::is_comment ( $parent ) ) {
			pmlnr_base::debug ( "comment #{$comment_ID} parent is not a comment" );
			return false;
		}

		if ( empty ( $parent->comment_author_url ) ) {
			pmlnr_base::debug ( "comment #{$comment_ID} no author url for parent" );
			return false;
		}

		return $parent->comment_author_url;
	}


	/**
	 *
	 */
	public static function comment_get_webmention( &$comment = null, $parsedown = false ) {

		if ( ! static::is_comment( $comment ) )
			return false;

		if ( $cached = wp_cache_get ( $comment->comment_ID, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$r = false;

		$webmention_url = static::is_a_reply ($comment->comment_ID);
		if ( false != $webmention_url ) {
			$h = __('This is a reply to:');
			$cl = 'u-in-reply-to';
			$prefix = '**RE:** ';
			$webmention_title = str_replace ( parse_url( $webmention_url, PHP_URL_SCHEME) .'://', '', $webmention_url);
			$r = "\n{$prefix}[{$webmention_title}]({$webmention_url}){.{$cl}}\n";

			if ($parsedown)
				$r = pmlnr_markdown::parsedown($r);

		}

		wp_cache_set ( $comment->comment_ID, $r, __CLASS__ . __FUNCTION__, static::expire );

		return $r;
	}


	/**
	 *
	 */
	public static function template_vars (&$comment = null, $post = null, $prefix = '' ) {

		if (!static::is_comment($comment))
			return false;

		if (!static::is_post($post))
			return false;

		if ( $cached = wp_cache_get ( $comment->comment_ID . $prefix, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$time = strtotime($comment->comment_date);

		$r = array (
			'id' => $comment->comment_ID,
			'url' => rtrim(get_bloginfo('url'),'/') . '/' . static::comment_endpoint() . '/' . $comment->comment_ID,
			'pubdate_iso' => date( 'c', $time ),
			'pubdate_print' => sprintf ('%s %s', date( get_option('date_format'), $time ), date( get_option('time_format'), $time ) ),
			'from' => $comment->comment_author,
			'from_url' => $comment->comment_author_url,
			'content' => $comment->comment_content,
			'webmention' => static::comment_get_webmention( $comment, true ),
		);

		if (!empty($prefix)) {
			foreach ($r as $key => $value ) {
				$r[ $prefix . $key ] = $value;
				unset($r[$key]);
			}
		}

		wp_cache_set ( $comment->comment_ID . $prefix, $r, __CLASS__ . __FUNCTION__, static::expire );

		return $r;
	}
}

