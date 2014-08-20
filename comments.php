<?php global $petermolnareu_theme; ?>
<?php if ( post_password_required() ) return; ?>

<section class="content-comments"><div class="content-inner">
<?php
/*
if ( comments_open() ) :
	comment_form( array( 'title_reply' => __( 'Leave a reply, or use <a href="http://indiewebcamp.com/webmentions" rel="nofollow">webmentions</a> from your site.', $petermolnareu_theme->theme_constant) ));
endif;
*/
?>

<?php if ( have_comments() ) : ?>
	<p><a id="comments" /></a></p>
	<h2 class="comments-title">
		<?php
			printf( _n( 'One thought on &ldquo;%2$s&rdquo;', '%1$s thoughts on &ldquo;%2$s&rdquo;', get_comments_number(), $petermolnareu_theme->theme_constant ),
				number_format_i18n( get_comments_number() ), get_the_title() );
		?>
	</h2>

	<?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : ?>
	<nav id="comment-nav-above" class="navigation comment-navigation" role="navigation">
		<h1 class="screen-reader-text"><?php _e( 'Comment navigation', $petermolnareu_theme->theme_constant ); ?></h1>
		<div class="nav-previous"><?php previous_comments_link( __( '&larr; Older Comments', $petermolnareu_theme->theme_constant ) ); ?></div>
		<div class="nav-next"><?php next_comments_link( __( 'Newer Comments &rarr;', $petermolnareu_theme->theme_constant ) ); ?></div>
	</nav><!-- #comment-nav-above -->
	<?php endif; // Check for comment navigation. ?>

	<ol class="comment-list">
		<?php
			wp_list_comments( array(
				'style'      => 'ol',
				'short_ping' => false,
				'avatar_size'=> 42,
			) );
		?>
	</ol><!-- .comment-list -->

	<?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : ?>
	<nav id="comment-nav-below" class="navigation comment-navigation" role="navigation">
		<h1 class="screen-reader-text"><?php _e( 'Comment navigation', $petermolnareu_theme->theme_constant ); ?></h1>
		<div class="nav-previous"><?php previous_comments_link( __( '&larr; Older Comments', $petermolnareu_theme->theme_constant ) ); ?></div>
		<div class="nav-next"><?php next_comments_link( __( 'Newer Comments &rarr;', $petermolnareu_theme->theme_constant ) ); ?></div>
	</nav><!-- #comment-nav-below -->
	<?php endif; // Check for comment navigation. ?>

<?php endif; // have_comments() ?>

</div></section>
