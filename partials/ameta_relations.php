<?php

	$webmention_url = get_post_meta ( $post->ID, 'webmention_url', true);
	$webmention_type = get_post_meta ( $post->ID, 'webmention_type', true);

	switch ($webmention_type) {
		case 'u-like-of':
			$h = __('This is a like of:');
			$cl = 'u-like-of';
			break;
		case 'u-repost-of':
			$h = __('This is a repost of:');
			$cl = 'u-repost-of';
			break;
		default:
			$h = __('This is a reply to:');
			$cl = 'u-in-reply-to';
			break;
	}
?>

<?php if ( !empty($webmention_url)): ?>
	<h5><?php echo $h ?></h5>
	<p>
		<a href="<?php echo $webmention_url ?>" class="<?php echo $cl ?>"><?php echo $webmention_url; ?></a>
	</p>
<?php endif; ?>
