<?php get_header(); ?>
	<div class="content-padder">
		<div class="post-content">
		<?php if ( have_posts() ) : ?>
			<?php while ( have_posts() ) : the_post(); ?>

			<div id="post-<?php the_ID(); ?>" class="entry">
				<h2 class="entry-title"><a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>"><?php the_title(); ?></a></h2>
				<div class="entry-header">
					<span class="published"><abbr class="published-time" title="<?php the_time( get_option('date_format') .' - '. get_option('time_format') ); ?>"><?php the_time( get_option('date_format') ); ?></abbr></span>
				</div>


				<?php if ( is_archive() || is_search() ) : ?>
					<div class="entry-summary">
						<?php the_excerpt(); ?>
						<a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>" class="single-entry-link">more <span class="meta-nav">&rarr;</span></a>
					</div>
				<?php else : ?>
					<div class="entry-content">
					<?php the_content( 'Continue reading <span class="meta-nav">&rarr;</span>' ); ?>
					<?php comments_template(); ?>
					</div>
				<?php endif; ?>

			</div>
			<?php endwhile; ?>
			<?php if(function_exists('wp_paginate')) {
				wp_paginate();
			} ?>

		<?php else : ?>

			<div id="post-not-found" class="entry">
				<h2 class="entry-title">Not Found</h2>
				<div class="entry-content">
					<p>Sorry, but you are looking for something that isn't here.</p>
					<?php //get_search_form();
						?>
				</div>
			</div>

		<?php endif; ?>
		</div>
	</div>

<?php get_sidebar(); ?>
<?php get_footer(); ?>