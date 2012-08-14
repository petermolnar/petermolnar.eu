	<div class="clear">&nbsp;</div>
	</section>

	<?php if ((is_single() || is_page() ) && comments_open()) : ?>
		<?php comments_template( ); ?>
	<?php endif; ?>

	<footer class="content-footer">
		<?php wp_footer(); ?>
		<div class="grid33 alignleft">&nbsp;</div>
		<div class="grid33 aligncenter">
				Molnár Péter © 1999-<?php echo date('Y'); ?> <br />All rights reserved.
		</div>
		<div class="grid33 alignright">
			<?php/*
				if(function_exists('the_flattr_permalink')):
			<p class="donation-link opacity50 donation-flattr-button">
				the_flattr_permalink();
			</p>
			endif; */ ?>
			<p class="donation-link opacity50 aligncenter">
				<a class="donate-button round" href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=8LZ66LGFLMKJW&lc=HU&item_name=Peter%20Molnar%20photographer%2fdeveloper&item_number=petermolnar%2dpaypal%2ddonation&currency_code=USD&bn=PP%2dDonationsBF%3acredit%2epng%3aNonHosted">donate</a>
			</p>
		</div>
	</footer>

	<!-- Google analytics -->
	<?php
		if ( function_exists( 'yoast_analytics' ) ) {
			yoast_analytics();
		}
	?>

</body>
</html>
