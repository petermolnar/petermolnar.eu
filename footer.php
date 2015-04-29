<?php if ( is_singular() ) comments_template( ); ?>

<!-- main menu -->
<header class="content-header">
	<a href="#" id="showContentHeader" class="nav-toggle-button" > </a>
	<nav class="content-navigation">
		<?php wp_nav_menu( array( 'container' => '' , 'theme_location' => 'header'  ) ); ?>
	</nav>
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

		jQuery(function(){
			jQuery('.e-content').magnificPopup({
				delegate: 'a.adaptlink',
				type: 'image',
				image: {
					cursor: null,
					titleSrc: 'title'
				},
				gallery: {
					enabled: true,
					////preload: [0,1], // Will preload 0 - before current, and 1 after the current image
					navigateByImgClick: true
				}
			});
		});
	</script>
	<!-- end toggle menu -->
</header>
<!-- end main menu -->

<!-- main footer -->
<footer class="content-footer aligncenter">
	<div class="limit">
		<a rel="license" href="/licence" title="Licence">&copy;</a> 1999-<?php echo date('Y'); ?> by <?php require ( dirname(__FILE__) . '/partials/vcard.php' ); /* echo pmlnr_article::vcard(); */ ?>
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
</footer>
<!-- end main footer -->

</body>
</html>
