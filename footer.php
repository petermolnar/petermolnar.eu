	<div class="clear">&nbsp;</div>
	</div>

	<?php if ((is_single() || is_page() ) && comments_open()) : ?>
	<div class="comments-content container">
		<div class="content-padder">
			<?php do_action('fbc_display_login_button'); ?>
			<?php comments_template( ); ?>
		</div>
	</div>
	<?php endif; ?>

	<div id="footer" class="container">
		<div class="footer-supports"></div>

		<div class="footer-widget">
			Molnár Péter © 1999-<?php echo date('Y'); ?> <br />
			All rights reserved.

		</div>

		<div class="footer-supports opacity50">
		</div>
	</div>

</body>
</html>