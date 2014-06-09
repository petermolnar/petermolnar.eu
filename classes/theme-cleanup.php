<?php

class theme_cleaup {
	public $urlfilters = array ();
	private $relative_urls = false;

	public function __construct () {

		$this->urlfilters = array(
			'post_link', // Normal post link
			'post_type_link', // Custom post type link
			'page_link', // Page link
			'attachment_link', // Attachment link

			'post_type_archive_link', // Post type archive link
			'get_pagenum_link', // Paginated link
			'get_comments_pagenum_link', // Paginated comment link
			'term_link', // Term link, including category, tag
			'search_link', // Search link
			'day_link', // Date archive link
			'month_link',
			'year_link',
			'get_comment_link',
			'wp_get_attachment_image_src',
			'wp_get_attachment_thumb_url',
			'wp_get_attachment_url',
		);

		remove_action('wp_head', 'rsd_link'); // Display the link to the Really Simple Discovery service endpoint, EditURI link
		remove_action('wp_head', 'wlwmanifest_link'); // Display the link to the Windows Live Writer manifest file.
		remove_action('wp_head', 'index_rel_link'); // Index link
		remove_action('wp_head', 'parent_post_rel_link', 10, 0); // Prev link
		remove_action('wp_head', 'start_post_rel_link', 10, 0); // Start link
		remove_action('wp_head', 'adjacent_posts_rel_link', 10, 0); // Display relational links for the posts adjacent to the current post.
		remove_action('wp_head', 'wp_generator'); // Display the XHTML generator that is generated on the wp_head hook, WP version
		remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
		remove_action('wp_head', 'rel_canonical');

	}

	/**
	 *
	 */
	public function filters() {
		/* relative urls */
		if ( $this->relative_urls ) {
			add_filter( 'the_content', array( &$this, 'fix_urls'), 100);
			if ( ! is_feed()  && ! get_query_var( 'sitemap' ) )
				foreach ( $this->urlfilters as $filter )
					add_filter( $filter, 'wp_make_link_relative' );
		}

		/* reorder autop */
		remove_filter( 'the_content', 'wpautop' );
		add_filter( 'the_content', 'wpautop', 20 );
		add_filter( 'the_content', 'shortcode_unautop', 100 );

		/**/
		add_filter( 'wp_title', array(&$this, 'nice_title') );

	}

	/**
	 *
	 */
	public function nice_title ( $title ) {
		return trim( str_replace ( array ('&raquo;', '»' ), array ('',''), $title ) );
	}

	/**
	 * replaces all non secure absolute url to relative, therefore making it secure
	 */
	public function fix_urls ( $src ) {
		if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' )
			$_SERVER['HTTPS'] = 'on';

		if ( isset($_SERVER['HTTPS']) && (( strtolower($_SERVER['HTTPS']) == 'on' )  || ( $_SERVER['HTTPS'] == '1' ) )) {
			$nonsecurl = str_replace ( 'https://', 'http://',  get_bloginfo('url') );
			$securl = str_replace ( 'http://', 'https://',  get_bloginfo('url') );
			$src = str_replace ( $nonsecurl, '', $src  );
		}

		return $src;
	}

}


?>
