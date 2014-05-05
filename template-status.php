<?php
	global $post;
	global $petermolnareu_theme;
?>

<article id="post-<?php the_ID(); ?>" class="article-status">

	<?php
		$petermolnareu_theme->article_time();

		ob_start();
		the_content();
		$content = ob_get_clean();

		$content = $petermolnareu_theme->replace_images_with_adaptive ( $content );
		//$content = preg_replace('/([a-z]+\:\/\/[a-z0-9\-\.]+\.[a-z]+(:[a-z0-9]*)?\/?([a-z0-9\-\._\:\?\,\'\/\\\+&%\$#\=~])*[^\.\,\)\(\s])/i', $content);
		$content = $petermolnareu_theme->twtreplace($content);
		$content = $petermolnareu_theme->linkify($content);
		echo $content;
	?>

	<footer class="article-status-footer">
		<?php echo $petermolnareu_theme->share ( get_permalink() , substr( get_the_excerpt(), 0, 80 ), true ); ?>
	</footer>

	<br class="clear" />
</article>
