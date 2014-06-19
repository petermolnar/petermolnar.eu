<?php
global $petermolnareu_theme;
$logo_img = $petermolnareu_theme->image_url . 'peter_molnar_logo.svg';
$favicon = $petermolnareu_theme->image_url . 'favicon.png';

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title><?php wp_title( ); ?></title>
	<link rel="shortcut icon" href="<?php echo "$favicon" ?>" />
	<?php	wp_head(); ?>
</head>

<body>
	<header class="content-header"><div class="inner">
		<a href="#" id="showContentHeader" class="nav-toggle-button" > </a>
		<figure class="content-logo">
			<a href="<?php bloginfo( 'url' ) ?>">
				<img src="<?php echo $logo_img; ?>" title="<?php bloginfo( 'name' ) ?>" alt="<?php bloginfo( 'name' ) ?>" />
			</a>
		</figure><nav class="content-navigation">
			<?php wp_nav_menu( array( 'container' => '' , 'theme_location' => 'header'  ) ); ?>
		</nav>
	</div></header>


