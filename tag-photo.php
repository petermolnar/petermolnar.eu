<?php

define('HEIGHT', 300);

add_action( 'wp_enqueue_scripts', 'pmlnr_portfolio_scripts' );

function pmlnr_portfolio_scripts () {
	wp_enqueue_style( 'magnific-popup' );
	wp_enqueue_script ('magnific-popup');
	wp_enqueue_style( 'Justified-Gallery' );
	wp_enqueue_script ('Justified-Gallery');
}

global $query_string;
$posts_per_page = 24;
$_query_string = $query_string;
$_query_string = $query_string . '&posts_per_page=42';

query_posts( $_query_string );

$twigvars['site'] = pmlnr_site::template_vars();

$elements = array();
if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();

		if (!has_post_thumbnail($post))
			continue;

		$thid = get_post_thumbnail_id( $post->ID );
		$src = wp_get_attachment_image_src ($thid, 'adaptive_3');
		$s = site_url($src[0]);
		$target = wp_get_attachment_image_src ($thid, 'large');
		$t = site_url($target[0]);

		$post = pmlnr_post::template_vars($post);

		$tile = array (
			'target' => $t,
			'img' => $s,
			'alt' => htmlspecialchars(strip_tags($post['content'])),
			'title' => htmlspecialchars(strip_tags($post['title'])),
		);

		array_push( $elements, $tile );

	}

	$twigvars['elements'] = $elements;
}

$twig = $petermolnareu_theme->twig->loadTemplate('partial_gallery.html');
echo $twig->render($twigvars);