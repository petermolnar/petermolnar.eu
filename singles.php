<?php
	the_post();

	$format = get_post_format();

	if ( $format === false)
		$format = get_post_type();

	/**
	* page
	*/
	if ( $format == 'page' ) :
	?>
		<arcticle id="page-<?php the_ID(); ?>" class="single-arcticle">
			<?php the_content(); ?>
			<footer>
				<?php wp_share ( get_permalink() , wp_title( '', false ), $post->ID ); ?>
			</footer>
		</arcticle>

	<?php

	/**
	* "image" (photoblog)
	*/
	elseif ( $format == 'image' ) :
	?>
		<article class="photoblog" id="photoblog-<?php the_ID(); ?>" >
			<aside>
				<nav class="photoblog-navigation">
					<div class="link left">Previous <?php	previous_post_link( '&laquo; %link' , '%title' , true ); ?></div>
					<div class="link right"><?php	next_post_link( '%link &raquo;' , '%title' , true ); ?> Next</div>
				</nav>
			</aside>

			<header>
				<hgroup>
					<h1>
						<a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>">
							<?php the_title(); ?>
						</a>
					</h1>
				</hgroup>
			</header>

			<?php the_content(); ?>

			<footer class="article-footer">
				<?php wp_share ( get_permalink() , wp_title( '', false ) ); ?>
			</footer>
		</article>

	<?php

	/**
	* "gallery" (portfolio)
	*/
	elseif ( $format == 'gallery' ) :
	?>
		<article class="portfolio" id="portfolio-<?php the_ID(); ?>">
				<?php the_content(); ?>
			<aside>
				<nav class="portfolio-menu">
					<?php wp_nav_menu( array( 'container' => '' , 'theme_location' => 'portfolio'  ) ); ?>
				</nav>
			</aside>
			<!--
			<section class="portfolio-description">
				<?php $description = get_post_custom_values('description'); ?>
				<?php if (is_array($description)) print array_pop($description); ?>
			</section>
			-->
		</article>

	<?php

	/**
	* default template for posts
	*/
	else:
		/**
		* single
		*/
		if ( is_single() ):
		?>
		<arcticle id="post-<?php the_ID(); ?>" class="single-arcticle">
			<header class="article-meta">
				<hgroup>
					<h2>
						<a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>">
							<?php the_title(); ?>
						</a>
					</h2>
				</hgroup>
				<time pubdate="<?php the_time( 'r' ); ?>">
					<?php the_time( get_option('date_format') ); ?>
				</time>
			</header>

			<div class="arcticle-body">
				<?php the_content(); ?>
			</div>

			<footer class="article-footer">
				<nav class="tags">
					<?php the_tags( '', ', ', '' ); ?>
				</nav>
				<?php wp_share ( get_permalink() , wp_title( '', false ), $post->ID ); ?>
			</footer>
		</arcticle>

		<aside class="adsense aligncenter">
			<script type="text/javascript"><!--
				google_ad_client = "ca-pub-4708327409285010";
				/* petermolnar tech */
				google_ad_slot = "6064647749";
				google_ad_width = 728;
				google_ad_height = 90;
				//-->
			</script>
			<script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></script>
		</aside>

		<?php
		/**
		* lister
		*/
		else:
		?>
		<arcticle id="post-<?php the_ID(); ?>" class="article-list">

			<header class="article-meta">
				<hgroup>
					<h2>
						<a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>">
							<?php the_title(); ?>
						</a>
					</h2>
				</hgroup>
				<time pubdate="<?php the_time( 'r' ); ?>">
					<?php the_time( get_option('date_format') ); ?>
				</time>
			</header>

			<summary class="arcticle-body">
				<?php if ( has_post_thumbnail () ) : ?>
					<figure class="article-thumbnail">
						<a href="<?php the_permalink() ?>">
						<?php
							the_post_thumbnail('thumbnail', array(
								'alt'	=> trim(strip_tags( $post->post_title )),
								'title'	=> trim(strip_tags( $post->post_title )),
							));
						?>
						</a>
					</figure>
				<?php endif; ?>
				<?php the_excerpt(); ?>
			</summary>

			<nav class="arcticle-content-link">
				<a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>">
					more <span class="meta-nav">&rarr;</span>
				</a>
			</nav>
		</arcticle>

	<?php
		endif;
	endif;

?>
