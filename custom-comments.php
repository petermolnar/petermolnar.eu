<section class="comments-content round">
<?php
/* password protection */
if ( !empty( $post->post_password ) && $_COOKIE['wp-postpass_'.COOKIEHASH] != $post->post_password) : ?>
	<p class="comments-locked"><?php e_('Enter your password to view comments.'); ?></p>
<?php endif; ?>

<?php
/* trackback link */
if (pings_open()) : ?>
	<p class="trackback-link">Trackback: <a href="<?php trackback_url() ?>" rel="trackback"><?php trackback_url() ?></a>
	</p>
<?php endif; ?>

<?php if ($comments) :
	/* Count the totals */
	$numPingBacks = 0;
	$numComments = 0;

	/* Loop throught comments to count these totals */
	foreach ($comments as $comment) :
		if (get_comment_type() != "comment")
			$numPingBacks++;
		else
			$numComments++;
	endforeach;
endif;
?>

<?php
/* pingbacks */
if ($numPingBacks != 0) :
?>
	<section class="pingbacks">
		<header class="comments-header">
			<h2><?php _e($numPingBacks); _e(' Trackbacks/Pingbacks'); ?></h2>
		</header>

		<details class="pingback-list">
			<ol>
		<?php
			foreach ( $comments as $comment ) :
				if ( get_comment_type() != "comment" ) :
				?>
				<li id="comment-<?php comment_ID() ?>" class="<?php _e($thiscomment); ?>">
				<?php comment_type(__('Comment'), __('Trackback'), __('Pingback')); ?>:
				<?php comment_author_link(); ?>, <?php comment_date(); ?>
				</li>
			<?php endif;
			endforeach;
		?>
			</ol>
		</details>
	</section>
<?php endif; ?>


<?php
/* comments */
if ($numComments != 0) :
?>
	<section class="comments">
		<header class="comments-header">
			<h2><?php /*_e('Comments'); */ ?>Comments</h2>
		</header>

		<ol>
		<?php
			foreach ( $comments as $comment ) :
				if ( get_comment_type() == "comment" ) :
		?>
			<li id="comment-<?php comment_ID(); ?>">
				<span class="comment-avatar">
					<?php echo get_avatar( $comment->comment_author_email, 48 ); ?>
				</span>
				<span class="comment-author">
					<?php comment_author_link(); ?>
				</span>
				<time pubtime="<?php comment_date('r'); ?>" class="comment-date">
					<?php comment_date(); ?>
				</time>
				<?php comment_text(); ?>
				<?php comment_reply_link( array('reply_text' => 'Reply this comment') ); ?>
			</li>
		<?php
				endif;
			endforeach;
		?>
		</ol>
	</section>
<?php endif; ?>
</section>

<?php if (comments_open()) : ?>
<section class="comments-content round">
	<fieldset class="comments-form">
	<legend>Leave a comment</legend>

	<?php
	/* registration needed to leave comment */
	if (get_option('comment_registration') && !$user_ID ) : ?>
		<p class="comments-blocked">
			You must be <a href="<?php echo get_option('siteurl'); ?>/wp-login.php?redirect_to=<?php the_permalink(); ?>">logged in</a> to post a comment.
		</p>
	<?php
	/* comment form */
	else: ?>
		<form action="<?php echo get_option('siteurl'); ?>/wp-comments-post.php" method="post" id="commentform">

		<?php if ($user_ID) : ?>
			<p>
				Logged in as
				<a href="<?php echo get_option('siteurl'); ?>/wp-admin/profile.php"><?php echo $user_identity; ?></a>.
				<a href="<?php echo get_option('siteurl'); ?>/wp-login.php?action=logout" title="Log out of this account">Logout</a>
			</p>
		<?php else : ?>

			<p>
				<label for="author">Name<?php if ($req) _e(' (required)'); ?></label>
				<input type="text" name="author" id="author" />
			</p>

			<p>
				<label for="email">E-mail<?php if ($req) _e(' (required)'); ?><span class="info"> - will not be published</span></label>
				<input type="email" name="email" id="email" />
			</p>

			<p>
				<label for="url">Website</label>
				<input type="url" name="url" id="url" />
			</p>

			<?php /*do_action( 'social_connect_form' );*/ ?>
		<?php endif; ?>

		<p>
			<textarea name="comment" id="comment" rows="5" cols="30"></textarea>
		</p>

		<p>
			<input name="submit" type="submit" id="submit" value="Submit" class="submit" />
			<input type="hidden" name="comment_post_ID" value="<?php echo $id; ?>">
		</p>

		<?php do_action('comment_form', $post->ID); ?>
	</form>
	</fieldset>
</section>
	<?php endif; ?>
<?php endif; ?>
