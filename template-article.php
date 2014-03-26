<?php
	global $post;
	global $petermolnareu_theme;
	global $category_meta;

?>
<section class="category-postlist">
	<article id="post-<?php the_ID(); ?>" class="article-single">
		<header class="article-header">
			<h2>
				<time class="article-pubdate" pubdate="<?php the_time( 'r' ); ?>">
					<span class="year"><?php the_time( 'Y' ); ?></span>
					<span class="month"><?php the_time( 'M' ); ?></span>
					<span class="day"><?php the_time( 'd' ); ?></span>
				</time>
				<a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>">
					<?php the_title(); ?>
				</a>
			</h2>

		</header>

		<div class="article-body column-2">
			<?php the_content(); ?>
		</div>
	</article>
</section>
<?php echo $petermolnareu_theme->related_posts( $post ); ?>
<?php //wp_reset_query();
?>

<footer class="article-single-footer">
	<?php
		echo $petermolnareu_theme->share ( get_permalink() , wp_title( '', false ), true );
	?>
</footer>
