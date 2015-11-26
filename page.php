<?php

get_header();

the_post();

extract(pmlnr_post::template_vars( $post ), EXTR_PREFIX_ALL, 'post' );
//extract(pmlnr_author::template_vars( $post ), EXTR_PREFIX_ALL, 'post_author' );

?>
<section class="content-body">
	<article id="post-<?php echo $post_id; ?>" class="h-entry">

		<!-- article meta -->
		<header class="hide">
			<?php include (dirname(__FILE__) . '/partials/ameta_author.php'); ?>
			<?php include (dirname(__FILE__) . '/partials/ameta_pubdate.php'); ?>
		</header>
		<!-- end article meta -->

		<!-- article content -->
		<div class="e-content">
			<div class="content-inner">
				<?php echo $post_content; ?>
				<br class="clear" />
			</div>
		</div>
		<!-- endcontent -->

	</article>
</section>

<?php
get_footer();
