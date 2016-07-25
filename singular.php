<?php
the_post();

global $petermolnareu_theme;
$twigvars = array (
	'site' => pmlnr_site::template_vars(),
	'post' => pmlnr_post::template_vars( $post )
);

$tmpl = 'singular.html';

if (is_page())
	$tmpl = 'page.html';

$twig = $petermolnareu_theme->twig->loadTemplate( $tmpl );
echo $twig->render($twigvars);