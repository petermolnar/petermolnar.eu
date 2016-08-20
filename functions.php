<?php

namespace PETERMOLNAR;

define('PETERMOLNAR\CACHE_DIR', \WP_CONTENT_DIR
	. DIRECTORY_SEPARATOR  .'cache' );
define('PETERMOLNAR\TWIG_DIR', CACHE_DIR
	. DIRECTORY_SEPARATOR  .'twig' );
define( 'PETERMOLNAR\menu_header', 'header' );

require __DIR__ . '/vendor/autoload.php';
\Twig_Autoloader::register();
\Twig_Extensions_Autoloader::register();

$classes = array( 'base.php', 'cleanup.php', 'markdown.php',
	'post.php', 'author.php', 'site.php', 'comment.php', 'archive.php',
	'image.php' );

foreach ( $classes as $class ) {
	require_once ( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes'
		.DIRECTORY_SEPARATOR . $class );
}

use \PETERMOLNAR\CLEANUP;
use \PETERMOLNAR\MARKDOWN;
use \PETERMOLNAR\IMAGE;

new \pmlnr_post();
new \pmlnr_author();
new \pmlnr_site();
new \pmlnr_comment();


\register_activation_hook( __FILE__ , '\PETERMOLNAR\theme_activate' );

// init all the things!
\add_action( 'init', 'PETERMOLNAR\init' );

/**
 *
 */
function theme_activate () {

	if ( version_compare( phpversion(), 5.4, '<' ) ) {
		die( 'The minimum PHP version required for this plugin is 5.3' );
	}

}

/**
 *
 */
function twig ( $template, $vars ) {

	$d = array ( CACHE_DIR, TWIG_DIR );
	foreach ( $d as $dir ) {
		if ( ! is_dir( $dir ) )
			mkdir( $dir );
	}

	$tplDir = dirname( __FILE__ ) . '/twig';
	$twigloader = new \Twig_Loader_Filesystem( $tplDir );
	$twig = new \Twig_Environment( $twigloader, array(
		'cache' => TWIG_DIR,
		'auto_reload' => true,
		'autoescape' => false,
	));

	$twig = $twig->loadTemplate( $template );
	$twig = $twig->render( $vars );

	return $twig;
}

/**
 *
 */
function init () {

	\add_theme_support( 'post-thumbnails' );
	//\add_theme_support( 'menus' );
	\add_theme_support( 'html5', array( 'search-form' ) );
	\add_theme_support( 'title-tag' );
	\add_theme_support( 'custom-logo' );

	\add_filter('upload_mimes',
		function ( $mimes ) {
			$mimes['svg'] = 'image/svg+xml';
			return $mimes;
		},10, 1 );

	// fix any incoming .eu request
	\add_filter ( 'url_to_postid',
		function( $url ) {
			return str_replace( 'petermolnar.eu', 'petermolnar.net', $url );
		}, 1, 1 );

	// disable photo2content if it's not a photo
	\add_filter ( 'wp_photo2content_enabled',
		function( $enabled, $new_status, $old_status, $post  ) {
			if ( 'post' != $post->post_type )
				return false;

			$format = \pmlnr_base::post_format ( $post );
			if ( 'photo' != $format )
				return false;

			if ( function_exists( '\send_webmention' ) )
				\send_webmention( \get_permalink( $post->ID ), 'https://brid.gy/publish/webmention' );

			return $enabled;

		}, 1, 4 );

}

/**
 *
 * debug messages; will only work if WP_DEBUG is on
 * or if the level is LOG_ERR, but that will kill the process
 *
 * @param string $message
 * @param int $level
 *
 * @output log to syslog | wp_die on high level
 * @return false on not taking action, true on log sent
 */
function debug( $message, $level = LOG_NOTICE ) {
	if ( empty( $message ) )
		return false;

	if ( @is_array( $message ) || @is_object ( $message ) )
		$message = json_encode($message);

	$levels = array (
		LOG_EMERG => 0, // system is unusable
		LOG_ALERT => 1, // Alert 	action must be taken immediately
		LOG_CRIT => 2, // Critical 	critical conditions
		LOG_ERR => 3, // Error 	error conditions
		LOG_WARNING => 4, // Warning 	warning conditions
		LOG_NOTICE => 5, // Notice 	normal but significant condition
		LOG_INFO => 6, // Informational 	informational messages
		LOG_DEBUG => 7, // Debug 	debug-level messages
	);

	// number for number based comparison
	// should work with the defines only, this is just a make-it-sure step
	$level_ = $levels [ $level ];

	// in case WordPress debug log has a minimum level
	if ( defined ( '\WP_DEBUG_LEVEL' ) ) {
		$wp_level = $levels [ \WP_DEBUG_LEVEL ];
		if ( $level_ > $wp_level ) {
			return false;
		}
	}

	// ERR, CRIT, ALERT and EMERG
	if ( 3 >= $level_ ) {
		\wp_die( '<h1>Error:</h1>' . '<p>' . $message . '</p>' );
		exit;
	}

	$trace = debug_backtrace();
	$caller = $trace[1];
	$parent = $caller['function'];

	if (isset($caller['class']))
		$parent = $caller['class'] . '::' . $parent;

	return error_log( "{$parent}: {$message}" );
}