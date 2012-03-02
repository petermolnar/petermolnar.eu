<?php get_header(); ?>

	<?php

	$category = array_pop( get_the_category() );
	$template = TEMPLATEPATH . '/single-' . $category->slug . '.php';
	$default = TEMPLATEPATH . '/single-default.php';

	the_post();

	if (file_exists( $template ))
		include( $template );
	else
		include( $default );
	?>

<?php get_footer(); ?>