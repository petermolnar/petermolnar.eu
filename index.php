<?php
	define ('REDIRECT_TO_PORTFOLIO', get_option ('siteurl') . '/portfolio/through-a-lupe/' );
	$_query_string = $query_string;

	if ( is_home() || is_front_page() )
	{
		wp_redirect( REDIRECT_TO_PORTFOLIO );
		exit;
	}

	$cat = get_query_var('cat');
	if ( !empty( $cat ) ) :
		$show_pagination = false;
		$show_sidebar = false;
		//$posts_per_page = get_option('posts_per_page ');
		$posts_per_page = 10;
		$category = get_category( $cat );
		$orderby = 'date';
		$order = 'DESC';
		switch ($category->slug) :
			case 'portfolio':
					wp_redirect( REDIRECT_TO_PORTFOLIO );
					exit;
				break;
			case 'photoblog':
				$posts_per_page = 4;
				$show_pagination = true;
				break;
			case 'wordpress':
				$orderby = 'modified';
				$show_sidebar = true;
				break;
			default:
				$show_sidebar = true;
				break;
		endswitch;
		$_query_string = $query_string . '&posts_per_page=' . $posts_per_page . '&order=' . $order . '&orderby=' . $orderby;
	endif;

	get_header();
	query_posts( $_query_string );

	if ( $show_sidebar ) :
	?>
		<section class="category-postlist">
	<?php
	endif;

	if ( have_posts() ) :
		while ( have_posts() ) :
			get_template_part('singles');
		endwhile;
	endif;

	if ( $show_sidebar ) :
	?>
		</section>
		<section class="sidebar">
	<?php
		echo wp_list_posts( -1, $posts_per_page );
	?>
		</section>
	<?php
	endif;

	if( function_exists('wp_paginate') && $show_pagination )
		wp_paginate();

	get_footer();
?>
