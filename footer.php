	<?php global $petermolnareu_theme; ?>
	<div class="clear">&nbsp;</div>
	<?php wp_footer(); ?>
	</div></section>

	<?php
		if ( is_singular() && comments_open()) {
			?><section class="content-comments"><div class="inner"><a id="comments" /><?php
			comments_template( );
			?></div></section><?php
		}
	?>

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

	<footer class="content-footer aligncenter"><div class="inner">
		© 1999-<?php echo date('Y'); ?> by
		<?php echo $petermolnareu_theme->author(); ?>
		<span class="spacer">Licence:</span>
		<a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/3.0/deed.en_GB" title="All photographs are licenced under Creative Commons Attribution-NonCommercial-ShareAlike 3.0 License">
			<span class="icon-cc-by"></span><span class="icon-cc-nc-eu"></span><span class="icon-cc-sa"></span>
		</a>
		<span class="spacer">Engine:</span>
		<a rel="engine" href="http://wordpress.org" title="Powered by WordPress">
			<span class="icon-wordpress"></span>
		</a>

	</div></footer>

</body>
</html>
