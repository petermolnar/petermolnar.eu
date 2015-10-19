<?php
/**
 * displays post publish date
 */
?>

<time class="dt-published" datetime="<?php echo $post_pubdate_iso ?>"><?php echo $post_pubdate_print ?></time>
<span class="hide">
	<time class="dt-updated" datetime="<?php echo $post_moddate_iso ?>"><?php echo $post_moddate_print ?></time>
</span>
