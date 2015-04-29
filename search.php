<?php
/*
Template Name: Search Page
*/

global $is_search;
global $query_string;
$is_search = true;

get_header();
?>
<section class="content-body content-light">
	<h1><?php _e( "Displaying results for:" ); echo '"'. get_query_var('s'). '"'; ?></h1>
	<?php	if ( have_posts() ): ?>
		<?php while (have_posts()) : ?>

		<?php
			the_post();
			get_template_part( '/partials/element-journal' );
		?>
		<?php endwhile; ?>
	<?php endif; ?>
	<?php petermolnareu::paginate(); ?>
</section>

<?php get_footer(); ?>
