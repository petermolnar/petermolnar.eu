<?php $share = petermolnareu::post_get_sharelist(); ?>

<?php if ( !empty($share)): ?>
<indie-action do="post" with="<?php echo get_permalink(); ?>" class="share">
	<h5><?php _e('Share') ?></h5>
	<ul>
	<?php foreach ($share as $silo => $url ): ?>
		<li>
			<a href="<?php echo $url ?>" class="icon-<?php echo $silo ?>"><?php echo $silo ?></a>
		</li>
	<?php endforeach; ?>
	</ul>
</indie-action>

<?php endif;
