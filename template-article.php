<?php
	global $post;
	global $petermolnareu_theme;
?>

<article id="post-<?php the_ID(); ?>" class="article-single category-postlist">

		<header class="article-header">
			<h2>
				<?php $petermolnareu_theme->article_time(); ?>
				<a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>">
					<?php the_title(); ?>
				</a>
			</h2>
		</header>

		<div class="article-body">
			<?php the_content(); ?>
		</div>

		<footer class="article-single-footer">
			<?php echo $petermolnareu_theme->share ( get_permalink() , wp_title( '', false ), true ); ?>
		</footer>

</article>

<?php echo $petermolnareu_theme->related_posts( $post ); ?>
