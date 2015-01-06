<?php
global $petermolnareu_theme;
$logo_img = $petermolnareu_theme->image_url . 'peter_molnar_logo.svg';
$favicon = $petermolnareu_theme->image_url . 'favicon.png';

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<base href="<?php echo $petermolnareu_theme->base_url; ?>" />
	<meta charset="<?php bloginfo('charset'); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title><?php wp_title( ); ?></title>
	<link rel="shortcut icon" href="<?php echo "$favicon" ?>" />
	<link rel="icon" href="<?php echo "$favicon" ?>" />
	<link rel="apple-touch-icon-precomposed" href="<?php echo "$favicon" ?>" />
	<?php wp_head(); ?>
</head>

<body>
	<header class="content-header">
		<div class="limit">
			<a href="#" id="showContentHeader" class="nav-toggle-button" > </a>
			<a href="<?php bloginfo( 'url' ) ?>" class="content-logo">
				<img src="<?php echo $logo_img; ?>" title="<?php bloginfo( 'name' ) ?>" alt="<?php bloginfo( 'name' ) ?>" />
			</a>
			<nav class="content-navigation">
				<?php wp_nav_menu( array( 'container' => '' , 'theme_location' => 'header'  ) ); ?>
			</nav>
		</div>
	</header>


