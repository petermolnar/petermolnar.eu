	<div class="clear">&nbsp;</div>
	</section>

	<?php if ((is_single() || is_page() ) && comments_open()) : ?>
		<?php comments_template( ); ?>
	<?php endif; ?>

	<footer class="content-footer">
		<?php wp_footer(); ?>
		<section class="grid33">&nbsp;</section>
		<section class="grid33 aligncenter">
				Molnár Péter © 1999-<?php echo date('Y'); ?> <br />All rights reserved.
		</section>
		<section class="grid33 opacity50 alignright">
			<figure class="donation-link">
				<?php if(function_exists('the_flattr_permalink')) the_flattr_permalink(); ?>
			</figure>
		</section>
	</footer>

</body>
</html>