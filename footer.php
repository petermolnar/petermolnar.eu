	</div>

	<?php if ((is_single() || is_page() ) && comments_open()) : ?>
	<div id="comments-content" class="container">
		<div class="padded">
			<?php do_action('fbc_display_login_button'); ?>
			<?php comments_template( ); ?>
		</div>
	</div>
	<?php endif; ?>

	<div id="footer" class="container">
		<div class="footer-supports"></div>

		<div class="footer-widget">
			<?php if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar('footer-widget') ) : ?><?php endif; ?>
		</div>

		<div class="footer-supports opacity50">
		</div>
	</div>

</body>
