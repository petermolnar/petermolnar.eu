<?php $author = get_the_author_meta( 'ID' ); ?>
<?php $author_name =  get_the_author_meta ( 'display_name' , $author ); ?>
<?php $author_email =  get_the_author_meta ( 'user_email' , $author ); ?>
<?php $author_gravatar = sprintf('https://s.gravatar.com/avatar/%s?=64', md5( strtolower( trim( $author_email ) ) )); ?>
<?php $author_url = get_the_author_meta ( 'user_url' , $author ); ?>

<span class="p-author h-card vcard">
	<a class="fn p-name url u-url" href="<?php echo $author_url ?>"><?php echo $author_name ?></a>
	<img class="photo avatar u-photo u-avatar" src="<?php echo $author_gravatar ?>" alt="<?php printf(__('Photo of %s'), $author_name); ?>"/>
</span>
