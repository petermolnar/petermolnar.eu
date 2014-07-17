<?php
/*
Template Name: Search Page
*/

	global $petermolnareu_theme;
	global $query_string;
	$category_meta = $petermolnareu_theme->category_meta();

	$posts_per_page = $category_meta['posts-per-page'];
	query_posts( $query_string . '&posts_per_page=' . $category_meta['posts-per-page'] );

	get_header();
	?>
	<section class="content-body content-<?php echo $category_meta['theme']; ?>">

	<h2><?php _e( "Displaying results for: ", $petermolnareu_theme->theme_constant); echo '"'. get_query_var('s'). '"'; ?></h2>
	<?php
		if ( have_posts() ) {
			while ( have_posts() ) {
				the_post();
				get_template_part('template', 'search');
			}
		}
	?></section>

	<?php

	if( function_exists('wp_paginate') && !empty( $category_meta['show-pagination'] ) && $category_meta['show-pagination'] == 1 )
		wp_paginate();

	get_footer();
?>
