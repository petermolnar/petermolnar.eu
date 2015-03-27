<?php $minstoread = ceil( str_word_count( strip_tags($post->post_content), 0 ) / 300 ); ?>

<span class="right spacer icon-clock"><?php echo $minstoread ?> <?php _e('mins to read') ?></span>
