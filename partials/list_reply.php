<?php $reply = petermolnareu::post_get_replylist(); ?>

<?php if ( !empty($reply)): ?>
<indie-action do="reply" with="<?php echo get_permalink(); ?>" class="share">
	<h5><?php _e('Reply') ?></h5>
	<ul>
	<?php foreach ($reply as $silo => $url ): ?>
		<li>
			<a href="<?php echo $url ?>" class="icon-<?php echo $silo ?>"><?php echo $silo ?></a>
		</li>
	<?php endforeach; ?>
	</ul>
</indie-action>

<?php endif;
