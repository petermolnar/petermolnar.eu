
	<!-- content end -->

	<?php if ( is_singular() )	comments_template( ); ?>

	<footer class="content-footer aligncenter">
		<div class="limit">
			<a rel="license" href="/licence" title="Licence"><i class="icon-cc"></i></a> 1999-<?php echo date('Y'); ?> by <?php echo pmlnr_article::vcard(); ?>
			<span class="spacer"></span>
			<a href="http://wordpress.org" title="Powered by WordPress"><i class="icon-wordpress"></i></a>
			<br />
			<aside class="footer-forms">
				<form role="search" method="get" class="search-form" action="<?php echo pmlnr_utils::replace_if_ssl(get_bloginfo('url')); ?>">
					<label for="search" class="spacer"><?php _e('Search:' ); ?></label>
					<input type="search" class="search-field" placeholder="Search …" value="" name="s" title="<?php _e('Search for:' ); ?>">
					<input type="submit" class="search-submit" value="<?php _e('Go' ); ?>">
				</form>

				<?php dynamic_sidebar( 'subscribe' ); ?>
			</aside>

			<?php wp_footer(); ?>
	</div>
	</footer>

	<!-- toggle menu -->
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
	<!-- end toggle menu -->

</body>
</html>
