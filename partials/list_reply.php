<?php $reply = petermolnareu::post_get_replylist(); ?>

<indie-action do="reply" with="<?php echo get_permalink(); ?>" class="share">
	<h5><?php _e('Reply') ?></h5>
	<ul>
	<?php if ( !empty($reply)): ?>
	<?php foreach ($reply as $silo => $url ): ?>
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


