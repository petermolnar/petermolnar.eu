<?php
	wp_old_slug_redirect();
	header('HTTP/1.0 404 Not Found');
	get_header();
?>

<section class="content-body content-dark">
	<div class="content-inner">
	<article id="error-404">
		<h1 class="icon-meh" style="text-align:center; font-size:8em"></h1>
		<h2 style="text-align: center">Nope, that's not here. You might want to search for it:</h2>
		<form role="search" method="get" class="search-form aligncenter" action="<?php echo pmlnr_utils::replace_if_ssl(get_bloginfo('url')); ?>">
			<label for="search" class="spacer"><?php _e('Search:' ); ?></label>
			<input type="search" class="search-field" placeholder="Search …" value="" name="s" title="<?php _e('Search for:' ); ?>">
			<input type="submit" class="search-submit" value="<?php _e('Go' ); ?>">
		</form>
	</article>
	</div>
</section>
<?php get_footer(); ?>
