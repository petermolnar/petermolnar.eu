<?php

class pmlnr_post extends pmlnr_base {

	public function __construct () {
		// add graphmeta, because world
		add_action('wp_head',array(&$this, 'post_graphmeta'));
	}

	/**
	 *
	 */
	public static function get_the_content( &$post = null, $clean = false ){

		$post = static::contentfilters ( $post );

		if ( false === $post )
			return false;

		if ( $cached = wp_cache_get ( $post->ID . $clean, __CLASS__ . __FUNCTION__ ) )
			return $cached;



		$r = $post->post_content;

		if ( $clean == 'clean')
			$r = static::remove_reaction ( $r );
		else
			$r = static::convert_reaction ( $r );

		$r = apply_filters('the_content', $r);

		wp_cache_set ( $post->ID . $clean, $r, __CLASS__ . __FUNCTION__, static::expire );

		return $r;
	}

	public static function contentfilters ( $post ) {
		$post = static::fix_post($post);

		if ( false === $post )
			return false;

		//if ( has_term( 'indieweb', 'post_tag', $post ) ) {
			//$lang = get_post_meta ( $post->ID, 'locale', true );

			//if ( empty( $lang ) || $lang == 'en' || $lang == 'eng' )
				//$post->post_content .= '<a href="http://news.indiewebcamp.com/en" class="u-syndication"></a>';
		//}

		return $post;
	}

	/**
	 *
	 */
	public static function get_the_excerpt( &$post = null ){
		$post = static::fix_post($post);

		if ( $cached = wp_cache_get ( $post->ID, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$r = apply_filters('the_excerpt', $post->post_excerpt);

		wp_cache_set ( $post->ID, $r, __CLASS__ . __FUNCTION__, static::expire );

		return $r;
	}

	/**
	 *
	 *
	public static function post_get_webmention( &$post = null, $parsedown = false ) {
		$post = static::fix_post($post);


		if ($post === false )
			return false;

		if ( $cached = wp_cache_get ( $post->ID, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		// all done already, because it's inline
		$content = $post->post_content;
		$matches = static::has_reaction( $content );
		if ( false != $matches )
			return false;

		// otherwise try to get the meta field which is deprecated and I should delete
		// this code
		$r = $migrate = false;

		$webmention_url = get_post_meta ( $post->ID, 'webmention_url', true);
		$webmention_type = get_post_meta ( $post->ID, 'webmention_type', true);
		$webmention_rsvp = get_post_meta ( $post->ID, 'webmention_rsvp', true);

		if ( !empty($webmention_url)) {
			switch ($webmention_type) {
				case 'u-like-of':
					$h = __('This is a like of:');
					$cl = 'u-like-of';
					$prefix = '';
					$migrate = "---\nlike: {$webmention_url}\n---\n\n";
					break;
				case 'u-repost-of':
					$h = __('This is a repost of:');
					$cl = 'u-repost-of';
					$prefix = '*reposted from:* ';
					$migrate = "---\nfrom: {$webmention_url}\n---\n\n";
					break;
				case 'u-in-reply-to':
					$h = __('This is a reply to:');
					$cl = 'u-in-reply-to';
					$prefix = '**RE:** ';
					$migrate = "---\nre: {$webmention_url}\n---\n\n";
					break;
				default:
					$h = __('');
					$cl = 'u-url';
					$prefix = '**URL:** ';
					$migrate = "---\n{$webmention_url}\n---\n\n";
					break;
			}

			$rsvps = array (
				'no' => __("Sorry, can't make it."),
				'yes' => __("I'll be there."),
				'maybe' => __("I'll do my best, but don't count on me for sure."),
			);

			$webmention_title = str_replace ( parse_url( $webmention_url, PHP_URL_SCHEME) .'://', '', $webmention_url);
			$rel = str_replace('u-', '', $cl );

			$r = "\n{$prefix}[{$webmention_title}]({$webmention_url}){.{$cl}}\n";

			if (!empty($webmention_rsvp)) {
				$r .= '<data class="p-rsvp" value="' . $webmention_rsvp .'">'. $rsvps[ $webmention_rsvp ] .'</data>';
				$migrate = "---\nre: {$webmention_url}\n{$webmention_rsvp}\n---\n\n";
			}

			// this should be a temporary thing
			//global $wpdb;
			//$dbname = "{$wpdb->prefix}posts";
			//$req = false;
			//$modcontent = $migrate . $post->post_content;

			//static::debug("Updating post content for #{$post->ID}");

			//$q = $wpdb->prepare( "UPDATE `{$dbname}` SET `post_content`='%s' WHERE `ID`='{$post->ID}'", $modcontent );

			//try {
				//$req = $wpdb->query( $q );
			//}
			//catch (Exception $e) {
				//static::debug('Something went wrong: ' . $e->getMessage());
			//}

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

		$format = static::post_format( $post );

		$r = [];

		if ( $format == 'photo' && ! has_term ( 'photo', 'post_tag', $post ) )
			wp_set_object_terms( $post->ID, 'photo', 'post_tag', true );

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

		foreach ($og as $property => $content ) {
			$tag = ( strstr( $property, 'og' ) ) ? 'property': 'name';
			printf( '<meta %s="%s" content="%s" />%s', $tag, $property, $content, "\n" );
		}
	}

	/**
	 *
	 */
	public static function get_comments ( $post = null, $type = '' ) {

		$r = array();

		$post = static::fix_post($post);

		if ( false === $post )
			return $r;

		if ( $cached = wp_cache_get ( $post->ID . $type , __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$t = false;
		switch ($type) {
			case 'like':
				$t = array ( 'like', 'favorite' );
				break;
			case 'reply':
				$t = array ( 'reply', 'comment', 'webmention', 'ping' );
				break;
			default:
				$t = false;
				break;
		}

		$args = array (
			'post_id' => $post->ID,
			'order' => 'ASC',
		);

		if ( false != $t )
			$args['type__in'] = $t;

		$comments = get_comments ( $args );

		if (!empty($comments)) {
			foreach ($comments as $k => $comment ) {
				$time = strtotime($comment->comment_date);
				$c = array (
					'id' => $comment->comment_ID,
					'author_url' => $comment->comment_author_url,
					'author' => $comment->comment_author,
					'avatar' => get_avatar( $comment, 42 ),
					'content' => pmlnr_markdown::parsedown ($comment->comment_content),
					//'content' => $comment->comment_content,
					'pubdate_iso' => date( 'c', $time ),
					'pubdate_print' => sprintf ('%s %s',
						date( get_option('date_format'), $time ),
						date( get_option('time_format'), $time ) ),
					'source' => get_comment_meta ( $comment->comment_ID, 'comment_url', true ),
				);

				array_push($r, $c);
			}
		}

		wp_cache_set ( $post->ID . $type, $r, __CLASS__ . __FUNCTION__, static::expire );

		return $r;
	}

	/**
	 *
	 */
	public static function get_reacji ( $post = null ) {

		$r = array();

		$post = static::fix_post($post);

		if ( false === $post )
			return $r;

		if ( $cached = wp_cache_get ( $post->ID , __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$t = false;

		$args = array (
			'post_id' => $post->ID,
			'type__in' => array ( 'reacji', 'like', 'favorite' )
		);

		$comments = get_comments ( $args );

		if (!empty($comments)) {
			foreach ($comments as $k => $comment ) {
				$time = strtotime($comment->comment_date);
				$c = array (
					'id' => $comment->comment_ID,
					'author_url' => $comment->comment_author_url,
					'author' => $comment->comment_author,
					'avatar' => get_avatar( $comment, 42 ),
					'pubdate_iso' => date( 'c', $time ),
					'pubdate_print' => sprintf ('%s %s', date(
						get_option('date_format'), $time ),
						date( get_option('time_format'), $time ) ),
				);

				switch ( $comment->comment_type ) {
					case 'like':
					case 'favorite':
						$reacji = '★';
						break;
					default:
						$reacji = $comment->comment_content;
						break;
				}

				if ( ! isset ( $r [ $reacji ] ) )
					$r [ $reacji ] = array();

				array_push($r[ $reacji ], $c);

			}
		}

		wp_cache_set ( $post->ID, $r, __CLASS__ . __FUNCTION__, static::expire );

		return $r;
	}


	/**
	 *
	 */
	public static function convert_reaction ( $content ) {

		$matches = static::has_reaction( $content );
		if ( false == $matches )
			return $content;

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
			case 'repost':
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

		return str_replace ( $replace, $r, $content );
	}

	/**
	 *
	 */
	public static function template_vars (&$post = null, $prefix = '' ) {
		$r = array();
		$post = static::fix_post($post);

		if ($post === false)
			return $r;

		if ( $cached = wp_cache_get ( $post->ID . $prefix, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$r = array (
			'id' => $post->ID,
			'url' => get_permalink( $post->ID ),
			'title' => trim(get_the_title( $post->ID )),
			'shorturl' => wp_get_shortlink( $post->ID ),
			'thumbnail' => static::post_thumbnail ($post),
			'content' => static::get_the_content($post, 'clean'),
			'parsed_content' => static::get_the_content($post),
			'raw_content' => $post->post_content,
			'excerpt' => static::get_the_excerpt($post),
			'author_meta' => get_post_meta ( $post->ID, 'author', true),
			'pubdate_iso' => get_the_time( 'c', $post->ID ),
			'pubdate_print' => sprintf ('%s %s',
				get_the_time( get_option('date_format'), $post->ID ),
				get_the_time( get_option('time_format'), $post->ID ) ),
			'minstoread' => ceil( str_word_count( strip_tags($post->post_content), 0 ) / 300 ),
			'wordstoread' => str_word_count( strip_tags($post->post_content), 0 ),
			'tags' => static::post_get_tags_array($post),
			'format' => static::post_format($post),
			'webmention' => static::extract_reaction($post->post_content, true),
			'syndicates' => static::post_get_syndicates($post),
			'likes' => static::get_comments($post, 'like'),
			'replies' => static::get_comments($post, 'reply'),
			'reacji' => static::get_reacji($post),
			'singular' => is_singular(),
			'debug' => is_user_logged_in(),
			'uuid' => hash ( 'md5', (int)$post->ID + (int) get_post_time('U', true, $post->ID ) ),
		);

		$r['author'] = pmlnr_author::template_vars( $post->post_author );

		$r = static::prefix_array ( $r, $prefix );

		wp_cache_set ( $post->ID . $prefix, $r, __CLASS__ . __FUNCTION__, static::expire );

		return $r;
	}

}
