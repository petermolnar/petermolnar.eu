<?php
/*
Template Name: Search Page
*/

	global $petermolnareu_theme;
	global $query_string;
	global $post_format;
	global $category_meta;

	$category_meta = array (
		'custom-template' => 'default',
		'posts-per-page' => 8,
		'show-sidebar' => 0,
		'show-pagination' => 1,
		'order-by' => 'date',
		'sidebar-entries' => 1
	);

	$posts_per_page = $category_meta['posts-per-page'];
	query_posts( $query_string . '&posts_per_page=' . $category_meta['posts-per-page'] );

	get_header();
	?>
	<h2><?php _e( "Displaying results for: ", $petermolnareu_theme->theme_constant); echo '"'. get_query_var('s'). '"'; ?></h2>
	<section class="searchresults">
	<?php
		if ( have_posts() ) {
			while ( have_posts() ) {
				the_post();
				get_template_part('template', 'search');
			}
		}
	?></section><?php

	if( function_exists('wp_paginate') && !empty( $category_meta['show-pagination'] ) && $category_meta['show-pagination'] == 1 )
		wp_paginate();

	get_footer();
?>
