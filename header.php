<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes() ?> xmlns:fb="http://www.facebook.com/2008/fbml">
<head>
	<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
	<meta name="verify-v1" content="SnWboc4r4qG/Nvp4SpjDxQ8GLJ3rCB1VNSe2y2+NENs=" />
	<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen,projection" />
	<title><?php wp_title( ); ?></title>
	<?php wp_head(); ?>
</head>


<body>

	<div id="header" class="container">
		<div id="menu">
			<?php wp_nav_menu( array( 'container' => '' , 'theme_location' => 'header'  ) ); ?>
		</div>
		<div id="logo">
				<a href="<?php bloginfo( 'url' ); ?>"><?php bloginfo( 'name' ) ?></a>
		</div>
	</div>

	<div class="content container">
