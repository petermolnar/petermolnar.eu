<?php

/**
 * register widget function
 */
	if ( function_exists('register_sidebar') )
	{

		register_sidebar(array(
			'name' => 'sidebar',
			'before_widget' => '',
			'after_widget' => '',
			'before_title' => '<h2>',
			'after_title' => '</h2>',
		));

		register_sidebar(array(
			'name' => 'footer-widget',
			'before_widget' => '',
			'after_widget' => '',
			'before_title' => '<h4>',
			'after_title' => '</h4>',
		));


	}

/**
 * Returns unordered list of current category's posts
 *
 */
function wp_list_posts( $limit=-1 ) {
	global $post;

	$categories = get_the_category( $post->ID );

	foreach ($categories as $category) {
		$out .= '<h3 class="widget-title">'. $category->name .'</h3>';
		$out .= '<ul class="list-of-posts">';
		$posts = get_posts( array( 'category' => $category->cat_ID , 'orderby' => 'date' , 'order' => 'DESC' , 'numberposts' => $limit ));

		foreach ($posts as $post) {
			$post_title = htmlspecialchars(stripslashes($post->post_title));
			$out .= '<li><a href="' . get_permalink($post->ID) . '" title="'. $post_title .'" >' . $post_title . '</a> </li>';
		}
		$out .= '</ul>';
	}
	return $out;

}

function lightbox_filter ($content) {
	global $post;

	$pattern = "/(<a(?![^>]*?rel=['\"]lightbox.*)[^>]*?href=['\"][^'\"]+?\.(?:bmp|gif|jpg|jpeg|png)['\"][^\>]*)>/i";
	$replacement = '$1 rel="lightbox['.$post->title.']">';

	return preg_replace($pattern, $replacement, $content);
}
?>