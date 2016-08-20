<?php

//include_once( dirname (__FILE__) . DIRECTORY_SEPARATOR . 'functions-ng.php');

global $wp;

$endpoint = pmlnr_comment::comment_endpoint();
// look for single comment page requests
if ( array_key_exists( $endpoint, $wp->query_vars ) ) {
	$comment_id = $wp->query_vars[$endpoint];
	$comment = get_comment($comment_id);

	if (pmlnr_base::is_comment($comment)) {
		$post = get_post( $comment->comment_post_ID);
		if (pmlnr_base::is_post($post)) {

			$twigvars = array (
				'site' => pmlnr_site::template_vars(),
				'post' => pmlnr_comment::template_vars( $comment, $post )
			);

			echo PETERMOLNAR\twig( 'comment.html', $twigvars );
			//$twigvars = pmlnr_comment::template_vars( $comment );
			//$twig = $petermolnareu_theme->twig->loadTemplate();
			//echo $twig->render($twigvars);
			exit;
		}
	}
}

$twigvars['site'] = pmlnr_site::template_vars();
$twigvars['taxonomy'] = pmlnr_archive::template_vars();
$twigvars['posts'] = array();

if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();
		$tmpl_vars = pmlnr_post::template_vars( $post );
		$tmpl_vars['singular'] = false;
		$twigvars['posts'][] = $tmpl_vars;
	}
}

echo PETERMOLNAR\twig( 'archive.html', $twigvars );
