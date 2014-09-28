<?php
global $post;

if ( post_password_required() ) return;
/*
if ( have_comments() ) {
	$plink = get_permalink( $post->ID );
?>
<section class="content-comments">
	<div class="content-inner">
		<p><a id="comments" /></a></p>
		<ol class="comment-list">
<?php

	$commargs = array(
		'status' => 'approve',
		'post_id' => $post->ID,
	);

	$comments = get_comments($commargs);
		foreach($comments as $comment) : ?>
			<li class="comment p-comment">
				<?php
				$an = sprintf ( '<a name="comment-%s"></a>', $comment->comment_ID);
				echo $an;
				?>
				<article class="comment-body">
					<header class="comment-meta">
						<?php
							$rto = sprintf ('<a class="u-in-reply-to hide" href="%s">%s</a>', $plink, $plink );
							echo $rto;
						?>
						<span class="p-author h-card vcard">
							<?php
								$gravatar = md5( strtolower( trim( $comment->comment_author_email )));
								$img = sprintf ('<img src="https://secure.gravatar.com/avatar/%s?s=42" class="photo avatar u-photo u-avatar" />', $gravatar );
								echo $img;
							?>
							<span class="fn p-name">
							<?php
								if ( !empty($comment->comment_author_url ) )
									$u = sprintf ( '<a href="%s" rel="external nofollow" class="url u-url">%s</a>', $comment->comment_author_url, $comment->comment_author );
								else
									$u = $comment->comment_author;

								echo $u;
							?>
							</span>

						</span>
						<?php
							$pdate = date('c', strtotime($comment->comment_date_gmt ));
							$date = date(get_option('date_format'), strtotime($comment->comment_date_gmt ));
							$time = date(get_option('time_format'), strtotime($comment->comment_date_gmt ));

							$ctime = sprintf ('<time class="dt-published" datetime="%s">%s %s</time>', $pdate, $date, $time);

							$clink = sprintf ( '<a href="%s#comment-%s" class="u-url">%s</a>', $plink, $comment->comment_ID, $ctime);

							echo $clink;
						?>
					</header>
					<div class="comment-content e-content">
						<?php echo pmlnr_md::parsedown( pmlnr_utils::tweetify ( pmlnr_utils::facebookify ( $comment->comment_content ))); ?>
					</div>
				</article>
			</li> <?php
		endforeach; ?>
		</ol>
	</div>
</section><?php
}
/*
}
else {
*/
	if ( have_comments() ) { ?>
		<!-- comments -->
		<section class="content-comments">
			<div class="content-inner">
				<p><a id="comments" /></a></p>

				<!-- comment-list -->
				<ol class="comment-list">
					<?php
						wp_list_comments( array(
							'style'      => 'ol',
							'short_ping' => false,
							'avatar_size'=> 42,
						) );
					?>
				</ol>
				<!-- end comment-list -->
			</div>
		</section>
		<!-- comments end --><?php
	}
// }


