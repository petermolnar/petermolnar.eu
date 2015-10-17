<?php
$post_url = get_the_permalink();
$post_title = get_the_title();
$hstyle = '';

/* background image for header, only to make the entry look more trendy */
if ( ! adaptive_images::is_u_photo($post) ) {

	$thid = get_post_thumbnail_id( $post->ID );

	$bgimg = (empty( $thid)) ? array() : wp_get_attachment_image_src( $thid , 'headerbg');

	if ( isset($bgimg[1]) && $bgimg[3] != false )
		$hstyle = 'class="article-header" style="background-image:url('.$bgimg[0].');"';
}

petermolnareu::exportyaml($post->ID);
petermolnareu::makesyndication();
petermolnareu::checkshorturl($post);

?>
<section class="content-body" id="main-content">
	<article class="h-entry" id="post-<?php the_ID(); ?>" >
		<header <?php echo $hstyle; ?>>
			<div class="content-inner">
				<?php require_once (dirname(__FILE__) . '/ameta_readtime.php'); ?>
				<?php require_once (dirname(__FILE__) . '/ameta_pubdate.php'); ?>
				<h1>
					<a class="u-url" href="<?php echo $post_url ?>" rel="bookmark" title="<?php echo $post_title ?>">
						<span class="p-name"><?php echo $post_title ?></span>
					</a>
				</h1>
				<div>
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
				//require_once (dirname(__FILE__) . '/ameta_relations.php');
				require_once (dirname(__FILE__) . '/ameta_imported.php');
				require_once (dirname(__FILE__) . '/list_tag.php');
				require_once (dirname(__FILE__) . '/list_reply.php');
				require_once (dirname(__FILE__) . '/list_share.php');
				//require_once (dirname(__FILE__) . '/list_siblings.php');
				require_once (dirname(__FILE__) . '/ameta_footer.php');
			?>
			</div>
		</footer>
	</article>
</section>
