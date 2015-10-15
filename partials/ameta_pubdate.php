<?php
$published_iso = get_the_time( 'c', $post->ID );
$published_print = sprintf ('%s %s', get_the_time( get_option('date_format'), $post->ID ), get_the_time( get_option('time_format'), $post->ID ) );
$modified_iso = get_the_modified_time( 'c', $post->ID );
$modified_print = sprintf ('%s %s', get_the_modified_time( get_option('date_format'), $post->ID ), get_the_modified_time( get_option('time_format'), $post->ID ) );
?>

<time class="dt-published" itemprop="datePublished" content="<?php echo $published_iso ?>" datetime="<?php echo $published_iso ?>"><?php echo $published_print ?></time>
<span class="hide">
	<time class="dt-updated" itemprop="dateModified" content="<?php echo $modified_iso ?>" datetime="<?php echo $modified_iso ?>"><?php echo $modified_print ?></time>
</span>
