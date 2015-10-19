<?php
	wp_old_slug_redirect();

	global $wp_query;

	$wp_query->set_404();
	header('HTTP/1.0 404 Not Found');
	header('HTTP/1.1 404 Not Found');

	get_header();
	$lang = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : '';
?>
<section class="content-body content-dark">
	<div class="content-inner">
	<article id="error-404">
		<h1 style="text-align:center; font-size:8em">😞</h1>
		<h2 style="text-align: center">Nope, that's not here. You might want to search for it:</h2>
		<?php include (dirname(__FILE__) . '/partials/search.php'); ?>
	</article>
	</div>
</section>
<?php get_footer(); ?>
