<?php get_header(); ?>

<section class="content-body content-light">

<?php

	if ( have_posts() ) {
		while ( have_posts() ) {
			the_post();
			$categories = get_the_category(get_the_ID());
			$category = ( $categories ) ? array_pop($categories) : null;

			if ( isset($category->slug) && !empty($category->slug) && file_exists( dirname(__FILE__) . '/partials/element-' . $category->slug . '.php' ))
				get_template_part( '/partials/element-' . $category->slug );
			else
				get_template_part( '/partials/element-journal' );
		}
	}

	petermolnareu::paginate();

?>
</section>

<?php
get_footer();
