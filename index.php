<?php
global $query_string;
$_query_string = $query_string;

$theme = 'light';

$cat = get_query_var('cat');
$tag = get_query_var('tag');

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

query_posts( $_query_string );

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
