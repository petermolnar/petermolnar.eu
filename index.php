<?php get_header(); ?>

<section class="content-body h-feed" id="main-content">

<?php

	if ( have_posts() ) {
		while ( have_posts() ) {
			the_post();

			$post_id = get_the_ID();

			$format = pmlnr_post::post_format($post);
			$twigvars = pmlnr_post::template_vars( $post, 'post_' );

			if (in_array($format, array('photo', 'image')))
				$tmpl = 'element-photo.html';
			elseif ( in_array($format, array('article')) )
				$tmpl = 'element-long.html';
			else
				$tmpl = 'element-short.html';

			$tmpl = $petermolnareu_theme->twig->loadTemplate($tmpl);
			echo $tmpl->render($twigvars);

		}
	}

?>
</section>

<?php
get_template_part( '/partials/paginate' );

get_footer();
