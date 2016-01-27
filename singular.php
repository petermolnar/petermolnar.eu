<?php
//if (is_user_logged_in()) {
	//$url = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	////echo $url;
	//$url = preg_replace( '/^https?:\/\//i', 'http://', $url );
	//$post_id = url_to_postid( $url );
	//echo $post_id;
	//die('');
//}


the_post();

global $petermolnareu_theme;

//$twigvars = petermolnareu::template_vars();
$twigvars = array (
	'site' => pmlnr_site::template_vars(),
	'post' => pmlnr_post::template_vars( $post )
);
//WP_FLATBACKUPS::export_yaml($post);
//pmlnr_base::livedebug($twigvars);
//petermolnareu::migrate_stuff ($post);

if (is_page()) {
	$twig = $petermolnareu_theme->twig->loadTemplate('page.html');
}
else {
	petermolnareu::make_post_syndication ($post);

	pmlnr_post::post_format($post);
	$twig = $petermolnareu_theme->twig->loadTemplate('singular.html');
}

echo $twig->render($twigvars);

dynamic_sidebar( 'home_right_1' );
