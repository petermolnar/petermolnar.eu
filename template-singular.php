<?php
	global $post;
	global $petermolnareu_theme;
	global $category;

	$post_format = get_post_format();
	if ( $post_format === false )
		$post_format = get_post_type();

	if ( empty ( $category )) {
		$category = get_the_category( $post->ID );
		$category = array_shift( $category );
	}
	$template = $category->slug;

	$siblings = ( $template == 'photoblog') ? true : false;
	$footer = ( $template == 'portfolio') ? false : true;
	$header = $linkify = $sidebar = false;
	switch ( $post_format ) {
		case 'page':
			$footer = false;
			break;
		case 'link':
		case 'quote':
		case 'status':
		case 'image':
		case 'video':
		case 'audio':
			//$siblings = true;
			$linkify = true;
			$header = 'pubdate';
			$sidebar = 'category-postlist';
			break;
		case 'gallery':
			$header = 'small';
			break;
		default:
			$sidebar = 'category-postlist';
			$header = 'normal';
			break;
	}

?>

<article id="post-<?php the_ID(); ?>" class="h-entry <?php echo $sidebar ?> article-<?php echo $post_format ?>">
	<span class="u-uid hide"><?php the_ID(); ?></span>

	<!-- prev-next links -->
	<?php if ( $siblings ) : ?>
	<nav class="siblings">
		<div class="link left"><?php	next_post_link( '&laquo; %link' , '%title' , true ); ?></div>
		<div class="link right"><?php	previous_post_link( '%link &raquo; ' , '%title' , true ); ?></div>
		<br class="clear" />
	</nav>
	<?php endif; ?>

	<!-- article header -->
	<header class="article-header">
		<?php  if ( $header == 'pubdate' ) : ?>
			<a class="u-url" href="<?php the_permalink() ?>">
				<?php $petermolnareu_theme->article_time(true)?>
			</a>
			<span class="hide p-name"><?php the_title(); ?></span>
		<?php elseif ( $header == 'small' ): ?>
			<h1>
				<a class="u-url" href="<?php the_permalink() ?>">
					<span class="p-name"><?php the_title(); ?></span>
				</a>
			</h1>
			<span class="hide"><?php $petermolnareu_theme->article_time(); ?></span>
		<?php elseif ( $header == 'normal'): ?>
			<?php $petermolnareu_theme->article_time(); ?>
			<h1>
				<a class="u-url" href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>">
					<span class="p-name"><?php the_title(); ?></span>
				</a>
			</h1>
		<?php endif; ?>
		<span class="hide"><?php echo $petermolnareu_theme->author( true ) ?></span>
	</header>

	<!-- article content -->
	<?php
		ob_start();
		the_content();
		$content = ob_get_clean();
		$feat = get_post_thumbnail_id( $post->ID );

		if ( $linkify ) {
			/* adaptify */
			$icontent = $petermolnareu_theme->replace_images_with_adaptive ( $content );

			/* auto feat img */
			if ( $content == $icontent && !empty($feat) )
				$content .= do_shortcode( '[adaptimg aid=' . $feat .' size=hd share=0 standalone=1]');
			else
				$content = $icontent;

			/* twittify */
			if ( has_tag( 'twitter' ) )
				$content = $petermolnareu_theme->twtreplace($content);

			/* linkify */
			$content = $petermolnareu_theme->linkify($content);
		}

	?>
	<div class="article-content e-content">
		<?php echo $content ?>
	</div>

	<!-- article footer -->
	<?php if ( $footer ) : ?>
	<footer class="article-footer">
		<?php if ( function_exists('add_js_rel_syndication')) echo add_js_rel_syndication(''); ?>
		<?php echo $petermolnareu_theme->share ( get_permalink() , wp_title( '', false ), true ); ?>
	</footer>
	<?php endif; ?>

</article>

<!-- related posts -->
<?php  if ( $sidebar ) : ?>
	<aside class="sidebar">
	<?php echo $petermolnareu_theme->related_posts( $post, true ); ?>
	</aside>
<?php endif; ?>
