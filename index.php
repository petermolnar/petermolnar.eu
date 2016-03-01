<?php

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

			//$twigvars = pmlnr_comment::template_vars( $comment );
			$twig = $petermolnareu_theme->twig->loadTemplate('comment.html');
			echo $twig->render($twigvars);
			exit;
		}
	}
}

$twigvars['site'] = pmlnr_site::template_vars();
$twigvars['archive'] = pmlnr_archive::template_vars();
$twigvars['posts'] = array();

if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();

		// cleanup
		//WP_SHORTSLUG::check_shorturl( $post->post_status, $post->post_status, $post );

		$tmpl_vars = pmlnr_post::template_vars( $post );
		$twigvars['posts'][] = $tmpl_vars;
	}
}

$twig = $petermolnareu_theme->twig->loadTemplate('archive.html');
echo $twig->render($twigvars);