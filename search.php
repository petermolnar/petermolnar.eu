<?php
/*
Template Name: Search Page
*/

global $is_search;
global $query_string;
$is_search = true;

get_header();
?>
<section class="content-body">
	<h1><?php _e( "Displaying results for:" ); echo '"'. get_query_var('s'). '"'; ?></h1>
	<?php
		if ( have_posts() ):
			while (have_posts()) :
				the_post();
				$twigvars = pmlnr_post::template_vars( $post, 'post_' );
				$tmpl = $petermolnareu_theme->twig->loadTemplate('element-long.html');
				echo $tmpl->render($twigvars);
			endwhile;

		endif;

	include(dirname(__FILE__) . '/partials/paginate.php' );
	?>
</section>

<?php get_footer(); ?>
