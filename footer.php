
	<!-- content end -->

	<?php if ( is_single() )	comments_template( ); ?>

	<footer class="content-footer aligncenter">
		<div class="limit">
			<a rel="license" href="/licence" title="Licence"><i class="icon-cc"></i></a> 1999-<?php echo date('Y'); ?> by <?php echo pmlnr_article::vcard(); ?>
			<span class="spacer"></span>
			<a class="spacer" href="http://wordpress.org" rel="nofollow" title="Powered by WordPress"><i class="icon-wordpress"></i></a>
			<a class="spacer" href="<?php echo bloginfo('rss2_url'); ?>" rel="nofollow" title="RSS"><i class="icon-rss"></i></a>
			<br />
			<aside class="footer-forms">
				<form role="search" method="get" class="search-form" action="<?php echo pmlnr_utils::replace_if_ssl(get_bloginfo('url')); ?>">
					<label for="search" class="spacer"><?php _e('Search:' ); ?></label>
					<input type="search" class="search-field" placeholder="Search …" value="" name="s" title="<?php _e('Search for:' ); ?>">
					<input type="submit" class="search-submit" value="<?php _e('Go' ); ?>">
				</form>

				<?php // dynamic_sidebar( 'subscribe' ); ?>
			</aside>
			<?php wp_footer(); ?>
	</div>

	<!-- toggle menu -->
	<script>
		var menuButton = document.getElementById('showContentHeader');
		var headerBar = document.getElementsByClassName('content-header');
		headerBar = headerBar[0];

		menuButton.addEventListener('click', function(e) {
			e.preventDefault();

			var bClass = ( menuButton.className ) ? menuButton.className : '';
			var hClass = ( headerBar.className ) ? headerBar.className : '';

			if ( bClass.indexOf("active") > -1 || hClass.indexOf("open") > -1 ) {
				menuButton.className = bClass.replace(/\ ?active/, '');
				headerBar.className = hClass.replace(/\ ?open/, '');
			}
			else {
				menuButton.className += ' active';
				headerBar.className += ' open';
			}
			return false;
		});

	</script>
	<!-- end toggle menu -->

	<!-- Piwik
	<script type="text/javascript">
		var _paq = _paq || [];
		_paq.push(['trackPageView']);
		_paq.push(['enableLinkTracking']);
		(function() {
			var u="//petermolnar.eu/piwik/";
			_paq.push(['setTrackerUrl', u+'piwik.php']);
			_paq.push(['setSiteId', 1]);
			var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
			g.type='text/javascript'; g.async=true; g.defer=true; g.src=u+'piwik.js'; s.parentNode.insertBefore(g,s);
		})();
	</script>
	<noscript><p><img src="//petermolnar.eu/piwik.php?idsite=1" style="border:0;" alt="" /></p></noscript>
	-->

</body>
</html>
