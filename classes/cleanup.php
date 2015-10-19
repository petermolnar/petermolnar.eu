<?php
class pmlnr_cleanup extends pmlnr_base {

	public function __construct ( ) {
		add_action( 'init', array( &$this, 'init'));
		// cleanup
		remove_action('wp_head', 'rsd_link');
		remove_action('wp_head', 'wlwmanifest_link');
		remove_action('wp_head', 'index_rel_link'); // Index link
		remove_action('wp_head', 'parent_post_rel_link', 10, 0); // Prev link
		remove_action('wp_head', 'start_post_rel_link', 10, 0);
		remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
		remove_action('wp_head', 'adjacent_posts_rel_link', 10, 0);
		remove_action('wp_head', 'wp_generator');
		//remove_action('wp_head', 'rel_canonical');
		remove_action('admin_print_styles', 'print_emoji_styles' );
		remove_action('wp_head', 'print_emoji_detection_script', 7 );
		remove_action('admin_print_scripts', 'print_emoji_detection_script' );
		remove_action('wp_print_styles', 'print_emoji_styles' );

		// RSS will be added by hand
		remove_action( 'wp_head', 'feed_links', 2 );
		remove_action( 'wp_head','feed_links_extra', 3);

		// replace shortlink
		remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );

	}

	/* init function, should be used in the theme init loop */
	public function init (  ) {
		// cleanup
		remove_filter( 'the_content', 'wpautop' );
		remove_filter( 'the_excerpt', 'wpautop' );
		remove_filter( 'the_content', 'make_clickable', 12 );
		remove_filter( 'comment_text', 'make_clickable', 9);
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		add_filter( 'tiny_mce_plugins', array(&$this, 'disable_emojicons_tinymce') );

		// remove too special chars
		add_filter( 'content_save_pre' , array(&$this, 'sanitize_content') , 10, 1);
	}

	public function disable_emojicons_tinymce( $plugins ) {
		if ( is_array( $plugins ) )
			return array_diff( $plugins, array( 'wpemoji' ) );
		else
			return array();
	}

	/**
	 * remove hidious quote chars and other exotic things
	 */
	public static function sanitize_content( $content ) {
		$search = array( '”', '“', '’', '–' );
		$replace = array ( '"', '"', "'", '-' );

		$content = str_replace( $search, $replace, $content );
		return $content;
	}
}
