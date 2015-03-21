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
	<?php petermolnareu::graphmeta(); ?>
	<?php wp_head(); ?>
</head>

<body>
	<header class="content-header">
		<div class="limit">
			<a href="#" id="showContentHeader" class="nav-toggle-button" > </a>
			<!-- <a href="<?php bloginfo( 'url' ) ?>" class="content-logo">
				<img src="<?php echo $logo_img; ?>" title="<?php bloginfo( 'name' ) ?>" alt="<?php bloginfo( 'name' ) ?>" />
			</a> -->
			<nav class="content-navigation">
				<?php wp_nav_menu( array( 'container' => '' , 'theme_location' => 'header'  ) ); ?>
			</nav>
			<?php /*echo pmlnr_article::vcard(false,false,false,2); */ ?>
		</div>
	</header>


