<?php
/*
Template Name: Search Page
*/

global $is_search;
global $query_string;
$is_search = true;

get_header();
?>

	<section class="content-body content-light">

		<h1><?php _e( "Displaying results for:" ); echo '"'. get_query_var('s'). '"'; ?></h1>
		<?php
			if ( have_posts() ) {
				while ( have_posts() ) {
					get_template_part('listelement');
				}
			}
		?>
	</section>

<?php

if( function_exists('wp_paginate')) wp_paginate();

get_footer();
?>
