<?php $post_url = get_the_permalink(); ?>
<?php $post_title = get_the_title(); ?>
<?php $post_images = adaptive_images::imagewithmeta( get_post_thumbnail_id( $post->ID ) ) ?>
<?php $post_image = (empty($post_images)) ? false : $post_images['mediumurl'] ?>
<?php $meta_content = petermolnareu::get_metacontent( $post->ID); ?>
<?php $post_format = petermolnareu::get_type($post->ID); ?>
<?php
	$setbg = ( empty($post_format) or $post_format == 'standard' ) ? true : false;

	$bgimg = (empty( $post_images) or !$setbg ) ? array() : wp_get_attachment_image_src(  $post_images['id'] , 'headerbg');

	if ( !$setbg or !isset($bgimg[1]) or $bgimg[3] == false )
		$bgimg = false;

	$hstyle = ( $bgimg ) ? 'class="article-header" style="background-image:url('.$bgimg[0].');"' : '';


	petermolnareu::doyaml($post->ID);
?>

<?php

// syndication creator & cleaner
petermolnareu::makesyndication();

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
			<?php
				the_content();
			?>
			<br class="clear" />
		</div>
	</div>

	<footer>
		<div class="content-inner">
		<?php
			require_once (dirname(__FILE__) . '/ameta_relations.php');
			require_once (dirname(__FILE__) . '/ameta_imported.php');
			require_once (dirname(__FILE__) . '/list_tag.php');
			require_once (dirname(__FILE__) . '/list_reply.php');
			require_once (dirname(__FILE__) . '/list_share.php');
			require_once (dirname(__FILE__) . '/list_siblings.php');
		?>
		</div>
	</footer>
</article>
