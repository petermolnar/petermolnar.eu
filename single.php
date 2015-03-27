<?php

if ( $_SERVER['SERVER_ADDR'] == $_SERVER['REMOTE_ADDR'] || $_SERVER['REMOTE_ADDR'] == '127.0.0.1' ) {
	include_once ( dirname(__FILE__) . '/flat.php');
	exit;
}
?>

<?php the_post(); ?>
<?php get_header(); ?>

<section class="content-body content-<?php echo $meta['theme'] ?>">
	<?php require (dirname(__FILE__) . '/partials/element-singular.php'); ?>
</section>
<?php get_footer(); ?>
