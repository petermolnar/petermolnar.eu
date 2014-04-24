<?php
	global $post;
	global $petermolnareu_theme;
?>

<article id="post-<?php the_ID(); ?>" class="article-list-element">
	<header class="article-header">
			<?php $petermolnareu_theme->article_time(); ?>
			<h2>
				<a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>">
				<?php the_title(); ?>
			</a>
		</h2>
	</header>

	<div class="article-body">
		<figure class="article-thumbnail">
		<?php if ( has_post_thumbnail () ) { ?>
			<a href="<?php the_permalink() ?>">
			<?php
				$thumb = get_the_post_thumbnail( $post->ID, 'thumbnail', array(
					'alt'	=> trim(strip_tags( $post->post_title )),
					'title'	=> trim(strip_tags( $post->post_title )),
				));
				echo $petermolnareu_theme->replace_if_ssl ( $thumb );
			?>
			</a>
		<?php } ?>
		</figure>
		<?php the_excerpt(); ?>

	</div>
	<br class="clear" />
</article>
