		<div id="post-sidebar">
			<div id="widget-area">
				<?php if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar('sidebar') ) : ?><?php endif; ?>
			</div>
			<div id="posts-of-current-category" class="widget">
				<?php print wp_list_posts(); ?>
			</div>
		</div>