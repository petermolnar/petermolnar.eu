<li id="comment-<?php comment_ID( $like ) ?>" class="comment">
	<div class="comment-author vcard">
		<a href="<?php comment_author_url( $like ); ?>" title="<?php echo strip_tags($like->comment_content); ?>"><?php echo get_avatar( $like, 42 ); ?></a>
		<span class="hide">
			<cite class="fn"><?php comment_author_link( $like ); ?></cite>
		</span>
	</div>
</li>
