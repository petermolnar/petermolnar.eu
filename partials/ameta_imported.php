<?php

function icon4url ( $url ) {
	return substr(parse_url($url, PHP_URL_HOST), 0 , (strrpos(parse_url($url, PHP_URL_HOST), ".")));
}

$repost_of = $reply_to = $repost_icon = $reply_icon = false;
$reply_to = false;

$twitter_url = get_post_meta( $post->ID, 'twitter_permalink', true);
if ( $twitter_url )
	$repost_of = $twitter_url;


$twitter_reply_user = get_post_meta( $post->ID, 'twitter_in_reply_to_user_id', true);
$twitter_reply_id = get_post_meta( $post->ID, 'twitter_in_reply_to_status_id', true);
if ( $twitter_reply_user && $twitter_reply_id )
	$reply_to = 'https://twitter.com/' . $twitter_reply_user . '/status/' . $twitter_reply_id;


?>

<?php if ( $repost_of && !empty($repost_of)): ?>
<h5><?php _e('Reposted from:') ?></h5>
<p>
	<a class="u-repost-of icon-<?php echo icon4url($repost_of) ?>" href="<?php echo $repost_of ?>"><?php echo $repost_of ?></a>
</p>
<?php endif; ?>

<?php if ( $reply_to && !empty($reply_to)): ?>
<h5><?php _e('In reply to:') ?></h5>
<p>
	<a class="u-in-reply-to" href="<?php echo $reply_to ?>"><?php echo $reply_to ?></a>
</p>
<?php endif; ?>
