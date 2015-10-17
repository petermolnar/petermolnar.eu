<?php
/*
this is for all posts with photos made by me
*/
$post_url = get_the_permalink();
$post_title = get_the_title();
?>

<div class="content-inner">
	<article class="h-entry" id="post-<?php the_ID(); ?>" >

		<header>
			<div class="meta">
				<?php require ( dirname(__FILE__) . '/ameta_pubdate.php' ); ?>
				<div class="hide">
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
