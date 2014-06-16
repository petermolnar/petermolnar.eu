<?php
	global $petermolnareu_theme;
	global $query_string;
	global $post_format;
	global $category_meta;
	global $category;
	$_query_string = $query_string;

	$cat = get_query_var('cat');

	if ( is_home() ) {
		$query_string .= '&cat=5';
		$cat = 5;
	}

	if ( !empty( $cat ) ) {
		/* get category */
		$category = get_category( $cat );

		$category_meta = array();
		switch ( $category->slug ) {
			case 'blips':
				$category_meta = array (
					'custom-template' => 'status',
					'posts-per-page' => 18,
					'show-sidebar' => 0,
					'columns' => 1,
					'siblings' => false,
					'show-pagination' => 1,
					'sidebar-entries' => 0,
				);
				break;
			case 'photoblog':
				$category_meta = array (
					'custom-template' => 'gallery',
					'posts-per-page' => 6,
					'show-sidebar' => 0,
					'show-pagination' => 1,
					'columns' => 0,
					'siblings' => true,
					'sidebar-entries' => 0,
				);
				break;
			case 'portfolio':
				$category_meta = array (
					'custom-template' => 'gallery',
					'posts-per-page' => -1,
					'show-sidebar' => 0,
					'show-pagination' => 0,
					'order-by' => 'modified',
					'columns' => 0,
					'siblings' => false,
				);
				break;
			/*
			case 'journal':
				$category_meta = array (
					'custom-template' => 'default',
					'posts-per-page' => 4,
					'show-sidebar' => 0,
					'show-pagination' => 1,
					'order-by' => 'date',
					'sidebar-entries' => 12,
					'columns' => 0,
					'siblings' => true,
				);
				break;
			*/
			default:
				$category_meta = array (
					'custom-template' => 'default',
					'posts-per-page' => 4,
					'show-sidebar' => 1,
					'show-pagination' => 1,
					'order-by' => 'date',
					'sidebar-entries' => 12,
					'columns' => 0,
					'siblings' => false,
				);
		}

		/* post per page "feature" fix */
		$posts_per_page = $category_meta['posts-per-page'] + $category_meta['sidebar-entries'];

		$_query_string = $query_string . '&posts_per_page=' . $category_meta['posts-per-page'] . '&order=DESC&orderby=' . $category_meta['order-by'];

	}

	$tag = get_query_var('tag');
	if ( !empty( $tag ) ) {
		$_query_string = $query_string . '&posts_per_page=-1';
	}

	query_posts( $_query_string );

	get_header();

	$is_single = is_singular();

	if ( $is_single ) {
		the_post();
		//get_template_part('article', 'header');
		//get_template_part('article', 'body');
		//get_template_part('article', 'footer');
		get_template_part('template', 'single');
	}
	/* not singular */
	else {

		$sectionclass = $category->slug . "-postlist";
		if ( $category_meta['show-sidebar'] == 1 )
			$sectionclass = " category-postlist";
		elseif ( $category_meta['columns'] == 1 )
			$sectionclass = " category-columns";
		?>
		<section class="<?php echo $sectionclass; ?>">
		<?php
			if ( have_posts() ) {
				while ( have_posts() ) {
					the_post();
					//get_template_part('article', 'header');
					//get_template_part('article', 'body');
					//get_template_part('article', 'footer');
					get_template_part('template', 'single');
				}
			}

		?></section><?php

		if ( $category_meta['show-sidebar'] == 1 ) { ?>
			<section class="sidebar">
				<?php
					$page = get_query_var('paged');

					if ( empty ( $page ) ) $page = 1;
					$pstart = $page * $category_meta['posts-per-page'];

					?><h3><?php _e ('Earlier posts:', $petermolnareu_theme->theme_constant ); ?></h3><?php
					echo $petermolnareu_theme->list_posts( $category, $category_meta['sidebar-entries'], $pstart ); ?>
			</section>

		<?php }

	}


	if( function_exists('wp_paginate') && !empty( $category_meta['show-pagination'] ) && $category_meta['show-pagination'] == 1 )
		wp_paginate();

	get_footer();
?>
