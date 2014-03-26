<?php
	global $post;
	global $petermolnareu_theme;
	global $category_meta;
	global $post_format;
?>

<article id="post-<?php the_ID(); ?>" class="article-list-element">
	<header class="article-header">
		<h2>
			<time class="article-pubdate" pubdate="<?php the_time( 'r' ); ?>">
				<span class="year"><?php the_time( 'Y' ); ?></span>
				<span class="month"><?php the_time( 'M' ); ?></span>
				<span class="day"><?php the_time( 'd' ); ?></span>
			</time>
			<a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>">
				<?php the_title(); ?>
			</a>
		</h2>
	</header>

	<div class="article-body">
	<?php /* if ( $post_format == 'link' ) {

		$content = get_the_content();
		$linktoend = stristr($content, "http" );
		$afterlink = stristr($linktoend, ">");
		if ( ! strlen( $afterlink ) == 0 )
			$linkurl = substr($linktoend, 0, -(strlen($afterlink) + 1));
		else
			$linkurl = $linktoend;
		?>

		<a class="link-external" href="<?php echo $linkurl; ?>"><?php the_title(); ?></a>

	<?php }
	else { */ ?>

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

	<?php /* }  */ ?>

	</div>

		<?php
		/*

	<footer class="article-footer">
		<nav class="article-tags">
			<?php if ( $category_additions['tags'] ) { ?>
				<?php the_tags( '', ', ', '' ); ?>
			<?php } ?>
		</nav>
		<nav class="article-responses">
			<a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>">
				<?php echo comments_number( 'No responses', '1 response', '% responses' ); ?>
			</a>
		</nav>


	</footer>
		*/ ?>

	<br class="clear" />
</article>
<?php

/*
		<nav class="article-more">
			<?php if ( $category_additions['more'] ) { ?>
				<a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>">
					<span class="article-more-text">more </span><span class="article-more-icon">&rarr;</span>
				</a>
			<?php } ?>
		</nav>
*/
?>
