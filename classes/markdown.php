<?php

class pmlnr_markdown extends pmlnr_base {

	/**
	 *
	 */
	public function __construct ( ) {
		add_action( 'init', array( &$this, 'init'));
	}

	/* init function, should be used in the theme init loop */
	public function init (  ) {

		// replace img inserts with Markdown
		add_filter( 'image_send_to_editor', array( 'pmlnr_markdown', 'media_string_html2md'), 10 );

		// markdown
		add_filter( 'the_content', array( 'pmlnr_markdown', 'parsedown'), 8, 1 );
		add_filter( 'the_excerpt', array( 'pmlnr_markdown', 'parsedown'), 8, 1 );

		// press this
		add_filter ('press_this_suggested_content', array ( 'pmlnr_markdown', 'html2markdown' ), 1);

		// convert comment HTML
		add_filter ( 'wp_webmention_again_comment_content', array ( 'pmlnr_markdown', 'html2markdown' ) );
	}

	/**
	 * replace HTML img insert with Markdown Extra syntax
	 */
	public static function media_string_html2md( $str ) {

		if ( !strstr ( $str, '<img' ) )
			return $str;

		$hash = sha1($str);
		if ( $cached = wp_cache_get ( $hash, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$src = static::preg_value ( $str, '/src="([^"]+)"/' );
		$title = static::preg_value ( $str, '/title="([^"]+)"/' );
		$alt = static::preg_value ( $str, '/alt="([^"]+)"/' );
		if ( empty ( $alt ) && !empty ( $title ) ) $alt = $title;
		$wpid = static::preg_value ( $str, '/wp-image-(\d*)/' );
		$src = static::preg_value ( $str, '/src="([^"]+)"/' );
		$cl = static::preg_value ( $str, '/class="([^"]+)?(align(left|right|center))([^"]+)?"/', 2 );
		if (!empty($cl)) $cl = ' .' . $cl;

		if (!empty($title)) $title = ' ' . $title;
		if (!empty($wpid)) $imgid = '#img-' . $wpid;


		$img = sprintf ('![%s](%s%s){%s%s}', $alt, $src, $title, $imgid, $cl);

		wp_cache_set ( $hash, $img, __CLASS__ . __FUNCTION__, static::expire );
		return $img;
	}

	/**
	 * parsedown
	 */
	public static function parsedown ( $md ) {

		if ( empty ( $md ) )
			return false;

		$hash = sha1($md);
		if ( $cached = wp_cache_get ( $hash, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$parsedown = new ParsedownExtra();
		$parsedown->setBreaksEnabled(true);
		$parsedown->setUrlsLinked(true);
		$r = $parsedown->text ( $md );

		wp_cache_set ( $hash, $r, __CLASS__ . __FUNCTION__, static::expire );

		return $r;
	}


	public static function html2markdown ( $content ) {

		if (empty($content))
			return false;

		$hash = sha1($content);
		if ( $cached = wp_cache_get ( $hash, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$content = preg_replace('#\s(id|class|style|rel|data|content)="[^"]+"#', '', $content);
		/*
		 * Credits to @gnarf
		 * https://stackoverflow.com/questions/3026096/remove-all-attributes-from-an-html-tag
		 *
			/              # Start Pattern
			 <             # Match '<' at beginning of tags
			 (             # Start Capture Group $1 - Tag Name
			  [a-z]         # Match 'a' through 'z'
			  [a-z0-9]*     # Match 'a' through 'z' or '0' through '9' zero or more times
			 )             # End Capture Group
			 [^>]*?        # Match anything other than '>', Zero or More times, not-greedy (wont eat the /)
			 (\/?)         # Capture Group $2 - '/' if it is there
			 >             # Match '>'
			/i            # End Pattern - Case Insensitive
		 *
		 */
		//$content = preg_replace("/<([a-z][a-z0-9]*)[^>]*?(\/?)>/i",'<$1$2>', $content);

		/**
		 * replace <pre>, <code>, [code] and [cc]
		 */

		if ( strstr( $content, '<pre><code>' )) {
			$s = array ( '<pre><code>', '</code></pre>' );
			$r = array ( "```\n", "\n```" );
			$content = str_replace ( $s, $r, $content );
		}

		if ( strstr( $content, '<pre>' )) {
			$s = array ( '</pre><pre>', '</pre>' );
			$r = array ( "```\n", "\n```" );
			$content = str_replace ( $s, $r, $content );
		}

		if ( strstr( $content, '</code>' )) {
			$s = array ( '<code>', '</code>' );
			$r = array ( "```\n", "\n```" );
			$content = str_replace ( $s, $r, $content );
		}


		// straigtforward formatting: html to markdown
		$s = array ( '<tt>', '</tt>', '<bold>', '</bold>', '<strong>', '</strong>', '<em>', '</em>', '<i>', '</i>' );
		$r = array ( '`', '`', '**', '**', '**', '**', '*', '*', '*', '*' );
		$content = str_replace ( $s, $r, $content );

		$s = array ( '<p>','</p>', '<br />', '<br>', '<h1>', '</h1>', '<h2>', '</h2>','<h3>', '</h3>','<h4>', '</h4>','<h5>', '</h5>','<h6>', '</h6>', '<blockquote>', '</blockquote>' );
		$r = array ( "\n", "\n", "\n", "\n", '#', '', '## ', '', '### ', '', '#### ', '', '##### ', '', '###### ', '', '> ', '' );
		$content = str_replace ( $s, $r, $content );

		preg_match_all('/<ul>(.*?)< \/ul>/s', $content, $uls);
		if ( !empty ( $uls[0] ) ) {
			foreach ( $uls[0] as $to_replace ) {
				$to_clean = preg_replace ( '/\t<li>/', '- ', $to_replace );
				$s = array ( '</li>', '</ul><ul>', '</ul>', '<li>' );
				$r = array ( '', '', '', '- ' );
				$to_clean = str_replace ( $s, $r, $to_clean );
				$content = str_replace ( $to_replace, $to_clean, $content );
			}
		}

		preg_match_all('/<ol>(.*?)< \/ol>/s', $content, $ols);
		if ( !empty ( $ols[0] ) ) {
			foreach ( $ols[0] as $to_replace ) {
				$to_clean = $to_replace;
				preg_match_all('/<li>(.*?)< \/li>/s', $to_clean, $lis);
				foreach ( $lis[0] as $id=>$lis_replace ) {
						$liline = $lis_replace;
						$lis_replace = preg_replace ( '/\t</li><li>/', $id+1 . '. ', $lis_replace );
						$lis_replace = preg_replace ( '/</li><li>/', $id+1 . '. ', $lis_replace );
						$to_clean = str_replace ( $liline , $lis_replace, $to_clean );
				}

				$content = str_replace ( $to_replace, $to_clean, $content );
			}
		}

		$s = array ( '<ol>', '</ol>', '</li>' );
		$r = array ( '', '', '' );
		$content = str_replace ( $s, $r, $content );

		preg_match_all('/<dl>(.*?)< \/dl>/s', $content, $dl);
		if ( !empty ( $dl[0] ) ) {
			foreach ( $dl[0] as $to_replace ) {
				$to_clean = $to_replace;
				preg_match_all('/<dt>(.*?)< \/dt>/s', $to_clean, $dts);
				preg_match_all('/<dd>(.*?)< \/dd>/s', $to_clean, $dds);

				foreach ( $dts[0] as $id=>$dt ) {
						$o_dt = $dt;
						$o_dd = $dds[0][$id];

						$dt =  str_replace ( array('<dt>', '</dt>' ), array( "" , "\n" ), $dt );

				}
			}
		}

		$c = str_get_html ( $content );
		if (!$c)
			return $content;

		// find links
		foreach($c->find('a') as $a) {
			$out = $href = $title = $txt = '';
			$href = $a->href;
			$title = $a->title;
			$txt = $a->innertext;

			if ( !empty( $txt ) && !empty ( $href ) ) {
				if (!empty($title))
					$out = '['. $txt .' '.$title.']('. $href .')';
				else
					$out = '['. $txt .']('. $href .')';
				$content = str_replace ( $a->outertext, $out, $content );
			}
		}

		// clean up images:
		foreach($c->find('img') as $img) {
			$src = $alt = $title = $cl = $out = false;

			$src = $img->src;
			$alt = $img->alt;
			$title = $img->title;

			if ( empty($alt) && !empty($title) ) $alt = $title;
			if ( empty($alt) ) $alt = $src;

			$img = '!['.$alt.']('. $src;
			if ( !empty($title) ) $img .= ' '. $title;
			$img .= ')';

			$content = str_replace ( $img->outertext, $img, $content );
		}

		// fix potential hashtag issues
		$content = preg_replace ( '/^#/mi', '\#', $content );

		wp_cache_set ( $hash, $content, __CLASS__ . __FUNCTION__, static::expire );

		return $content;
	}

}
