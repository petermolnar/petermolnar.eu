<?php
/*
Template Name: portfolio-main
*/
?>
<?php get_header(); ?>

<?php the_post(); ?>

<?php if (is_front_page ()) : ?>
	<?php wp_reset_query(); ?>
	<?php query_posts( 'page=292' ); ?>
<?php endif; ?>

	<div class="content">
		<?php the_content(); ?>

		<div class="portfolio-sidebar">
			<div class="portfolio-menu">
				<?php wp_nav_menu( array( 'container' => '' , 'theme_location' => 'portfolio'  ) ); ?>
			</div>
			<div id="portfolio-description">
				<?php $description = get_post_custom_values('description'); ?>
				<?php if (is_array($description)) print array_pop($description); ?>
			</div>
		</div>
		<!--<div class="clear">&nbsp;</div>-->
	</div>

<?php get_footer(); ?>