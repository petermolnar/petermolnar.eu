<?php get_header(); ?>

<section class="content-body content-light h-feed" id="main-content">

<?php
/*
if (is_user_logged_in()) {
	global $wp_scripts;
	var_dump($wp_scripts->queue);

	global $wp_styles;
	var_dump($wp_styles->queue);
}
*/
?>

<?php /*
	if (is_category()):
		$currcat = get_category(get_query_var('cat'),false);
		$currurl =  home_url() . '/' . $currcat->slug;
		$currfeed =  $currurl . '/feed' ;
?>
<div class="alignright content-inner">

<input type="button" class="button-rss" id="button-rss" name="button-rss" data-subtome-suggested-service-url="http://blogtrottr.com/?subscribe={feed}" data-subtome-suggested-service-name="Blogtrottr" data-subtome-feeds="<?php echo $currfeed; ?>" data-subtome-resource="<?php echo $currurl; ?>" value="&#xE80B; follow all new entries published in <?php echo $currcat->name; ?>" onclick="(function(btn){var z=document.createElement('script');document.subtomeBtn=btn;z.src='https://www.subtome.com/load.js';document.body.appendChild(z);})(this)" /></div>


<?php endif */ ?>

<?php

	if ( have_posts() ) {
		while ( have_posts() ) {
			the_post();

			$post_id = get_the_ID();
			$categories = get_the_terms( $post_id, 'category' );
			//$categories = get_the_category(get_the_ID());
			$category = ( $categories ) ? array_pop($categories) : null;

			if ( isset($category->slug) && !empty($category->slug) && file_exists( dirname(__FILE__) . '/partials/element-' . $category->slug . '.php' ))
				get_template_part( '/partials/element-' . $category->slug );
			else
				get_template_part( '/partials/element-journal' );
		}
	}

?>
</section>

<?php petermolnareu::paginate(); ?>

<?php
get_footer();
