<?php $post_url = get_the_permalink(); ?>
<?php $post_title = get_the_title(); ?>
<?php $post_images = adaptive_images::imagewithmeta( get_post_thumbnail_id( $post->ID ) ) ?>
<?php $post_image = (empty($post_images)) ? false : $post_images['mediumurl'] ?>
<?php $post_format = get_post_format($post->ID); ?>
<?php
	$setbg = ( empty($post_format) || $post_format == 'standard' ) ? true : false;

	$bgimg = (empty( $post_images) || !$setbg ) ? array() : wp_get_attachment_image_src(  $post_images['id'] , 'headerbg');

	if ( !isset($bgimg[1]) or $bgimg[3] == false )
		$bgimg = false;

	$hstyle = ( $bgimg ) ? 'class="article-header" style="background-image:url('.$bgimg[0].');"' : '';

?>

<?php
		petermolnareu::makesyndication();
?>

<?php
// syndication cleaner
$syn = [];
$_syn = get_post_meta( $post->ID, 'syndication_urls', true);
if ( $_syn && !empty($_syn))
	$syn = explode ("\n", $_syn);
?>

<article id="post-<?php the_ID(); ?>" class="h-entry">
	<header <?php echo $hstyle; ?>>
		<div class="content-inner">
			<?php require_once (dirname(__FILE__) . '/ameta_readtime.php'); ?>
			<?php require_once (dirname(__FILE__) . '/ameta_pubdate.php'); ?>
			<h1>
				<a class="u-url" href="<?php echo $post_url ?>" rel="bookmark" title="<?php echo $post_title ?>">
					<span class="p-name"><?php echo $post_title ?></span>
				</a>
			</h1>
			<div class="hide">
				<?php require_once (dirname(__FILE__) . '/ameta_author.php'); ?>
			</div>
		</div>
	</header>

	<div class="e-content">
		<div class="content-inner">
			<?php the_content(); ?>
			<br class="clear" />
		</div>
	</div>

	<footer>
		<div class="content-inner">
		<?php
			require_once (dirname(__FILE__) . '/list_tag.php');

			//if (is_user_logged_in())
			//	require_once (dirname(__FILE__) . '/list_reply.php');

			//require_once (dirname(__FILE__) . '/list_share.php');
			require_once (dirname(__FILE__) . '/list_siblings.php');
		?>
		</div>
	</footer>
</article>
