<?php get_header(); ?>

<section class="content-body content-light h-feed" id="main-content">

<?php

	global $query_string;
	$_query_string = $query_string . '&posts_per_page=24';
	//$posts_per_page = 24;
	query_posts( $_query_string );

	if ( have_posts() ) {
		while ( have_posts() ) {
			the_post();
			get_template_part( '/partials/element-photo' );
		}
	}

	petermolnareu::paginate();
/*
	else:

		global $query_string;
		$_query_string = $query_string . '&posts_per_page=24';
		query_posts( $_query_string );

		if ( have_posts() ) {
			?><div id="justified-gallery"><?php
			while ( have_posts() ) {
				the_post();

				$post_title = get_the_title();
				$post_aid = get_post_thumbnail_id( $post->ID );
				$adaptive = new adaptive_images;

				//$post_images = adaptive_images::imagewithmeta( $post_aid );
				$img = $adaptive->get_imagemeta( $post_aid );

				?>
				<a href="<?php echo get_permalink($post->ID) ?>">
					<img src="<?php echo $img['src']['h'][540][0] ?>" title="<?php echo $post->post_title ?>" width="<?php echo $img['src']['h'][540][1] ?>" height="<?php echo $img['src']['h'][540][2] ?>" />
					<div class="caption"><?php echo $post->post_title ?></div>
				</a>
				<?php
			}
			?></div><?php
		}

		?><script>
			jQuery("#justified-gallery").justifiedGallery({
				margins: 2,
				captions: true,
				rowHeight : 240,
				captionSettings: {
					animationDuration: 500,
					visibleOpacity: 0.8,
					nonVisibleOpacity: 0.4
				},
			});
		</script>
		<?php

		petermolnareu::paginate();

	endif;
*/

?>
</section>

<?php
get_footer();
