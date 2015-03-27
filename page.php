<?php

get_header();

the_post();

/* get the content */
ob_start();
the_content();
$content = ob_get_clean();

?>
<section class="content-body content-dark">
	<article id="post-<?php the_ID(); ?>" class="h-entry">

		<!-- article meta -->
		<header class="hide">
			<?php require_once (dirname(__FILE__) . '/partials/ameta_author.php'); ?>
			<?php require_once (dirname(__FILE__) . '/partials/ameta_pubdate.php'); ?>
		</header>
		<!-- end article meta ->

		<!-- article content -->
		<div class="article-content e-content">
			<div class="content-inner">
				<?php echo $content; ?>
				<br class="clear" />
			</div>
		</div>
		<!-- endcontent -->

	</article>
</section>

<?php
get_footer();
