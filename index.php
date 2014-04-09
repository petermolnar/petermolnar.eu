<?php
	define ('REDIRECT_TO_PORTFOLIO', get_option ('siteurl') . '/portfolio/through-a-lupe/' );
	define ('REDIRECT_TO_MAIN', get_option ('siteurl') . '/linux-tech-coding/' );

	global $petermolnareu_theme;
	global $query_string;
	global $post_format;
	global $category_meta;
	global $category;
	$_query_string = $query_string;
	//$posts_per_page = 10;

	$cat = get_query_var('cat');
	$category_meta = array();
	$meta_keys = array (
		'custom-template' => 'default',
		'posts-per-page' => 10,
		'show-sidebar' => 1,
		'show-pagination' => 1,
		'order-by' => 'date',
		'sidebar-entries' => 10
	);

	if ( !empty( $cat ) ) {
		/* get category */
		$category = get_category( $cat );

		/* portfolio should not be accessed directly, go to first page */
		if ($category->slug == 'portfolio' ) {
			wp_redirect( REDIRECT_TO_PORTFOLIO );
			exit;
		}

		foreach ( $meta_keys as $key=>$default ) {
			unset ($val);
			$val = get_field( $key, 'category_'. $category->term_id );

			if ( $val == 'yes' )
				$val = 1;
			elseif ( empty($val) )
				$val = $default;
			elseif ( $val === 0 || $val == 'no' || $val === '0' )
				$val = 0;
			elseif ( is_numeric($val) )
				$val = intval ( $val );

			$category_meta[ $key ] = $val;

		}

		if ( $category->slug == 'photoblog' && is_user_logged_in() )
			$category_meta['posts-per-page'] = 9;

		$_query_string = $query_string . '&posts_per_page=' . $category_meta['posts-per-page'] . '&order=DESC&orderby=' . $category_meta['order-by'];

	}

	$tag = get_query_var('tag');
	if ( !empty( $tag ) ) {
		$_query_string = $query_string . '&posts_per_page=-1';
	}

	get_header();
	query_posts( $_query_string );

	if ( $category_meta['show-sidebar'] == 1 ) { ?>
		<section class="category-postlist">
	<?php }

	if ( have_posts() ) {

		while ( have_posts() ) {
			the_post();

			$post_format = get_post_format();
			if ( $post_format === false )
				$post_format = get_post_type();

			$is_single = is_singular();
			if ( $is_single ) {
					//echo "<!-- SINGLE -->";
					switch ( $post_format ) {
						case 'page':
						case 'aside':
							get_template_part('template', 'page');
							break;
						case 'gallery':
							get_template_part('template', 'gallery');
							break;
						default:
							get_template_part('template', 'article');
				}
			}
			elseif ( !empty($category_meta['custom-template']) && $category_meta['custom-template'] != 'default'  ) {
					get_template_part('template', $category_meta['custom-template'] );
			}
			else {
				get_template_part('template', 'list');
			}
		}
	}

	if ( $category_meta['show-sidebar'] == 1 ) {?>
		</section>
		<section class="sidebar">
			<?php
				$page = get_query_var('paged');

				if ( empty ( $page ) ) $page = 1;
				$pstart = $page * $category_meta['posts-per-page'];
				echo $petermolnareu_theme->list_posts( $category, $category_meta['sidebar-entries'], $pstart ); ?>
		</section>
	<?php }

	if( function_exists('wp_paginate') && !empty( $category_meta['show-pagination'] ) && $category_meta['show-pagination'] == 1 )
		wp_paginate();

	get_footer();
?>
