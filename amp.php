<?php
the_post();
$post_url = get_the_permalink();
$post_title = get_the_title();
$published_iso = get_the_time( 'c', $post->ID );

$author = get_post_meta ( $post->ID, 'author', true);
if (empty($author)) {
	$author_id = $post->post_author;
	$author =  get_the_author_meta ( 'display_name' , $author_id );
	$author_url = get_the_author_meta ( 'user_url' , $author_id );

	$author = '<a class="fn p-name url u-url" itemprop="url" href="' . $author_url . '">' . $author .'</a>';
}
?><!doctype html>
<html amp>
	<head>
		<link rel="canonical" href="<?php echo $post_url; ?>">
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width,minimum-scale=1">
		<script async src="https://cdn.ampproject.org/v0.js"></script>
		<style>body {opacity: 0}</style><noscript><style>body {opacity: 1}</style></noscript>
	</head>
	<body>
		<article id="post-<?php the_ID(); ?>" class="h-entry">
			<header>
				<time class="dt-published" datetime="<?php echo $published_iso ?>"><?php echo $published_print ?></time>
				<h1>
					<a class="u-url" href="<?php echo $post_url ?>" rel="bookmark" title="<?php echo $post_title ?>">
						<span class="p-name"><?php echo $post_title ?></span>
					</a>
				</h1>
				<span class="p-author h-card vcard">
				<?php echo $author ?>
				</span>
			</header>

			<div class="e-content">
				<?php
					the_content();
				?>
			</div>

			<footer>
				<?php
					require_once (dirname(__FILE__) . '/partials/list_tag.php');
				?>
			</footer>
		</article>
	</body>
</html>
