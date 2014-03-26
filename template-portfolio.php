<?php
	global $post;
	global $petermolnareu_theme;

	$content = get_the_content();
	$to_clear = array ('[wp-galleriffic]','[photogal]','[adaptgal]');
	$content = str_replace($to_clear, '', $content);

?>

<article class="portfolio" id="portfolio-<?php the_ID(); ?>">
	<?php echo $content; ?>
	<?php echo do_shortcode('[adaptgal]'); ?>
</article>
