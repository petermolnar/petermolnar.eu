<?php

namespace PETERMOLNAR;

if ( stripos( \get_option('siteurl'), 'https://') === 0) {
	$_SERVER['HTTPS'] = 'on';
}

define('PETERMOLNAR\CACHE_DIR', \WP_CONTENT_DIR
	. DIRECTORY_SEPARATOR  .'cache' );
define('PETERMOLNAR\TWIG_DIR', CACHE_DIR
	. DIRECTORY_SEPARATOR  .'twig' );
define( 'PETERMOLNAR\menu_header', 'header' );

require __DIR__ . '/vendor/autoload.php';
\Twig_Autoloader::register();
\Twig_Extensions_Autoloader::register();

$classes = array( 'base.php', 'cleanup.php', 'markdown.php',
	'post.php', 'author.php', 'site.php', 'archive.php',
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
//new \pmlnr_comment();

// init all the things!
\add_action( 'init', '\PETERMOLNAR\init' );

\add_action( 'static_generator', '\PETERMOLNAR\static_generator' );

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

			$format = post_format_ng ( $post );
			if ( 'photo' != $format )
				return false;

			//if ( function_exists( '\send_webmention' ) )
				//\send_webmention( \get_permalink( $post->ID ), 'https://brid.gy/publish/webmention' );

			return $enabled;

		}, 1, 4 );

	if (!wp_get_schedule( 'static_generator' ))
		wp_schedule_event ( time(), 'daily', 'static_generator' );


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

/**
 *
 */
function extract_reaction ( &$content ) {

	$pattern = "/[\*\+]{3}\s+(reply|fav|repost):?\s+(https?\:\/\/?[a-zA-Z0-9\.\/\?\:@\-_=#]+\.[a-zA-Z0-9\.\/\?\:@\-_=#&]*)(?:\s+(yes|no|maybe))?/i";

	preg_match_all( $pattern, $content, $matches);
	//debug( $matches );

	if ( empty( $matches[0] ) )
		return false;

	return $matches;
}

/**
 *
 */
function post_format_ng ( $post ) {

	$reaction = extract_reaction( $post->post_content );
	$reaction_type = false;
	if ( $reaction && isset( $reaction[1][0] )
		&& ! empty( $reaction[1][0] ) ) {
		$reaction_type = trim( $reaction[1][0] );
	}

	$images = IMAGE\md_images( $post->post_content );
	$is_photo = false;
	if ( count( $images[0]) == 1 ) {
		$is_photo = IMAGE\is_photo( $images[2][0] );
	}

	$is_it = \has_term( 'it', 'post_tag', $post );
	$is_journal = \has_term( 'journal', 'post_tag', $post );
	//$is_journal = \has_term( 'journal', 'post_tag', $post );

	$type = 'note';

	if ( $is_it ) {
		$type = 'article';
	}
	if ( $is_journal && strlen( $post->post_excerpt ) > 0 ) {
		$type = 'journal';
	}
	elseif ( !empty( $reaction_type ) && $reaction_type == 'reply' ) {
		$type = 'note';
	}
	elseif ( ! empty( $reaction_type ) ) {
		$type = 'bookmark';
	}
	elseif ( $is_photo ) {
		$type = 'photo';
	}

	$taxonomy = 'category';
	$id = \term_exists( $type, $taxonomy );
	if ($id === 0 || $id === null) {
		\wp_insert_term ( $type, $taxonomy );
	}

	$id = \term_exists( $type, $taxonomy );
	if ($id !== 0 && $id !== null) {
		$current = \pmlnr_base::get_type( $post );
		if ($current != $type ) {
			debug(
				"post type refresh for {$post->post_title} ({$post->ID}): is set to {$type}" );
			wp_set_post_terms( $post->ID, $id, 'category', false );
		}
	}

	return $type;
}

function static_generator() {
	global $post;
	global $query_string;

	$folder = \WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'html';
	if ( ! is_dir( $folder ) )
		mkdir( $folder );

	global $wpdb;
	$postids = $wpdb->get_results( "
		SELECT ID FROM {$wpdb->posts}
		WHERE post_status = 'publish' AND post_password = ''
		ORDER BY post_type DESC, post_date DESC"
	);

	$include = [ 'post', 'page' ];
	foreach ( $postids as $p ) {
		$pid = $p->ID;
		debug ( "trying to query {$pid}");
		if ( ! in_array( \get_post_type( $pid ), $include ) )
			continue;

		$query_string = "p={$pid}";
		query_posts( $query_string );

		\the_post();
		$tmpl = 'singular.html';

		if ( !is_object( $post ) ) {
			$query_string = "page_id={$pid}";
			query_posts( $query_string );
			\the_post();
			$tmpl = 'page.html';
		}

		$htmlfile = $folder. DIRECTORY_SEPARATOR . $post->post_name . '.html';
		$timestamp_post_pub = get_the_time( 'U', $post );
		$timestamp_post_mod = get_the_modified_time( 'U', $post );

		$timestamp_post = ( $timestamp_post_mod > $timestamp_post_pub )
			? $timestamp_post_mod
			: $timestamp_post_pub;

		if ( file_exists( $htmlfile ) ) {
			$timestamp_html = get_the_time( 'U', $post );
			if ( $timestamp_html == $timestamp_post ) {
				continue;
			}
		}

		$twigvars = array (
			'site' => \pmlnr_site::template_vars(),
			'post' => \pmlnr_post::template_vars( $post )
		);


		$twig = twig( $tmpl, $twigvars );
		debug( "Exporting {$post->post_name} ({$post->ID}) to {$htmlfile}" );
		file_put_contents( $htmlfile, $twig );
		touch ( $htmlfile, get_the_time( 'U', $post ) );

		\wp_reset_postdata();
	}
}