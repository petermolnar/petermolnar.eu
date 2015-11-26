<?php
/**
 * template for single post
 *
 */
?>

<section class="content-body" id="main-content">
	<article class="h-entry" id="post-<?php echo $post_id ?>" >
		<header <?php echo $post_bgstyle; ?>>
			<div class="content-inner">

				<?php //include (dirname(__FILE__) . '/ameta_readtime.php'); ?>
				<?php
					$ameta_readtime = $petermolnareu_theme->twig->loadTemplate('ameta_readtime.html');
					echo $ameta_readtime->render($twigvars);
				?>

				<?php //include (dirname(__FILE__) . '/ameta_pubdate.php'); ?>
				<?php
					$ameta_pubdate = $petermolnareu_theme->twig->loadTemplate('ameta_pubdate.html');
					echo $ameta_pubdate->render($twigvars);
				?>

				<h1>
					<a class="u-url" href="<?php echo $post_url ?>" rel="bookmark" title="<?php echo $post_title ?>">
						<span class="p-name"><?php echo $post_title ?></span>
					</a>
				</h1>
				<?php
					$ameta_author = $petermolnareu_theme->twig->loadTemplate('ameta_author.html');
					echo $ameta_author->render($twigvars);
				?>
				<?php //include (dirname(__FILE__) . '/ameta_author.php'); ?>
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

				//include (dirname(__FILE__) . '/ameta_imported.php');
				$ameta_imported = $petermolnareu_theme->twig->loadTemplate('ameta_imported.html');
				echo $ameta_imported->render($twigvars);

				//include (dirname(__FILE__) . '/list_tag.php');
				$list_tag = $petermolnareu_theme->twig->loadTemplate('list_tag.html');
				echo $list_tag->render($twigvars);

				//include (dirname(__FILE__) . '/list_reply.php');
				$list_reply = $petermolnareu_theme->twig->loadTemplate('list_reply.html');
				echo $list_reply->render($twigvars);


				//include (dirname(__FILE__) . '/list_share.php');
				$list_share = $petermolnareu_theme->twig->loadTemplate('list_share.html');
				echo $list_share->render($twigvars);

				//require_once (dirname(__FILE__) . '/list_siblings.php');
				//include (dirname(__FILE__) . '/ameta_footer.php');
				$ameta_footer = $petermolnareu_theme->twig->loadTemplate('ameta_footer.html');
				echo $ameta_footer->render($twigvars);
			?>
			</div>
		</footer>
	</article>
</section>
