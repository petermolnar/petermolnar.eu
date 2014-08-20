<?php
	global $petermolnareu_theme;
	global $query_string;
	global $post_format;
	global $category_meta;
	global $category;
	$_query_string = $query_string;
	$theme = 'dark';

	$cat = get_query_var('cat');
	$tag = get_query_var('tag');

	if ( is_home() ) {
		$posts_per_page = 10;
		$_query_string .= $query_string . '&posts_per_page=' . $posts_per_page;
		$category_meta = $petermolnareu_theme->category_meta( );
		$theme = $category_meta['theme'];
		//$query_string .= '&cat=341';
		//$cat = 341;
	}

	if ( !empty( $cat ) ) {
		/* get category */
		$category = get_category( $cat );

		$category_meta = $petermolnareu_theme->category_meta( $category );

		/* post per page "feature" fix */
		$posts_per_page = $category_meta['posts-per-page'] + $category_meta['sidebar-entries'];
		$_query_string = $query_string . '&posts_per_page=' . $category_meta['posts-per-page'] . '&order=DESC&orderby=' . $category_meta['order-by'];
		$theme = $category_meta['theme'];
	}

	if ( !empty( $tag ) ) {
		$_query_string = $query_string . '&posts_per_page=-1';
		$theme = 'light';
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
		?>
		<section class="content-body content-<?php echo $theme; ?>">
		<?php
		$sectionclass = $category->slug . "-postlist";
		if ( $category_meta['columns'] == 1 )
			$sectionclass .= " category-columns";
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
	}

	if( function_exists('wp_paginate') && !empty( $category_meta['show-pagination'] ) && $category_meta['show-pagination'] == 1 )
		wp_paginate();

	get_footer();
?>
