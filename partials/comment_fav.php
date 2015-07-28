<?php $post_url = get_the_permalink(); ?>
<?php $post_title = get_the_title(); ?>

<li id="comment-<?php comment_ID( $fav ) ?>" class="p-comment h-entry comment">
	<p class="hide"><a class="u-like-of" href="<?php echo $post_url ?>"><?php echo $post_title ?></a></p>
	<div class="comment-author p-author h-card vcard">
		<a href="<?php comment_author_url( $fav ); ?>" title="<?php echo strip_tags($fav->comment_content); ?>"><?php echo get_avatar( $fav, 42 ); ?></a>
		<span class="hide">
			<cite class="fn"><?php comment_author_link( $fav ); ?></cite>
		</span>
	</div>
</li>
