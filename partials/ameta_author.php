<?php
/**
 * to be included for author data in a post
 */
?>

<?php if (!empty($post_author_meta)) : ?>

<span>by <span class="p-author h-card vcard"><?php echo $post_author_meta ?></span></span>

<?php elseif ( in_array($post_format, array('article','photo','reply','rsvp', 'note')) ) : ?>

by <span class="p-author h-card vcard">
	<a class="fn p-name url u-url" href="<?php echo $post_author_url ?>"><?php echo $post_author_name ?></a>
	<img class="u-photo u-avatar hide" src="<?php echo $post_author_avatar ?>" />
</span>

<?php endif;
