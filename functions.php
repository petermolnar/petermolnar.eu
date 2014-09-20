<?php

include_once ('classes/adaptgal-ng.php');
include_once ('classes/article-utils.php');
include_once ('classes/utils.php');
include_once ('classes/format-utils.php');
include_once ('classes/markdown-utils.php');

if ( !function_exists ( 'preg_value' ) ) {
	function preg_value ( $string, $pattern, $index = 1 ) {
		preg_match( $pattern, $string, $results );
		if ( isset ( $results[ $index ] ) && !empty ( $results [ $index ] ) )
			return $results [ $index ];
		else
			return false;
	}
}

class petermolnareu {
	public $theme_constant = 'petermolnareu';
	const menu_header = 'header';
	const twitteruser = 'petermolnar';
	const fbuser = 'petermolnar.eu';
	const shortdomain = 'http://pmlnr.eu/';
	const shorturl_enabled = true;
	const cache_group = 'theme_meta';
	const cache_time = 86400;
	const cache = 0;

	public $base_url = '';
	public $js_url = '';
	public $css_url = '';
	public $font_url = '';
	public $image_url = '';
	public $theme_url = '';
	public $image_sizes = array();
	public $adaptive_images = null;
	private $parsedown = null;
	public $formatter = null;

	private $relative_urls = false;

	public function __construct () {

		$this->base_url = pmlnr_utils::replace_if_ssl( get_bloginfo("url") );
		$this->theme_url = pmlnr_utils::replace_if_ssl( get_bloginfo("stylesheet_directory") );
		$this->js_url = $this->theme_url . '/assets/js/';
		$this->css_url = $this->theme_url . '/assets/css/';
		$this->font_url = $this->theme_url . '/assets/font/';
		$this->image_url = $this->theme_url . '/assets/image/';

		$this->adaptive_images = new adaptive_images( $this );

		add_action( 'init', array( &$this, 'init'));
		add_action( 'init', array( &$this->adaptive_images, 'init'));
		add_action( 'init', array( &$this, 'rewrites'));

		add_action( 'wp_enqueue_scripts', array(&$this,'register_css_js'));

		/* replace shortlink */
		remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );
		add_action( 'wp_head', array(&$this, 'shortlink'));

		/* cleanup */
		remove_action('wp_head', 'rsd_link'); // Display the link to the Really Simple Discovery service endpoint, EditURI link
		remove_action('wp_head', 'wlwmanifest_link'); // Display the link to the Windows Live Writer manifest file.
		remove_action('wp_head', 'index_rel_link'); // Index link
		remove_action('wp_head', 'parent_post_rel_link', 10, 0); // Prev link
		remove_action('wp_head', 'start_post_rel_link', 10, 0); // Start link
		remove_action('wp_head', 'adjacent_posts_rel_link', 10, 0); // Display relational links for the posts adjacent to the current post.
		remove_action('wp_head', 'wp_generator'); // Display the XHTML generator that is generated on the wp_head hook, WP version
		remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
		remove_action('wp_head', 'rel_canonical');

		add_action( 'widgets_init', array( &$this, 'widgets_init' ) );
	}

	public function init () {
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'menus' );
		add_theme_support( 'automatic-feed-links' );

		/* http://codex.wordpress.org/Post_Formats */
		add_theme_support( 'post-formats', array(
			'image', 'aside', 'video', 'audio', 'quote', 'link',
		) );

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support( 'html5', array(
			'search-form', 'comment-form', 'comment-list'
		) );

		/* add main menus */
		register_nav_menus( array(
			self::menu_header => __( self::menu_header , $this->theme_constant ),
		) );

		/* enable custom uploads */
		add_filter('upload_mimes', array( &$this, 'custom_upload_mimes' ) );

		/* add syntax highlighting */
		add_shortcode('code', array ( &$this, 'syntax_highlight' ) );
		add_shortcode('cc', array ( &$this, 'syntax_highlight' ) );


		/* post type additional data */
		$this->formatter = new pmlnr_format();
		add_filter( 'the_content', array( $this->formatter, 'filter'), 1 );

		/* have links in the admin *
		add_filter( 'pre_option_link_manager_enabled', '__return_true' );*/

		/* additional user meta */
		add_filter('user_contactmethods', array( &$this, 'add_user_meta_fields'));

		/* better title */
		add_filter( 'wp_title', array(&$this, 'nice_title') );

		/* shortlink replacement */
		add_filter( 'get_shortlink', array(&$this, 'get_shortlink'), 1, 4 );

		/* WordPress SEO cleanup */
		add_filter('wpseo_author_link', array(&$this, 'author_url'));

		/* replace img inserts with Markdown */
		add_filter( 'image_send_to_editor', array( 'pmlnr_md', 'rebuild_media_string'), 10 );

	}

	/**
	 * register & queue css & js
	 */
	public function register_css_js () {

		/* enqueue CSS */
		wp_register_style( 'style', $this->theme_url . '/style.css' , false, $this->css_version ( dirname(__FILE__) . '/style.css' ) );

		/* syntax highlight */
		wp_register_style( 'prism', $this->css_url . 'prism.css', false, null );
		wp_register_script( 'prism' , $this->js_url . 'prism.js', false, null, true );

		/* CDN scripts */
		wp_deregister_script( 'jquery' );
		wp_register_script( 'jquery', pmlnr_utils::replace_if_ssl( 'http://code.jquery.com/jquery-1.11.0.min.js' ), false, null, false );
		wp_enqueue_script( 'jquery' );

		wp_enqueue_style( 'style' );
		wp_enqueue_style( 'prism' );
		wp_enqueue_script( 'prism' );

		/* this is to have reply fields correctly */
		if ( is_singular() && comments_open() && get_option('thread_comments') )
			wp_enqueue_script( 'comment-reply' );


		wp_deregister_style( 'jetpack-subscriptions' );
	}

	public function widgets_init () {
		register_sidebar( array(
			'name' => __( 'Subscribe', $this->theme_constant ),
			'id' => 'subscribe',
			'before_widget' => '',
			'after_widget'  => '',
			'before_title'  => '',
			'after_title'   => '',
		) );
	}

	/**
	 * redirect old stuff to prevent broken links
	 */
	public function rewrites () {
		add_rewrite_rule("indieweb-decentralize-web-centralizing", "indieweb-decentralize-web-centralizing-ourselves", "bottom" );
		add_rewrite_rule("/journal/living-without-google-on-android-phone/", "/linux-tech-coding/journal/living-without-google-on-android-phone/", "bottom" );
		add_rewrite_rule("/photoblog(.*)", '/photo$matches[1]', "bottom" );
		add_rewrite_rule("/blog(.*)", '/journal$matches[1]', "bottom" );
		add_rewrite_rule("/wordpress(.*)", '/open-source$matches[1]', "bottom" );
		add_rewrite_rule("/b(.*)", '/blips$matches[1]', "bottom" );
		add_rewrite_rule("/open-source/wp-ffpc(.*)", 'https://github.com/petermolnar/wp-ffpc', "bottom" );
		add_rewrite_rule("/open-source/wordpress/(.*)", '/open-source/$matches[1]', "bottom" );
	}

	/**
	 * replace original shortlink
	 */
	public function shorturl () {
		global $post;

		if ( self::shorturl_enabled ) {
			return self::shortdomain . $post->ID;
		}
		else {
			$url = rtrim( get_bloginfo('url'), '/' ) . '/';
			return $url.'?p='.$post->ID;
		}
	}

	public function get_shortlink ( $shortlink, $id, $context, $allow_slugs ) {
		return $this->shorturl();
	}

	public function shortlink () {
		echo '<link rel="shortlink" href="'. $this->shorturl() . '" />'."\n";
	}

	/**
	 * extend allowed mime types
	 *
	 * @param array $existing_mimes Array containing existing mime types
	 */
	public function custom_upload_mimes ( $existing_mimes=array() ) {
		$existing_mimes['svg'] = 'image/svg+xml';
		$existing_mimes['webp'] = 'image/webp';

		return $existing_mimes;
	}

	/**
	 * WordPress SEO adds Google Plus url instead of regular author url, replace it
	 */
	public function author_url ( $url ) {
			global $post;
			$aid =  get_the_author_meta( 'ID' );
			return get_the_author_meta ( 'user_url' , $aid );
	}

	/**
	 * additional user fields
	 */
	public function add_user_meta_fields ($profile_fields) {

		$profile_fields['github'] = __('Github username', $this->theme_constant);
		$profile_fields['mobile'] = __('Mobile phone number', $this->theme_constant);
		$profile_fields['linkedin'] = __('LinkedIn username', $this->theme_constant);
		$profile_fields['flickr'] = __('Flickr username', $this->theme_constant);

		return $profile_fields;
	}

	/**
	 *
	 */
	public function nice_title ( $title ) {
		return trim( str_replace ( array ('&raquo;', '»' ), array ('',''), $title ) );
	}

	private function css_version ( $file ) {
		$version = 0;
		$handle = fopen($file, "r");
		if ($handle) {
			while (($line = fgets($handle)) !== false && empty($version) ) {
				if ( strstr($line,'Version') ) {
					$v = explode("\t",$line);
					if ( !empty($v[2]) )
						$version = $v[2];
						break;
				}
			}
		}
		fclose($handle);

		return $version;
	}

}

/**** END OF FUNCTIONS *****/

if ( !isset( $petermolnareu_theme ) || empty ( $petermolnareu_theme ) ) {
	$petermolnareu_theme = new petermolnareu();
}

?>
