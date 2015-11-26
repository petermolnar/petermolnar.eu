<?php

global $petermolnareu_theme;

$tmpl = $petermolnareu_theme->twig->loadTemplate('footer.html');
echo $tmpl->render(pmlnr_site::template_vars());
return;

/*

<?php $lang = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : ''; ?>

<?php //if ( is_singular() ) comments_template( ); ?>

<!-- main menu -->
<label for="showContentHeader" class="nav-toggle-button" onclick>☰</label>
<input type="checkbox" id="showContentHeader" class="hide" />
<header class="content-header" id="main-header">
	<nav class="content-navigation">
		<?php wp_nav_menu( array( 'container' => '' , 'theme_location' => 'header'  ) ); ?>
	</nav>

	<div class="limit content-contact">
		<a rel="license" href="/licence" title="Licence" class="icon-copyright">&copy;</a> <?php require ( dirname(__FILE__) . '/partials/vcard.php' ); ?><a class="icon-rss" title="RSS feed" href="<?php bloginfo('rss2_url'); ?>"></a>
	</div>

		<aside class="limit footer-forms">
			<i class='icon-subscribe'></i><label for="email"><?php
			switch ($lang) {
				case 'hu':
					_e('Feliratkozás' );
					break;
				default:
					_e('Subscribe' );
					break;
			}
		?></label><br />
		<?php dynamic_sidebar( 'subscribe' ); ?>
		<?php include (dirname(__FILE__) . '/partials/search.php'); ?>
		</aside>


</header>
<!-- end main menu -->

<!-- main footer -->
<footer class="content-footer" id="main-footer">
	<?php wp_footer(); ?>
</footer>
<!-- end main footer -->

</body>
</html>
*/
