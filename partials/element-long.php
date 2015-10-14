<?php
/*
this is for all articles longer than ARTICLE_MIN_LENGTH (set in functions.php)
*/

$post_url = get_the_permalink();
$post_title = get_the_title();
$thid = get_post_thumbnail_id( $post->ID );
$post_thumbnail = false;
if ( $thid ) {
	$thumbnail = wp_get_attachment_image_src($thid,'thumbnail');
	if ( isset($thumbnail[1]) && $thumbnail[3] != false )
		$post_thumbnail = pmlnr_utils::fix_url($thumbnail[0]);
}
?>

<div class="content-inner">
	<article id="post-<?php the_ID(); ?>" class="h-entry article-list-element">

		<header>
			<div class="meta">
				<?php require ( dirname(__FILE__) . '/ameta_pubdate.php' ); ?>
				<?php require ( dirname(__FILE__) . '/ameta_readtime.php' ); ?>
				<div class="hide">
					<?php require ( dirname(__FILE__) . '/ameta_author.php' ); ?>
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

			<?php the_excerpt(); ?>
			<br class="clear" />
		</div>

	</article>
</div>
