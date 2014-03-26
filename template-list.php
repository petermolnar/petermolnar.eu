<?php
	global $post;
	global $petermolnareu_theme;
	global $category_additions;
?>

<arcticle id="post-<?php the_ID(); ?>" class="arcticle-list-element <?php echo $category_additions['class']; ?>">
	<header class="article-header">
		<h2>
		<?php if ( $category_additions['time'] ): ?>
			<time class="arcticle-pubdate" pubdate="<?php the_time( 'r' ); ?>">
				<span class="year"><?php the_time( 'Y' ); ?></span>
				<span class="month"><?php the_time( 'M' ); ?></span>
				<span class="day"><?php the_time( 'd' ); ?></span>
			</time>
		<?php endif; ?>
			<a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>">
				<?php the_title(); ?>
			</a>
		</h2>
	</header>

	<div class="arcticle-body">
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

		<?php
		/*

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
		*/ ?>

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