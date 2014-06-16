<?php
	global $post;
	global $petermolnareu_theme;
	global $category;
	global $category_meta;

	$post_format = get_post_format();
	if ( $post_format === false )
		$post_format = get_post_type();

	if ( empty ( $category )) {
		$category = get_the_category( $post->ID );
		$category = array_shift( $category );
	}

	$header = 'normal';
	$adaptify = false;
	$footer = (is_singular()) ? true : false;
	$siblings = false;
	$content_type = ( is_singular() ) ? 'e-content' : 'e-summary';
	$class = (is_singular()) ? '' : ' article-list-element';
	$featimg = false;
	$commentcounter = '<a class="u-url right icon-comment commentcounter" href="'. get_the_permalink() . '#comments">'. get_comments_number( '', '1', '%' ) . '</a>';
	$showccntr = (is_singular()) ? false : true;
	$showtags = (is_singular()) ? true : false;
	$sidebar = '';

	switch ( $post_format ) {
		case 'link':
		case 'quote':
		case 'status':
		case 'image':
		case 'video':
		case 'audio':
		case 'aside':
			$header = 'pubdate';
			$adaptify = true;
			$content_type = 'e-content';
			break;
		case 'gallery':
			$header = ( is_singular() ) ? 'small' : 'none';
			$content_type = ( is_singular() ) ? 'e-content' : 'image';
			if ( !is_singular() ) $class = ' photoblog-preview';
			$footer = false;
			$showccntr = false;
			break;
		case 'page':
			$footer = false;
			$content_type = 'e-content';
			$showtags = false;
			$header = 'none';
			break;
		default:
			$featimg = true;
			switch ( $category->slug ) {
				case 'portfolio':
					$footer = false;
					$showtags = false;
					break;
				case 'photoblog':
					$siblings = true;
					$showtags = false;
					break;
				/*
				case 'journal':
					$footer = true;
					$siblings = true;
					$content_type = 'e-content';
					break;
				*/
				default:
					if ( is_singular() ) {
						$class = ' category-postlist';
						$sidebar = true;
					}
					break;
			}
			break;
	}

	if ( is_singular()) {
		$h = 1;
	}
	else {
		$h = 2;
	}

?>

<article id="post-<?php the_ID(); ?>" class="h-entry<?php echo $class; ?>">

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

		<?php  if ( $header == 'none' ) : ?>
			<div class="hide">
				<span class="p-name"><?php the_title(); ?></span>
				<?php echo $petermolnareu_theme->article_time(); ?>
			</div>
		<?php  elseif ( $header == 'pubdate' ) : ?>
			<a class="u-url" href="<?php the_permalink() ?>">
				<?php echo $petermolnareu_theme->article_time(); ?>
			</a>
			<?php if ($showccntr) echo $commentcounter; ?>
			<span class="hide p-name"><?php the_title(); ?></span>
		<?php elseif ( $header == 'small' ): ?>
			<h<?php echo $h; ?>>
				<a class="u-url" href="<?php the_permalink() ?>">
					<span class="p-name"><?php the_title(); ?></span>
				</a>
			</h<?php echo $h; ?>>
			<span class="hide"><?php echo $petermolnareu_theme->article_time(); ?></span>
		<?php elseif ( $header == 'normal'): ?>
			<?php echo $petermolnareu_theme->article_time(); ?>
			<?php if ($showccntr) echo $commentcounter; ?>
			<h<?php echo $h; ?>>
				<a class="u-url" href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>">
					<span class="p-name"><?php the_title(); ?></span>
				</a>
			</h<?php echo $h; ?>>
		<?php endif; ?>

		<!-- semantic data -->
		<div class="hide">
			<?php echo $petermolnareu_theme->author( true ); ?>
			<p class="u-uid"><?php echo wp_get_shortlink(); ?></p>
		</div>
		<!-- end of semantic data -->

		<!-- reply / repost / like / webmention data -->
			<?php $petermolnareu_theme->repost_data(); ?>
		<!-- end of reply / repost / like / webmention data -->
	</header>

	<!-- article content -->
	<?php $aid = get_post_thumbnail_id( $post->ID );

	if ( $content_type == 'image') : ?>
		<a class="u-url" href="<?php the_permalink(); ?>">
			<?php
				$title = get_the_title();
				echo do_shortcode( '[adaptimg aid=' . $aid .' title="'. $title .'" share=0]');
			?>
		</a>
	<?php else:
		ob_start();

		if ( $content_type == 'e-summary' )
			the_excerpt();
		else
			the_content();

		$content = ob_get_clean();

		?>
		<div class="article-content <?php echo $contenttype ?>">
			<?php if ( $featimg && has_post_thumbnail () ) : ?>
				<figure class="article-thumbnail">
					<a href="<?php the_permalink() ?>">
					<?php
						$thumb = get_the_post_thumbnail( $post->ID, 'thumbnail', array(
							'alt'	=> trim(strip_tags( $post->post_title )),
							'title'	=> trim(strip_tags( $post->post_title )),
							'class'	=> "u-photo",
						));
						echo $petermolnareu_theme->replace_if_ssl ( $thumb );
					?>
					</a>
				</figure>
			<?php endif ?>

			<?php echo $content ?>
		</div>
	<?php endif; ?>

	<!-- article footer -->
	<?php if ( $footer ) : ?>
	<footer class="article-footer">

		<?php if ( $showtags ): ?>
			<h6><?php _e('Posted in:', $petermolnareu_theme->theme_constant) ?></h6>
		<?php endif; ?>

		<?php $hidetags = ( $showtags ) ? '' : ' hide'; ?>
		<?php the_tags('<nav class="p-category'.$hidetags.'">', ', ', '</nav>'); ?>

		<?php if ( function_exists('add_js_rel_syndication')) echo add_js_rel_syndication(''); ?>

		<?php
		//$rt = get_post_meta( get_the_ID(), 'twitter_retweeted_status_id', true );
		$tw = get_post_meta( get_the_ID(), 'twitter_tweet_id', true );
		if ( !empty($tw) ) :
			$twlnk = 'https://twitter.com/petermolnar/status/' . $tw;
			?>
			<nav class="usyndication"><h6><?php _e('Also on:', $petermolnareu_theme->theme_constant); ?></h6><ul><li><a class='u-syndication icon-twitter link-twitter' href='<?php echo $twlnk; ?>'> Twitter</a></li></ul></nav>
		<?php endif; ?>

		<?php
			echo $petermolnareu_theme->share_ ( get_permalink() , wp_title( '', false ), true );
		?>
	</footer>
	<?php endif; ?>

</article>

<!-- related posts -->
<?php  if ($sidebar) : ?>
	<aside class="sidebar">
	<?php echo $petermolnareu_theme->related_posts( $post ); ?>
	</aside>
<?php endif; ?>
