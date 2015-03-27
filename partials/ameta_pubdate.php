<?php $published_iso = get_the_time( 'c', $post->ID ); ?>
<?php $published_print = sprintf ('%s %s', get_the_time( get_option('date_format'), $post->ID ), get_the_time( get_option('time_format'), $post->ID ) );  ?>
<?php $modified_iso = get_the_modified_time( 'c', $post->ID ); ?>
<?php $modified_print = sprintf ('%s %s', get_the_modified_time( get_option('date_format'), $post->ID ), get_the_modified_time( get_option('time_format'), $post->ID ) ); ?>

<time class="dt-published" datetime="<?php echo $published_iso ?>"><?php echo $published_print ?></time>
<span class="hide">
	<time class="dt-updated" datetime="<?php echo $modified_iso ?>"><?php echo $modified_print ?></time>
</span>
