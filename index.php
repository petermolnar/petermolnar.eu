<?php get_header(); ?>

<section class="content-body h-feed" id="main-content">

<?php

	if ( have_posts() ) {
		while ( have_posts() ) {
			the_post();

			$post_id = get_the_ID();

			//$post_length = strlen( $post->post_content );
			//$is_photo = pmlnr_image::is_u_photo($post);
			$format = pmlnr_post::post_format_discovery($post);

			extract(pmlnr_post::template_vars( $post ), EXTR_PREFIX_ALL, 'post' );
			//extract($data );

			if (in_array($format, array('photo', 'image')))
				include( dirname(__FILE__). '/partials/element-photo.php' );
			elseif ( in_array($format, array('article')) )
				include( dirname(__FILE__). '/partials/element-long.php' );
			else
				include( dirname(__FILE__). '/partials/element-short.php' );
		}
	}

?>
</section>

<?php
get_template_part( '/partials/paginate' );

get_footer();
