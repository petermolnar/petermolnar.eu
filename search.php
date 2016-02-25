<?php
/*
Template Name: Search Page
*/

global $is_search;
global $query_string;
$is_search = true;


$twigvars['site'] = pmlnr_site::template_vars();
$twigvars['site']['is_search'] = true;
$twigvars['posts'] = array();
$twigvars['site']['page_title'] = '<h1>' . __( "Displaying results for:" ) . ' "' . get_query_var('s') . '"' . '</h1>';

	if ( have_posts() ) {
		while ( have_posts() ) {
			the_post();

			$post_id = get_the_ID();
			$tmpl_vars = pmlnr_post::template_vars( $post );
			$twigvars['posts'][] = $tmpl_vars;
		}
	}

$twig = $petermolnareu_theme->twig->loadTemplate('archive.html');
$r = $twig->render($twigvars);
echo petermolnareu::maybe_tidy ( $r );
