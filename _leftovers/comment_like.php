<?php
$post_url = get_the_permalink();
$post_title = get_the_title();
?>
<li id="comment-<?php comment_ID( $like ) ?>" class="p-comment h-entry comment">
	<p class="hide"><a class="u-like-of" href="<?php echo $post_url ?>"><?php echo $post_title ?></a></p>
	<div class="comment-author p-author h-card vcard">
		<a href="<?php comment_author_url( $like ); ?>" title="<?php echo strip_tags($like->comment_content); ?>"><?php echo get_avatar( $like, 42 ); ?></a>
		<span class="hide">
			<cite class="fn"><?php comment_author_link( $like ); ?></cite>
		</span>
	</div>
</li>
