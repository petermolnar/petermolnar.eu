<?php get_header(); ?>

<section class="content-body h-feed" id="main-content">

<?php

	if ( have_posts() ) {
		while ( have_posts() ) {
			the_post();

			$post_id = get_the_ID();

			$post_length = strlen( $post->post_content );
			$is_photo = pmlnr_image::is_u_photo($post);

			extract(pmlnr_post::template_vars( $post ), EXTR_PREFIX_ALL, 'post' );
			//extract($data );

			if ( $post_length > ARTICLE_MIN_LENGTH )
				include( dirname(__FILE__). '/partials/element-long.php' );
			elseif ( $is_photo )
				include( dirname(__FILE__). '/partials/element-photo.php' );
			else
				include( dirname(__FILE__). '/partials/element-short.php' );
		}
	}

?>
</section>

<?php
get_template_part( '/partials/paginate' );

get_footer();
