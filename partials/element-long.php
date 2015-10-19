<?php
/*
this is for all articles longer than ARTICLE_MIN_LENGTH (set in functions.php)
*/
?>

<div class="content-inner">
	<article class="h-entry" id="post-<?php echo $post_id ?>">

		<header>
			<div class="meta">
				<?php include ( dirname(__FILE__) . '/ameta_pubdate.php' ); ?>
				<?php include ( dirname(__FILE__) . '/ameta_readtime.php' ); ?>
				<div class="hide">
					<?php include ( dirname(__FILE__) . '/ameta_author.php' ); ?>
				</div>
			</div>
			<h2>
				<a class="u-url" href="<?php echo $post_url ?>" rel="bookmark" title="<?php echo $post_title ?>">
					<span class="p-name more"><?php echo $post_title ?></span>
				</a>
			</h2>
		</header>

		<div class="e-summary">
			<?php if (!empty($post_thumbnail)): ?>
			<img class="article-thumbnail" src="<?php echo $post_thumbnail ?>" />
			<?php endif; ?>

			<?php echo $post_excerpt ?>
			<br class="clear" />
		</div>

	</article>
</div>
