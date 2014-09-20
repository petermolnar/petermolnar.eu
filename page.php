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
			<?php
				echo pmlnr_article::pubdate();
				echo pmlnr_article::photo();
				echo pmlnr_article::author();
			?>
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
