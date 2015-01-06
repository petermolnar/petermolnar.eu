
	<!-- content end -->

	<?php if ( is_single() )	comments_template( ); ?>

	<footer class="content-footer aligncenter">
		<div class="limit">
			<a rel="license" href="/licence" title="Licence"><i class="icon-cc"></i></a> 1999-<?php echo date('Y'); ?> by <?php echo pmlnr_article::vcard(); ?>
			<span class="spacer"></span>
			<a href="http://wordpress.org" title="Powered by WordPress"><i class="icon-wordpress"></i></a>
			<span class="spacer"></span>
			<a href="<?php echo bloginfo('rss2_url'); ?>" title="RSS"><i class="icon-rss"></i></a>
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
	</footer>

	<!-- toggle menu -->
	<script>
		var menuButton = document.getElementById('showContentHeader');
		var headerBar = document.getElementsByClassName('content-header');
		headerBar = headerBar[0];

		menuButton.addEventListener('click', function(e) {
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

	<?php /* if ( is_user_logged_in()) { ?>
	<script>
		window.navigator.registerProtocolHandler('indie+action', 'https://petermolnar.eu/indie-config.html?handler=%s', 'Peter Molnar');
	</script>
	<?php } */ ?>

</body>
</html>
