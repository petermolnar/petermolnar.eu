<?php
	global $post;
	global $petermolnareu_theme;
	global $category_meta;
	global $post_format;
?>

<article id="post-<?php the_ID(); ?>" class="article-list-element">
	<time class="article-pubdate" pubdate="<?php the_time( 'r' ); ?>">
		<span class="year"><?php the_time( 'Y' ); ?></span>
		<span class="month"><?php the_time( 'M' ); ?></span>
		<span class="day"><?php the_time( 'd, ' ); ?></span>
		<span class="hour"><?php the_time( 'H:i' ); ?></span>
	</time><br class="clear" />

	<?php if ( has_post_thumbnail () ) { ?>
	<figure class="article-thumbnail-small">
		<?php
			$thumb = get_the_post_thumbnail( $post->ID, 'thumbnail', array(
				'alt'	=> trim(strip_tags( $post->post_title )),
				'title'	=> trim(strip_tags( $post->post_title )),
			));
			echo $petermolnareu_theme->replace_if_ssl ( $thumb );
		?>
	</figure>
	<?php } ?>

	<?php the_content(); ?>

	<footer class="article-status-footer small">
		<?php echo $petermolnareu_theme->share ( get_permalink() , substr( get_the_excerpt(), 0, 80 ), true ); ?>
	</footer>


	<br class="clear" />
</article>
