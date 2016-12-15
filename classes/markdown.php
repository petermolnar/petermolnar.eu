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
 *
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
		'footnotes',
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
		'footnotes',
	);

	$cmd =
		"/usr/bin/pandoc -p -f html -t markdown+". join( "+", $extras )
		. " -o- {$f}";
	exec( $cmd, $r, $retval);
	$r = join( "\n", $r );

	unlink($f);

	return $r;
}

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

