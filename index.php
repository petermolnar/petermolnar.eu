<?php get_header(); ?>

<section class="content-body content-light h-feed" id="main-content">

<?php

	if ( have_posts() ) {
		while ( have_posts() ) {
			the_post();

			$post_id = get_the_ID();

			$post_length = strlen( $post->post_content );
			$is_photo = adaptive_images::is_u_photo($post);

			if ( $post_length > ARTICLE_MIN_LENGTH )
				get_template_part( '/partials/element-long' );
			elseif ( $is_photo )
				get_template_part( '/partials/element-photo' );
			else
				get_template_part( '/partials/element-short' );
		}
	}

?>
</section>

<?php
get_template_part( '/partials/paginate' );

get_footer();
