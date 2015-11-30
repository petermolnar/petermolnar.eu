<?php
the_post();

get_header();

$twigvars = pmlnr_post::template_vars( $post, 'post_' );
extract(pmlnr_post::template_vars( $post ), EXTR_PREFIX_ALL, 'post' );
//extract(pmlnr_author::template_vars( $post ), EXTR_PREFIX_ALL, 'post_author' );
petermolnareu::make_post_syndication ($post);
//petermolnareu::check_shorturl ($post);
petermolnareu::export_yaml($post);
pmlnr_post::post_format($post);

//pmlnr_base::livedebug( $twigvars );

//include (dirname(__FILE__) . '/partials/element-singular.php');
$singular = $petermolnareu_theme->twig->loadTemplate('element-singular.html');
echo $singular->render($twigvars);

get_footer();
