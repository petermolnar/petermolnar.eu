<?php

class pmlnr_post extends pmlnr_base {

	public function __construct () {
		// add graphmeta, because world
		add_action('wp_head',array(&$this, 'post_graphmeta'));
		add_action('init',array(&$this, 'init'));
	}

	public function init() {
		add_filter( 'the_content', array( &$this, 'dyn_self_url'), 1, 10 );
		add_filter( 'the_excerpt', array( &$this, 'dyn_self_url'), 1, 10 );
	}

	/**
	 *
	public static function insert_post_relations( $content, $post = null ) {
		if ( $post == null )
			$post = static::fix_post($post);

		$webmention = static::post_get_webmention( $post );

		return $webmention . "\n" . $content;
	}*/

	/**
	 *
	 */
	public static function get_the_content( &$post = null ){
		//global $post;
		//$prevpost = $post;

		$post = static::fix_post($post);

		//if ($post === false ) {
			//$post = $prevpost;
			//return false;
		//}

		if ( $cached = wp_cache_get ( $post->ID, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$r = apply_filters('the_content', $post->post_content);

		//setup_postdata( $post );
		//ob_start();
		//the_content();
		//$r = ob_get_clean();
		//wp_reset_postdata( $post );
		//$post = $prevpost;

		wp_cache_set ( $post->ID, $r, __CLASS__ . __FUNCTION__, static::expire );

		return $r;
	}

	/**
	 *
	 */
	public static function get_the_excerpt( &$post = null ){
		//global $post;
		//$prevpost = $post;

		$post = static::fix_post($post);

		//if ($post === false ) {
			//$post = $prevpost;
			//return false;
		//}

		if ( $cached = wp_cache_get ( $post->ID, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$r = apply_filters('the_excerpt', $post->post_excerpt);
		//setup_postdata( $post );
		//ob_start();
		//the_excerpt();
		//$r = ob_get_clean();
		//wp_reset_postdata( $post );
		//$post = $prevpost;

		wp_cache_set ( $post->ID, $r, __CLASS__ . __FUNCTION__, static::expire );

		return $r;
	}

	/**
	 *
	 */
	public static function post_get_webmention( &$post = null, $parsedown = false ) {
		$post = static::fix_post($post);


		if ($post === false )
			return false;

		if ( $cached = wp_cache_get ( $post->ID, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$r = false;

		$webmention_url = get_post_meta ( $post->ID, 'webmention_url', true);
		$webmention_type = get_post_meta ( $post->ID, 'webmention_type', true);
		$webmention_rsvp = get_post_meta ( $post->ID, 'webmention_rsvp', true);

		switch ($webmention_type) {
			case 'u-like-of':
				$h = __('This is a like of:');
				$cl = 'u-like-of';
				$prefix = '';
				break;
			case 'u-repost-of':
				$h = __('This is a repost of:');
				$cl = 'u-repost-of';
				$prefix = '*reposted from:* ';
				break;
			default:
				$h = __('This is a reply to:');
				$cl = 'u-in-reply-to';
				$prefix = '**RE:** ';
				break;
		}

		$rsvps = array (
			'no' => __("Sorry, can't make it."),
			'yes' => __("I'll be there."),
			'maybe' => __("I'll do my best, but don't count on me for sure."),
		);

		if ( !empty($webmention_url)) {
			$webmention_title = str_replace ( parse_url( $webmention_url, PHP_URL_SCHEME) .'://', '', $webmention_url);
			$rel = str_replace('u-', '', $cl );
			//$r = "\n\n##### $h";

			$r = "\n{$prefix}[{$webmention_title}]({$webmention_url}){.{$cl}}\n";
			if (!empty($webmention_rsvp))
				$r .= '<data class="p-rsvp" value="' . $webmention_rsvp .'">'. $rsvps[ $webmention_rsvp ] .'</data>';

			if ($parsedown)
				$r = pmlnr_markdown::parsedown($r);
		}

		wp_cache_set ( $post->ID, $r, __CLASS__ . __FUNCTION__, static::expire );

		return $r;
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

		wp_cache_set ( $post->ID, $r, __CLASS__ . __FUNCTION__, static::expire );

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

		$r = array();
		$syndicates = get_post_meta ( $post->ID, 'syndication_urls', true );

		if ( !$syndicates )
			return $r;

		$syndicates = explode( "\n", $syndicates );

		foreach ($syndicates as $syndicate ) {
			// example https://(www.)(facebook).(com)/(...)/(post_id)
			preg_match ( '/^http[s]?:\/\/(www\.)?([0-9A-Za-z]+)\.([0-9A-Za-z]+)\/(.*)\/(.*)$/', $syndicate, $split);

			if ( !empty($split) && isset($split[2]) && !empty($split[2]) && isset($split[3]) && !empty($split[3]))
				$r[$split[2]] = $syndicate;
		}

		wp_cache_set ( $post->ID, $r, __CLASS__ . __FUNCTION__, static::expire );

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

		wp_cache_set ( $post->ID, $r, __CLASS__ . __FUNCTION__, static::expire );

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

		wp_cache_set ( $post->ID, $r, __CLASS__ . __FUNCTION__, static::expire );

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

		wp_cache_set ( $post->ID, $r, __CLASS__ . __FUNCTION__, static::expire );

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

		wp_cache_set ( $post->ID, $r, __CLASS__ . __FUNCTION__, static::expire );

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

		wp_cache_set ( $post->ID, $r, __CLASS__ . __FUNCTION__, static::expire );

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

		wp_cache_set ( $post->ID, $r, __CLASS__ . __FUNCTION__, static::expire );

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

	/**
	 *
	 */
	public static function post_graphmeta () {
		global $post;
		$post = static::fix_post($post);

		if ( $post === false )
			return true;

		if (!is_singular())
			return true;

		$og = array();

		$og['og:locale'] = get_bloginfo( 'language' );
		$og['og:site_name'] = get_bloginfo('name');
		$og['og:type'] = 'website';
		$og['twitter:card'] = 'summary_large_image';

		$og['og:type'] = 'article';
		$og['og:url'] = wp_get_shortlink( $post->ID );
		$og['og:title'] = $og['twitter:title'] = trim(get_the_title( $post->ID ));

		$loc = get_post_meta( $post->ID, 'locale', true );
		if ($loc) $og['og:locale'] = $loc;

		if ( $tw = get_the_author_meta( 'twitter', $post->post_author ) )
			$og['twitter:site'] = '@' . $tw;

		$og['og:updated_time'] = get_the_modified_time( 'c', $post->ID );
		$og['article:published_time'] = get_the_time( 'c', $post->ID );
		$og['article:modified_time'] = get_the_modified_time( 'c', $post->ID );

		$desc = strip_tags(static::get_the_excerpt($post));
		$og['og:description'] = $desc;
		$og['twitter:description'] = $desc;

		$tags = static::post_get_tags_array($post);

		if ( !empty($tags) ) {
			$tags = array_keys($tags);
			$og['article:tag'] = join(",", $tags);
		}

		$thid = get_post_thumbnail_id( $post->ID );
		if ( $thid ) {
			$src = wp_get_attachment_image_src( $thid, 'large');
			if ( !empty($src[0])) {
				$src = pmlnr_base::fix_url($src[0]);
				$og['og:image'] = $src;
				$og['twitter:image:src'] = $src;
			}
		}

		ksort($og);

		foreach ($og as $property => $content )
			printf( '<meta property="%s" content="%s" />%s', $property, $content, "\n" );
	}

	/**
	 *
	 */
	public static function template_vars (&$post = null, $prefix = '' ) {
		$post = static::fix_post($post);

		if ($post === false)
			return false;

		if ( $cached = wp_cache_get ( $post->ID . $prefix, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$r = array (
			'id' => $post->ID,
			'url' => static::dyn_self_url(get_permalink( $post->ID )),
			'title' => trim(get_the_title( $post->ID )),
			'shorturl' => wp_get_shortlink( $post->ID ),
			'thumbnail' => static::post_thumbnail ($post),
			'content' => static::get_the_content($post),
			'raw_content' => $post->post_content,
			'excerpt' => static::get_the_excerpt($post),
			//'author_name' => get_the_author_meta ( 'display_name' , $post->post_author ),
			//'author_url' => get_the_author_meta ( 'user_url' , $post->post_author ),
			//'author_email' => get_the_author_meta ( 'user_email' , $post->post_author ),
			'author_meta' => get_post_meta ( $post->ID, 'author', true),
			'pubdate_iso' => get_the_time( 'c', $post->ID ),
			'pubdate_print' => sprintf ('%s %s', get_the_time( get_option('date_format'), $post->ID ), get_the_time( get_option('time_format'), $post->ID ) ),
			'moddate_iso' => get_the_modified_time( 'c', $post->ID ),
			'moddate_print' => sprintf ('%s %s', get_the_modified_time( get_option('date_format'), $post->ID ), get_the_modified_time( get_option('time_format'), $post->ID ) ),
			'minstoread' => ceil( str_word_count( strip_tags($post->post_content), 0 ) / 300 ),
			//'repost_of' => static::post_repost_of ( $post ),
			'twitter_repost' => static::twitter_repost_of( $post ),
			'twitter_reply' => static::twitter_reply_to( $post ),
			'bgstyle' => static::post_background ($post),
			'tags' => static::post_get_tags_array($post),
			'format' => static::post_format($post),
			'webmention' => static::post_get_webmention($post, true),
			'syndicates' => static::post_get_syndicates($post),
		);

		$r['author'] = pmlnr_author::template_vars( $post->post_author );

		if (!empty($prefix)) {
			foreach ($r as $key => $value ) {
				$r[ $prefix . $key ] = $value;
				unset($r[$key]);
			}
		}

		wp_cache_set ( $post->ID . $prefix, $r, __CLASS__ . __FUNCTION__, static::expire );

		return $r;
	}

}
