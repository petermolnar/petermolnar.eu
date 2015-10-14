<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<base href="<?php bloginfo("url") ?>" />
	<meta charset="<?php bloginfo('charset'); ?>" />
	<meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1" />
	<title><?php wp_title( ); ?></title>
	<link rel="shortcut icon" href="<?php echo get_bloginfo('template_directory') . '/images/favicon.png' ?>" />
	<link rel="icon" href="<?php echo get_bloginfo('template_directory') . '/images/favicon.png' ?>" />
	<link rel="alternate" type="application/rss+xml" title="<?php bloginfo('name'); ?> RSS Feed" href="<?php bloginfo('rss2_url'); ?>" />
	<link rel="pgpkey" href="/pgp.asc">
	<?php wp_head(); ?>
</head>

<body>
