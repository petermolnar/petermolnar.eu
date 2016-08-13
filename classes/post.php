<?php

class pmlnr_post extends pmlnr_base {

	public function __construct () {
		// add graphmeta, because world
		add_action('wp_head',array(&$this, 'post_graphmeta'));
	}

	public static function insert_bridgy_urls ( $content, $post ) {
		$where = static::bridgy_to( $post );

		foreach ( $where as $endpoint ) {
			$content .= "\n" . '<a href="https://brid.gy/publish/'
				. $endpoint .'"></a>';
		}

		return $content;
	}

	/**
	 *
	 */
	public static function bridgy_to( &$post ) {
		$bridgy_urls = array();

		$post = static::fix_post( $post );

		if ( false === $post )
			return $bridgy_urls;

		$format = pmlnr_base::post_format( $post );

		if ( in_array( $format, array( 'photo' ) ) ) {
			array_push( $bridgy_urls, 'facebook' );
			array_push( $bridgy_urls, 'flickr' );
		}

		$bridgy = array (
			'flickr',
			'twitter'
		);

		$reaction = pmlnr_base::has_reaction( $post->post_content );

		if ( empty( $reaction[0] ) )
			return $bridgy_urls;

		foreach ( $reaction[0] as $cntr => $replace ) {
			$url = trim($reaction[2][$cntr]);
			if ( empty( $url ) )
				continue;

			foreach ( $bridgy as $endpoint ) {
				if ( stristr( $url, $endpoint ) ) {
					array_push( $bridgy_urls, $endpoint );
				}
			}
		}

		return array_unique ( $bridgy_urls );
	}

	/**
	 *
	 */
	public static function make_reaction ( &$post, $format = 'markdown', $reaction = false ) {

		if ( false === $reaction )
			$reaction = static::has_reaction ( $post->post_content );

		if ( empty( $reaction ) )
			return false;

		/* this should be obsolete now
		if ( empty( $reaction ) ) {
			$webmention_url = get_post_meta( $post->ID, 'webmention_url', true );

			// content is missing reaction, reinsert it
			if ( ! empty ( $webmention_url ) ) {
				$webmention_type = get_post_meta( $post->ID, 'webmention_type', true );
				$webmention_data = get_post_meta( $post->ID, 'webmention_rsvp', true );

				$react = "*** {$webmention_type}: {$webmention_url}";
				if ( !empty ($webmention_data) )
					$react .= " {$webmention_data}";

				$c = $react . "\n\n" . $post->post_content;
				static::replace_content( $post, $c );

				$reaction = static::has_reaction( $c );
			}
		}

		// check for standalone, duplicate, leftover URLs
		$rurls = array();
		foreach ( $reaction[0] as $cntr => $replace ) {
			$ccontent = str_replace( $replace, '', $post->post_content );
			array_push( $rurls, $reaction[2][$cntr] );
		}
		$urls = static::extract_urls( $ccontent );
		if ( !empty($urls)) {
			foreach( $urls as $url ) {
				if ( in_array( $url, $rurls ) )
					$ccontent = str_replace( $url, '', $ccontent );
			}

			if ( empty(trim($ccontent)) ) {
				$newcontent = "";
				foreach ( $reaction[0] as $cntr => $insert ) {
					$newcontent .= $insert . "\n";
				}
				static::replace_content( $ppost, $newcontent );
			}
		}
		*/


		$r_all = array();

		foreach ( $reaction[0] as $cntr => $replace ) {
			//$replace = $reaction[0][0];
			$type = trim($reaction[1][$cntr]);
			$url = trim($reaction[2][$cntr]);
			$rsvp = trim($reaction[3][$cntr]);

			if ( empty( $url ) )
				continue;

			if ( $format != 'markdown' ) {
				$r = "*** ${type}: {$url}";
				if ( !empty( $rsvp ) )
					$r .= " ${rsvp}";

				array_push( $r_all, $r );
			}
			else {
				$rsvps = array (
					'no' => __("Sorry, can't make it."),
					'yes' => __("I'll be there."),
					'maybe' => __("I'll do my best, but don't count on me for sure."),
				);

				if ( ( $type == 're' || $type == 'reply' ) && !empty( $data ) )
					$rsvp_data = '<data class="p-rsvp" value="' . $rsvp .'">'. $rsvps[ $rsvp ] .'</data>';

				switch ($type) {
					case 'from':
					case 'repost':
						$cl = 'u-repost-of';
						$prefix = '**FROM:** ';
						break;
					case 're':
					case 'reply':
						$cl = 'u-in-reply-to';
						$prefix = '**RE:** ';
						break;
					default:
						$cl = 'u-like-of';
						$prefix = '**URL:** ';
						break;
				}

				$title = str_replace ( parse_url( $url, PHP_URL_SCHEME) .'://', '', $url);
				$r = "\n{$prefix}[{$title}]({$url}){.{$cl}}";

				if ( !empty( $rsvp_data ) )
					$r .= "\n{$rsvp_data}";

				array_push ( $r_all, $r );
			}
		}

		$r_all = array_unique( $r_all );
		sort ( $r_all );

		return join( "\n", $r_all );
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
	public static function convert_reaction ( &$post ) {

		$reaction = static::has_reaction( $post->post_content );

		if ( false == $reaction )
			return $post->post_content;

		$md = static::make_reaction( $post, 'markdown', $reaction );
		$r = static::remove_reaction( $post->post_content, $reaction );

		$r = $md . "\n\n" . $r;

		return $r;
	}

	/**
	 *
	 */
	public static function get_the_content( &$post = null, $clean = false ){

		$post = static::fix_post ( $post );

		if ( false === $post )
			return false;

		if ( $cached = wp_cache_get ( $post->ID . $clean, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$r = $post->post_content;

		$fixedcontent = static::post_content_fixes( $post );

		//$has_reaction = static::has_reaction ( $modcontent );
		//$modcontent = static::remove_reaction ( $modcontent, $has_reaction );
		//$reaction = static::make_reaction( $post, '', $has_reaction );
		//$modcontent = trim ( $reaction . "\n\n" . $modcontent );

		if ( $fixedcontent != $post->post_content ) {
			static::replace_content ( $post, $fixedcontent );
		}


		$r = static::convert_reaction ( $post );
		$r = apply_filters('the_content', $r);
		$r = static::insert_bridgy_urls ( $r, $post );

		//$format = static::post_format( $post );
		//if ( $post->post_type == 'post' && $format == 'article' )
			//$r = static::toc( $r );

		wp_cache_set ( $post->ID . $clean, $r, __CLASS__ . __FUNCTION__, static::expire );

		return $r;
	}

	/**
	 *
	 */
	public static function toc ( $content ) {
		$toc = '';
		$pattern = "/<h([2-6])(?:\s+id=\"(.*?)\")?>(.*)<\/h[2-6]>/mi";
		$matches = array();
		preg_match_all( $pattern, $content, $matches );

		if ( ! empty( $matches ) && isset( $matches[0] ) && ! empty( $matches[0] ) && ( count( $matches[0] ) > 2 ) ) {
			$currd = 2;
			//$post_base = rtrim( site_url(), '/') . '/' . $post->post_name . '/';

			foreach ( $matches[3] as $cntr => $h ) {
				$h = strip_tags( $h );
				$h = preg_replace( "/^[0-9]+\.\s(.*)/", '${1}', $h );
				$id = $matches[2][$cntr];
				$depth = $matches[1][$cntr];
				$l = "<a href=\"#{$id}\">{$h}</a>";

				// just add one more line
				if ( $depth == $currd ) {
					// starting the list
					if ( empty( $toc ) )
						$toc = "<p><strong>Table of contents</strong></p><ol class=\"toc\"><li>{$l}";
					// normal line
					else
						$toc .= "</li><li>{$l}";
				}
				// going deeper
				elseif ( $depth > $currd ) {
					$toc .= "<ol><li>{$l}";
				}
				// leaving the depth
				else {
					$toc .= "</ol></li><li>{$l}";
				}
				$currd = $depth;
			}

			// closing any potentially open depths and ending the list
			$toc .= str_repeat ( '</ol></li>', ($currd - 1)  ) . '</li></ol>';
		}

		return $toc . $content;
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
	 */
	public static function post_get_tags_array ( &$post = null ) {
		$post = static::fix_post($post);

		if ($post === false)
			return false;

		if ( $cached = wp_cache_get ( $post->ID, __CLASS__ . __FUNCTION__ ) )
			return $cached;

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

		wp_cache_set ( $post->ID, $r, __CLASS__ . __FUNCTION__, static::expire );

		return $r;
	}

	/**
	 *
	 */
	public static function post_thumbnail ( &$post = null ) {
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
				$r = site_url($thumbnail[0]);
		}

		wp_cache_set ( $post->ID, $r, __CLASS__ . __FUNCTION__, static::expire );

		return $r;
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

		//if ( !empty($tags) ) {
			//$tags = array_keys($tags);
			//$og['article:tag'] = str_replace ( '"', "'" , join(",", $tags) );
		//}

		$thid = get_post_thumbnail_id( $post->ID );
		if ( $thid ) {
			$src = wp_get_attachment_image_src( $thid, 'large');
			if ( !empty($src[0])) {
				$src = site_url($src[0]);
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
	public static function get_comments ( &$post = null, $type = '' ) {

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
	public static function get_reacji ( &$post = null ) {

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
	public static function has_hashtags ( &$content ) {

		$c = explode( "\n", $content );

		$last = false;
		for ( $i = count($c)-1; $i > 0; $i--) {
			if ( empty ( trim( $c[ $i ] ) ) ) {
				continue;
			}
			else {
				$last = $c[ $i ];
				break;
			}
		}

		$pattern = "/\#(.*?)(?:,|$|\z|\n)\s?+/";
		//$pattern = "/\#(.*?)(?:,|$|\z|\n)\s?+/";

		$matches = array();
		preg_match_all( $pattern, $last, $matches);

		if ( ! empty( $matches ) && isset( $matches[0] ) && ! empty( $matches[0] ) )
			return $matches;

		return false;
	}

	/**
	 *
	 */
	public static function remove_hashtags ( &$content ) {

		$matches = static::has_hashtags( $content );
		if ( false == $matches )
			return $content;

		return str_replace ( join('', $matches[0]), '', $content );
	}


	/**
	 *
	 *
	 */
	public static function post_content_fixes ( $post ) {
		$content = $post->post_content;

		$content = static::post_content_fix_dl ( $content, $post );
		$content = static::post_content_fix_emstrong ( $content, $post );
		$content = static::post_content_url2footnote ( $content, $post );
		// skip the ones that are inside a code block...
		//$content = static::post_content_setext_headers ( $content, $post );

		return $content;
	}

	public static function post_content_fix_emstrong ( $content, $post ) {

		// these regexes are borrowed from https://github.com/erusev/parsedown
		$invalid = array (
			'strong' => array(
				'__' => '/__((?:\\\\_|[^_]|_[^_]*_)+?)__(?!_)/us',
			),
			'em' => array (
				'*' => '/[*]((?:\\\\\*|[^*]|[*][*][^*]+?[*][*])+?)[*](?![*])/s',
			)
		);

		$replace_map = array (
			'*' => '_',
			'__' => '**',
		);


		foreach ( $invalid as $what => $regexes ) {
			$m = array();
			foreach ( $regexes as $key => $regex ) {
				preg_match_all( $regex, $content, $m );
				if ( empty( $m ) || ! isset( $m[0] ) || empty( $m[0] ) )
					continue;

				foreach ( array_keys ( $m[1] ) as $cntr ) {
					$content = str_replace (
						$m[0][$cntr],
						$replace_map[ $key ] . $m[1][$cntr] . $replace_map[ $key ],
						$content
					);
				}

			}
		}

		return $content;
	}

	/**
	 *
	 *
	 */
	public static function post_content_fix_dl ( $content, $post ) {
		preg_match_all( '/^.*\n(:\s+).*$/mi', $content, $m );

		if ( empty( $m ) || ! isset( $m[0] ) || empty( $m[0] ) )
			return $content;

		foreach ( $m[0] as $i => $match ) {
			$match = str_replace( $m[1][$i], ':    ', $match );
			$content = str_replace( $m[0][$i], $match, $content );
		}

		return $content;
	}

	/**
	 * find markdown links and replace them with footnote versions
	 *
	 */
	public static function post_content_url2footnote ( $content, $post ) {

		//
		$pattern = "/[\s*_\/]+(\[([^\s].*?)\]\((.*?)(\s?+[\\\"\'].*?[\\\"\'])?\))/";
		preg_match_all( $pattern, $content, $m );
		// [1] -> array of []()
		// [2] -> array of []
		// [3] -> array of ()
		// [4] -> (maybe) "" titles
		if ( ! empty( $m ) && isset( $m[0] ) && ! empty( $m[0] ) ) {
			foreach ( $m[1] as $cntr => $match ) {
				$name = trim( $m[2][$cntr] );
				$url = trim( $m[3][$cntr] );
				if ( ! strstr( $url, 'http') )
					$url = \site_url( $url );

				$title = "";

				if ( isset( $m[4][$cntr] ) && !empty( $m[4][$cntr] ) )
					$title = " {$m[4][$cntr]}";

				$refid = $cntr+1;

				$footnotes[] = "[{$refid}]: {$url}{$title}";
				$content = str_replace (
					$match,
					"[" . trim( $m[2][$cntr] ) . "][". $refid ."]" ,
					$content
				);
			}

			$content = $content . "\n\n" . join( "\n", $footnotes );
		}

		return $content;
	}


	/**
	 * find all second level markdown headers and replace them with
	 * underlined version
	 *
	 */
	public static function post_content_setext_headers ( $content, $post ) {

		$map = array (
			1 => "=", // asciidoc, restuctured text, and markdown compatible
			2 => "-", // asciidoc, restuctured text, and markdown compatible
			//3 => "~", // asciidoc only
			//4 => "^", // asciidoc only
			//5 => "+", // asciidoc only
		);

		preg_match_all( "/^([#]+)\s?+(.*)$/m", $content, $m );

		if ( ! empty( $m ) && isset( $m[0] ) && ! empty( $m[0] ) ) {
			foreach ( $m[0] as $cntr => $match ) {
				$depth = strlen( trim( $m[1][$cntr] ) );

				if ( $depth > 2 )
					continue;

				$title = trim( $m[2][$cntr] );
				$u = str_repeat( $map[ $depth ], mb_strlen( $title ) );
				$content = str_replace ( $match, "{$title}\n{$u}", $content );
			}
		}

		return $content;
	}

	/**
	 *
	 */
	public static function template_vars ( &$post = null ) {
		$r = array();
		$post = static::fix_post($post);

		if ($post === false)
			return $r;

		if ( $cached = wp_cache_get ( $post->ID, __CLASS__ . __FUNCTION__ ) )
			return $cached;


		$r = array (
			'id' => $post->ID,
			'url' => get_permalink( $post->ID ),
			'title' => trim(get_the_title( $post->ID )),
			'shorturl' => wp_get_shortlink( $post->ID ),
			'thumbnail' => static::post_thumbnail ($post),
			'content' => static::get_the_content($post, 'clean'),
			'excerpt' => static::get_the_excerpt($post),
			'published' => strtotime( $post->post_date_gmt ),
			'modified' => strtotime( $post->post_modified_gmt ),
			'tags' => static::post_get_tags_array($post),
			'format' => static::post_format( $post ),
			//'show_author' => $show_author,
			'singular' => is_singular(),
			'uuid' => hash ( 'md5', (int)$post->ID + (int) get_post_time('U', true, $post->ID ) ),
			'author' => pmlnr_author::template_vars( $post->post_author ),
		);

		wp_cache_set ( $post->ID, $r, __CLASS__ . __FUNCTION__, static::expire );

		return $r;
	}
}
