<?php $tags = petermolnareu::post_get_tags_array(); ?>

<?php if ( !empty($tags)): ?>
<?php /* <h5><?php _e('Tagged as:') ?></h5> */?>
<div class="tags">
	<ul class="p-category">
	<?php foreach ($tags as $name => $url ): ?>
		<li>
			<a href="<?php echo $url ?>" class="icon-tag"><?php echo $name ?></a>
		</li>
	<?php endforeach; ?>
	</ul>
</div>

<?php endif;
