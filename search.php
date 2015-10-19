<?php
/*
Template Name: Search Page
*/

global $is_search;
global $query_string;
$is_search = true;

get_header();
?>
<section class="content-body">
	<h1><?php _e( "Displaying results for:" ); echo '"'. get_query_var('s'). '"'; ?></h1>
	<?php
		if ( have_posts() ):
			while (have_posts()) :
				the_post();
				extract(pmlnr_post::template_vars( $post ), EXTR_PREFIX_ALL, 'post' );
				include(dirname(__FILE__) . '/partials/element-long.php' );
			endwhile;

		endif;

	include(dirname(__FILE__) . '/partials/paginate.php' );
	?>
</section>

<?php get_footer(); ?>
