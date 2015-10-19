<?php
/**
 * template for single post
 */
?>

<section class="content-body" id="main-content">
	<article class="h-entry" id="post-<?php echo $post_id ?>" >
		<header <?php echo $post_bgstyle; ?>>
			<div class="content-inner">
				<?php include (dirname(__FILE__) . '/ameta_readtime.php'); ?>
				<?php include (dirname(__FILE__) . '/ameta_pubdate.php'); ?>
				<h1>
					<a class="u-url" href="<?php echo $post_url ?>" rel="bookmark" title="<?php echo $post_title ?>">
						<span class="p-name"><?php echo $post_title ?></span>
					</a>
				</h1>
				<div>
					<?php include (dirname(__FILE__) . '/ameta_author.php'); ?>
				</div>
			</div>
		</header>

		<div class="e-content">
			<div class="content-inner">
				<?php echo $post_content ?>
				<br class="clear" />
			</div>
		</div>

		<footer>
			<div class="content-inner">
			<?php
				//require_once (dirname(__FILE__) . '/ameta_relations.php');
				include (dirname(__FILE__) . '/ameta_imported.php');
				include (dirname(__FILE__) . '/list_tag.php');
				include (dirname(__FILE__) . '/list_reply.php');
				include (dirname(__FILE__) . '/list_share.php');
				//require_once (dirname(__FILE__) . '/list_siblings.php');
				include (dirname(__FILE__) . '/ameta_footer.php');
			?>
			</div>
		</footer>
	</article>
</section>
