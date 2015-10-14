<?php
$author = get_post_meta ( $post->ID, 'author', true);
if (!empty($author)) : ?>

<span class="p-author h-card vcard" itemprop="author" itemscope itemtype="http://schema.org/Person">
	by <span itemprop="name"><?php echo $author ?></span>
</span>

<?php else :
	$author = get_the_author_meta( 'ID' );
	$author_name =  get_the_author_meta ( 'display_name' , $author );
	$author_url = get_the_author_meta ( 'user_url' , $author );
?>

<span class="p-author h-card vcard" itemprop="author" itemscope itemtype="http://schema.org/Person">
	by <a class="fn p-name url u-url" itemprop="url" href="<?php echo $author_url ?>"><span itemprop="name"><?php echo $author_name ?></span></a>
</span>

<?php endif;
