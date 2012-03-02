<?php
/*
Template Name: text-page
*/
?>
<?php get_header(); ?>

		<div id="post-single-content">
		<?php the_post(); ?>
			<?php the_content(); ?>
		</div>

<?php get_footer(); ?>