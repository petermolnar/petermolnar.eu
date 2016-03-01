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

		// autocompile LESS to CSS
		$dirname = dirname(__FILE__);
		$lessfile = $dirname . '/style.less';
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
		add_action( 'init', array( &$this, 'init'));

		// add css & js
		add_action( 'wp_enqueue_scripts', array(&$this,'register_css_js'),10);

		// enable webmentions for comments
		add_action ( 'comment_post', array(&$this, 'comment_webmention'),8,2);

		// do things on post publish
		add_action( 'transition_post_status', array( &$this, 'on_publish' ), 99, 5 );

		// hook for mail sending
		add_action( 'posse_to_smtp', array( 'petermolnareu', 'posse_to_smtp' ), 99, 3 );
	}

	/**
	 *
	 */
	public function init () {

		// required WP Theme magic
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'menus' );
		add_theme_support( 'html5', array( 'search-form' ) );
		add_theme_support( 'title-tag' );

		// add main menus
		register_nav_menus( array(
			static::menu_header => __( self::menu_header , 'petermolnareu' ),
		) );

		// replace default <title></title>
		add_filter('wp_title', array(&$this, 'nice_title',),10,1);

		// add link extraction to webmention and ping hooks as they
		// don't always do a good enough job
		add_filter ('webmention_links', array(&$this, 'webmention_links'), 1, 2);
		add_filter ('get_to_ping', array(&$this, 'webmention_links'), 1);

		// I want to upload svg
		add_filter('upload_mimes', array(&$this, 'cc_mime_types'));

		// add comment endpoint to query vars
		add_filter( 'query_vars', array( &$this, 'add_query_var' ) );
		add_rewrite_endpoint ( pmlnr_comment::comment_endpoint(), EP_ROOT );

		// add reaction url if any and clean up Press This content
		add_filter ('press_this_suggested_content', array (&$this, 'press_this_add_reaction_url'));
		add_filter ('enable_press_this_media_discovery', '__return_false' );
		add_filter ('press_this_suggested_content', array ('pmlnr_markdown', 'html2markdown'), 1);
		add_filter ('press_this_suggested_content', array (&$this, 'cleanup_press_this_content'), 2);


		// for responsive videos
		add_filter( 'embed_oembed_html', array ( &$this, 'custom_oembed_filter' ), 10, 4 ) ;


		//add_filter ( 'wp_webmention_again_comment_content', array ( 'pmlnr_markdown', 'html2markdown') );
	}

	/**
	 * adds a wrapper div around video iframes to make them responsive
	 *
	 */
	public function custom_oembed_filter($html, $url, $attr, $post_ID) {
		$return = '<div class="video-container">'.$html.'</div>';
		return $return;
	}


	/**
	 * add webmention to accepted query vars
	 *
	 * @param array $vars current query vars
	 *
	 * @return array extended vars
	 */
	public function add_query_var($vars) {
		array_push($vars, pmlnr_comment::comment_endpoint() );
		return $vars;
	}

	/**
	 * register & queue css & js
	 *
	 */
	public function register_css_js () {
		$base_url = get_bloginfo("template_directory");
		$js_url = "{$base_url}/js";
		$css_url = "{$base_url}/css";

		// this is moved to inline
		//wp_register_style( "style", "{$base_url}/style.css" , false );
		//wp_enqueue_style( "style" );

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
		wp_register_script( "indie-config" ,  "{$js_url}/indie-config.js", false, null, true );
		wp_enqueue_script( "indie-config" );
		wp_register_script( "webactions" ,  "{$js_url}/webactions.js", false, null, true );
		wp_enqueue_script( "webactions" );

		// srcset fallback
		wp_register_script( "picturefill" , "{$base_url}/lib/picturefill/dist/picturefill.min.js", false, null, true );
		wp_enqueue_script( "picturefill" );

	}

	/**
	 * filter links to webmentions
	 *
	 * this is needed because markdown
	 * and because of the special fields the to be poked webmention
	 * url is stored in
	 */
	public function webmention_links ( $links, $postid = null ) {

		if (empty($postid))
			$post = pmlnr_base::fix_post();
		else
			$post = get_post ( $postid );

		if (!pmlnr_base::is_post($post))
			return $links;

		// Find all external links in the source
		$matches = pmlnr_base::extract_urls($post->post_content);

		if (!empty($matches)) {
			$links = array_merge($links, $matches);
		}

		// additional meta content links
		$webmention_url = get_post_meta( $post->ID, 'webmention_url', true );
		if (!empty($webmention_url)) {
			array_push($links, $webmention_url);
		}

		// additional urls from comments
		$comment_urls = get_post_meta( $post->ID, pmlnr_comment::comment_endpoint(), false );
		$links = array_merge($links, $comment_urls);

		foreach ($links as $k => $link) {
			$links[$k] = strtolower($link);
		}

		$links = array_unique($links);

		pmlnr_base::debug ( "Post {$post->ID} urls for webmentioning: " . join(', ', $links) );
		pmlnr_base::debug ( debug_backtrace() );
		return $links;
	}

	/**
	 *
	 */
	public function nice_title ( $title ) {
		if (is_home() || empty($title))
			return get_bloginfo('name');

		return trim( str_replace ( array ('&raquo;', '»' ), array ('',''), $title ) );
	}


	/**
	 *
	 */
	public static function make_post_syndication ( &$post = null ) {
		$post = pmlnr_base::fix_post($post);

		if ($post === false)
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
	public function cc_mime_types($mimes) {
		$mimes['svg'] = 'image/svg+xml';
		return $mimes;
	}

	/**
	 *
	 */
	public function on_publish( $new_status, $old_status, $post ) {

		$post = pmlnr_base::fix_post($post);
		if ( ! pmlnr_base::is_post( $post ) )
			return false;

		if ( 'post' != $post->post_type )
			return false;

		// -- these will run on anything ---
		$yaml = pmlnr_base::get_yaml();
		$format = pmlnr_base::post_format ( $post );

		if ( 'photo' == $format )
			static::autotag_by_photo ( $post );

		// --- these only on already publish one, incl. refresh ---
		if ( 'publish' != $new_status )
			return false;

		// --- these only when a post is freshly published ---
		if ( $new_status == $old_status )
			return false;

		if ( in_array( $format, $yaml['smtp_categories']) ) {
			$args = array ( 'post' => $post );
			wp_schedule_single_event( time() + 120, 'posse_to_smtp', $args );
		}

	}

	/**
	 *
	 */
	public static function autotag_by_photo ( $post ) {

		$taxonomy = 'post_tag';

		$thid = get_post_thumbnail_id( $post->ID );

		if ( empty($thid) )
			return false;

		$meta = pmlnr_base::get_extended_thumbnail_meta ( $thid );
		if ( isset( $meta['image_meta'] ) && isset ( $meta['image_meta']['keywords'] ) && !empty( $meta['image_meta']['keywords'] ) ) {

			$keywords = $meta['image_meta']['keywords'];

			// add photo tag
			$keywords[] = 'photo';

			if ( isset ( $meta['image_meta']['camera'] ) && ! empty ( $meta['image_meta']['camera'] ) ) {

				// add camera
				$keywords[] = $meta['image_meta']['camera'];

				// add camera manufacturer
				if ( strstr( $meta['image_meta']['camera'], ' ' ) ) {
					$manufacturer = ucfirst ( strtolower ( substr ( $meta['image_meta']['camera'], 0, strpos( $meta['image_meta']['camera'], ' ') ) ) ) ;
					$keywords[] = $manufacturer;
				}

			}

			$keywords = array_unique($keywords);
			foreach ( $keywords as $tag ) {
				if ( !term_exists( $tag, $taxonomy ))
					wp_insert_term ( $tag, $taxonomy );

				if ( !has_term( $tag, $taxonomy, $post ) ) {
					pmlnr_base::debug ( "appending post #{$post->ID} {$taxonomy} taxonomy with: {$tag}");
					wp_set_post_terms( $post->ID, $tag, $taxonomy, true );
				}
			}
		}
	}


	/**
	 *
	 */
	public static function posse_to_smtp ( $_post ) {
		pmlnr_base::debug( "POSSE #{$_post->ID} to SMTP" );

		$_post = pmlnr_base::fix_post($_post);
		if ( ! pmlnr_base::is_post( $_post ) ) {
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
					Ha le akarsz iratkozni, <a href="mailto:'. $email . '">szólj</a>.
				</p>
			</body>
		</html>';

		// add webmention url
		//$url = get_post_meta ( $post->ID, 'webmention_url', true);
		//if ( $url )
		//	$url = '<h2><a href="'.$url.'">'.$url.'</a></h2>';
		//$content = sprintf ( $content, $url );


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

	}

	/**
	 *
	 */
	public static function set_html_content_type() {
		return 'text/html';
	}

	/**
	 *
	 */
	public function comment_webmention ( $comment_ID, $comment_approved = false ) {
		if ( ! function_exists( 'send_webmention' ) ) {
			return false;
		}

		if ( false == $comment_approved ) {
			pmlnr_base::debug ( "comment #{$comment_ID} is not approved" );
			return false;
		}

		$comment = get_comment( $comment_ID );

		if ( ! pmlnr_base::is_comment ( $comment ) ) {
			pmlnr_base::debug ( "comment #{$comment_ID} is not a comment" );
			return false;
		}

		if ( empty( $comment->comment_parent ) ) {
			pmlnr_base::debug ( "comment #{$comment_ID} doesn't have a parent" );
			return false;
		}

		$parent = get_comment( $comment->comment_parent );

		if ( ! pmlnr_base::is_comment ( $parent ) ) {
			pmlnr_base::debug ( "comment #{$comment_ID} parent is not a comment" );
			return false;
		}

		if ( empty ( $parent->comment_author_url ) ) {
			pmlnr_base::debug ( "comment #{$comment_ID} no author url for parent" );
			return false;
		}

		$permalink = pmlnr_comment::get_permalink($comment_ID);

		pmlnr_base::debug ( "comment #{$comment_ID} sending webmention to: {$parent->comment_author_url} as: {$permalink}" );
		send_webmention ( $permalink, $parent->comment_author_url );
	}

	/*
	 *
	 */
	public static function cleanup_press_this_content ( $content ) {
		$content = preg_replace("/^Source: /m", '\- ', $content);
		return $content;
	}

	/**
	 * extract the url from the uri and insert it formatted accordingly automatically
	 *
	 */
	public function press_this_add_reaction_url ( $content ) {
		$ref = array();
		parse_str ( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_QUERY ), $ref );

		if ( is_array( $ref ) && isset ( $ref['u'] ) && ! empty( $ref['u'] ) ) {
			$url = $ref['u'];
			$t = '';

			if ( isset( $ref['type'] ) )
				$t = $ref['type'];

			switch ( $t ) {
				case 'fav':
				case 'like':
				case 'u-like-of':
					$type = 'like: ';
					break;
				case 'repost':
					$type = 'from: ';
					break;
				case 'reply':
					$type = 're: ';
					break;
				default:
					$type = '';
					break;
			}

			$relation = "---\n{$type}{$url}\n---\n\n";

			$content = $relation . $content;

		}

		return $content;
	}

}

if ( !isset( $petermolnareu_theme ) || empty ( $petermolnareu_theme ) ) {
	$petermolnareu_theme = new petermolnareu();
}


