<?php
	global $post;
	global $petermolnareu_theme;
	global $category_additions;
?>

<arcticle id="post-<?php the_ID(); ?>" class="arcticle-list-element <?php echo $category_additions['class']; ?>">
	<header class="article-header">
		<h2>
			<a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>">
				<?php the_title(); ?>
			</a>
		</h2>
		<?php if ( $category_additions['time'] ): ?>
			<time pubdate="<?php the_time( 'r' ); ?>">
				<?php the_time( get_option('date_format') ); ?>
			</time>
		<?php endif; ?>
	</header>

	<div class="arcticle-body">
		<figure class="article-thumbnail">
		<?php if ( has_post_thumbnail () ) { ?>
			<a href="<?php the_permalink() ?>">
			<?php
				the_post_thumbnail('thumbnail', array(
					'alt'	=> trim(strip_tags( $post->post_title )),
					'title'	=> trim(strip_tags( $post->post_title )),
				));
			?>
			</a>
		<?php } ?>
		</figure>
		<?php the_excerpt(); ?>
	</div>

	<footer class="article-footer">
		<nav class="arcticle-tags">
			<?php if ( $category_additions['tags'] ) { ?>
				<?php the_tags( '', ', ', '' ); ?>
			<?php } ?>
		</nav>
		<nav class="arcticle-responses">
			<a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>">
				<?php echo comments_number( 'No responses', '1 response', '% responses' ); ?>
			</a>
		</nav>

	</footer>

	<br class="clear" />
</arcticle>
<?php

/*
		<nav class="arcticle-more">
			<?php if ( $category_additions['more'] ) { ?>
				<a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>">
					<span class="article-more-text">more </span><span class="article-more-icon">&rarr;</span>
				</a>
			<?php } ?>
		</nav>
*/
?>
