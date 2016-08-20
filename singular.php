<?php
the_post();

global $petermolnareu_theme;
$twigvars = array (
	'site' => pmlnr_site::template_vars(),
	'post' => pmlnr_post::template_vars( $post )
);

$twigvars['post']['singular'] = true;

$twigvars['meta'] = json_encode ( $twigvars['post'], JSON_PRETTY_PRINT );

$tmpl = 'singular.html';

if (is_page())
	$tmpl = 'page.html';

echo PETERMOLNAR\twig( $tmpl, $twigvars );
