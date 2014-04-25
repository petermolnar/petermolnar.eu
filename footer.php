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
		Molnár Péter © 1999-<?php echo date('Y'); ?> <a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/3.0/deed.en_GB" title="All photographies licenced under Creative Commons Attribution-NonCommercial-ShareAlike 3.0 Unported License"><img alt="All photographies licenced under Creative Commons Attribution-NonCommercial-ShareAlike 3.0 Unported License" src="<?php echo $petermolnareu_theme->image_dir; ?>by-nc-sa.png" /></a>
	</div></footer>

</body>
</html>
