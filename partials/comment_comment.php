<?php $comment_iso = get_comment_date( 'c', $comment->comment_ID ); ?>
<?php $comment_print = sprintf ('%s %s', get_comment_date( get_option('date_format'), $comment->comment_ID ), get_comment_time( get_option('time_format'), $comment->comment_ID ) ); ?>
<?php $comment_author =  get_comment_author($comment); ?>
<?php $comment_author_url = get_comment_author_url( $comment );  ?>
<?php $comment_avatar = get_avatar($comment, 42); ?>
<?php $comment_id = $comment->comment_ID;  ?>
<?php $comment_text = get_comment_text( $comment_id ); ?>
<?php $comment_link = esc_url( get_comment_link( $comment->comment_ID ) ); ?>
<?php $post_url = get_the_permalink(); ?>
<?php $post_title = get_the_title(); ?>
<li id="comment-<?php echo $comment_id; ?>" class="p-comment h-entry comment">
	<header class="comment-meta">
		<p class="hide"><a class="u-in-reply-to" href="<?php echo $post_url ?>"><?php echo $post_title ?></a></p>
		<div class="comment-author p-author h-card vcard">
			<a href="<?php echo $comment_author_url; ?>" title="<?php echo $comment_author;?>"><?php echo $comment_avatar; ?></a>
			<b class="fn">
				<a href="<?php echo $comment_author_url; ?>" rel="external nofollow" class="url">
				<?php echo $comment_author; ?>
				</a>
			</b>
		</div>
		<a class="u-url" href="<?php echo $comment_link ?>">
			<time datetime="<?php echo $comment_iso ?>"><?php echo $comment_print ?></time>
		</a>
	</header>
	<div class="comment-content">
		<?php echo $comment_text; ?>
	</div>
</li>
