
<?php $post_url = get_the_permalink() ?>
<?php $post_title = get_the_title(); ?>
<?php $post_images = adaptive_images::imagewithmeta( get_post_thumbnail_id( $post->ID ) ) ?>
<?php $post_image = (empty($post_images)) ? false : $post_images['mediumurl'] ?>
<?php $post_thumbnail = (empty($post_images)) ? false : $post_images['thumbnail'] ?>
<?php $post_format = get_post_format() ?>

<div class="content-inner">
	<article id="post-<?php the_ID(); ?>" class="h-entry article-list-element">

		<header>
			<div class="meta">
				<a class="u-url" href="<?php echo $post_url ?>" rel="bookmark" title="<?php echo $post_title ?>">
				<?php require ( dirname(__FILE__) . '/ameta_pubdate.php' ); ?>
				</a>
				<div class="hide">
					<span class="p-name more"><?php echo $post_title ?></span>
					<?php if (!empty($post_image)): ?>
					<img class="u-photo" src="<?php echo $post_image ?>" />
					<?php endif; ?>
					<?php require ( dirname(__FILE__) . '/ameta_author.php' ); ?>
				</div>
			</div>
		</header>

		<div class="e-content">
			<?php if ($post_format == 'link'): ?>
			<p><strong><?php echo $post_title ?></strong></p>
			<?php endif; ?>

			<?php the_content(); ?>
			<br class="clear" />
		</div>

	</article>
</div>
