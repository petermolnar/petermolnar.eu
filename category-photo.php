<?php get_header(); ?>

<section class="content-body content-light h-feed am-container" id="am-container">

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

?>
</section>

<?php
get_footer();
