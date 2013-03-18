<?php
	//define ('REDIRECT_TO_PORTFOLIO', get_option ('siteurl') . '/portfolio/through-a-lupe/' );
	//define ('REDIRECT_TO_MAIN', get_option ('siteurl') . '/linux-tech-coding/' );
	global $post_counter;
		$post_counter = 0;
	global $cat_template;
		$cat_template = false;
	$_query_string = $query_string;

	//if ( is_home() || is_front_page() )
	//{
	//	wp_redirect( REDIRECT_TO_MAIN );
	//	exit;
	//}

	$cat = get_query_var('cat');
	if ( !empty( $cat ) ) :
		$category = get_category( $cat );

		//if ($category->slug == 'portfolio')
		//{
		//	wp_redirect( REDIRECT_TO_PORTFOLIO );
		//	exit;
		//}

		/* meta of category */
		$category_meta = get_metadata ( 'taxonomy' , $category->term_id, '');

		foreach ($category_meta as $key => &$val)
			$val = @array_shift($val);

		/* show sidebar */
		$show_sidebar = ( $category_meta['show-sidebar'] == 'no' ) ? false : true;

		/* show pagination */
		$show_pagination = ( $category_meta['show-pagination'] == 'no' ) ? false : true;

		/* category template, default false */
		$cat_template = $category_meta['custom-template'];

		/* posts per page, default 10 */
		//$posts_per_page = get_option('posts_per_page ');
		$posts_per_page = $category_meta['posts-per-page'];
		if ( empty ($posts_per_page) )
			$posts_per_page = 10;

		/* order by */
		$orderby = $category_meta['order-by'];
		if ( empty( $orderby ) )
			$orderby = 'date';

		$order = 'DESC';
		$_query_string = $query_string . '&posts_per_page=' . $posts_per_page . '&order=' . $order . '&orderby=' . $orderby;
	endif;

	$tag = get_query_var('tag');
	if ( !empty( $tag ) ) :
		$posts_per_page = -1;
		$_query_string = $query_string . '&posts_per_page=' . $posts_per_page;
	endif;

	get_header();
	query_posts( $_query_string );

	if ( $show_sidebar ) :
	?>
		<section class="category-postlist">
	<?php
	endif;

	if ( have_posts() ) :
		$post_counter = 0;
		$post_template = false;
		while ( have_posts() ) :
			if ( is_user_logged_in() )
				get_template_part('singles');
			else
				get_template_part('singles');
			$post_counter++;
		endwhile;
	endif;

	if ( $show_sidebar ) :
	?>
		</section>
		<section class="sidebar">
			<?php echo wp_list_posts( -1, $posts_per_page ); ?>
		</section>
	<?php
	elseif ( $cat_template == 'all-older-bottom' ):
	?>
		<section class="footer-sidebar">
			<?php echo wp_list_posts( -1, $posts_per_page ); ?>
		</section>
	<?php
	endif;

	if( function_exists('wp_paginate') && $show_pagination )
		wp_paginate();

	get_footer();
?>
