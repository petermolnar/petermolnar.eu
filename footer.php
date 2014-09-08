	<?php global $petermolnareu_theme; ?>
	<br class="clear" />
	</section>

	<?php if ( is_singular() && comments_open() ) : ?>
		<?php comments_template( ); ?>
	<?php endif; ?>

	<footer class="content-footer aligncenter"><div class="inner">
		© 1999-<?php echo date('Y'); ?> by
		<?php echo $petermolnareu_theme->author(); ?>
		<span class="spacer"></span>
		<a rel="license" href="/licence" title="Licence"><i class="icon-cc"></i></a>
		<span class="spacer"><a href="http://wordpress.org" title="Powered by WordPress"><i class="icon-wordpress"></i></a></span>
		<br />
		<aside class="footer-forms">
			<form role="search" method="get" class="search-form" action="<?php echo $petermolnareu_theme->replace_if_ssl(get_bloginfo('url')); ?>">
				<label for="search" class="spacer"><?php _e('Search:', $petermolnareu_theme->theme_constant ); ?></label>
				<input type="search" class="search-field" placeholder="Search …" value="" name="s" title="<?php _e('Search for:', $petermolnareu_theme->theme_constant ); ?>">
				<input type="submit" class="search-submit" value="<?php _e('Go', $petermolnareu_theme->theme_constant ); ?>">
			</form>

			<?php dynamic_sidebar( 'subscribe' ); ?>
		</aside>

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

	<!-- Piwik -->
	<script type="text/javascript">
		var _paq = _paq || [];
		_paq.push(['trackPageView']);
		_paq.push(['enableLinkTracking']);
		(function() {
			var u=(("https:" == document.location.protocol) ? "https" : "http") + "://petermolnar.eu/piwik/";
			_paq.push(['setTrackerUrl', u+'piwik.php']);
			_paq.push(['setSiteId', 1]);
			var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0]; g.type='text/javascript';
			g.defer=true; g.async=true; g.src=u+'piwik.js'; s.parentNode.insertBefore(g,s);
		})();
	</script>
	<noscript><p><img src="https://petermolnar.eu/piwik/piwik.php?idsite=1" style="border:0;" alt="" /></p></noscript>
	<!-- End Piwik Code -->

	<?php	if ( function_exists( 'yoast_analytics' ) ) yoast_analytics(); ?>

</body>
</html>
