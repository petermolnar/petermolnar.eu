<?php

namespace PETERMOLNAREU;

define('PETERMOLNAREU\CACHE_DIR', \WP_CONTENT_DIR
	. DIRECTORY_SEPARATOR  .'cache' );
define('PETERMOLNAREU\TWIG_DIR', CACHE_DIR
	. DIRECTORY_SEPARATOR  .'twig' );
define( 'PETERMOLNAREU\menu_header', 'header' );

require __DIR__ . '/vendor/autoload.php';
\Twig_Autoloader::register();
\Twig_Extensions_Autoloader::register();

$classes = array( 'base.php', 'image.php', 'cleanup.php', 'markdown.php',
	'post.php', 'author.php', 'site.php', 'comment.php', 'archive.php' );

foreach ( $classes as $class ) {
	require_once ( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes'
		.DIRECTORY_SEPARATOR . $class );
}

use \PETERMOLNAREU\CLEANUP;
use \PETERMOLNAREU\MARKDOWN;
//\PETERMOLNAREU\CLEANUP\construct();

new \pmlnr_image();
new \pmlnr_post();
new \pmlnr_author();
new \pmlnr_site();
new \pmlnr_comment();


\register_activation_hook( __FILE__ , '\PETERMOLNAREU\theme_activate' );
//\register_deactivation_hook( __FILE__ , '\PETERMOLNAREU\plugin_deactivate' );

// init all the things!
\add_action( 'init', 'PETERMOLNAREU\init' );

// HTML generator
//\add_action( 'htmlgen', 'PETERMOLNAREU\generate_html' );

//
//\add_action( 'transition_post_status', 'PETERMOLNAREU\autobridgy', 99, 3 );


/**
 *
 *
function autobridgy ( $new_status = null, $old_status = null,
	$post = null ) {

	if ( $new_status == null || $old_status == null || $post == null )
		return false;

	$post = \pmlnr_base::fix_post( $post );
	if ( false === $post )
		return false;

	if ( 'publish' != $new_status )
		return false;

	maybe_send_bridgy( $post );
}

/**
 *
 *
function maybe_send_bridgy( $post = null ) {

	$post = \pmlnr_base::fix_post( $post );
	if ( false === $post )
		return false;

	$bridgy_endpoints = \pmlnr_post::bridgy_to ( $post );

	if ( empty( $bridgy_endpoints ) )
		return false;

	$syndications = \get_post_meta ( $post->ID, 'syndication_urls', true );
	if ( empty( $syndications ) )
		$syndications = array();
	else
		$syndications = explode ( "\n", $syndications );

	foreach ( $bridgy_endpoints as $endpoint ) {

		foreach ( $syndications as $syndication ) {
			if ( stristr( $syndication, $endpoint ) ) {
				continue;
			}
		}

		$args = array (
			'body' => 'source=' . urlencode( get_permalink( $post->ID ) )
				. '&target=' . urlencode( "https://brid.gy/publish/{$endpoint}" ),
			'timeout' => 10,
		);

		\pmlnr_base::debug ( $args );


		$r = \wp_remote_post ( 'https://brid.gy/publish/webmention', $args );
		if ( \is_wp_error( $r  ) ) {
			\pmlnr_base::debug( "sending bridgy failed: "
				. $r->get_error_message(), 4);
			continue;
		}

		if ( ! isset( $r['response'] )
			|| ! isset( $r['response']['code'] ) || 201 != $r['response']['code'] ) {
				\pmlnr_base::debug( "sending bridgy failed, it returned "
					. str_replace( '#012', '', json_encode( $r ) ), 4);
				continue;
			}

		$r = json_decode( $r['body'], true );
		if ( !isset( $r['url'] ) ) {
			\pmlnr_base::debug( "brid.gy return URL was empty", 4 );
			continue;
		}

		\pmlnr_base::debug ( "Bridgy responded: {$url}" );
		array_push( $syndications, $r['url'] );
	}

	$syndications = array_unique( $syndications );
	return \update_post_meta( $post->ID, 'syndication_urls', join( "\n", $syndications ) );
}

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

	// htmlgen
	//if (! \wp_get_schedule( 'htmlgen' ))
		//\wp_schedule_event ( time(), 'daily', 'htmlgen' );

}

/**
 *
 */
function generate_html () {
	global $post;
	global $query_string;

	$_p = $post;

	$folder = \WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'html';

	if ( ! is_dir( $folder ) )
		mkdir( $folder );

	global $wpdb;

	$postids = $wpdb->get_results( "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_password = '' ORDER BY post_type DESC, post_date DESC" );

	$exclude = [ 'attachment', 'revision', 'nav_menu_item' ];
	foreach ( $postids as $p ) {
		$pid = $p->ID;
		if ( in_array( \get_post_type( $pid ), $exclude ) )
			continue;

		$posts = query_posts( "p={$pid}" );
		if ( \have_posts() ) {
			while ( \have_posts() ) {
				\the_post();
				$twigvars = array (
					'site' => \pmlnr_site::template_vars(),
					'post' => \pmlnr_post::template_vars( $post )
				);

				$htmlfile = $folder. DIRECTORY_SEPARATOR . $post->post_name . '.html';
				\pmlnr_base::debug( "Exporting {$post->ID} to {$htmlfile}" );

				$tmpl = 'singular.html';
				if ( \is_page() )
					$tmpl = 'page.html';

				$twig = twig( $tmpl, $twigvars );

				file_put_contents( $htmlfile, $twig );
				touch ( $htmlfile, get_the_time( 'U', $post ) );
			}
			\wp_reset_postdata();
		}
		wp_reset_query();
	}
	/*

	// CSS and JS is still not added :(

	$per_page = \get_option('posts_per_page');
	$include = [ 'category', 'post_tag' ];
	$taxonomies = \get_taxonomies( array (), 'objects' );

	foreach ( $taxonomies as $taxonomy ) {
		if ( ! in_array( $taxonomy->name, $include ) )
			continue;

		if ( ! isset( $taxonomy->rewrite )
			|| ! isset( $taxonomy->rewrite['slug'] )
			|| empty( $taxonomy->rewrite['slug'] )
		)
			$fragment = $taxonomy->name;
		else
			$fragment = $taxonomy->rewrite['slug'];

		$terms = \get_terms( $taxonomy->name );

		foreach ( $terms as $term ) {

			// getting max size for pagination
			$args = array(
				'posts_per_page' => -1,
				'tax_query' => array(
					array(
						'taxonomy' => $taxonomy->name,
						'field' => 'slug',
						'terms' => $term->slug,
					)
				)
			);
			$postslist = get_posts( $args );
			$num = sizeof ( $postslist );
			$pages = (int) ceil( (int)$num / (int)$per_page );

			$q = $query_string;
			switch ( $term->taxonomy ) {
				case 'post_tag':
					$q .= "&tag_id={$term->term_id}";
					break;
				case 'category':
					$q .= "&cat={$term->term_id}";
					break;
				default:
					continue;
			}
			$q .= "&posts_per_page={$per_page}";

			for( $i = 0; $i<= $pages; $i++ ) {

				if ( $i > 0 ) {
					$p = $i+1;
					$q .= "&paged={$p}";
					$static = "/{$fragment}/{$term->slug}/page/{$p}";
				}
				else {
					$static = "/{$fragment}/{$term->slug}";
				}

				$subdirs = explode( '/', $static );
				$subdirpath = $folder;
				foreach ( $subdirs as $c => $subdir ) {
					$subdirpath = rtrim( $subdirpath, DIRECTORY_SEPARATOR );
					$subdirpath .= DIRECTORY_SEPARATOR . trim( $subdir, '/' ) ;
					if ( ! is_dir( $subdirpath ) ) {
						mkdir ( $subdirpath );
					}
				}

				$twigvars = array();
				$twigposts = array();
				$posts = query_posts( $q );
				$t = 0;

				if ( \have_posts() ) {
					while ( \have_posts() ) {
						$twigvars = array (
							'site' => \pmlnr_site::template_vars(),
							'archive' => \pmlnr_archive::template_vars( $q ),
						);

						\the_post();
						$pt = get_the_time( 'U', $post );
						if ( $pt > $t )
							$t = $pt;
						array_push( $twigposts, \pmlnr_post::template_vars() );
						\wp_reset_postdata();
					}
				}

				$twigvars['posts'] = $twigposts;
				$twig = twig( 'archive.html', $twigvars );
				$path = $subdirpath . DIRECTORY_SEPARATOR . 'index.html';
				file_put_contents( $path, $twig );
				touch ( $path, $t );
				\pmlnr_base::debug( "Exporting {$term->slug} ({$fragment}), page {$i} to {$path}" );
				wp_reset_query();
			}
		}
	}
	*/
}
