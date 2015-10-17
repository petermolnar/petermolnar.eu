<?php
global $post;
$author = get_post_meta ( $post->ID, 'author', true);
if (!empty($author)) : ?>

<span class="p-author h-card vcard">
	by <span><?php echo $author ?></span>
</span>

<?php else :
	$author_id = $post->post_author;
	$author =  get_the_author_meta ( 'display_name' , $author_id );
	$author_url = get_the_author_meta ( 'user_url' , $author_id );
?>

by <span class="p-author h-card vcard">
	<a class="fn p-name url u-url" href="<?php echo $author_url ?>"><?php echo $author ?></a>
</span>

<?php endif;
