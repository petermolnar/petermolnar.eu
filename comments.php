<?php global $petermolnareu_theme; ?>
<?php if ( post_password_required() ) return; ?>

<section class="content-comments"><div class="content-inner">
<?php
if ( comments_open() ) :
	/* <h6 class="urel"><?php _e('Feel free to use your own website to make a comment, a like, a reply, petermolnar.eu is <a href="http://indiewebcamp.com/webmentions">webmentions</a>-ready.', $petermolnareu_theme->theme_constant); ?></h6><?php */
	comment_form( array( 'title_reply' => __( 'Leave a reply, or use <a href="http://indiewebcamp.com/webmentions" rel="nofollow">webmentions</a> from your site.', $petermolnareu_theme->theme_constant) ));
endif; ?>

<?php if ( have_comments() ) : ?>
	<a id="comments" /></a>
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
				'short_ping' => true,
				'avatar_size'=> 34,
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
