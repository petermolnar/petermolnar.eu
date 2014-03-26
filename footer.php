	<?php global $petermolnareu_theme; ?>
	<div class="clear">&nbsp;</div>
	<?php wp_footer(); ?>
	</section>

	<?php
		if ( is_single() && comments_open()) {
			comments_template( );
		}
	?>

	<script>
	jQuery(document).ready(function($) {

		$('#showContentHeader').click ( function (event) {
			$(this).toggleClass( 'active' );
			$('.content-header' ).toggleClass('open');
		} );
	});

	</script>

	<footer class="content-footer aligncenter">
		Molnár Péter © 1999-<?php echo date('Y'); ?> <a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/3.0/deed.en_GB" title="All photographies licenced under Creative Commons Attribution-NonCommercial-ShareAlike 3.0 Unported License"><img alt="All photographies licenced under Creative Commons Attribution-NonCommercial-ShareAlike 3.0 Unported License" src="<?php echo $petermolnareu_theme->image_dir; ?>by-nc-sa.png" /></a>
	</footer>

</body>
</html>
