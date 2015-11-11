<?php

class pmlnr_post extends pmlnr_base {

	public static function post_decode_format ( $format ) {

	}

	/**
	 *
	 */
	public static function post_get_tags_array ( &$post = null ) {
		$post = static::fix_post($post);

		if ($post === false)
			return false;

		if ( $cached = wp_cache_get ( $post->ID, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$r = [];

		$tags = get_the_tags( $post->ID );
		if ( $tags )
			foreach( $tags as $tag )
				$r[ $tag->name ] = get_tag_link( $tag->term_id );

		wp_cache_set ( $post->ID, $r, __CLASS__ . __FUNCTION__, self::expire );

		return $r;
	}

	/**
	 *
	 */
	public static function post_get_syndicates ( &$post = null ) {
		$post = static::fix_post($post);

		if ($post === false)
			return false;

		if ( $cached = wp_cache_get ( $post->ID, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$r = [];
		$syndicates = get_post_meta ( $post->ID, 'syndication_urls', true );

		if ( !$syndicates )
			return $parsed;

		$syndicates = explode( "\n", $syndicates );

		foreach ($syndicates as $syndicate ) {
			// example https://(www.)(facebook).(com)/(...)/(post_id)
			preg_match ( '/^http[s]?:\/\/(www\.)?([0-9A-Za-z]+)\.([0-9A-Za-z]+)\/(.*)\/(.*)$/', $syndicate, $split);

			if ( !empty($split) && isset($split[2]) && !empty($split[2]) && isset($split[3]) && !empty($split[3]))
				$r[$split[2]] = $split;
		}

		wp_cache_set ( $post->ID, $r, __CLASS__ . __FUNCTION__, self::expire );

		return $r;
	}

	/**
	 *
	 */
	public static function post_get_replylist ( &$post = null ) {
		$post = static::fix_post($post);

		if ($post === false)
			return false;

		if ( $cached = wp_cache_get ( $post->ID, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$r = [];
		$syndicates = static::post_get_syndicates();

		if (empty($syndicates))
			return $reply;

		foreach ($syndicates as $silo => $syndicate ) {
			if ($silo == 'twitter') {
				//$rurl = sprintf ('https://twitter.com/intent/tweet?in_reply_to=%s',  $syndicate[5]);
				continue;
			}
			else {
				$r[ $silo ] = $syndicate[0];
			}
		}

		wp_cache_set ( $post->ID, $r, __CLASS__ . __FUNCTION__, self::expire );

		return $r;
	}

	/**
	 *
	 */
	public static function post_get_sharelist ( &$post = null ) {
		$post = static::fix_post($post);

		if ($post === false)
			return false;

		if ( $cached = wp_cache_get ( $post->ID, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$r = [];

		$syndicates = static::post_get_syndicates();

		$url = urlencode( get_permalink( $post ) );
		$title = urlencode( trim(get_the_title( $post->ID )) );
		$description = urlencode( $post->post_excerpt );

		$media_url = '';
		$media = ( $thid = get_post_thumbnail_id( $post->ID )) ? wp_get_attachment_image_src($thid,'large', true) : false;
		if ( isset($media[1]) && $media[3] != false )
			$media_url = urlencode(static::fix_url($thumbnail[0]));

		if (!empty($syndicates)) {
			foreach ($syndicates as $silo => $syndicate ) {
				//if ($silo == 'twitter') {
					//$rurl = sprintf ( 'https://twitter.com/intent/retweet?tweet_id=%s', $syndicate[5]);
				//}
				if ($silo == 'facebook') {
					$rurl = sprintf ( 'https://www.facebook.com/share.php?u=%s', urlencode($syndicate[0]) );
				}
				else {
					continue;
				}

				if ($rurl)
					$r[$silo] = $rurl;
			}
		}

		if (!isset($r['facebook']))
			$r['facebook'] = sprintf ('https://www.facebook.com/share.php?u=%s', $url );

		if (!isset($r['twitter']))
			$r['twitter'] = sprintf('https://twitter.com/share?url=%s&text=%s', $url, $title );

		$r['googleplus'] = sprintf('https://plus.google.com/share?url=%s', $url );

		$r['tumblr'] = sprintf('http://www.tumblr.com/share/link?url=%s&title=%s&description=%s', $url, $title, $description );

		$r['pinterest'] = sprintf('https://pinterest.com/pin/create/bookmarklet/?media=%s&url=%s&description=%s&is_video=false', $media_url, $url, $title );

		// short url / webmention
		$r['webmention'] = $url;

		wp_cache_set ( $post->ID, $r, __CLASS__ . __FUNCTION__, self::expire );

		return $share;
	}


	/**
	 *
	 */
	public static function post_background (&$post = null) {
		$post = static::fix_post($post);

		if ($post === false)
			return false;

		if ( $cached = wp_cache_get ( $post->ID, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$r = false;

		if ( ! static::is_u_photo($post) ) {

			$thid = get_post_thumbnail_id( $post->ID );
			$bgimg = (empty( $thid)) ? array() : wp_get_attachment_image_src( $thid , 'headerbg');

			if ( isset($bgimg[1]) && $bgimg[3] != false )
				$r = 'class="article-header" style="background-image:url('.$bgimg[0].');"';
		}

		wp_cache_set ( $post->ID, $r, __CLASS__ . __FUNCTION__, self::expire );

		return $r;
	}

	/**
	 *
	 */
	public static function post_thumbnail (&$post = null) {
		$post = static::fix_post($post);

		if ($post === false)
			return false;

		if ( $cached = wp_cache_get ( $post->ID, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$r = false;
		$thid = get_post_thumbnail_id( $post->ID );
		if ( $thid ) {
			$thumbnail = wp_get_attachment_image_src($thid,'thumbnail');
			if ( isset($thumbnail[1]) && $thumbnail[3] != false )
				$r = static::fix_url($thumbnail[0]);
		}

		wp_cache_set ( $post->ID, $r, __CLASS__ . __FUNCTION__, self::expire );

		return $r;
	}

	/**
	 *
	 */
	public static function twitter_repost_of (&$post = null) {
		$post = static::fix_post($post);

		if ($post === false)
			return false;

		if ( $cached = wp_cache_get ( $post->ID, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$r = false;

		$twitter_url = get_post_meta( $post->ID, 'twitter_permalink', true);
		if ( $twitter_url )
			$r = $twitter_url;

		wp_cache_set ( $post->ID, $r, __CLASS__ . __FUNCTION__, self::expire );

		return $r;
	}

	/**
	 *
	 */
	public static function twitter_reply_to (&$post = null) {
		$post = static::fix_post($post);

		if ($post === false)
			return false;

		if ( $cached = wp_cache_get ( $post->ID, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$r = false;

		$twitter_reply_user = get_post_meta( $post->ID, 'twitter_in_reply_to_user_id', true);
		$twitter_reply_id = get_post_meta( $post->ID, 'twitter_in_reply_to_status_id', true);
		if ( $twitter_reply_user && $twitter_reply_id )
			$r = 'https://twitter.com/' . $twitter_reply_user . '/status/' . $twitter_reply_id;

		wp_cache_set ( $post->ID, $r, __CLASS__ . __FUNCTION__, self::expire );

		return $r;
	}

	/**
	 *
	 */
	public static function template_vars (&$post = null) {
		$post = static::fix_post($post);

		if ($post === false)
			return false;

		if ( $cached = wp_cache_get ( $post->ID, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$r = array (
			'author_name' => get_the_author_meta ( 'display_name' , $post->post_author ),
			'author_url' => get_the_author_meta ( 'user_url' , $post->post_author ),
			'author_email' => get_the_author_meta ( 'user_email' , $post->post_author ),
			'author_meta' => get_post_meta ( $post->ID, 'author', true),
			'pubdate_iso' => get_the_time( 'c', $post->ID ),
			'pubdate_print' => sprintf ('%s %s', get_the_time( get_option('date_format'), $post->ID ), get_the_time( get_option('time_format'), $post->ID ) ),
			'moddate_iso' => get_the_modified_time( 'c', $post->ID ),
			'moddate_print' => sprintf ('%s %s', get_the_modified_time( get_option('date_format'), $post->ID ), get_the_modified_time( get_option('time_format'), $post->ID ) ),
			'minstoread' => ceil( str_word_count( strip_tags($post->post_content), 0 ) / 300 ),
			//'repost_of' => static::post_repost_of ( $post ),
			'twitter_repost' => static::twitter_repost_of( $post ),
			'twitter_reply' => static::twitter_reply_to( $post ),
			//'webmention' =>
			'url' => get_permalink( $post ),
			'title' => trim(get_the_title( $post->ID )),
			'shorturl' => wp_get_shortlink( $post->ID ),
			//'thumbnail_id' => get_post_thumbnail_id( $post->ID ),
			'thumbnail' => static::post_thumbnail ($post),
			'bgstyle' => static::post_background ($post),
			'content' => static::get_the_content($post),
			'excerpt' => static::get_the_excerpt($post),
			'id' => $post->ID,
			'tags' => static::post_get_tags_array($post),
		);

		wp_cache_set ( $post->ID, $r, __CLASS__ . __FUNCTION__, self::expire );

		return $r;
	}


	/**
	 *
	 */
	public static function post_replace_title ( &$post, $new_title = '' ) {
		$post = static::fix_post($post);

		if ($post === false )
			return false;

		// store the old title, just in case
		$current_title = $post->post_title;
		update_post_meta ( $post->ID, '_wp_old_title', $current_title );

		$_post = array(
			'ID' => $post->ID,
			'post_title' => sanitize_title($new_title),
		);

		$r = wp_update_post( $_post, true );

		// something went wrong, log and revert
		if (is_wp_error($r)) {
			$errors = $r->get_error_messages();
			foreach ($errors as $error) {
				static::debug( $error );
			}

			$_post = array(
				'ID' => $post->ID,
				'post_title' => $current_title,
			);
			wp_update_post( $_post );
			delete_post_meta($post->ID, '_wp_old_title', $current_title );
		}
	}
}
