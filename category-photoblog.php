<?php the_post (); ?>
<?php $permalink = get_permalink( $post->ID ); ?>
<?php wp_redirect( $permalink ); ?>


<?php get_header(); ?>

<?php $category = array_pop( get_the_category() ); ?>
<?php $template = TEMPLATEPATH . '/single-' . $category->slug . '.php'; ?>

	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<?php
				if (file_exists($template))
					include($template);
			?>
		<?php endwhile; ?>
	<?php endif; ?>

<?php  get_footer(); ?>