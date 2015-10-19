<?php
/*
this is for all posts with photos made by me
*/
?>

<div class="content-inner">
	<article class="h-entry" id="post-<?php echo $post_id ?>" >

		<header>
			<div class="meta">
				<?php include ( dirname(__FILE__) . '/ameta_pubdate.php' ); ?>
				<div class="hide">
					<?php include ( dirname(__FILE__) . '/ameta_author.php' ); ?>
				</div>
			</div>
			<h2>
				<a class="u-url" href="<?php echo $post_url ?>" rel="bookmark" title="<?php echo $post_title ?>">
					<span class="p-name"><?php echo $post_title ?></span>
				</a>
			</h2>
		</header>

		<div class="e-content">
			<?php echo $post_content ?>
			<br class="clear" />
		</div>

	</article>
</div>
