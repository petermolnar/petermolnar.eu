<?php
global $post;
global $petermolnareu_theme;


$ameta = $petermolnareu_theme->article_meta ();

if ( is_singular()) {
	$h = 1;
	$more = '';
	?>
	<section class="content-body content-<?php echo $ameta['theme']; ?>">
	<?php
}
else {
	$h = 2;
	$more = '';
}


if ( $ameta['content_type'] == 'e-summary' ) {
	/* get the excerpt */
	ob_start();
	the_excerpt();
	$excerpt = ob_get_clean();
}

/* get the content */
ob_start();
the_content();
$content = ob_get_clean();

$commentcounter = ($ameta['showccntr']) ? '<a class="u-url right icon-comment commentcounter" href="'. get_the_permalink() . '#comments">'. get_comments_number( '', '1', '%' ) . '</a>' : '';

$thid = ( has_post_thumbnail () ) ? get_post_thumbnail_id( $post->ID ) : false;

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
	<?php
	$hstyle = 'class="article-header"';

	if ( is_singular() && $ameta['featimg'] && !empty( $thid ) ) {
		$bgimg = wp_get_attachment_image_src( $thid, 'large' );
		if ( $bgimg[1] > 720 ) $hstyle = 'class="article-header article-header-singular" style="background-image:url('.$bgimg[0].');"';
	}
	?>
	<header <?php echo $hstyle; ?>>
		<?php if ( $ameta['limitwidth'] ) echo '<div class="content-inner">'; ?>

		<?php  if ( $ameta['header'] == 'none' ) : ?>
			<div class="hide">
				<span class="p-name"><?php the_title(); ?></span>
				<?php echo $petermolnareu_theme->article_time(); ?>
			</div>
		<?php  elseif ( $ameta['header'] == 'pubdate' ) : ?>
			<?php echo $commentcounter; ?>
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
			<?php echo $commentcounter; ?>
			<?php echo '<span class="right minstoread">'. round( str_word_count(strip_tags($content), 0 ) / 300 ) . ' mins to read</span>'; ?>
			<?php echo $petermolnareu_theme->article_time(); ?>
			<h<?php echo $h; ?>>
				<a class="u-url" href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>">
					<span class="p-name more"><?php the_title(); ?></span>
				</a>
			</h<?php echo $h; ?>>
		<?php elseif ( $ameta['header'] == 'titled'): ?>
			<?php echo $commentcounter; ?>
			<?php echo $petermolnareu_theme->article_time(); ?>
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

		<?php if ( $ameta['limitwidth'] ) echo '</div>'; ?>
	</header>

	<!-- article content -->
	<?php $aid = get_post_thumbnail_id( $post->ID );

	/* portfolio & gallery list view images */
	if ( $ameta['content_type'] == 'image') : ?>
		<a class="u-url" href="<?php the_permalink(); ?>">
			<?php
				$title = get_the_title();
				echo do_shortcode( '[adaptimg aid=' . $aid .' title="'. $title .'"]');
			?>
		</a>
	<?php else: ?>
		<div class="article-content <?php echo $ameta['content_type'] ?>">
			<?php
				if ( $ameta['limitwidth'] )
					echo '<div class="content-inner">';

				if ( $ameta['featimg'] && has_post_thumbnail () && !is_singular() ) :
					/*if ( is_singular() ):

						$thumb = get_the_post_thumbnail( $post->ID, 'medium', array(
							'alt'	=> trim(strip_tags( $post->post_title )),
							'title'	=> trim(strip_tags( $post->post_title )),
							'class'	=> "u-photo alignleft",
						));
						echo $petermolnareu_theme->replace_if_ssl ( $thumb );

					else:*/
					 ?><figure class="article-thumbnail"><a href="<?php the_permalink() ?>"><?php
						$thumb = get_the_post_thumbnail( $post->ID, 'thumbnail', array(
							'alt'	=> trim(strip_tags( $post->post_title )),
							'title'	=> trim(strip_tags( $post->post_title )),
							'class'	=> "u-photo",
						));
						echo $petermolnareu_theme->replace_if_ssl ( $thumb );
					?></a></figure><?php
					//endif;
				endif;

				if ( $ameta['content_type'] == 'e-summary' )
					echo $excerpt;
				else {
					if ( $ameta['adaptify'] )
						$content = $petermolnareu_theme->replace_images_with_adaptive ( $content );
					if ( $ameta['tweetify'] )
						$content = $petermolnareu_theme->tweetify ( $content );

					echo $content;
				}

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
			<h5><?php _e('Tagged as:', $petermolnareu_theme->theme_constant) ?></h5>
		<?php endif; ?>

		<?php $hidetags = ( $ameta['showtags'] ) ? '' : ' hide'; ?>
		<?php the_tags('<nav class="p-category'.$hidetags.'">', ', ', '</nav>'); ?>

		<?php
			echo $petermolnareu_theme->share_ ( get_permalink() , wp_title( '', false ), true );
		?>

		<h5><?php _e( '<a name="how-to-respond"></a>No, no comment form here. Still want to talk about it?', $petermolnareu_theme->theme_constant ); ?></h5>
		<?php
			$wm = __ ( '<p>Use <a href="http://indiewebcamp.com/webmentions" rel="nofollow">webmentions</a>, or send <a href="http://www.wpbeginner.com/beginners-guide/what-why-and-how-tos-of-trackbacks-and-pingbacks-in-wordpress/">a pingback or a trackback</a>, maybe mention <a href="https://twitter.com/petermolnar">@petermolnar</a> in a tweet with the thought', $petermolnareu_theme->theme_constant ); ?>

		<?php
			$syndicated = array();
			if ( function_exists('getRelSyndicationFromSNAP'))
				$syndicated = getRelSyndicationFromSNAP( true );

			$tw = get_post_meta( get_the_ID(), 'twitter_tweet_id', true );
			if ( !empty($tw) )
				$syndicated['twitter'] = 'https://twitter.com/petermolnar/status/' . $tw;

			if (!empty($syndicated)) {
				$wm .=  __ ( ", or reply on:</p>", $petermolnareu_theme->theme_constant );
				echo $wm;
				echo '<nav class="usyndication"><ul>' . implode ( "\n", $syndicated ) . '</ul></nav>';
				//_e ( '<p class="small">Your comment will show up here as well, thanks to <a href="https://www.brid.gy/">bridgy</a>.</p>', $petermolnareu_theme->theme_constant );
			}
			else {
				$wm .= '.</p>';
				echo $wm;
			}

		?>
		<?php if ( $ameta['limitwidth'] ) echo '</div>'; ?>
	</footer>
	<?php endif; ?>

</article>

<!-- related posts -->
<?php
if ($ameta['sidebar']) :
	$sposts = $petermolnareu_theme->related_posts( $post, true, 4 );
	if ( $sposts ): ?>
	<aside class="sidebar content-inner">
		<?php echo $sposts; ?>
	</aside>
<?php endif;
endif;
?>
