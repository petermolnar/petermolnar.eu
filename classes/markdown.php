<?php

namespace PETERMOLNAR\MARKDOWN;

define ( 'PETERMOLNAR\MARKDOWN\CACHE', \WP_CONTENT_DIR . DIRECTORY_SEPARATOR
	. 'cache' . DIRECTORY_SEPARATOR . 'pandoc' . DIRECTORY_SEPARATOR );

\add_action( 'init', 'PETERMOLNAR\MARKDOWN\init' );

	/* init function, should be used in the theme init loop */
function init (  ) {

	add_filter( 'image_send_to_editor', 'PETERMOLNAR\MARKDOWN\media_string_html2md', 10 );
	add_filter( 'the_content', 'PETERMOLNAR\MARKDOWN\pandoc_md2html', 8, 1 );
	add_filter( 'the_excerpt', 'PETERMOLNAR\MARKDOWN\pandoc_md2html', 8, 1 );

}

/**
 * replace HTML img insert with Markdown Extra syntax
 */
function media_string_html2md( $str ) {

	if ( !strstr ( $str, '<img' ) )
		return $str;

	$src = preg_value ( $str, '/src="([^"]+)"/' );
	$title = preg_value ( $str, '/title="([^"]+)"/' );
	$alt = preg_value ( $str, '/alt="([^"]+)"/' );
	if ( empty ( $alt ) && !empty ( $title ) ) $alt = $title;
	$wpid = preg_value ( $str, '/wp-image-(\d*)/' );
	$src = preg_value ( $str, '/src="([^"]+)"/' );
	$cl = preg_value ( $str, '/class="([^"]+)?(align(left|right|center))([^"]+)?"/', 2 );
		if (!empty($cl)) $cl = ' .' . $cl;

	if (!empty($title)) $title = ' ' . $title;
	if (!empty($wpid)) $imgid = '#img-' . $wpid;

	if ( ! preg_match( '/https?:\/\//', $src ) )
		$src = site_url( $src );

		$img = sprintf ('![%s](%s%s){%s%s}', $alt, $src, $title, $imgid, $cl);

	return $img;
}

/**
 * parsedown
 */
function parsedown ( $md ) {

	global $post;

	if ( empty ( $md ) )
		return false;

	$parsedown = new \ParsedownExtra();
	$parsedown->setBreaksEnabled(true);
	$parsedown->setUrlsLinked(true);
	$r = $parsedown->text ( $md );

	// match cite
	preg_match_all( '/<blockquote>(?:.*?)\s(?:\\-|–|&#8211;)(.*?)(?:<\/p>\s?)<\/blockquote>/s', $r, $matches );

	if ( !empty($matches) && isset( $matches[1] ) && isset( $matches[1][0] ) ) {
		$r = str_replace ( $matches[1][0], "<cite>{$matches[1][0]}</cite>", $r );
	}

	return $r;
}

/**
 * parsedown
 */
function pandoc_md2html ( $md ) {

	if ( empty ( $md ) )
		return false;

	$hash = sha1($md);

	if ( ! is_dir( CACHE ) )
		mkdir( CACHE );

	if ( is_file( CACHE . $hash ))
		return file_get_contents( CACHE . $hash );

	$f = tempnam( "/run/shm", "md_" );
	file_put_contents( $f, $md );
	$extras = array (
		'backtick_code_blocks',
		'auto_identifiers',
		'fenced_code_attributes',
		'definition_lists',
		'grid_tables',
		'pipe_tables',
		'strikeout',
		'superscript',
		'subscript',
		'markdown_in_html_blocks',
		'shortcut_reference_links',
		'autolink_bare_uris',
		'raw_html',
		'link_attributes',
		'header_attributes',
	);

	$cmd =
		"/usr/bin/pandoc -p -f markdown+". join( "+", $extras )
		. " -t html -o- {$f}";
	exec( $cmd, $r, $retval);
	$r = join( "\n", $r );

	unlink($f);

	file_put_contents( CACHE . $hash, $r );

	return $r;
}


/**
 *
 */
function pandoc_html2md ( $html ) {

	if ( empty ( $html ) )
		return false;

	$f = tempnam( "/run/shm", "html_" );
	file_put_contents( $f, $html );

	$extras = array (
		'backtick_code_blocks',
		'auto_identifiers',
		'fenced_code_attributes',
		'definition_lists',
		'grid_tables',
		'pipe_tables',
		'strikeout',
		'superscript',
		'subscript',
		'markdown_in_html_blocks',
		'shortcut_reference_links',
		'autolink_bare_uris',
		'raw_html',
	);

	$cmd =
		"/usr/bin/pandoc -p -f html -t markdown+". join( "+", $extras )
		. " -o- {$f}";
	exec( $cmd, $r, $retval);
	$r = join( "\n", $r );

	unlink($f);

	return $r;
}

///**
 //*
 //*/
//function html2markdown ( $content ) {

	//if (empty($content))
		//return false;

	//$hash = sha1($content);
	//if ( $cached = wp_cache_get ( $hash, __CLASS__ . __FUNCTION__ ) )
		//return $cached;

	//$content = preg_replace('#\s(id|class|style|rel|data|content)="[^"]+"#', '', $content);
	///*
	 //* Credits to @gnarf
	 //* https://stackoverflow.com/questions/3026096/remove-all-attributes-from-an-html-tag
	 //*
		///              # Start Pattern
		 //<             # Match '<' at beginning of tags
		 //(             # Start Capture Group $1 - Tag Name
		  //[a-z]         # Match 'a' through 'z'
		  //[a-z0-9]*     # Match 'a' through 'z' or '0' through '9' zero or more times
		 //)             # End Capture Group
		 //[^>]*?        # Match anything other than '>', Zero or More times, not-greedy (wont eat the /)
		 //(\/?)         # Capture Group $2 - '/' if it is there
		 //>             # Match '>'
		///i            # End Pattern - Case Insensitive
	 //*
	 //*/
	////$content = preg_replace("/<([a-z][a-z0-9]*)[^>]*?(\/?)>/i",'<$1$2>', $content);

	///**
	 //* replace <pre>, <code>, [code] and [cc]
	 //*/

	//if ( strstr( $content, '<pre><code>' )) {
		//$s = array ( '<pre><code>', '</code></pre>' );
		//$r = array ( "```\n", "\n```" );
		//$content = str_replace ( $s, $r, $content );
	//}

	//if ( strstr( $content, '<pre>' )) {
		//$s = array ( '</pre><pre>', '</pre>' );
		//$r = array ( "```\n", "\n```" );
		//$content = str_replace ( $s, $r, $content );
	//}

	//if ( strstr( $content, '</code>' )) {
		//$s = array ( '<code>', '</code>' );
		//$r = array ( "```\n", "\n```" );
		//$content = str_replace ( $s, $r, $content );
	//}


	//// straigtforward formatting: html to markdown
	//$s = array ( '<tt>', '</tt>', '<bold>', '</bold>', '<strong>', '</strong>', '<em>', '</em>', '<i>', '</i>' );
	//$r = array ( '`', '`', '**', '**', '**', '**', '*', '*', '*', '*' );
	//$content = str_replace ( $s, $r, $content );

	//$s = array ( '<p>','</p>', '<br />', '<br>', '<h1>', '</h1>', '<h2>', '</h2>','<h3>', '</h3>','<h4>', '</h4>','<h5>', '</h5>','<h6>', '</h6>', '<blockquote>', '</blockquote>' );
	//$r = array ( "\n", "\n", "\n", "\n", '#', '', '## ', '', '### ', '', '#### ', '', '##### ', '', '###### ', '', '> ', '' );
	//$content = str_replace ( $s, $r, $content );

	//preg_match_all('/<ul>(.*?)< \/ul>/s', $content, $uls);
	//if ( !empty ( $uls[0] ) ) {
		//foreach ( $uls[0] as $to_replace ) {
			//$to_clean = preg_replace ( '/\t<li>/', '- ', $to_replace );
			//$s = array ( '</li>', '</ul><ul>', '</ul>', '<li>' );
			//$r = array ( '', '', '', '- ' );
			//$to_clean = str_replace ( $s, $r, $to_clean );
			//$content = str_replace ( $to_replace, $to_clean, $content );
		//}
	//}

	//preg_match_all('/<ol>(.*?)< \/ol>/s', $content, $ols);
	//if ( !empty ( $ols[0] ) ) {
		//foreach ( $ols[0] as $to_replace ) {
			//$to_clean = $to_replace;
			//preg_match_all('/<li>(.*?)< \/li>/s', $to_clean, $lis);
			//foreach ( $lis[0] as $id=>$lis_replace ) {
					//$liline = $lis_replace;
					//$lis_replace = preg_replace ( '/\t</li><li>/', $id+1 . '. ', $lis_replace );
					//$lis_replace = preg_replace ( '/</li><li>/', $id+1 . '. ', $lis_replace );
					//$to_clean = str_replace ( $liline , $lis_replace, $to_clean );
			//}

			//$content = str_replace ( $to_replace, $to_clean, $content );
		//}
	//}

	//$s = array ( '<ol>', '</ol>', '</li>' );
	//$r = array ( '', '', '' );
	//$content = str_replace ( $s, $r, $content );

	//preg_match_all('/<dl>(.*?)< \/dl>/s', $content, $dl);
	//if ( !empty ( $dl[0] ) ) {
		//foreach ( $dl[0] as $to_replace ) {
			//$to_clean = $to_replace;
			//preg_match_all('/<dt>(.*?)< \/dt>/s', $to_clean, $dts);
			//preg_match_all('/<dd>(.*?)< \/dd>/s', $to_clean, $dds);

			//foreach ( $dts[0] as $id=>$dt ) {
					//$o_dt = $dt;
					//$o_dd = $dds[0][$id];

					//$dt =  str_replace ( array('<dt>', '</dt>' ), array( "" , "\n" ), $dt );

			//}
		//}
	//}

	//$c = str_get_html ( $content );
	//if (!$c)
		//return $content;

	//// find links
	//foreach($c->find('a') as $a) {
		//$out = $href = $title = $txt = '';
		//$href = $a->href;
		//$title = $a->title;
		//$txt = $a->innertext;

		//if ( !empty( $txt ) && !empty ( $href ) ) {
			//if (!empty($title))
				//$out = '['. $txt .' '.$title.']('. $href .')';
			//else
				//$out = '['. $txt .']('. $href .')';
			//$content = str_replace ( $a->outertext, $out, $content );
		//}
	//}

	//// clean up images:
	//foreach($c->find('img') as $img) {
		//$src = $alt = $title = $cl = $out = false;

		//$src = $img->src;
		//$alt = $img->alt;
		//$title = $img->title;

		//if ( empty($alt) && !empty($title) ) $alt = $title;
		//if ( empty($alt) ) $alt = $src;

		//$img = '!['.$alt.']('. $src;
		//if ( !empty($title) ) $img .= ' '. $title;
		//$img .= ')';

		//$content = str_replace ( $img->outertext, $img, $content );
	//}

	//// fix potential hashtag issues
	//$content = preg_replace ( '/^#/mi', '\#', $content );

	//wp_cache_set ( $hash, $content, __CLASS__ . __FUNCTION__, static::expire );

	//return $content;
//}

/**
 *
 */
function preg_value ( $string, $pattern, $index = 1 ) {
	preg_match( $pattern, $string, $results );
	if ( isset ( $results[ $index ] ) && !empty ( $results [ $index ] ) )
		return $results [ $index ];
	else
		return false;
}

