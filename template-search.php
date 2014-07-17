<?php
	global $post;
	global $petermolnareu_theme;
	global $category;
	global $category_meta;
	$class = ' content-inner article-list-element category-postlist';
?>

<article id="post-<?php the_ID(); ?>" class="h-entry<?php echo $class; ?>">

	<!-- article header -->
	<header class="article-header">

		<?php echo $petermolnareu_theme->article_time(); ?>
		<?php if ($showccntr) echo $commentcounter; ?>
			<h2>
				<a class="u-url" href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>">
					<span class="p-name"><?php the_title(); ?></span>
				</a>
			</h2>

		<!-- semantic data -->
		<div class="hide">
			<?php echo $petermolnareu_theme->author( true ); ?>
			<p class="u-uid"><?php echo wp_get_shortlink(); ?></p>
		</div>
		<!-- end of semantic data -->
	</header>

	<!-- article content -->
	<?php $aid = get_post_thumbnail_id( $post->ID );

		ob_start();
		the_excerpt();
		$content = ob_get_clean();

		?>
		<div class="article-content <?php echo $contenttype ?>">
			<?php if ( has_post_thumbnail () ) : ?>
				<figure class="article-thumbnail">
					<a href="<?php the_permalink() ?>">
					<?php
						$thumb = get_the_post_thumbnail( $post->ID, 'thumbnail', array(
							'alt'	=> trim(strip_tags( $post->post_title )),
							'title'	=> trim(strip_tags( $post->post_title )),
							'class'	=> "u-photo",
						));
						echo $petermolnareu_theme->replace_if_ssl ( $thumb );
					?>
					</a>
				</figure>
			<?php endif ?>

			<?php echo $content ?>

			<br class="clear" />
		</div>
</article>
