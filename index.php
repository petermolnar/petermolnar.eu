<?php

	//define ('REDIRECT_TO_MAIN', get_option ('siteurl') . '/linux-tech-coding/' );

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

	$category_meta = array();

	$cmeta = array (
		'blips' => array (
			'custom-template' => 'status',
			'posts-per-page' => 18,
			'show-sidebar' => 0,
		),
		'photoblog' => array (
			'custom-template' => 'gallery',
			'posts-per-page' => 6,
			'show-sidebar' => 0,
			'show-pagination' => 1,
		),
		'portfolio' => array (
			'custom-template' => 'gallery',
			'posts-per-page' => -1,
			'show-sidebar' => 0,
			'show-pagination' => 0,
			'order-by' => 'modified',
		),
		'default' => array (
			'custom-template' => 'default',
			'posts-per-page' => 4,
			'show-sidebar' => 1,
			'show-pagination' => 1,
			'order-by' => 'date',
			'sidebar-entries' => 12
		)
	);

	if ( !empty( $cat ) ) {
		/* get category */
		$category = get_category( $cat );

		$category_meta = ( empty( $cmeta[ $category->slug ] ) ) ? $cmeta['default'] : array_merge ( $cmeta['default'], $cmeta [ $category->slug ] );

		/* post per page bugfix */
		$posts_per_page = $category_meta['posts-per-page'];

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
		get_template_part('template', 'singular');
	}
	/* not singular */
	else {
		$sectionclass = $category_meta['custom-template']. "-postlist";
		if ( $category_meta['show-sidebar'] == 1 )
			$sectionclass .= " category-postlist"
		?>
		<section class="<?php echo $sectionclass; ?>">
		<?php
			if ( have_posts() ) {
				while ( have_posts() ) {
					the_post();
					get_template_part('template', 'listelement');

				//	if ( is_user_logged_in()) {
				//
				//	}
				//	else {
				//	$post_format = get_post_format();
				//	if ( $post_format === false )
				//		$post_format = get_post_type();
				//
				//	if ( !empty($category_meta['custom-template']) && $category_meta['custom-template'] != 'default'  )
				//		get_template_part('template', $category_meta['custom-template'] );
				//	else
				//		get_template_part('template', 'list');
				//}
				}
			}

		?></section><?php

		if ( $category_meta['show-sidebar'] == 1 ) { ?>
			<section class="sidebar">
				<?php
					$page = get_query_var('paged');

					if ( empty ( $page ) ) $page = 1;
					$pstart = $page * $category_meta['posts-per-page'];
					echo $petermolnareu_theme->list_posts( $category, $category_meta['sidebar-entries'], $pstart ); ?>
			</section>

		<?php }

	}


	if( function_exists('wp_paginate') && !empty( $category_meta['show-pagination'] ) && $category_meta['show-pagination'] == 1 )
		wp_paginate();

	get_footer();
?>
