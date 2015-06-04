<?php $lang = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : ''; ?>

<?php /* if ( is_singular() ) comments_template( ); */ ?>

<!-- main menu -->
<header class="content-header" id="main-header">
	<nav class="content-navigation">
		<?php wp_nav_menu( array( 'container' => '' , 'theme_location' => 'header'  ) ); ?>
	</nav>
	<a href="#" id="showContentHeader" class="nav-toggle-button" > </a>

	<div class="limit content-contact">
		<a rel="license" href="/licence" title="Licence" class="spacer">&copy;</a> <?php require ( dirname(__FILE__) . '/partials/vcard.php' ); /* echo pmlnr_article::vcard(); */ ?><a class="icon-rss" title="RSS feed" href="<?php bloginfo('rss2_url'); ?>"></a><a class="icon-rss spacer" href="<?php bloginfo('atom_url'); ?>" title="Atom feed"></a>
		<br />
		<?php /*<label for="button-rss" class="hide"><?php __('Follow this site'); ?></label>
		<input type="button" class="button-rss" id="button-rss" name="button-rss" data-subtome-suggested-service-url="http://blogtrottr.com/?subscribe={feed}" data-subtome-suggested-service-name="Blogtrottr" data-subtome-feeds="<?php bloginfo('rss2_url'); ?>" data-subtome-resource="<?php echo home_url(); ?>" value="&#xE80B; follow petermolnar.eu" onclick="(function(btn){var z=document.createElement('script');document.subtomeBtn=btn;z.src='https://www.subtome.com/load.js';document.body.appendChild(z);})(this)" /><br /> */ ?>
		<label for="email"><?php
			switch ($lang) {
				case 'hu':
					_e('Feliratkozás frissítésekre' );
					break;
				default:
					_e('Subscribe with email' );
					break;
			}
		?></label><br />
		<?php dynamic_sidebar( 'subscribe' ); ?>
		<aside class="footer-forms">
			<form role="search" method="get" class="search-form" action="<?php echo pmlnr_utils::absurl(get_bloginfo('url')); ?>">
				<label for="search"><?php
					switch ($lang) {
						case 'hu':
							_e('Keresés' );
							break;
						default:
							_e('Search' );
							break;
					}
				?></label><br />
				<input type="search" class="search-field" placeholder="Search …" value="" name="s" title="<?php _e('Search for:' ); ?>">
				<input type="submit" class="search-submit" value="<?php _e('Go' ); ?>">
			</form>
		</aside>
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

		//var vw = Math.max(document.documentElement.clientWidth, window.innerWidth || 0)
		var vh = Math.max(document.documentElement.clientHeight, window.innerHeight || 0)

		var adaptimg = document.getElementsByClassName('adaptimg');
		[].forEach.call(adaptimg, function (el) {
			//var w = el.offsetWidth;
			var h = el.offsetHeight;

			if ( h > vh ) {
				el.style.height = vh + 'px';
				el.style.width = 'auto';
			}

			/*height: auto;
			width: 100%;*/
		});
		//document.getElementById('note').style.fontWeight = 'bold';

	</script>
	<!-- end toggle menu -->
</header>
<!-- end main menu -->

<!-- main footer -->
<footer class="content-footer aligncenter" id="main-footer">
	<?php wp_footer(); ?>
</footer>
<!-- end main footer -->

</body>
</html>
