<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>" />
	<title><?php wp_title( ); ?></title>
	<link rel="author" href="https://plus.google.com/117393351799968573179/posts" />
	<?php wp_head(); ?>
</head>


<body>
	<header class="content-header">
		<nav class="content-navigation">
			<?php wp_nav_menu( array( 'container' => '' , 'theme_location' => 'header'  ) ); ?>
		</nav>
		<?php
			$logo_img = get_bloginfo("stylesheet_directory") . '/images/petermolnar_logo.png';

/*
			$logo_url = get_bloginfo( 'url' );
			$logo_img = parse_url($_SERVER['REQUEST_URI']);
			$logo_img = explode( '/' , $logo_img['path']);

			if (empty($logo_img[0]))
			{
				$logo_url = $logo_url . '/';
				array_shift($logo_img);
			}

			if (isset($logo_img[0]) && !empty($logo_img[0]))
			{
				$logo_url = $logo_url . $logo_img[0];
				$logo_tax = get_category_by_slug ( $logo_img[0] );

				if ( !empty( $logo_tax ) )
					$logo_img = @array_shift(get_metadata ( 'taxonomy', $logo_tax->term_taxonomy_id, 'logo-replacement', true));
				else
					$logo_img = '';
			}
			else
			{
				$logo_img = '';
			}

			if ( empty( $logo_img ) )
				$logo_img = get_bloginfo("stylesheet_directory") . '/images/petermolnar_logo.png';
*/

		?>
		<figure class="content-logo">
			<a href="<?php echo $logo_url; ?>">
				<img src="<?php echo $logo_img; ?>" title="<?php bloginfo( 'name' ) ?>" alt="<?php bloginfo( 'name' ) ?>" />
			</a>
		</figure>
	</header>

	<section class="content-body round">
