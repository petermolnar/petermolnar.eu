<?php
	global $petermolnareu_theme;
	global $query_string;
	global $post_format;
	global $category_meta;
	global $category;
	$_query_string = $query_string;

	$cat = get_query_var('cat');

	if ( is_home() ) {
		$query_string .= '&cat=341';
		$cat = 341;
	}

	if ( !empty( $cat ) ) {
		/* get category */
		$category = get_category( $cat );

		$category_meta = $petermolnareu_theme->category_meta( $category );

		/* post per page "feature" fix */
		$posts_per_page = $category_meta['posts-per-page'] + $category_meta['sidebar-entries'];
		$_query_string = $query_string . '&posts_per_page=' . $category_meta['posts-per-page'] . '&order=DESC&orderby=' . $category_meta['order-by'];

	}

	$tag = get_query_var('tag');
	if ( !empty( $tag ) ) {
		$_query_string = $query_string . '&posts_per_page=-1';
	}

	query_posts( $_query_string );
	$is_single = is_singular();

	get_header();

	if ( $is_single ) {
		the_post();
		get_template_part('template', 'single');
	}
	/* not singular */
	else {

		if ( $category_meta['custom-template'] == 'gallery'): ?>
		<section class="content-body content-dark"><div class="inner">
		<?php else: ?>
		<section class="content-body content-light"><div class="inner">
		<?php endif;

		$sectionclass = $category->slug . "-postlist";
		if ( $category_meta['show-sidebar'] == 1 )
			$sectionclass = " category-postlist";
		elseif ( $category_meta['columns'] == 1 )
			$sectionclass = " category-columns";
		?>
		<div class="<?php echo $sectionclass; ?>">
		<?php
			if ( have_posts() ) {
				while ( have_posts() ) {
					the_post();
					get_template_part('template', 'single');
				}
			}

		?></div><?php

		if ( $category_meta['show-sidebar'] == 1 ) { ?>
			<aside class="sidebar">
				<?php
					$page = get_query_var('paged');

					if ( empty ( $page ) ) $page = 1;
					$pstart = $page * $category_meta['posts-per-page'];

					?><h3><?php _e ('Earlier posts:', $petermolnareu_theme->theme_constant ); ?></h3><?php
					echo $petermolnareu_theme->list_posts( $category, $category_meta['sidebar-entries'], $pstart ); ?>
			</aside>

		<?php }

	}

	if( function_exists('wp_paginate') && !empty( $category_meta['show-pagination'] ) && $category_meta['show-pagination'] == 1 )
		wp_paginate();

	get_footer();
?>
