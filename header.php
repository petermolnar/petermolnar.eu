<!DOCTYPE html>
<html>
<head>
	<meta charset="<?php bloginfo('charset'); ?>" />
	<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" />
	<title><?php wp_title( ); ?></title>
	<?php wp_head(); ?>
	<meta name="google-site-verification" content="8cMr_dHrPJe84VNp4jCsIp0VEGUfpfmanyKmpbTh7oM" />
</head>


<body>
	<?php
		if ( strstr( ABSPATH , 'disk' ))
			echo '<h1 style="background-color:#0ff; position:absolute; top:0; right:0; text-align:right; font-size: 70%; z-index:200">running from localhost</h1>';
	?>

	<header class="content-header">
		<nav class="content-navigation">
			<?php wp_nav_menu( array( 'container' => '' , 'theme_location' => 'header'  ) ); ?>
		</nav>
		<figure class="content-logo">
			<a href="<?php bloginfo( 'url' ); ?>">
				<img src="<?php bloginfo("stylesheet_directory"); ?>/images/logo.png" title="<?php bloginfo( 'name' ) ?>" alt="<?php bloginfo( 'name' ) ?>" />
			</a>
		</figure>
	</header>

	<section class="content-body round">