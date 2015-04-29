<?php global $post; ?>
<?php if ( post_password_required() ) return; ?>
<?php
	$comments = get_comments ( array(
		'post_id' => $post->ID,
		)
	);

	global $c;

	$c = array(
		'comments' => [],
		'likes' => [],
		'favs' => [],
		'pings' => [],
	);

	if ( $comments ):
		foreach ($comments as $comment):
			if ($comment->comment_approved == 1 ):
				switch (strtolower($comment->comment_type)):
					case 'pingback':
					case 'trackback':
						array_push( $c['pings'], $comment);
						break;
					case 'like':
						array_push( $c['likes'], $comment);
						break;
					case 'favorite':
						array_push( $c['favs'], $comment);
						break;
					//case 'webmention':
					//	$type = get_comment_meta ();
					default:
						array_push( $c['comments'], $comment);
						break;
				endswitch;
			endif;
		endforeach;
	endif;
?>

<?php if ( have_comments() ): ?>

<section class="content-comments">
	<div class="content-inner">

	<?php if (!empty($c['likes'])): ?>
		<h5><a name="likes"><?php _e('Likes') ?></a></h5>
		<ol class="likes comment-list">
		<?php foreach ($c['likes'] as $like): ?>
			<?php require (dirname(__FILE__) . '/partials/comment_like.php'); ?>
		<?php endforeach; ?>
		</ol>
		<br class="clear" />
	<?php endif; ?>

	<?php if (!empty($c['favs'])): ?>
		<h5><?php _e('Favorites') ?></h5>
		<ol class="favs comment-list">
		<?php foreach ($c['favs'] as $fav): ?>
			<?php require (dirname(__FILE__) . '/partials/comment_fav.php'); ?>
		<?php endforeach; ?>
		</ol>
		<br class="clear" />
	<?php endif; ?>

	<?php if (!empty($c['comments'])): ?>
		<h5><a name="comments"><?php _e('Comments') ?></a></h5>
		<ol class="comment-list">
		<?php foreach ($c['comments'] as $comment): ?>
			<?php require (dirname(__FILE__) . '/partials/comment_comment.php'); ?>
		<?php endforeach; ?>
		</ol>
		<br class="clear" />
	<?php endif; ?>

	</div>
</section>

<?php endif;
