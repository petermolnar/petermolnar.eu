<?php
	global $wp_query;

	$wp_query->set_404();
	header('HTTP/1.0 404 Not Found');

	$twigvars = array (
		'site' => pmlnr_site::template_vars(),
	);

	$tmpl = $petermolnareu_theme->twig->loadTemplate('404.html');
	echo $tmpl->render($twigvars);
