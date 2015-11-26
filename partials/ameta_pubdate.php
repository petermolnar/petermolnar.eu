<?php
/**
 * displays post publish date
 */
?>

<time class="dt-published" datetime="<?php echo $post_pubdate_iso ?>"><?php echo $post_pubdate_print ?></time>
<time class="dt-updated hide" datetime="<?php echo $post_moddate_iso ?>"><?php echo $post_moddate_print ?></time>
