<?php

class pmlnr_comment extends pmlnr_base {

	public static function comment_endpoint () {
		return 'webmention_response';
	}

	public function __construct () {
		// init all the things!
		add_action( 'init', array( &$this, 'init'));

		// enable webmentions for comments
		add_action ( 'comment_post', array(&$this, 'comment_webmention'),8,2);
	}

	public function init() {
		add_filter ( 'wp_webmention_again_validate_local', array ( &$this, 'validate_local'), 2, 2 );

		// add comment endpoint to query vars
		add_filter( 'query_vars', array( &$this, 'add_query_var' ) );
		add_rewrite_endpoint ( pmlnr_comment::comment_endpoint(), EP_ROOT );
	}

	/**
	 * add webmention to accepted query vars
	 *
	 * @param array $vars current query vars
	 *
	 * @return array extended vars
	 */
	public function add_query_var( $vars ) {
		array_push($vars, static::comment_endpoint() );
		return $vars;
	}

	/**
	 *
	 */
	public function validate_local ( $postid, $target ) {
		static::debug ( $target );
		$target = strtolower ( $target );
		$endpoint = static::comment_endpoint();

		if (strstr( $target, $endpoint ) ) {
			$target = explode ( '/', $target );
			$tnum = array_search ( $endpoint, $target, true );
			$tnum = (int) $tnum + 1;
			$comment_id = $target[ $tnum ];
			$comment = get_comment($comment_id);
			static::debug ( $comment_id );

			if (pmlnr_base::is_comment($comment)) {
				static::debug ( $comment );
				$post = get_post( $comment->comment_post_ID);
				static::debug ( $post );
				if (pmlnr_base::is_post($post)) {
					$postid = $post->ID;
				}
			}
		}

		return $postid;
	}

	/**
	 *
	 */
	public static function get_permalink( &$comment_ID ) {
		if ( empty( $comment_ID ) )
			return false;

		return rtrim(get_bloginfo('url'),'/') . '/' . static::comment_endpoint() . '/' . $comment_ID;
	}

	/**
	 *
	 */
	public static function is_a_reply ( &$comment_ID ) {

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

		$target = get_comment_meta ( $parent->comment_ID, 'comment_url', true );
		if ( empty ( $target ) ) {
			static::debug ( "comment #{$comment_ID}'s parent has no comment_url meta" );
			return false;
		}

		return $target;
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
	public function comment_webmention ( $comment_ID, $comment_approved = false ) {
		if ( ! function_exists( 'send_webmention' ) ) {
			return false;
		}

		if ( false == $comment_approved ) {
			static::debug ( "comment #{$comment_ID} is not approved" );
			return false;
		}

		$comment = get_comment( $comment_ID );

		if ( ! static::is_comment ( $comment ) ) {
			static::debug ( "comment #{$comment_ID} is not a comment" );
			return false;
		}

		if ( empty( $comment->comment_parent ) ) {
			static::debug ( "comment #{$comment_ID} doesn't have a parent" );
			return false;
		}

		$parent = get_comment( $comment->comment_parent );

		if ( ! static::is_comment ( $parent ) ) {
			static::debug ( "comment #{$comment_ID} parent is not a comment" );
			return false;
		}

		$target = get_comment_meta ( $parent->comment_ID, 'comment_url', true );
		if ( empty ( $target ) ) {
			static::debug ( "comment #{$comment_ID}'s parent has no comment_url meta" );
			return false;
		}

		$permalink = pmlnr_comment::get_permalink($comment_ID);

		static::debug ( "comment #{$comment_ID} sending webmention to: {$target} as: {$permalink}" );
		send_webmention ( $permalink, $target, 'comment', $comment_ID );
	}

	/**
	 *
	 */
	public static function template_vars (&$comment = null, $post = null ) {

		if (!static::is_comment($comment))
			return false;

		if (!static::is_post($post))
			return false;

		if ( $cached = wp_cache_get ( $comment->comment_ID, __CLASS__ . __FUNCTION__ ) )
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
			'parent' => get_permalink( $post->ID ),
		);

		$r['author'] = pmlnr_author::template_vars( $comment->user_id );

		wp_cache_set ( $comment->comment_ID, $r, __CLASS__ . __FUNCTION__, static::expire );

		return $r;
	}
}

