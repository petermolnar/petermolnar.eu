<?php $author = get_the_author_meta( 'ID' ); ?>
<?php $author_name =  get_the_author_meta ( 'display_name' , $author ); ?>
<?php $author_email =  get_the_author_meta ( 'user_email' , $author ); ?>
<?php $author_gravatar = sprintf('https://s.gravatar.com/avatar/%s?=64', md5( strtolower( trim( $author_email ) ) )); ?>
<?php $author_url = get_the_author_meta ( 'user_url' , $author ); ?>
<?php $author_socials = petermolnareu::author_social ( $author ); ?>
<?php $author_pgp = get_the_author_meta ( 'pgp' , $author ); ?>

<span class="p-author h-card vcard">
	<a class="fn p-name url u-url hide" href="<?php echo $author_url ?>"><?php echo $author_name ?></a>
	<img class="photo avatar u-photo u-avatar hide" src="<?php echo $author_gravatar ?>" alt="<?php printf(__('Photo of %s'), $author_name); ?>" style="height:1em; width:auto;"/>
	<a rel="me" class="u-email email icon-mail" href="mailto:<?php echo $author_email ?>" title="<?php echo $author_name ?> email address"><?php echo $author_email ?></a>
	<?php if (!empty($author_pgp)): ?>
	<a rel="me" class="u-key key icon-lock" href="<?php echo $author_pgp ?>" title="<?php echo $author_name ?> PGP"><?php echo $author_pgp ?></a>
	<?php endif; ?>
	<?php foreach ( $author_socials as $silo => $url ): ?>
	<a rel="me" class="u-<?php echo $silo ?> x-<?php echo $silo ?> icon-<?php echo $silo ?> url u-url" href="<?php echo $url ?>" title="<?php echo $author_name ?> @ <?php echo ucfirst($silo) ?>"><?php echo $silo ?></a>
	<?php endforeach; ?>
</span>
