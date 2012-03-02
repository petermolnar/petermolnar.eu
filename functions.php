<?php

/**
 * register widget function
 */
function petermolnar_init () {

	register_nav_menus( array(
		'header' => 'főmenü',
		'portfolio' => 'portfolio',
	) );

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

function wp_share ( $link , $title , $class='opacity50 icon-share' ) {
	$share = array (

		'facebook'=>array (
			'url'=>'http://www.facebook.com/share.php?u=' . $link . '&t=' . $title,
			'name'=>'Facebook',
			'title'=>'megosztás a Facebook-on'
		),

		'twitter'=>array (
			'url'=>'http://twitter.com/home?status=' .$title . ' - ' . $link,
			'name'=>'Twitter',
			'title'=>'megosztás a Twitteren'
		),

		'iwiw'=>array (
			'url'=>'http://iwiw.hu/like.jsp?u=' . $link . '&title=' . $title,
			'name'=>'iWiW',
			'title'=>'megosztás az iWiWen'
		),

		//'tumblr'=>array (
		//	'url'=>'http://www.tumblr.com/share?v=3&u=' . $link . '&t=' . $title,
		//	'name'=>'tumblr',
		//	'title'=>'megosztás az Tumblrön'
		//),

		//'digg'=>array (
		//	'url'=>'',
		//	'name'=>'Digg',
		//	'title'=>'megosztás a Diggel'
		//),

	);

	echo '<ul class="menu icon">';
	foreach ($share as $site=>$details)
		echo '<li><a class="' . $class . ' icon-' . $site . '" href="' . $details['url'] . '" title="' . $details['title'] . '">' . $details['title'] . '</a></li>';
	echo '</ul>';

	//echo '<iframe src="http://www.facebook.com/plugins/like.php?href=' . $link . '&layout=standard&show_faces=true&width=288&height=36&action=like&font=verdana&colorscheme=light" scrolling="no" class="' . $class . ' icon-likebutton"></iframe>';
}

petermolnar_init();
?>
