<?php
	define ('REDIRECT_TO_PORTFOLIO', get_option ('siteurl') . '/portfolio/through-a-lupe/' );
	define ('REDIRECT_TO_MAIN', get_option ('siteurl') . '/linux-tech-coding/' );


	global $petermolnareu_theme;

	global $query_string;
	$_query_string = $query_string;
	$posts_per_page = 10;

	$cat = get_query_var('cat');
	$category_meta = array();
	$category_template = false;
	if ( !empty( $cat ) ) {
		/* get category */
		$category = get_category( $cat );

		if ($category->slug == 'portfolio' ) {
			wp_redirect( REDIRECT_TO_PORTFOLIO );
			exit;
		}

		/* meta of category */
		$category_meta = get_metadata ( 'taxonomy' , $category->term_id, '');

		foreach ($category_meta as $key => &$val)
			$val = @array_shift($val);

		/* show sidebar */
		$category_meta['show-sidebar'] = ( $category_meta['show-sidebar'] == 'yes' ) ? true: false;

		/* show pagination */
		$category_meta['show-pagination'] = ( $category_meta['show-pagination'] == 'yes' ) ? true: false;

		//$category_meta['sidebar-entries'] = empty ( $category_meta['sidebar-entries'] ) ? 6 : $category_meta['sidebar-entries'];

		/* category template, default false */
		$category_template = empty ( $category_meta['custom-template'] ) ? false :  $category_meta['custom-template'];

		/* posts per page, default 10 */
		$posts_per_page = $category_meta['posts-per-page'] = empty ( $category_meta['posts-per-page'] ) ? $posts_per_page : $category_meta['posts-per-page'];

		/* order by */
		$category_meta['order-by'] = empty ( $category_meta['order-by'] ) ? 'date' : $category_meta['order-by'];
		//$category_meta['order-by'] = 'date';

		$category_meta['custom-template'] = '';

		$_query_string = $query_string . '&posts_per_page=' . $posts_per_page . '&order=DESC&orderby=' . $category_meta['order-by'];

	}

	$tag = get_query_var('tag');
	if ( !empty( $tag ) ) {
		$posts_per_page = -1;
		$_query_string = $query_string . '&posts_per_page=' . $posts_per_page;
	}


	get_header();
	query_posts( $_query_string );

	if ( isset ( $category_meta['show-sidebar'] ) &&  $category_meta['show-sidebar'] == true ) { ?>
		<section class="category-postlist">
	<?php }

	if ( have_posts() ) {
		$post_template = false;
		while ( have_posts() ) {
			the_post();
			$format = get_post_format();
			if ( $format === false )
				$format = get_post_type();

			switch ( $format ) {
				case 'page':
					get_template_part('template', 'page');
					break;
				case 'gallery':
					get_template_part('template', 'portfolio');
					break;
				case 'image':
					get_template_part('template', 'photoblog');
					break;
				default:
					$category_additions = array(
						'class' => false,
						'time' => false,
						'more' => false,
						'share' => false,
						'tags' => false
					);

					if ( !isset( $category_meta['custom-template'] ) ) $category_meta['custom-template'] = '';
					switch ( $category_meta['custom-template'] ) {
						case '3col':
							$category_additions['class'] = ' grid33';
							break;
						case 'opensource':
							$category_additions['class'] = ' grid50';
							break;
						default:
							$category_additions = array(
								'class' => false,
								'time' => true,
								'more' => true,
								'share' => true,
								'tags' => true
							);
							break;
					}

					$is_single = is_single();
					if ( is_single() && $category_template == false )
						get_template_part('template', 'arcticle');
					elseif (  !empty($category_template) && $category_template != 'default'  )
						get_template_part('template', $category_template );
					else
						get_template_part('template', 'list');
					break;
			}

		}
	}

	if ( isset ( $category_meta['show-sidebar'] ) &&  $category_meta['show-sidebar'] == true ) { ?>
		</section>
		<section class="sidebar">
			<?php
				$page = get_query_var('paged');
				if ( empty ( $page ) ) $page = 1;
				$pstart = $page * $posts_per_page;
				echo $petermolnareu_theme->list_posts( $category, $category_meta['sidebar-entries'], $pstart ); ?>
		</section>
	<?php }

	if( function_exists('wp_paginate') && isset ( $category_meta['show-pagination'] ) && $category_meta['show-pagination'] == true )
		wp_paginate();

	get_footer();
?>
