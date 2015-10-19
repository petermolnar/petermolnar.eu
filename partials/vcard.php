<?php

$data = pmlnr_author::template_vars( $post );

if (is_array($data) && !empty($data))
	extract($data, EXTR_PREFIX_ALL, 'author' );
else
	return;
?>

<span class="p-author h-card vcard">
	<a class="fn p-name url u-url hide" href="<?php echo $author_url ?>"><?php echo $author_name ?></a>
	<img class="photo avatar u-photo u-avatar hide" src="<?php echo $author_gravatar ?>" alt="<?php printf(__('Photo of %s'), $author_name); ?>" style="height:1em; width:auto;"/>
	<a rel="me" class="u-email email icon-mail" href="mailto:<?php echo $author_email ?>" title="<?php echo $author_name ?> email address"><?php echo $author_email ?></a>
	<?php /* if (!empty($author_pgp)): ?>
	<a rel="me" class="u-key key icon-lock" href="<?php echo $author_pgp ?>" title="<?php echo $author_name ?> PGP"><?php echo $author_pgp ?></a>
	<?php endif; */ ?>
	<?php foreach ( $author_socials as $silo => $url ): ?>
	<a rel="me" class="u-<?php echo $silo ?> x-<?php echo $silo ?> icon-<?php echo $silo ?> url u-url" href="<?php echo $url ?>" title="<?php echo $author_name ?> @ <?php echo ucfirst($silo) ?>"><?php echo $silo ?></a>
	<?php endforeach; ?>
</span>
