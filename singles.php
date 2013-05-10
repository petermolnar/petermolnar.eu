<?php
	global $post_counter;
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
		<article class="photoblog-article" id="photoblog-<?php the_ID(); ?>" >
			<nav class="photoblog-navigation">
				<div class="link left">Previous <?php	previous_post_link( '&laquo; %link' , '%title' , true ); ?></div>
				<div class="link right"><?php	next_post_link( '%link &raquo;' , '%title' , true ); ?> Next</div>
			</nav>

			<header class="photoblog-header">
				<h1>
					<a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>">
						<?php the_title(); ?>
					</a>
				</h1>
			</header>

			<div class="photoblog-content">
				<?php the_content(); ?>
			</div>

			<footer class="photoblog-footer">
				<?php $comment = (is_single()) ? false : true; ?>
				<?php wp_share ( get_permalink() , wp_title( '', false ), $comment ); ?>
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
<!--			<aside>
				<nav class="portfolio-menu">
					<?php wp_nav_menu( array( 'container' => '' , 'theme_location' => 'portfolio'  ) ); ?>
				</nav>
			</aside>-->
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
		//global $cat_template;
		//$category_additions = array();
		//	$category_additions['class'] = '';
		//	$category_additions['time'] = true;
		//	$category_additions['more'] = true;
		//	$category_additions['share'] = true;
		//	$category_additions['tags'] = true;
		//
		//switch ($cat_template)
		//{
		//	case '3col':
		//		$category_additions['class'] = ' grid33';
		//		$category_additions['time'] = false;
		//		$category_additions['more'] = false;
		//		$category_additions['share'] = false;
		//		$category_additions['tags'] = false;
		//		break;
		//	case 'opensource':
		//		$category_additions['class'] = ' grid50';
		//		$category_additions['time'] = false;
		//		$category_additions['more'] = false;
		//		$category_additions['share'] = false;
		//		$category_additions['tags'] = false;
		//	default:
		//		break;
		//}

		$is_single = is_single();
		?>
		<arcticle id="post-<?php the_ID(); ?>" class="arcticle-single <?php echo $category_additions['class']; ?>">
			<header class="article-header">
				<h2>
					<a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>">
						<?php the_title(); ?>
					</a>
				</h2>

				<?php if ( $category_additions['time'] ): ?>
					<time pubdate="<?php the_time( 'r' ); ?>">
						<?php the_time( get_option('date_format') ); ?>
					</time>
				<?php endif; ?>
			</header>

			<div class="arcticle-body">
				<?php
				if ( $is_single ):
					the_content();
				else:
					if ( has_post_thumbnail () ) : ?>
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
					<?php endif;

					the_excerpt();
				endif;
				?>
			</div>
				<footer class="article-footer">
						<nav class="arcticle-tags">
							<?php if ( $category_additions['more'] && !$is_single ) : ?>
							<span class="article-more-text">
								<a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>">
									<?php echo comments_number( 'No responses', '1 response', '% responses' ); ?>
								</a>
							</span><br />
							<?php endif; ?>
							<?php if ( $category_additions['tags'] ): ?>
								<?php the_tags( '', ', ', '' ); ?>
							<?php endif; ?>
						</nav>
						<nav class="arcticle-more">
							<?php if ( $category_additions['more'] && !$is_single ) : ?>
								<a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>">
									<span class="article-more-text">more </span><span class="article-more-icon">&rarr;</span>
								</a>
							<?php endif; ?>
						</nav>
					<?php if ( $category_additions['share'] && $is_single ): ?>
						<?php wp_share ( get_permalink() , wp_title( '', false ), $post->ID ); ?>
					<?php endif ?>
				</footer>

		</arcticle>
	<?php
	endif;

?>
