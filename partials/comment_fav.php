<li id="comment-<?php comment_ID( $fav ) ?>" class="comment">
	<div class="comment-author vcard">
		<a href="<?php comment_author_url( $fav ); ?>" title="<?php echo strip_tags($fav->comment_content); ?>"><?php echo get_avatar( $fav, 42 ); ?></a>
		<span class="hide">
			<cite class="fn"><?php comment_author_link( $fav ); ?></cite>
		</span>
	</div>
</li>
