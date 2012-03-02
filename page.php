<?php get_header(); ?>

		<div id="page-content">

		<?php if ( have_posts() ) : ?>
			<?php while ( have_posts() ) : the_post(); ?>
				<div id="post-<?php the_ID(); ?>" class="page">
					<?php the_content(); ?>
				</div>
			<?php endwhile; ?>
		<?php endif; ?>


			<div id="page-sidebar">
				<div id="page-menu">
					<?php wp_nav_menu( array( 'container' => '', 'menu' => 'portfolio' ) ); ?>
				</div>

				<div id="page-description">
					<?php $description = get_post_custom_values('description'); ?>
					<?php if (is_array($description)) print array_pop($description); ?>
				</div>
			</div>
			<div class="clear">&nbsp;</div>
		</div>


<?php get_footer(); ?>