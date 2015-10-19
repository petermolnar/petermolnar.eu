<?php
the_post();
get_header();

extract(pmlnr_post::template_vars( $post ), EXTR_PREFIX_ALL, 'post' );

petermolnareu::make_post_syndication ($post);
petermolnareu::check_shorturl ($post);
petermolnareu::export_yaml($post);

include (dirname(__FILE__) . '/partials/element-singular.php');

get_footer();
