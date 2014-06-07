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
	$linkify = false;
	switch ( $post_format ) {
		case 'link':
		case 'quote':
		case 'status':
		case 'image':
		case 'video':
		case 'audio':
		case 'aside':
			$linkify = true;
			$header = 'pubdate';
			break;
		case 'gallery':
			$header = 'small';
			break;
		case 'page':
			$footer = false;
			break;
		default:
			$sidebar = 'category-postlist';
			$header = 'normal';
			break;
	}

?>

<article id="post-<?php the_ID(); ?>" class="h-entry <?php echo $sidebar ?> article-<?php echo $post_format ?>">

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
		<!-- semantic data -->
		<div class="hide">
			<?php echo $petermolnareu_theme->author( true ); ?>
			<?php the_tags('<p class="p-category">', ', ', '</p>'); ?>
			<p class="u-uid"><?php $permalink = get_bloginfo('url') . '/?p=' . get_the_ID(); echo $permalink; ?></p>
		</div>
		<!-- end of semantic data -->
		<!-- reply / repost / like / webmention data -->
			<?php $petermolnareu_theme->repost_data(); ?>
		<!-- end of reply / repost / like / webmention data -->
	</header>

	<!-- article content -->
	<?php
		ob_start();
		the_content();
		$content = ob_get_clean();
		$feat = get_post_thumbnail_id( $post->ID );

		if ( $linkify ) {
			$content = $petermolnareu_theme->replace_images_with_adaptive ( $content );
		}

	?>
	<div class="article-content e-content">
		<?php echo $content ?>
	</div>

	<!-- article footer -->
	<?php if ( $footer ) : ?>
	<footer class="article-footer">
		<?php if ( function_exists('add_js_rel_syndication')) echo add_js_rel_syndication(''); ?>

		<?php
		//$rt = get_post_meta( get_the_ID(), 'twitter_retweeted_status_id', true );
		$tw = get_post_meta( get_the_ID(), 'twitter_tweet_id', true );
		if ( !empty($tw) ) {
			$twlnk = 'https://twitter.com/petermolnar/status/' . $tw;
			?>
			<nav class="usyndication"><h6><?php _e('Also on:', $petermolnareu_theme->theme_constant); ?></h6><ul><li><a class='u-syndication icon-twitter link-twitter' href='<?php echo $twlnk; ?>'> Twitter</a></li></ul></nav>
		<?php }
		?>

		<?php
			echo $petermolnareu_theme->share_ ( get_permalink() , wp_title( '', false ), true );
		?>
	</footer>
	<?php endif; ?>

</article>

<!-- related posts -->
<?php  if ( $sidebar ) : ?>
	<aside class="sidebar">
	<?php echo $petermolnareu_theme->related_posts( $post ); ?>
	</aside>
<?php endif; ?>
