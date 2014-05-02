<?php
	global $post;
	global $petermolnareu_theme;
?>

<article id="post-<?php the_ID(); ?>" class="article-status">

	<?php
		$petermolnareu_theme->article_time();
		the_content();
	?>

	<footer class="article-status-footer">
		<?php echo $petermolnareu_theme->share ( get_permalink() , substr( get_the_excerpt(), 0, 80 ), true ); ?>
	</footer>

	<br class="clear" />
</article>
