<?php header('HTTP/1.0 404 Not Found'); ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>" />
	<title><?php wp_title( ); ?></title>
	<link rel="author" href="https://plus.google.com/117393351799968573179/posts" />
	<?php wp_head(); ?>
</head>

<body>
	<section class="content-body round">
		<h1 style="text-align: center">Nope, that's not here.</h1>
		<h3 style="text-align: center">Yes, that means 404.</h3>
	</section>

	<footer class="content-footer aligncenter">
		<?php wp_footer(); ?>
	</footer>

</body>
</html>
<?php exit(1); ?>
