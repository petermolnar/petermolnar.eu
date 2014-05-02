<?php

global $post;
global $petermolnareu_theme;
global $category;

if ( empty ( $category )) $category = array_shift( get_the_category( $post->ID ) );
$template = $category->slug;

/* in a list */
if ( !is_singular() ):

?><article class="photoblog-preview" id="photoblog-<?php the_ID(); ?>" ><a href="<?php the_permalink(); ?>">

	<?php $aid = get_post_thumbnail_id( );

		$title = get_the_title();
		echo do_shortcode( '[adaptimg aid=' . $aid .' title="'. $title .'"]');
	?>
</a></article><?php

/* photoblog with the prev / next links */
elseif ( $template == 'photoblog' ):

?><article class="photoblog-article" id="photoblog-<?php the_ID(); ?>" >
	<nav class="photoblog-navigation">
		<div class="link left"><?php	next_post_link( '&laquo; %link' , '%title' , true ); ?></div>
		<div class="link right"><?php	previous_post_link( '%link &raquo; ' , '%title' , true ); ?></div>
		<br class="clear" />
	</nav>

	<header class="photoblog-header">
		<h1><?php $petermolnareu_theme->dimox_breadcrumbs(); ?></h1>
	</header>

	<div class="photoblog-content">
		<?php the_content(); ?>
	</div>

	<br class="clear" />
</article><?php

else:

?><article class="portfolio" id="portfolio-<?php the_ID(); ?>">
	<header class="portfolio-header">
		<h1><?php $petermolnareu_theme->dimox_breadcrumbs(); ?></h1>
	</header>
	<?php the_content(); ?>
</article>

<?php endif; ?>
