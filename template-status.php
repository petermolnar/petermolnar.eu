<?php
	global $post;
	global $petermolnareu_theme;
?>

<article id="post-<?php the_ID(); ?>" class="article-status">

	<?php $petermolnareu_theme->article_time(); ?>

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
