<?php $author = get_the_author_meta( 'ID' ); ?>
<?php $author_name =  get_the_author_meta ( 'display_name' , $author ); ?>
<?php $author_url = get_the_author_meta ( 'user_url' , $author ); ?>

<span class="p-author h-card vcard">
	by <a class="fn p-name url u-url" href="<?php echo $author_url ?>"><?php echo $author_name ?></a>
</span>
