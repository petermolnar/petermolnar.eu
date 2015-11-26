<?php

global $petermolnareu_theme;

$tmpl = $petermolnareu_theme->twig->loadTemplate('header.html');
echo $tmpl->render(pmlnr_site::template_vars());
return;

/*
$favicon = get_bloginfo('template_directory') . '/images/favicon.png';

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<base href="<?php bloginfo("url") ?>" />
	<meta charset="<?php bloginfo('charset'); ?>" />
	<meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1" />
	<link rel="shortcut icon" href="<?php echo $favicon; ?>" />
	<link rel="apple-touch-icon" href="<?php echo $favicon;  ?>" />
	<link rel="icon" href="<?php echo $favicon;  ?>" />
	<link rel="alternate" type="application/rss+xml" title="<?php bloginfo('name'); ?> RSS Feed" href="<?php bloginfo('rss2_url'); ?>" />
	<?php if (is_user_logged_in() && is_singular()) {
		printf( '<link rel="amphtml" href="%s" />', get_the_permalink() . '/amp' );
	} ?>
	<?php wp_head(); ?>
</head>

<body>
*/
