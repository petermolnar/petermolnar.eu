
<?php $post_url = get_the_permalink(); ?>
<?php $post_title = get_the_title(); ?>
<?php $post_images = adaptive_images::imagewithmeta( get_post_thumbnail_id( $post->ID ) ) ?>
<?php $post_image = (empty($post_images)) ? false : $post_images['mediumurl'] ?>

<div class="content-inner">
	<article id="post-<?php the_ID(); ?>" class="h-entry article-list-element">

		<header>
			<div class="meta">
				<?php require ( dirname(__FILE__) . '/ameta_pubdate.php' ); ?>
				<div class="hide">
					<?php if (!empty($post_image)): ?>
					<img class="u-photo" src="<?php echo $post_image ?>" />
					<?php endif; ?>
					<?php require ( dirname(__FILE__) . '/ameta_author.php' ); ?>
				</div>
			</div>
			<h2>
				<a class="u-url" href="<?php echo $post_url ?>" rel="bookmark" title="<?php echo $post_title ?>">
					<span class="p-name"><?php echo $post_title ?></span>
				</a>
			</h2>
		</header>

		<div class="e-content">
			<?php the_content(); ?>
			<br class="clear" />
		</div>

	</article>
</div>
