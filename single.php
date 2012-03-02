<?php get_header(); ?>

	<?php

	$category = array_pop( get_the_category() );
	$template = TEMPLATEPATH . '/single-' . $category->slug . '.php';
	$default = TEMPLATEPATH . '/single-default.php';

	the_post();

	if (file_exists( $template ))
	{
		include( $template );
	}
	else
	{
	?>

		<div id="post-single-content">
			<?php add_filter('the_content', 'lightbox_filter'); ?>
			<h1><a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>"><?php the_title(); ?></a></h1>
			<?php the_content(); ?>
			<div class="single-entry-footer">
				<span class="published"><abbr class="published-time" title="<?php the_time( get_option('date_format') .' - '. get_option('time_format') ); ?>"><?php the_time( get_option('date_format') ); ?></abbr></span>
			</div>

			<div id="share">
				<?php wp_share ( get_permalink() , wp_title( '', false ), $post->ID ); ?>
			</div>
			<div class="clear">&nbsp;</div>
		</div>
	<?php
	}
	?>

<?php get_footer(); ?>