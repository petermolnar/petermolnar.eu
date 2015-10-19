<?php if ( !empty($post_tags)): ?>
<?php /* <h5><?php _e('Tagged as:') ?></h5> */?>
<div class="tags">
	<ul class="p-category">
	<?php foreach ($post_tags as $tname => $turl ): ?>
		<li>
			<a href="<?php echo $turl ?>" class="icon-tag"><?php echo $tname ?></a>
		</li>
	<?php endforeach; ?>
	</ul>
</div>

<?php endif;
