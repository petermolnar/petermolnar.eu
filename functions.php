<?php

define('ARTICLE_MIN_LENGTH', 1100);

$dirname = dirname(__FILE__);

//
require_once ($dirname . '/lib/simple_html_dom/simple_html_dom.php');

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

		$lessfiles = array ( 'style.less', 'print.less' );
		$dirname = dirname(__FILE__);
		foreach ( $lessfiles as $lessfile ) {
			// autocompile LESS to CSS
			$lessfile = $dirname . DIRECTORY_SEPARATOR . $lessfile;
			$lessmtime = filemtime( $lessfile );
			$cssfile = str_replace('less', 'css', $lessfile);
			$cssmtime = filemtime( $cssfile );

			if ($cssmtime < $lessmtime ) {
				$less = new lessc;
				//$less->setFormatter("classic");
				$less->setFormatter("compressed");
				$less->compileFile( $lessfile, $cssfile );
				touch ( $cssfile, $lessmtime );
			}
		}

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

		// additional image sizes
		add_image_size ( 'headerbg', 720, 0, false );
		add_image_size ( 'thumbnail-large', 180, 180, true );

		// init all the things!
		add_action( 'init', array( 'petermolnareu', 'init'));

		// add css & js
		add_action( 'wp_enqueue_scripts', array('petermolnareu','register_css_js'),10);

		// do things on post publish
		add_action( 'transition_post_status', array( 'petermolnareu', 'on_publish' ), 99, 5 );

		// hook for mail sending
		add_action( 'posse_to_smtp', array( 'petermolnareu', 'posse_to_smtp' ), 99, 3 );
		add_action ( 'make_post_syndication', array( 'petermolnareu', 'make_post_syndication' ), 99, 1 );

		add_action("add_meta_boxes", array( 'petermolnareu', 'featured_exif' ));


		$url = home_url() . rtrim($_SERVER['REQUEST_URI'], '/');
		$postid = url_to_postid( $url );

	}

	/**
	 *
	 */
	public static function featured_exif () {
		add_meta_box(
			"featured_exif",
			"Featured image EXIF",
			array ( 'petermolnareu','featured_exif_data' ),
			"post",
			"side",
			"default",
			null
		);
	}

	/**
	 *
	 */
	public static function featured_exif_data () {
			$post = pmlnr_base::fix_post($post);

			$thid = get_post_thumbnail_id( $post->ID );

			// this way it will get cached, thumbnail or no thumbnail as well
			if ( empty($thid) )
				return false;

			$exif = pmlnr_image::photo_exif( $thid, $post->ID );
			echo "{$exif}";
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

		// add link extraction to webmention and ping hooks as they
		// don't always do a good enough job
		//add_filter ('webmention_links', array('petermolnareu', 'webmention_links'), 1, 2);
		add_filter ('get_to_ping', array('petermolnareu', 'webmention_links'), 1);

		// I want to upload svg
		add_filter('upload_mimes', array('petermolnareu', 'cc_mime_types'));

		// for responsive videos
		add_filter( 'embed_oembed_html', array ( 'petermolnareu', 'custom_oembed_filter' ), 10, 4 ) ;

		//add_filter( 'the_content', array ( 'pmlnr_post', 'convert_reaction' ) );


		//add_filter('script_loader_tag', array ( 'petermolnareu', 'add_async_attribute'), 10, 2);

	}

	/**
	 * http://matthewhorne.me/add-defer-async-attributes-to-wordpress-scripts/
	 *
	 */
	//public static function add_async_attribute($tag, $handle) {
		//return str_replace( ' src', ' async="async" defer="defer" src', $tag );
	//}

	/**
	 * adds a wrapper div around video iframes to make them responsive
	 *
	 */
	public static function custom_oembed_filter($html, $url, $attr, $post_ID) {
		$return = '<div class="video-container">'.$html.'</div>';
		return $return;
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

	/**
	 *
	 */
	public static function make_post_syndication ( $post_id = false ) {
		$post = get_post ( $post_id );
		//$post = pmlnr_base::fix_post($post);

		if ( false === pmlnr_base::is_post( $post ) )
			return false;

		global $nxs_snapAvNts;

		$_syndicated = $_syndicated_original = get_post_meta ( $post->ID, 'syndication_urls', true );
		if ($_syndicated && strstr($_syndicated, "\n" )) {
				$_syndicated = explode("\n", $_syndicated);
				foreach ($_syndicated as $key => $url ) {
					$_syndicated[$key] = rtrim(trim($url), '/');
				}
		}
		else {
			$_syndicated = array( $_syndicated );
		}

		$snap = array();
		$snap_options = get_option('NS_SNAutoPoster');
		$urlmap = array (
			'AP' => array(),
			'BG' => array(),
			// 'DA' => array(), /* DeviantArt will use postURL */
			'DI' => array(),
			'DL' => array(),
			'FB' => array( 'url' => '%BASE%/posts/%pgID%' ),
			//'FF' => array(), /* FriendFeed should be using postURL */
			//'FL' => array(), /* Flickr should be using postURL */
			'FP' => array(),
			'GP' => array(),
			'IP' => array(),
			'LI' => array( 'url' => '%pgID%' ),
			'LJ' => array(),
			'PK' => array(),
			'PN' => array(),
			'SC' => array(),
			'ST' => array(),
			'SU' => array(),
			'TR' => array( 'url'=>'%BASE%/post/%pgID%' ), /* even if Tumblr has postURL set as well, it's buggy and missing a */
			'TW' => array( 'url'=>'%BASE%/status/%pgID%' ),
			'VB' => array(),
			'VK' => array(),
			'WP' => array(),
			'YT' => array(),
		);

		if ( $nxs_snapAvNts && is_array($nxs_snapAvNts) && !empty($nxs_snapAvNts)) {
			foreach ( $nxs_snapAvNts as $key => $serv ) {
				$mkey = 'snap'. $serv['code'];
				$urlkey = $serv['lcode'].'URL';
				$okey = $serv['lcode'];
				$metas = maybe_unserialize(get_post_meta(get_the_ID(), $mkey, true ));
				if ( !empty( $metas ) && is_array ( $metas ) ) {
					foreach ( $metas as $cntr => $m ) {
						$url = false;

						if ( isset ( $m['isPosted'] ) && $m['isPosted'] == 1 && isset($snap_options[ $okey ][$cntr]) ) {
							/* postURL entry will only be used if there's no urlmap set for the service above
							 * this is due to either missing postURL values or buggy entries */
							if ( isset( $m['postURL'] ) && !empty( $m['postURL'] ) && !isset( $urlmap[ $serv['code'] ] ) ) {
								$url = $m['postURL'];

							}
							else {
								$base = (isset( $urlmap[ $serv['code'] ]['url'])) ? $urlmap[ $serv['code'] ]['url'] : false;

								if ( $base != false ) {
									/* Facebook exception, why not */
									if ( $serv['code'] == 'FB' ) {
										$pos = strpos( $m['pgID'],'_' );
										$pgID = ( $pos == false ) ? $m['pgID'] : substr( $m['pgID'], $pos + 1 );
									}
									else {
										$pgID = $m['pgID'];
									}

									$o = $snap_options[ $okey ][$cntr];
									$search = array('%BASE%', '%pgID%' );
									$replace = array ( $o[ $urlkey ], $pgID );
									$url = str_replace ( $search, $replace, $base );
								}
							}

							if ( $url != false && !empty($url)) {
								/* trim all the double slashes, some sites cannot coope with them */
								$url = preg_replace('~(^|[^:])//+~', '\\1/', $url);
								$snap[] = $url;
							}
						}
					}
				}
			}
		}

		foreach ($snap as $url ) {
			$url = rtrim($url, '/');
			if (!in_array($url, $_syndicated)) {
				array_push($_syndicated, $url);
			}
		}

		foreach ($_syndicated as $url ) {
			if (!strstr($url, '500px.com') && !strstr($url, 'instagram.com') && !strstr($url, 'tumblr.com') && !strstr($url, 'twitter.com'))
				$synds[] = $url;
		}

		$_syndicated = join("\n", $synds);
		if (!empty($_syndicated))
			update_post_meta ( $post->ID, 'syndication_urls', $_syndicated, $_syndicated_original );

	}

	/**
	 *
	 */
	public static function cc_mime_types($mimes) {
		$mimes['svg'] = 'image/svg+xml';
		return $mimes;
	}

	/**
	 *
	 */
	public static function on_publish( $new_status, $old_status, $post ) {

		$post = pmlnr_base::fix_post($post);

		if ( ! pmlnr_base::is_post( $post ) )
			return false;

		if ( 'post' != $post->post_type )
			return false;

		// -- these will run on anything ---
		$yaml = pmlnr_base::get_yaml();

		$format = pmlnr_base::post_format ( $post );

		$modcontent = $post->post_content;

		// convert hashtag line to real tags
		$hashtags = pmlnr_post::has_hashtags ( $post->post_content );
		pmlnr_base::debug ( $hashtags, 5);
		if ( ! empty ( $hashtags ) ) {
			pmlnr_post::autotag_by_hashtags ( $post );
			$modcontent = pmlnr_post::remove_hashtags( $modcontent );
		}

		// convert reactions to meta
		pmlnr_post::parse_reaction ( $post );
		$modcontent = pmlnr_post::remove_reaction ( $modcontent );

		$modcontent = trim ( $modcontent );
		if ( $modcontent != $post->post_content )
			pmlnr_post::replace_content ( $post, $modcontent );

		if ( 'photo' == $format ) {
			pmlnr_image::autotag_by_photo ( $post );
		}

		// --- these on already published ones, incl. refresh ---
		if ( 'publish' != $new_status )
			return false;

		// --- these only when a post is freshly published ---
		if ( $new_status == $old_status )
			return false;

		$args = array ( 'post_id' => $post->ID );
		wp_schedule_single_event( time() + 120, 'make_post_syndication', $args );

		if ( in_array( $format, $yaml['smtp_categories']) )
			wp_schedule_single_event( time() + 120, 'posse_to_smtp', $args );

	}

	/**
	 *
	 */
	public static function posse_to_smtp ( $post_id ) {
		pmlnr_base::debug( "POSSE #{$post_id} to SMTP" );

		$_post = get_post ( $post_id );

		if ( false === pmlnr_base::is_post( $_post ) ) {
			pmlnr_base::debug( "this is not a post." );
			return false;
		}

		if ( 'post' != $_post->post_type ){
			pmlnr_base::debug( "this is not a post type post." );
			return false;
		}

		// only on publish from now on
		if ( 'publish' != $_post->post_status ){
			pmlnr_base::debug( "this is not a published post." );
			return false;
		}

		// this if for filters on the content, 'cus it has no idea about the post
		global $post;
		$old = $post;

		$post = $_post;

		$yaml = pmlnr_base::get_yaml();
		$format = pmlnr_base::post_format ( $post );

		if ( ! in_array( $format, $yaml['smtp_categories']) ){
			pmlnr_base::debug( "this post shouldn't send a mail" );
			return false;
		}


		$meta_key = 'posse_to_smtp';
		$email = get_the_author_meta ( 'user_email' , 1 );
		$name = get_the_author_meta ( 'display_name' , 1 );

		$subscribers = $yaml['subscribers'];

		//$sent = get_post_meta ( $post->ID, $meta_key, true );
		//if ( !is_array( $sent ) )
			$sent = array();

		if ( empty (  array_diff( $subscribers, $sent ) ) ) {
			pmlnr_base::debug( "all sent already!" );
			return true;
		}

		$template_vars = pmlnr_post::template_vars( $post );

		$headers = array (
			"From: {$name} <{$email}>",
			"Content-type: text/html",
			'X-RSS-ID: ' . get_permalink($post->ID),
			'X-RSS-Feed: ' . bloginfo('rss2_url'),
			'X-RSS-URL: ' . get_permalink($post->ID)
		);

		$title = get_bloginfo('url') . ": " . $template_vars['title'] . " [{$format}]";

		$content = '<!DOCTYPE html>
		<html>
			<head>
				<meta charset="utf-8" />
			</head>
			<body>
				<h1>'. $template_vars['title'] .'</h1>
				'. $template_vars['parsed_content'] .'
				<hr />
				<p>
					Az oldalon: <a href="'. $template_vars['url'] .'">'. $template_vars['url'] .'</a>
				</p>
				<p>
					Ha le akarsz iratkozni, vagy csak simán nem kéred már ezeket a leveleket, <a href="mailto:'. $email . '?subject=túltoltad">szólj</a>, leveszlek.<br />
					( Nincs harag, én is kismillió dologról iratkoztam már le. )
				</p>
			</body>
		</html>';

		// attach image
		$attachment = false;
		$thid = get_post_thumbnail_id( $post->ID );
		if ( ! empty( $thid ) ) {
			$thmeta = pmlnr_base::get_extended_thumbnail_meta ( $thid );
			//pmlnr_base::debug ( $thmeta );
			$attachment = $thmeta['sizes']['adaptive_2']['path'];
			pmlnr_base::debug( "attachment found: {$attachment}" );
		}

		add_filter( 'wp_mail_content_type', array( __CLASS__, 'set_html_content_type') );

		foreach ( $subscribers as $addr ) {

			if ( in_array( $addr, $sent ))
				continue;

			pmlnr_base::debug( "sending to {$addr}" );
			$s = wp_mail( $addr, $title, $content, $headers, $attachment );

			if ( true == $s )
				array_push ( $sent, $addr );
		}

		remove_filter( 'wp_mail_content_type', array( __CLASS__, 'set_html_content_type') );
		update_post_meta ( $post->ID, $meta_key, $sent );

		// reset post to previous, just in case
		$post = $old;

	}

	/**
	 *
	 */
	public static function set_html_content_type() {
		return 'text/html';
	}

}

if ( !isset( $petermolnareu_theme ) || empty ( $petermolnareu_theme ) ) {
	$petermolnareu_theme = new petermolnareu();
}
