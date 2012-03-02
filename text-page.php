<?php
/*
Template Name: text-page
*/
?>
<?php get_header(); ?>

		<div id="post-single-content">

		<?php if ( have_posts() ) : ?>
			<?php while ( have_posts() ) : the_post(); ?>
				<div id="post-<?php the_ID(); ?>" class="page">
					<?php the_content(); ?>
				</div>
				<div class="clear">&nbsp;</div>
				<?php comments_template(); ?>
			<?php endwhile; ?>
		<?php endif; ?>
		</div>


<?php get_footer(); ?>