<?php

	$webmention_url = get_post_meta ( $post->ID, 'webmention_url', true);
	$webmention_type = get_post_meta ( $post->ID, 'webmention_type', true);
	$webmention_rsvp = get_post_meta ( $post->ID, 'webmention_rsvp', true);

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

	$rsvps = array (
		'no' => __("Sorry, can't make it."),
		'yes' => __("I'll be there."),
		'maybe' => __("I'll do my best, but don't count on me for sure."),
	);

?>

<?php if ( !empty($webmention_url)): ?>
	<h5><?php echo $h ?></h5>
	<p>
		<a href="<?php echo $webmention_url ?>" rel="<?php echo str_replace('u-', '', $cl ); ?>" class="<?php echo $cl ?>"><?php echo $webmention_url; ?></a>
		<?php if (!empty($webmention_rsvp)): ?>
			<data class="p-rsvp" value="<?php echo $webmention_rsvp ?>"><?php echo $rsvps[ $webmention_rsvp ]; ?></data>
		<?php endif; ?>
	</p>
<?php endif; ?>

