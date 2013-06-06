<?php
	global $post;
	global $petermolnareu_theme;
?>

<arcticle id="page-<?php the_ID(); ?>" class="single-arcticle">
	<?php the_content(); ?>
	<footer>
		<?php $petermolnareu_theme->share ( get_permalink() , wp_title( '', false ), $post->ID ); ?>
	</footer>
</arcticle>
