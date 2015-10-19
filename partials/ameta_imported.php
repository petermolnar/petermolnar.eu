<?php
/**
 * displays if the post is PESOS from somewhere else
 */
?>


<?php if ( $post_twitter_repost && !empty($post_twitter_repost)): ?>
<h5><?php _e('Reposted from:') ?></h5>
<p>
	<a class="u-repost-of <?php echo pmlnr_base::icon4url($post_twitter_repost) ?>" href="<?php echo $post_twitter_repost ?>"><?php echo $post_twitter_repost ?></a>
</p>
<?php endif; ?>

<?php if ( $post_twitter_reply && !empty($post_twitter_reply)): ?>
<h5><?php _e('In reply to:') ?></h5>
<p>
	<a class="u-in-reply-to" href="<?php echo $post_twitter_reply ?>"><?php echo $post_twitter_reply ?></a>
</p>
<?php endif; ?>
