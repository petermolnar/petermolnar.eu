<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title><?php wp_title( ); ?></title>
	<link rel="author" href="https://plus.google.com/117393351799968573179/posts" />
	<link rel="publisher" href="https://plus.google.com/117393351799968573179/posts" />
	<?php wp_head(); ?>
	<?php global $petermolnareu_theme; ?>
</head>


<?php $logo_img = $petermolnareu_theme->image_dir . 'peter_molnar_logo.svg'; ?>

<body>
	<a href="#" id="showContentHeader" class="nav-toggle-button" > </a>
	<header class="content-header"><div class="inner">
		<figure class="content-logo">
			<a href="<?php bloginfo( 'url' ) ?>">
				<img src="<?php echo $logo_img; ?>" title="<?php bloginfo( 'name' ) ?>" alt="<?php bloginfo( 'name' ) ?>" />
			</a>
		</figure><nav class="content-navigation">
			<?php wp_nav_menu( array( 'container' => '' , 'theme_location' => 'header'  ) ); ?>
		</nav><nav class="breadcrumbs"><?php $petermolnareu_theme->dimox_breadcrumbs(); ?></nav>
	</div></header>

	<section class="content-body"><div class="inner">
