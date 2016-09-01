<?php


if ( is_home() || is_front_page()) {
	global $wp_query;
	global $query_string;
	// bookmark: 262
	// reply: 7167
	$query_string = $query_string . '&cat=-262,-7167';
	query_posts( $query_string );
}

$twigvars['site'] = pmlnr_site::template_vars();
$twigvars['taxonomy'] = pmlnr_archive::template_vars();
$twigvars['posts'] = array();

if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();
		$tmpl_vars = pmlnr_post::template_vars( $post );
		$tmpl_vars['singular'] = false;
		$twigvars['posts'][] = $tmpl_vars;
	}
}

echo PETERMOLNAR\twig( 'archive.html', $twigvars );
