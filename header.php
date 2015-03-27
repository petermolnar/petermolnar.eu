<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<base href="<?php bloginfo("url") ?>" />
	<meta charset="<?php bloginfo('charset'); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title><?php wp_title( ); ?></title>
	<link rel="shortcut icon" href="<?php echo get_bloginfo('template_directory') . '/images/favicon.png' ?>" />
	<link rel="icon" href="<?php echo get_bloginfo('template_directory') . '/images/favicon.png' ?>" />
	<link rel="apple-touch-icon-precomposed" href="<?php echo get_bloginfo('template_directory') . '/images/favicon.png' ?>" />
	<?php wp_head(); ?>
	<?php petermolnareu::graphmeta(); ?>
</head>

<body>
