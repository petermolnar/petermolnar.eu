<?php
	global $post;
	global $petermolnareu_theme;
	global $category_additions;

	$official_url = get_post_meta( $post->ID, 'opensource-url', true);
	$project_name = get_post_meta( $post->ID, 'opensource-project-name', true);
	$project_link = get_post_meta( $post->ID, 'opensource-project-url', true);
	$github_link = get_post_meta( $post->ID, 'github-url', true);

?>

<arcticle id="post-<?php the_ID(); ?>" class="article-list-element arcticle-opensource">
	<?php if ( !empty ( $github_link ) ) { ?>

	<?php } ?>

	<header class="article-header">
		<h2>
			<a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>">
				<?php the_title(); ?>
			</a>
		</h2>
	</header>

	<div class="arcticle-body">
		<div class="arcticle-excerpt">
			<?php the_excerpt(); ?>
		</div>
		<dl class="opensource-data">
		<?php if ( !empty ( $official_url ) ) { ?>
				<dt><?php _e ( 'Official link', $petermolnareu_theme::theme_constant ); ?></dt>
					<dd><a href="<?php echo $official_url; ?>"><?php echo $official_url; ?></a></dd>
		<?php } ?>
		<?php if ( !empty ( $project_link ) && !empty( $project_name ) ) { ?>
				<dt><?php _e ( 'Project', $petermolnareu_theme::theme_constant ); ?></dt>
					<dd><a href="<?php echo $project_link; ?>"><?php echo $project_name; ?></a></dd>
		<?php } ?>
		<?php if ( !empty ( $github_link ) ) { ?>
				<dt><?php _e ( 'Github', $petermolnareu_theme::theme_constant ); ?></dt>
					<dd class="forkongithub-line"><a href="<?php echo $github_link; ?>"><?php the_title(); _e(' on Github', $petermolnareu_theme::theme_constant) ?></a></dd>
		<?php } ?>


		</dl>

	</div>

	<br class="clear" />
</arcticle>
