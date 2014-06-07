<?php
	global $post;
	global $petermolnareu_theme;
	global $category_meta;

	$featimg = $adaptify = $aclass = $share = $header = false;
	switch ($category_meta['custom-template']) {
		case 'gallery':
			$aclass = 'photoblog-preview';
			$contenttype = 'image';
			break;
		case 'status':
			$header = 'pubdate';
			//$share = false;
			$contenttype = 'e-content';
			$adaptify = true;
			$aclass = 'article-status';
			break;
		case 'blog':
			$header = 'normal';
			//$share = false;
			$contenttype = 'e-content';
			$adaptify = false;
			$aclass = 'article-list-element';
			$featimg = true;
			break;
		default:
			$header = 'normal';
			$contenttype = 'e-summary';
			$featimg = true;
			$aclass = 'article-list-element';
			break;
	}

?>

<article id="post-<?php the_ID(); ?>" class="h-entry hentry <?php echo $aclass ?>">
	<!-- semantic data -->
	<div class="hide">
		<?php echo $petermolnareu_theme->author( true ); ?>
		<?php the_tags('<p class="p-category">', ', ', '</p>'); ?>
		<p class="u-uid"><?php $permalink = get_bloginfo('url') . '/?p=' . get_the_ID(); echo $permalink; ?></p>
	</div>

	<?php if ( $header ) : ?>
	<!-- article header -->
	<header class="article-header">
		<?php  if ( $header == 'pubdate' ) : ?>
			<a class="u-url" href="<?php the_permalink() ?>">
				<?php $petermolnareu_theme->article_time()?>
			</a>
			<span class="hide p-name entry-title"><?php the_title(); ?></span>
		<?php else: ?>
			<?php $petermolnareu_theme->article_time(); ?>
			<h2>
				<a class="u-url" href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>">
					<span class="p-name entry-title"><?php the_title(); ?></span>
				</a>
			</h2>
		<?php endif; ?>
		<!-- reply / repost / like / webmention data -->
		<?php $petermolnareu_theme->repost_data(); ?>
		<!-- end of reply / repost / like / webmention data -->
	</header>
	<?php endif; ?>

	<!-- article content -->
	<?php $aid = get_post_thumbnail_id( $post->ID ); ?>
	<?php if ( $contenttype == 'image') : ?>
		<a class="u-url" href="<?php the_permalink(); ?>">
			<?php
				$title = get_the_title();
				echo do_shortcode( '[adaptimg aid=' . $aid .' title="'. $title .'" share=0]');
			?>
		</a>
	<?php else: ?>
	<?php
		ob_start();

		if ( $contenttype == 'e-summary' )
			the_excerpt();
		//elseif ( contenttype == 'image' )
			//do_shortcode( '[adaptimg aid=' . $aid .' size=hd share=0 standalone=1]');
		else
			the_content();

		$content = ob_get_clean();

		if ( $adaptify ) {
			$icontent = $petermolnareu_theme->replace_images_with_adaptive ( $content );

			/* auto feat img */
			if ( $content == $icontent && !empty($feat) )
				$content .= do_shortcode( '[adaptimg aid=' . $feat .' size=hd share=0 standalone=1]');
			else
				$content = $icontent;
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
<?php  if ( !empty($sidebar) ) echo $petermolnareu_theme->related_posts( $post ); ?>
