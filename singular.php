<?php
the_post();

global $petermolnareu_theme;

$twigvars = petermolnareu::template_vars();
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
