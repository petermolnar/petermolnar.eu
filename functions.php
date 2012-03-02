<?php

/**
 * register widget function
 */
function petermolnar_init () {
	/*
	 HTML5 fix for the brilliant IE
	*/
	/*wp_enqueue_script( 'html5.js' , 'http://html5shim.googlecode.com/svn/trunk/html5.js' , array('jquery') );*/
	wp_enqueue_script( 'html5.js' , get_bloginfo("stylesheet_directory") . '/html5.js' , array('jquery') );

	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'menus' );
	add_theme_support( 'post-formats', array( 'gallery', 'image' ) );

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

	add_filter('upload_mimes', 'custom_upload_mimes');
	function custom_upload_mimes ( $existing_mimes=array() ) {
		$existing_mimes['svg'] = 'image/svg+xml';
		return $existing_mimes;
	}

	//add_shortcode('skillmeter', 'shortcode_skillmeter');
	add_shortcode('readme', 'shortcode_readme');

}


/**
 * Returns unordered list of current category's posts
 *
 */
function wp_list_posts( $limit=-1 , $from=false ) {
	global $post;
	$out = '';
	$categories = get_the_category( $post->ID );

	foreach ($categories as $category) {
		if ( $limit == -1 && !$from )
			$title = $category->name;
		elseif ( ! $from )
			$title = 'Last '. $limit . ' of ' . $category->name;
		else
			$title = 'More of ' . $category->name;

		$posts = get_posts( array( 'category' => $category->cat_ID , 'orderby' => 'date' , 'order' => 'DESC' , 'numberposts' => $limit ));

		if ( $from != false )
		{
			for ($i=0; $i<$from; $i++)
			{
				array_shift ( $posts );
			}
		}


		if ( !empty ( $posts ))
		{
			$list = '';
			foreach ($posts as $post) {
				$post_title = htmlspecialchars(stripslashes($post->post_title));
				$list .= '
						<li>
							<a href="' . get_permalink($post->ID) . '" title="'. $post_title .'" >
								' . $post_title . '
							</a>
						</li>';
			}

			$out .= '
			<aside class="sidebar-widget">
				<nav class="sidebar-postlist">
					<h3 class="postlist-title">'. $title .'</h3>
					<ul class="postlist">
					'. $list .'
					</ul>
				</nav>
			</aside>';
		}
	}

	return $out;

}

function wp_share ( $link , $title ) {
	$class='opacity75 icon-share';

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

		//'iwiw'=>array (
		//	'url'=>'http://iwiw.hu/like.jsp?u=' . $link . '&title=' . $title,
		//	'name'=>'iWiW',
		//	'title'=>'megosztás az iWiWen'
		//),
		//
		//'tumblr'=>array (
		//	'url'=>'http://www.tumblr.com/share?v=3&u=' . $link . '&t=' . $title,
		//	'name'=>'tumblr',
		//	'title'=>'megosztás az Tumblrön'
		//),
		//
		//'digg'=>array (
		//	'url'=>'',
		//	'name'=>'Digg',
		//	'title'=>'megosztás a Diggel'
		//),

	);

	foreach ($share as $site=>$details)
		$out .= '
			<li>
				<a class="' . $class . ' icon-' . $site . '" href="' . $details['url'] . '" title="' . $details['title'] . '">
					' . $details['title'] . '
				</a>
			</li>';


	$out = '
	<nav class="share">
		<ul class="icons">
			'. $out .'
		</ul>
	</nav>';
	/*
		<!-- Google plus, shame on you for only providing JS solution -->
		<script type="text/javascript" src="http://apis.google.com/js/plusone.js"></script>
		<g:plusone href="' . $link . '"></g:plusone>
	*/

	echo $out;
}

//function shortcode_skillmeter ( $atts ,  $content = null ) {
//
//	extract( shortcode_atts(array(
//		'level' => 1
//	), $atts));
//
//	return '<span class="skill-level skill-level-'.$level.'">'.$level.'</span>';
//}

function shortcode_readme ( $atts ,  $content = null ) {

	extract( shortcode_atts(array(
		'level' => 1
	), $atts));

	if (empty($content))
		return false;

	require_once(ABSPATH . '/wp-load.php');

	if ( ! defined( 'WP_PLUGIN_DIR' ) )
		define( 'WP_PLUGIN_DIR', ABSPATH . 'wp-content/plugins' );

	$readme = WP_PLUGIN_DIR . '/' . $content .'/readme.txt';

	if (@is_readable( $readme ))
	{
		$readme = file_get_contents($readme);
		$readme = make_clickable(nl2br(wp_specialchars($readme)));
		$readme = preg_replace('/`(.*?)`/', '<tt>\\1</tt>', $readme);
		$readme = preg_replace('/[\040]\*\*(.*?)\*\*/', ' <strong>\\1</strong>', $readme);
		$readme = preg_replace('/[\040]\*(.*?)\*/', ' <em>\\1</em>', $readme);
		$readme = preg_replace('/=== (.*?) ===/', '<h2>\\1</h2>', $readme);
		$readme = preg_replace('/== (.*?) ==/', '<h3>\\1</h3>', $readme);
		$readme = preg_replace('/= (.*?) =/', '<h4>\\1</h4>', $readme);
		return $readme;
	}

	return false;
}

petermolnar_init();

?>
