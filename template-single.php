<?php
global $post;
global $petermolnareu_theme;


$ameta = $petermolnareu_theme->article_meta ();
$theme = empty( $ameta['theme'] ) ? 'dark' : $ameta['theme'];

if ( is_singular()) {
	$h = 1;
	$more = '';
	?>
	<section class="content-body content-<?php echo $theme; ?>">
	<?php
}
else {
	$h = 2;
	$more = '';
}

ob_start();
the_excerpt();
$excerpt = ob_get_clean();

ob_start();
the_content();
$content = ob_get_clean();

$words = str_word_count(strip_tags($content), 0 );
$commentcounter = '<a class="u-url right icon-comment commentcounter" href="'. get_the_permalink() . '#comments">'. get_comments_number( '', '1', '%' ) . '</a>';
$mins = '<span class="right minstoread">'. round( $words / 300 ) . ' mins to read</span>';

?>

<article id="post-<?php the_ID(); ?>" class="h-entry <?php echo $ameta['class']; ?>">

	<!-- prev-next links -->
	<?php if ( $ameta['siblings'] ) : ?>
	<nav class="siblings">
		<div class="link left"><?php	next_post_link( '&laquo; %link' , '%title' , true ); ?></div>
		<div class="link right"><?php	previous_post_link( '%link &raquo; ' , '%title' , true ); ?></div>
		<br class="clear" />
	</nav>
	<?php endif; ?>

	<!-- article header -->
	<header class="article-header">
		<?php if ( $ameta['limitwidth'] ) echo '<div class="content-inner">'; ?>

		<?php  if ( $ameta['header'] == 'none' ) : ?>
			<div class="hide">
				<span class="p-name"><?php the_title(); ?></span>
				<?php echo $petermolnareu_theme->article_time(); ?>
			</div>
		<?php  elseif ( $ameta['header'] == 'pubdate' ) : ?>
			<?php if ($ameta['showccntr']) echo $commentcounter; ?>
			<a class="u-url" href="<?php the_permalink() ?>">
				<?php echo $petermolnareu_theme->article_time(); ?>
			</a>
			<span class="hide p-name"><?php the_title(); ?></span>
		<?php elseif ( $ameta['header'] == 'small' ): ?>
			<h<?php echo $h; ?>>
				<a class="u-url" href="<?php the_permalink() ?>">
					<span class="p-name"><?php the_title(); ?></span><?php echo $more; ?>
				</a>
			</h<?php echo $h; ?>>
			<span class="hide"><?php echo $petermolnareu_theme->article_time(); ?></span>
		<?php elseif ( $ameta['header'] == 'normal'): ?>
			<?php if ($ameta['showccntr']) echo $commentcounter; ?>
			<?php echo $mins; ?>
			<?php echo $petermolnareu_theme->article_time(); ?>
			<h<?php echo $h; ?>>
				<a class="u-url" href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>">
					<span class="p-name"><?php the_title(); ?></span><?php echo $more; ?>
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

		<?php if ( $ameta['limitwidth'] ) echo '</div>'; ?>
	</header>

	<!-- article content -->
	<?php $aid = get_post_thumbnail_id( $post->ID );

	if ( $ameta['content_type'] == 'image') : ?>
		<a class="u-url" href="<?php the_permalink(); ?>">
			<?php
				$title = get_the_title();
				echo do_shortcode( '[adaptimg aid=' . $aid .' title="'. $title .'" share=0]');
			?>
		</a>
	<?php else: ?>
		<div class="article-content <?php echo $ameta['content_type'] ?>">
			<?php if ( $ameta['limitwidth'] ) echo '<div class="content-inner">'; ?>
			<?php if ( $ameta['featimg'] && has_post_thumbnail () ) : ?>
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

			<?php
				if ( $ameta['content_type'] == 'e-summary' )
					echo $excerpt;
				else
					echo $content;
			?>
			<br class="clear" />
			<?php if ( $ameta['limitwidth'] ) echo '</div>'; ?>
		</div>
	<?php endif; ?>

	<!-- article footer -->
	<?php if ( $ameta['footer'] ) : ?>
	<footer class="article-footer">
		<?php if ( $ameta['limitwidth'] ) echo '<div class="content-inner">'; ?>
		<?php if ( $ameta['showtags'] ): ?>
			<h6><?php _e('Posted in:', $petermolnareu_theme->theme_constant) ?></h6>
		<?php endif; ?>

		<?php $hidetags = ( $ameta['showtags'] ) ? '' : ' hide'; ?>
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
		<?php if ( $ameta['limitwidth'] ) echo '</div>'; ?>
	</footer>
	<?php endif; ?>

</article>

<!-- related posts -->
<?php
if ($ameta['sidebar']) :
	$sposts = $petermolnareu_theme->related_posts( $post, false, 4 );
	if ( $sposts ): ?>
	<aside class="sidebar content-inner">
		<?php echo $sposts; ?>
	</aside>
<?php endif;
endif;
?>
