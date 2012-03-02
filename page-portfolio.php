<?php
/*
Template Name: portfolio-page
*/
?>
<?php get_header(); ?>

<?php the_post(); ?>

<?php
	/* portfolio parent page */
	if ($post->ID == 292)
	{
		wp_reset_query();
		query_posts( 'page=296' );
		the_post();
	}
?>



	<?php the_content(); ?>
	<div class="sidebar">
		<div id="portfolio-menu">
			<?php wp_nav_menu( array( 'container' => '' , 'theme_location' => 'portfolio'  ) ); ?>
		</div>
		<div id="portfolio-description">
			<?php $description = get_post_custom_values('description'); ?>
			<?php if (is_array($description)) print array_pop($description); ?>
		</div>
	</div>
	<div class="clear">&nbsp;</div>

<?php get_footer(); ?>