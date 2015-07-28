<?php //$share = petermolnareu::post_get_sharelist(); ?>
<?php $share = array(); ?>

<indie-action do="post" with="<?php echo get_permalink(); ?>" class="share">
	<h5><?php _e('Share') ?></h5>
	<ul>
	<?php if ( !empty($share)): ?>
	<?php foreach ($share as $silo => $url ): ?>
		<li>
			<a href="<?php echo $url ?>" class="icon-<?php echo $silo ?>"><?php echo $silo ?></a>
		</li>
	<?php endforeach; ?>
	<?php endif; ?>
		<li>
			<a href="<?php echo get_permalink(); ?>" class="openwebicon-webmention">webmention</a>
		</li>
	</ul>
</indie-action>


