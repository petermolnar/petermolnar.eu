<?php
	//wp_old_slug_redirect();

	global $wp_query;

	$wp_query->set_404();
	header('HTTP/1.0 404 Not Found');
	header('HTTP/1.1 404 Not Found');

	$tmpl = $petermolnareu_theme->twig->loadTemplate('404.html');
	echo $tmpl->render(pmlnr_site::template_vars());

?>
