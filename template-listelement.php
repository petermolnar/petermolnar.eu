<?php
	global $post;
	global $petermolnareu_theme;
	global $category_meta;

	$featimg = $linkify = $aclass = $share = $header = false;
	switch ($category_meta['custom-template']) {
		case 'gallery':
			$aclass = 'photoblog-preview';
			$contenttype = 'image';
			break;
		case 'status':
			$header = 'pubdate';
			$share = true;
			$contenttype = 'e-content';
			$linkify = true;
			$aclass = 'article-status';
			break;
		default:
			$header = 'normal';
			$contenttype = 'e-summary';
			$featimg = true;
			$aclass = 'article-list-element';
			break;
	}

?>

<article id="post-<?php the_ID(); ?>" class="h-entry <?php echo $aclass ?>">
	<span class="u-uid hide"><?php the_ID(); ?></span>

	<?php if ( $header ) : ?>
	<!-- article header -->
	<header class="article-header">
		<?php  if ( $header == 'pubdate' ) : ?>
			<a class="u-url" href="<?php the_permalink() ?>">
				<?php $petermolnareu_theme->article_time(true)?>
			</a>
			<span class="hide p-name"><?php the_title(); ?></span>
		<?php elseif ( $header == 'normal'): ?>
			<?php $petermolnareu_theme->article_time(); ?>
			<h1>
				<a class="u-url" href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>">
					<span class="p-name"><?php the_title(); ?></span>
				</a>
			</h1>
		<?php endif; ?>
		<span class="hide"><?php echo $petermolnareu_theme->author( true ) ?></span>
	</header>
	<?php endif; ?>

	<?php if ( $contenttype == 'image') : ?>
		<a class="u-url" href="<?php the_permalink(); ?>">
			<?php
				$aid = get_post_thumbnail_id( );
				$title = get_the_title();
				echo do_shortcode( '[adaptimg aid=' . $aid .' title="'. $title .'"]');
			?>
		</a>
	<?php else: ?>
	<!-- article content -->
	<?php
		ob_start();

		if ( $contenttype == 'e-summary' )
			the_excerpt();
		else
			the_content();

		$content = ob_get_clean();

		if ( $linkify ) {
			$content = $petermolnareu_theme->replace_images_with_adaptive ( $content );
			$content = $petermolnareu_theme->twtreplace($content);
			$content = $petermolnareu_theme->linkify($content);
		}
		?>
		<div class="article-content <?php echo $contenttype ?>">
			<?php if ( $featimg ) : ?>
				<figure class="article-thumbnail">
				<?php if ( has_post_thumbnail () ) { ?>
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
				<?php } ?>
				</figure>
			<?php endif ?>

			<?php echo $content ?>
		</div>
	<?php endif; ?>

	<?php if ( $share ): ?>
		<!-- article footer -->
		<footer class="article-footer">
			<?php echo $petermolnareu_theme->share ( get_permalink() , wp_title( '', false ), true ); ?>
		</footer>
	<?php endif; ?>

</article>

<!-- related posts -->
<?php  if ( $sidebar ) echo $petermolnareu_theme->related_posts( $post ); ?>
