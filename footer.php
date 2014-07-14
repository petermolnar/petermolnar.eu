	<?php global $petermolnareu_theme; ?>
	<br class="clear" />
	</section>

	<?php if ( is_singular() ) : ?>
	<section class="content-comments"><div class="inner">
		<a id="comments" /></a>
		<?php comments_template( ); ?>
	</div></section>
	<?php endif; ?>

	<footer class="content-footer aligncenter"><div class="inner">
		© 1999-<?php echo date('Y'); ?> by
		<?php echo $petermolnareu_theme->author(); ?>
		<span class="spacer">Licence:</span>
		<a rel="license" href="http://creativecommons.org/licenses/by-sa/4.0/" title="All photographs are licenced under Creative Commons Attribution-ShareAlike 4.0 International">
			<span class="icon-cc-by"></span><span class="icon-cc-sa"></span>
		</a>
		<span class="spacer">Engine:</span>
		<a href="http://wordpress.org" title="Powered by WordPress">
			<span class="icon-wordpress"></span>
		</a>
		<form role="search" method="get" class="search-form" action="<?php echo $petermolnareu_theme->replace_if_ssl(get_bloginfo('url')); ?>">
			<label for="search" class="spacer"><?php _e('Search:', $petermolnareu_theme->theme_constant ); ?></label>
			<input type="search" class="search-field" placeholder="Search …" value="" name="s" title="<?php _e('Search for:', $petermolnareu_theme->theme_constant ); ?>">
			<input type="submit" class="search-submit" value="<?php _e('Go', $petermolnareu_theme->theme_constant ); ?>">
		</form>
		<?php wp_footer(); ?>
	</div></footer>

	<script>
	jQuery(document).ready(function($) {

		jQuery("a[href^='#']").click(function( e ) {
			e.preventDefault();
		});

		jQuery('#showContentHeader').click ( function (event) {
			jQuery(this).toggleClass( 'active' );
			jQuery('.content-header' ).toggleClass('open');
		} );

	});
	</script>

	<?php	if ( function_exists( 'yoast_analytics' ) ) yoast_analytics(); ?>

</body>
</html>
