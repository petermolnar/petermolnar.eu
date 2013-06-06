<?php
	global $post;
	global $petermolnareu_theme;
	global $category_additions;
?>
<section class="category-postlist">
	<arcticle id="post-<?php the_ID(); ?>" class="arcticle-single <?php echo $category_additions['class']; ?>">
		<header class="article-header">
			<h2>
				<a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>">
					<?php the_title(); ?>
				</a>
			</h2>
			<?php if ( $category_additions['time'] ): ?>
				<time pubdate="<?php the_time( 'r' ); ?>">
					<?php
						the_time( get_option('date_format') );
					?>
				</time>
			<?php endif; ?>
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
	<nav class="arcticle-tags">
		<?php	the_tags( '', ', ', '' ); ?>
	</nav>
	<?php if ( $category_additions['share'] ) {
		$petermolnareu_theme->share ( get_permalink() , wp_title( '', false ), $post->ID );
	} ?>
</footer>
