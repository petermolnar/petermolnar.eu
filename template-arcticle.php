<?php
	global $post;
	global $petermolnareu_theme;
	global $category_additions;
?>
<section class="category-postlist">
	<arcticle id="post-<?php the_ID(); ?>" class="arcticle-single <?php echo $category_additions['class']; ?>">
		<header class="article-header">
			<h2>
			<?php if ( $category_additions['time'] ): ?>
				<time class="arcticle-pubdate" pubdate="<?php the_time( 'r' ); ?>">
					<span class="year"><?php the_time( 'Y' ); ?></span>
					<span class="month"><?php the_time( 'M' ); ?></span>
					<span class="day"><?php the_time( 'd' ); ?></span>
				</time>
			<?php endif; ?>
				<a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>">
					<?php the_title(); ?>
				</a>
			</h2>

		</header>

		<div class="arcticle-body column-2">
			<?php the_content(); ?>
		</div>
	</arcticle>
</section>
<?php echo $petermolnareu_theme->related_posts( $post ); ?>
<?php //wp_reset_query();
?>

<footer class="article-single-footer">
	<?php if ( $category_additions['share'] ) {
		echo $petermolnareu_theme->share ( get_permalink() , wp_title( '', false ) );
	} ?>
</footer>
