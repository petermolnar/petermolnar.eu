<?php
/**
 * to be included for author data in a post
 */
?>

<?php if (!empty($post_author_url)) : ?>

by <span class="p-author h-card vcard">
	<a class="fn p-name url u-url" href="<?php echo $post_author_url ?>"><?php echo $post_author_name ?></a>
</span>

<?php else : ?>

<span>by <span class="p-author h-card vcard"><?php echo $post_author_name ?></span></span>

<?php endif;
