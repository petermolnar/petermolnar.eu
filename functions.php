<?php

define('ARTICLE_MIN_LENGTH', 1100);

$dirname = dirname(__FILE__);

require __DIR__ . '/vendor/autoload.php';
Twig_Autoloader::register();
Twig_Extensions_Autoloader::register();

require_once ($dirname . '/classes/base.php');
require_once ($dirname . '/classes/image.php');
require_once ($dirname . '/classes/cleanup.php');
require_once ($dirname . '/classes/markdown.php');
require_once ($dirname . '/classes/post.php');
require_once ($dirname . '/classes/author.php');
require_once ($dirname . '/classes/site.php');
require_once ($dirname . '/classes/comment.php');
require_once ($dirname . '/classes/archive.php');

class petermolnareu {
	const menu_header = 'header';

	public $twig = null;
	public $twigloader = null;
	private $twigcache = WP_CONTENT_DIR . '/cache/twig';

	public function __construct () {

		// set up Twig
		if (!is_dir($this->twigcache))
			mkdir($this->twigcache);

		$tplDir = dirname(__FILE__) . '/twig';
		$this->twigloader = new Twig_Loader_Filesystem( $tplDir );
		$this->twig = new Twig_Environment($this->twigloader, array(
			'cache' => $this->twigcache,
			'auto_reload' => true,
			'autoescape' => false,
		));
		$this->twig->addExtension(new Twig_Extensions_Extension_I18n());

		// set up theme class action hooks
		new pmlnr_image();
		new pmlnr_cleanup();
		new pmlnr_markdown();
		new pmlnr_post();
		new pmlnr_author();
		new pmlnr_site();
		new pmlnr_comment();

		// init all the things!
		add_action( 'init', array( 'petermolnareu', 'init'));

		// add css & js
		add_action( 'wp_enqueue_scripts', array('petermolnareu','register_css_js'),10);
		//add_action( "transition_post_status", array('petermolnareu','generate_sitemap') );

		add_action( 'htmlgen', array('petermolnareu','generate_html' ) );
	}

	public static function twig () {

		// set up Twig
		if (!is_dir($this->twigcache))
			mkdir($this->twigcache);

		$tplDir = dirname(__FILE__) . '/twig';
		$this->twigloader = new Twig_Loader_Filesystem( $tplDir );
		$twig = new Twig_Environment($this->twigloader, array(
			'cache' => $this->twigcache,
			'auto_reload' => true,
			'autoescape' => false,
		));
		//$twig->addExtension(new Twig_Extensions_Extension_I18n());

		return $twig;
	}

	/**
	 *
	 */
	public static function init () {

		// required WP Theme magic
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'menus' );
		add_theme_support( 'html5', array( 'search-form' ) );
		add_theme_support( 'title-tag' );
		add_theme_support( 'custom-logo' );

		// add main menus
		register_nav_menus( array(
			static::menu_header => __( static::menu_header , 'petermolnareu' ),
		) );

		// replace default <title></title>
		add_filter('wp_title', array('petermolnareu', 'nice_title',),10,1);

		// I want to upload svg
		add_filter('upload_mimes',
			function ( $mimes ) {
				$mimes['svg'] = 'image/svg+xml';
				return $mimes;
			},10, 1 );

		// fix any incoming .eu request
		add_filter ( 'url_to_postid',
			function( $url ) {
				return str_replace( 'petermolnar.eu', 'petermolnar.net', $url );
			}, 1, 1 );

		// disable photo2content if it's not a photo
		add_filter ( 'wp_photo2content_enabled',
			function( $enabled, $new_status, $old_status, $post  ) {
				if ( 'post' != $post->post_type )
					return false;

				$format = pmlnr_base::post_format ( $post );
				if ( 'photo' != $format )
					return false;

				if ( function_exists( 'send_webmention' ) )
					send_webmention( get_permalink( $post->ID ), 'https://brid.gy/publish/webmention' );

				return $enabled;

			}, 1, 4 );


		if (!wp_get_schedule( 'htmlgen' ))
			wp_schedule_event ( time(), 'daily', 'htmlgen' );

	}

	/**
	 * register & queue css & js
	 *
	 */
	public static function register_css_js () {
		$base_url = get_bloginfo("template_directory");
		$js_url = "{$base_url}/js";
		$css_url = "{$base_url}/css";

		// Magnific popup
		wp_register_style( "magnific-popup", "{$base_url}/lib/Magnific-Popup/dist/magnific-popup.css" , false );
		wp_register_script( "magnific-popup", "{$base_url}/lib/Magnific-Popup/dist/jquery.magnific-popup.min.js" , array("jquery"), null, false );

		// justified gallery
		wp_register_style( "Justified-Gallery", "{$base_url}/lib/Justified-Gallery/dist/css/justifiedGallery.min.css" , false );

		wp_register_script( "Justified-Gallery", "{$base_url}/lib/Justified-Gallery/dist/js/jquery.justifiedGallery.min.js" , array("jquery"), null, false );

		// syntax highlight
		wp_register_style( "prism", "{$css_url }/prism.css", false, null );
		wp_enqueue_style( "prism" );
		wp_register_script( "prism" ,  "{$js_url}/prism.js", false, null, true );
		wp_enqueue_script( "prism" );
	}

	/**
	 *
	 */
	public static function nice_title ( $title ) {
		if (is_home() || empty($title))
			return get_bloginfo('name');

		return trim( str_replace ( array ('&raquo;', '»' ), array ('',''), $title ) );
	}

	/*
	public static function generate_sitemap ($new_status = null , $old_status = null, $post = null ) {

		if (  null === $new_status || null === $old_status || null === $post )
			return;

		global $wpdb;

		$posts = $wpdb->get_results( "SELECT post_name FROM $wpdb->posts WHERE post_status = 'publish' AND post_password = '' ORDER BY post_type DESC, post_modified DESC" );
		$urls = array();

		foreach ( $posts as $post ) {
			array_push ( $urls, site_url( $post->post_name ) );
		}

		file_put_contents( __DIR__ . DIRECTORY_SEPARATOR . 'sitemap.txt', join ("\n", $urls ) );
	}
	*/

	public static function generate_html () {
		global $wpdb;
		global $post;
		global $petermolnareu_theme;

		$_p = $post;
		$posts = $wpdb->get_results( "SELECT ID FROM $wpdb->posts WHERE post_status = 'publish' AND post_password = '' ORDER BY post_type DESC, post_date DESC" );

		foreach ( $posts as $_post ) {
			$post = get_post( $_post->ID );
			setup_postdata( $post );
			//$url = get_permalink( $_post->ID );
			//$pubdate = get_the_time( 'U', $post->ID );

			$twigvars = array (
				'site' => pmlnr_site::template_vars(),
				'post' => pmlnr_post::template_vars( $post )
			);

			$folder = \WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'html';

			if ( ! is_dir( $folder ) )
				mkdir( $folder );

			$htmlfile = $folder. DIRECTORY_SEPARATOR . $post->post_name . '.html';

			$tmpl = 'singular.html';
			if (is_page())
				$tmpl = 'page.html';

			$twig = $this->twig->loadTemplate( $tmpl );
			file_put_contents( $htmlfile, $twig->render($twigvars) );
		}

		setup_postdata( $_p );
		return;
	}

}

if ( !isset( $petermolnareu_theme ) || empty ( $petermolnareu_theme ) ) {
	$petermolnareu_theme = new petermolnareu();
}
