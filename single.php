<?php the_post(); ?>

<?php
	//if (is_user_logged_in()) {
		//require (dirname(__FILE__) . '/amp.php');
		//return;
	//}
?>

<?php get_header(); ?>

<section class="content-body content-light" id="main-content">
	<?php require (dirname(__FILE__) . '/partials/element-singular.php'); ?>
</section>
<?php get_footer();
