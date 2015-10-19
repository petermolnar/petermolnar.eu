<?php
/*
this is for all short entries
*/
?>

<div class="content-inner">
	<article class="h-entry" id="post-<?php echo $post_id ?>">

		<header>
			<div class="meta">
				<a class="u-url" href="<?php echo $post_url ?>" rel="bookmark" title="<?php echo $post_title ?>">
				<?php include ( dirname(__FILE__) . '/ameta_pubdate.php' ); ?>
				</a>
				<div class="hide">
					<?php include ( dirname(__FILE__) . '/ameta_author.php' ); ?>
				</div>
			</div>
			<?php if ( !empty($post_title)): ?>
			<h2>
				<a class="u-url" href="<?php echo $post_url ?>" rel="bookmark" title="<?php echo $post_title ?>">
					<span class="p-name"><?php echo $post_title ?></span>
				</a>
			</h2>
			<?php endif; ?>
		</header>

		<div class="e-content">
			<?php echo $post_content; ?>
			<br class="clear" />
		</div>

	</article>
</div>
