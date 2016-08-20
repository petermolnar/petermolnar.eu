<?php

$twigvars['site'] = pmlnr_site::template_vars( );
$twigvars['taxonomy'] = pmlnr_archive::template_vars( $query_string . '&posts_per_page=42' );
$twigvars['posts'] = array();

if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();

		if (!has_post_thumbnail($post))
			continue;

		$thid = get_post_thumbnail_id( $post->ID );
		$src = wp_get_attachment_image_src ($thid, 'adaptive_3');
		$s = site_url( $src[0] );
		$target = wp_get_attachment_image_src ($thid, 'large');
		$t = site_url( $target[0] );

		$tile = array (
			'target' => $t,
			'img' => $s,
			'alt' => htmlspecialchars( strip_tags( $post->post_content ) ),
			'title' => htmlspecialchars( strip_tags( $post->post_title ) ),
		);

		array_push( $twigvars['posts'], $tile );

	}
}

echo PETERMOLNAR\twig( 'gallery.html', $twigvars );
