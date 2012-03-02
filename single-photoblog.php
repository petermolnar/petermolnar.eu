
		<div id="post-photoblog-content">
			<?php add_filter('the_content', 'lightbox_filter'); ?>
			<div id="post-<?php the_ID(); ?>" class="single-entry">
				<h1><a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>"><?php the_title(); ?></a></h1>
				<!--<span class="published"><abbr class="published-time" title="<?php the_time( get_option('date_format') .' - '. get_option('time_format') ); ?>"><?php the_time( get_option('date_format') ); ?></abbr></span>-->
				<?php the_content(); ?>
			</div>
			<div id="share">
				<?php wp_share ( get_permalink() , wp_title( '', false ) ); ?>
			</div>
			<div class="clear">&nbsp;</div>
			<?php comments_template( ); ?>
			<div class="photoblog-navigation">
				<div class="link left"><?php	previous_post_link( '&laquo; %link' , '%title' , true ); ?></div>
				<div class="link right"><?php	next_post_link( '%link &raquo;' , '%title' , true ); ?></div>
			</div>
		</div>
