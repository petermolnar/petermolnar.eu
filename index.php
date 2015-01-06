<?php
global $query_string;
$_query_string = $query_string;

$theme = 'light';

$cat = get_query_var('cat');
$tag = get_query_var('tag');

/*
if ( is_user_logged_in()) {
	$Qstr = $query_string . '&posts_per_page=-1';
	$P = new WP_Query( $Qstr );
	while ( $P->have_posts() ) {
			$P->the_post();
			//print_r ( $P );
			$Pids[] = $post->ID;
			echo "\nINSERT INTO `pm_postmeta` (post_id,meta_key,meta_value) VALUES  ('".$post->ID."','cc','by-nc-nd');";
	}

	wp_reset_postdata();
}
*/

if ( !empty( $cat ) ) {
	/* get category */
	$category = get_category( $cat );

	/* post per page "feature" fix */
	/*$posts_per_page = $category_meta['posts-per-page'] + $category_meta['sidebar-entries'];
	$_query_string = $query_string . '&posts_per_page=' . $category_meta['posts-per-page'] . '&order=DESC&orderby=' . $category_meta['order-by'];*/
}

if ( !empty( $tag ) ) {
	$_query_string = $query_string . '&posts_per_page=-1';
}

get_header();

?>

<section class="content-body content-<?php echo $theme; ?>">

<?php
	if ( have_posts() ) {
		while ( have_posts() ) {
			get_template_part('listelement');
		}
	}

	if( function_exists('wp_paginate') ) wp_paginate();

?>
</section>

<?php
get_footer();
