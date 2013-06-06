<?php
	global $post;
	global $petermolnareu_theme;

	$content = get_the_content();
	$content = str_replace('[wp-galleriffic]', '', $content);

?>

<article class="photoblog-article" id="photoblog-<?php the_ID(); ?>" >
	<nav class="photoblog-navigation">
		<div class="link left"><?php	next_post_link( '&laquo; %link' , '%title' , true ); ?></div>
		<div class="link right"><?php	previous_post_link( '%link &raquo; ' , '%title' , true ); ?></div>
	</nav>

	<header class="photoblog-header">
		<h2>
			<a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>">
				<?php the_title(); ?>
			</a>
		</h2>
	</header>

	<div class="photoblog-content">
		<?php echo $content; ?>
		<br /><br />
		<?php echo do_shortcode( '[photogal]' ); ?>
	</div>

	<footer class="photoblog-footer">
		<?php $comment = (is_single()) ? false : true; ?>
		<?php $petermolnareu_theme->share ( get_permalink() , wp_title( '', false ), $comment ); ?>
	</footer>
</article>
