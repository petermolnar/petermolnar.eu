<?php
/*
this is for all short entries
*/
$post_url = get_the_permalink();
$post_title = trim(get_the_title());
?>

<div class="content-inner">
	<article id="post-<?php the_ID(); ?>" class="h-entry article-list-element">

		<header>
			<div class="meta">
				<a class="u-url" href="<?php echo $post_url ?>" rel="bookmark" title="<?php echo $post_title ?>">
				<?php require ( dirname(__FILE__) . '/ameta_pubdate.php' ); ?>
				</a>
				<div class="hide">
					<?php require ( dirname(__FILE__) . '/ameta_author.php' ); ?>
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
			<?php the_content(); ?>
			<br class="clear" />
		</div>

	</article>
</div>
