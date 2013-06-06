<?php
	global $post;
	global $petermolnareu_theme;

	$content = get_the_content();
	$content = str_replace('[wp-galleriffic]', '', $content);

?>

<article class="portfolio" id="portfolio-<?php the_ID(); ?>">
	<?php echo $content; ?>
	<?php echo do_shortcode('[adaptgal]'); ?>
</article>
