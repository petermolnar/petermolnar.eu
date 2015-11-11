<?php

define('ARTICLE_MIN_LENGTH', 1100);

$dirname = dirname(__FILE__);

require_once ($dirname . '/lib/parsedown/Parsedown.php');
require_once ($dirname . '/lib/parsedown-extra/ParsedownExtra.php');
require_once ($dirname . '/lib/lessphp/lessc.inc.php');
require_once ($dirname . '/lib/simple_html_dom/simple_html_dom.php');
//require_once ($dirname . '/lib/Twig/lib/Twig/Autoloader.php');
//Twig_Autoloader::register();

require_once ($dirname . '/classes/base.php');
require_once ($dirname . '/classes/image.php');
require_once ($dirname . '/classes/cleanup.php');
require_once ($dirname . '/classes/markdown.php');
require_once ($dirname . '/classes/post.php');
require_once ($dirname . '/classes/author.php');

class petermolnareu {
	const menu_header = 'header';
	private $endpoints = array ('yaml');
//	public $twig;
//	public $twigloader;

	public function __construct () {

		// autocompile LESS to CSS {{{
		$dirname = dirname(__FILE__);
		$lessfile = $dirname . '/style.less';
		$lessmtime = filemtime( $lessfile );
		$cssfile = str_replace('less', 'css', $lessfile);
		$cssmtime = filemtime( $cssfile );

		if ($cssmtime < $lessmtime ) {
			//include_once ($dirname . '/lib/lessphp/lessc.inc.php');
			$less = new lessc;
			$less->compileFile( $lessfile, $cssfile );
			touch ( $cssfile, $lessmtime );
		}
		// }}}

		new pmlnr_image();
		new pmlnr_cleanup();
		new pmlnr_markdown();
		new pmlnr_post();
		new pmlnr_author();
		//new pmlnr_formats();

		//$loader = new Twig_Loader_Filesystem(dirname(__FILE__) .'/twig');
		//$twig = new Twig_Environment($loader, array(
			    //'cache' => WP_CONTENT_DIR . '/cache',
			//));

		add_image_size ( 'headerbg', 720, 0, false );

		// init all the things!
		add_action( 'init', array( &$this, 'init'));

		// replace shortlink
		add_action( 'wp_head', array(&$this, 'shortlink'));

		// add css & js
		add_action( 'wp_enqueue_scripts', array(&$this,'register_css_js'),10);

		// add graphmeta, because world
		//add_action('wp_head',array(&$this, 'graphmeta'));

		// Add meta boxes on the 'add_meta_boxes' hook.
		add_action( 'add_meta_boxes', array(&$this, 'post_meta_add' ));
		add_action( 'save_post', array(&$this, 'post_meta_save' ) );

		add_action('restrict_manage_posts', array(&$this, 'type_dropdown'));
		add_action( 'widgets_init', array( &$this, 'widgets_init' ) );

		if (is_admin() && !defined('DOING_AJAX')) {
			$statuses = array ('new', 'draft', 'auto-draft', 'pending', 'private', 'future' );
			foreach ($statuses as $status) {
				add_action("{$status}_to_publish", array(&$this, "check_shorturl"));
			}
		}

		add_action( 'template_redirect', array(&$this, 'template_redirect') );
		foreach ($this->endpoints as $endpoint ) {
			add_rewrite_endpoint( $endpoint, EP_PERMALINK | EP_PAGES );
		}

	}

	public function init () {

		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'menus' );
		//add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'html5', array( 'search-form' /*, 'comment-form', 'comment-list' */ ) );
		add_theme_support( 'title-tag' );

		// add main menus
		register_nav_menus( array(
			self::menu_header => __( self::menu_header , 'petermolnareu' ),
		) );

		// replace default <title></title>
		add_filter('wp_title', array(&$this, 'nice_title',),10,1);

		// add webmention box
		add_filter( 'the_content', array( &$this, 'insert_post_relations'), 1, 1 );

		// add the webmention box value to the webmention links list
		add_filter ('webmention_links', array(&$this, 'webmention_links'), 1, 2);

		//
		add_filter('parse_query', array(&$this, 'convert_id_to_term_in_query'));

		// shortlink replacement
		add_filter( 'get_shortlink', array(&$this, 'shorturl'), 1, 4 );

		//add_filter( 'embed_oembed_html', array(&$this, 'fix_youtube'), 1, 4 );

		//add_filter ('blogroll2email_content', array(&$this,'flickr_larger_picture'));

		// my own post formats
		register_taxonomy( 'kind', 'post', array (
			'label' => 'Type',
			'public' => true,
			'show_ui' => true,
			'hierarchical' => true,
			'show_admin_column' => true,
			'rewrite' => array( 'slug' => 'metatype' ),
		));
	}

	/**
	 * register & queue css & js
	 */
	public function register_css_js () {
		$base_url = get_bloginfo('template_directory');
		$js_url = $base_url . '/js';
		$css_url = $base_url . '/css';

		wp_register_style( 'style', $base_url . '/style.css' , false );
		wp_enqueue_style( 'style' );

		/* Magnific popup *
		wp_register_style( 'magnific-popup', $base_url . '/lib/Magnific-Popup/dist/magnific-popup.css' , false );
		//wp_enqueue_style( 'magnific-popup' );
		wp_register_script( 'magnific-popup', $base_url . '/lib/Magnific-Popup/dist/jquery.magnific-popup.min.js' , array('jquery'), null, false );
		//wp_enqueue_script ('magnific-popup');

		/* justified gallery *
		wp_register_style( 'Justified-Gallery', $base_url . '/lib/Justified-Gallery/dist/css/justifiedGallery.min.css' , false );
		//wp_enqueue_style( 'Justified-Gallery' );
		wp_register_script( 'Justified-Gallery', $base_url . '/lib/Justified-Gallery/dist/js/jquery.justifiedGallery.min.js' , array('jquery'), null, false );
		//wp_enqueue_script ('Justified-Gallery');

		/* syntax highlight */
		wp_register_style( 'prism', $css_url . '/prism.css', false, null );
		wp_enqueue_style( 'prism' );
		wp_register_script( 'prism' , $js_url . '/prism.js', false, null, true );
		wp_enqueue_script( 'prism' );


		/* srcset fallback */
		wp_register_script( 'picturefill' , $base_url . '/lib/picturefill/dist/picturefill.min.js', false, null, true );
		wp_enqueue_script( 'picturefill' );

		// cleanup
		wp_dequeue_style ('wp-mediaelement');
		wp_dequeue_style ('open-sans-css');
		wp_deregister_style ('wp-mediaelement');
		wp_deregister_style ('open-sans-css');

		wp_dequeue_script( 'mediaelement' );
		wp_dequeue_script( 'wp-mediaelement' );
		wp_dequeue_script ('wp-embed');
		wp_dequeue_script ('devicepx');

		wp_deregister_script( 'mediaelement' );
		wp_deregister_script( 'wp-mediaelement' );
		wp_deregister_script ('wp-embed');
		wp_deregister_script ('devicepx');

	}

	/**
	 * add webmention field to admin
	 */
	public function post_meta_add () {
		add_meta_box(
			'webmention',
			esc_html__( 'Webmention', 'petermolnareu' ),
			array(&$this, 'post_meta_display_webmention'),
			'post',
			'normal',
			'default'
		);

	}

	/**
	 * meta field display
	 */
	public function post_meta_display_webmention ( $object, $box ) {
		wp_nonce_field( basename( __FILE__ ), 'petermolnareu' );
		$urlfield = 'webmention_url';
		$webmention_url = get_post_meta( $object->ID, $urlfield, true );

		$typefield = 'webmention_type';
		$webmention_type = get_post_meta( $object->ID, $typefield, true );

		$rsvpfield = 'webmention_rsvp';
		$webmention_rsvp = get_post_meta( $object->ID, $rsvpfield, true );


		$types = array (
			'u-in-reply-to' => __('Reply'),
			'u-like-of' => __('Like'),
			'u-repost-of' => __('Repost'),
		);

		$rsvps = array ( 'no', 'yes', 'maybe' );

		?>
		<p>
			<label for="<?php echo $urlfield ?>"><?php _e('URL to poke'); ?></label><br />
			<input class="attachmentlinks" type="url" name="<?php echo $urlfield ?>" id="<?php echo $urlfield ?>" value="<?php echo $webmention_url ?>" />
		</p>
		<p>
			<label for="<?php echo $typefield ?>"><?php _e('Webmention type'); ?></label><br />
			<?php foreach ( $types as $type => $label ): ?>
			<span><input type="radio" name="<?php echo $typefield ?>" value="<?php echo $type ?>" <?php checked( $webmention_type, $type, 1 ); ?>><?php echo $label; ?></span>
			<?php endforeach; ?>
		</p>
		<p>
			<label for="<?php echo $rsvpfield ?>"><?php _e('RSVP'); ?></label><br />
			<?php foreach ( $rsvps as $data ): ?>
			<span><input type="radio" name="<?php echo $rsvpfield ?>" value="<?php echo $data ?>" <?php checked( $webmention_rsvp, $data, 1 ); ?>><?php echo $data; ?></span>
			<?php endforeach; ?>
		</p>

		<?php
	}


	/**
	 * handle additional post meta
	 */
	public function post_meta_save ( $post_id ) {
		if ( !isset( $_POST[ 'petermolnareu' ] ))
			return $post_id;

		 if (!wp_verify_nonce( $_POST[ 'petermolnareu' ], basename( __FILE__ ) ) )
			return $post_id;

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return $post_id;

		if ( ! current_user_can( 'edit_page', $post_id ) )
			return $post_id;

		// sanitize
		$san = array (
			'webmention_url' =>FILTER_SANITIZE_URL,
			'webmention_type' => FILTER_SANITIZE_STRING,
			'webmention_rsvp' => FILTER_SANITIZE_STRING,
		);

		foreach ($san as $key => $filter) {
			if (isset($_POST[$key])) {
				$new = filter_var($_POST[$key], $san[$key]);
				$curr = get_post_meta( $post_id, $key, true );

				if ( !empty($new) )
					$r = update_post_meta( $post_id, $key, $new, $curr );
				elseif ( empty($new) && !empty($curr) )
					$r = delete_post_meta( $post_id, $key );
			}
		}
	}

	/**
	 * filter links to webmentions
	 *
	 * this is needed because markdown
	 * and because of the special fields the to be poked webmention
	 * url is stored in
	 */
	public function webmention_links ( $links, $postid ) {

		if (empty($postid))
			return $links;

		$post = get_post( $postid );
		if (!$post || empty($post) || !is_object($post))
			return $links;

		// Find all external links in the source
		$matches = pmlnr_base::extract_urls($post->post_content);

		if (!empty($matches)) {
			$links = array_merge($links, $matches);
		}

		// additional meta content links
		$webmention_url = get_post_meta( $post->ID, 'webmention_url', true );
		array_push($links, $webmention_url);

		pmlnr_base::debug ( 'Post ' . $post->ID . ' urls for webmentioning: ' . join(', ', $links) );
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
	public static function graphmeta () {
		global $post;
		global $wp;
		$og = array();

		$og['og:locale'] = get_bloginfo( 'language' );
		$og['og:site_name'] = get_bloginfo('name');
		$og['og:type'] = 'website';
		$og['twitter:card'] = 'summary_large_image';

		if (is_singular()) {
			$og['og:type'] = 'article';
			$og['og:url'] = get_permalink();
			$og['og:title'] = $og['twitter:title'] = get_the_title();

			$loc = get_post_meta( $post->ID, 'locale' );
			if ($loc) $og['og:locale'] = $loc;

			$author = get_the_author();
			if ( $tw = get_the_author_meta( 'twitter' ) )
				$og['twitter:site'] = '@' . $tw;

			$og['og:updated_time'] = get_the_modified_time( 'c', $post->ID );
			$og['article:published_time'] = get_the_time( 'c', $post->ID );
			$og['article:modified_time'] = get_the_modified_time( 'c', $post->ID );

			$desc = strip_tags(get_the_excerpt());
			$og['og:description'] = $desc;
			$og['twitter:description'] = $desc;

			$tags = get_the_tags();
			$t = array();
			if ( $tags ) {
				foreach( $tags as $tag )
					array_push ($t, $tag->name );

				$og['article:tag'] = join(",", $t);
			}

			$thid = get_post_thumbnail_id( $post->ID );
			if ( $thid ) {
				$src = wp_get_attachment_image_src( $thid, 'large');
				if ( !empty($src[0])) {
					$src = pmlnr_base::fix_url($src[0]);
					$og['og:image'] = $src;
					$og['twitter:image:src'] = $src;
				}
			}
		}
		else {
			$img = get_bloginfo('template_directory') . '/images/favicon.png';
			$og['og:image'] = $img;
			$og['twitter:image:src'] = $img;
			$og['og:url'] = home_url(add_query_arg(array(),$wp->request));
			if ( is_category())
				$og['og:title'] = $og['twitter:title'] = single_cat_title( '', false );
			elseif (is_tag())
				$og['og:title'] = $og['twitter:title'] = single_cat_title( '', false );
			else
				$og['og:title'] = $og['twitter:title'] = get_bloginfo('name');
		}

		ksort($og);

		foreach ($og as $property => $content )
			printf( '<meta property="%s" content="%s" />%s', $property, $content, "\n" );
	}

	/**
	 *
	 */
	public static function insert_post_relations( $content, $post = null ) {
		if ( $post == null )
			global $post;

		if (empty($post) || !is_object($post))
			return $content;

		$webmention_url = get_post_meta ( $post->ID, 'webmention_url', true);
		$webmention_type = get_post_meta ( $post->ID, 'webmention_type', true);
		$webmention_rsvp = get_post_meta ( $post->ID, 'webmention_rsvp', true);

		switch ($webmention_type) {
			case 'u-like-of':
				$h = __('This is a like of:');
				$cl = 'u-like-of';
				break;
			case 'u-repost-of':
				$h = __('This is a repost of:');
				$cl = 'u-repost-of';
				break;
			default:
				$h = __('This is a reply to:');
				$cl = 'u-in-reply-to';
				break;
		}

		$rsvps = array (
			'no' => __("Sorry, can't make it."),
			'yes' => __("I'll be there."),
			'maybe' => __("I'll do my best, but don't count on me for sure."),
		);

		if ( !empty($webmention_url)) {
			$webmention_title = str_replace ( parse_url( $webmention_url, PHP_URL_SCHEME) .'://', '', $webmention_url);
			$rel = str_replace('u-', '', $cl );
			//$add = "\n##### $h";
			$add = "\n\n[$webmention_title]($webmention_url){.$cl}\n\n";
			if (!empty($webmention_rsvp))
				$add .= '<data class="p-rsvp" value="' . $webmention_rsvp .'">'. $rsvps[ $webmention_rsvp ] .'</data>';

			$content .= $add;
		}

		return $content;
	}


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

		/*$insta = get_post_meta( $post->ID, 'instagram_url', true);

		if ( $insta && !empty($insta))
			if ( !in_array($insta, $_syndicated))
				array_push($_syndicated, $insta);
		*/

		foreach ($_syndicated as $url ) {
			if (!strstr($url, '500px.com') && !strstr($url, 'instagram.com'))
				$synds[] = $url;
		}

		$_syndicated = join("\n", $synds);
		if (!empty($_syndicated))
			update_post_meta ( $post->ID, 'syndication_urls', $_syndicated, $_syndicated_original );

	}

	/**
	 *
	 */
	public function widgets_init () {
		register_sidebar( array(
			'name' => __( 'Subscribe', 'petermolnareu' ),
			'id' => 'subscribe',
			'before_widget' => '',
			'after_widget'  => '',
			'before_title'  => '',
			'after_title'   => '',
		) );
	}

	/**
	 *
	 */
	public function type_dropdown() {
		global $typenow;
		$post_type = 'post';
		$taxonomy = 'kind'; // change HERE
		if ($typenow == $post_type) {
			$selected = isset($_GET[$taxonomy]) ? $_GET[$taxonomy] : '';
			$info_taxonomy = get_taxonomy($taxonomy);
			wp_dropdown_categories(array(
				'show_option_all' => __("Show All {$info_taxonomy->label}"),
				'taxonomy' => $taxonomy,
				'name' => $taxonomy,
				'orderby' => 'name',
				'selected' => $selected,
				'show_count' => true,
				'hide_empty' => true,
			));
		};
	}

	/**
	 *
	 */
	public function convert_id_to_term_in_query($query) {
		global $pagenow;
		$post_type = 'post'; // change HERE
		$taxonomy = 'kind'; // change HERE
		$q_vars = &$query->query_vars;
		if ($pagenow == 'edit.php' && isset($q_vars['post_type']) && $q_vars['post_type'] == $post_type && isset($q_vars[$taxonomy]) && is_numeric($q_vars[$taxonomy]) && $q_vars[$taxonomy] != 0) {
			$term = get_term_by('id', $q_vars[$taxonomy], $taxonomy);
			$q_vars[$taxonomy] = $term->slug;
		}
	}

	/**
	 * since WordPress has it's built-in rewrite engine, it's eaiser to use
	 * that for adding the short urls
	 */
	public static function check_shorturl(&$post = null) {
		$post = pmlnr_base::fix_post($post);

		if ($post === false)
			return false;

		$epoch = get_the_time('U', $post->ID);
		$url36 = pmlnr_base::epoch2url($epoch);
		$url62 = pmlnr_base::epoch2url($epoch,62);

		pmlnr_base::debug($post->post_name . ': ' . $url36 . ', ' . $url62);

		// if the generated url is the same as the current slug, walk away
		if ( $url36 == $post->post_name || $url62 == $post->post_name )
			return true;

		$meta = get_post_meta( $post->ID, '_wp_old_slug', false);

		// cleanup if url is the same as slug
		if ( $key = array_search( $post->post_name, $meta)) {
			delete_post_meta($post->ID, '_wp_old_slug', $slug);
			unset($meta[$key]);
		}

		// 2 generated, 1 additional is still ok
		if ( count($meta) > 3 ) {
			foreach ($meta as $key => $slug ) {

				// base62 matches
				if (preg_match('/^[0-9a-zA-Z]{5}$/', $slug)) {
					static::debug('deleting slug ' . $slug . ' from ' . $post->ID );
					delete_post_meta($post->ID, '_wp_old_slug', $slug);
					unset($meta[$key]);
				}

				// base36 matches
				if (preg_match('/^[0-9a-z]{5}$/', $slug)) {
					static::debug('deleting slug ' . $slug . ' from ' . $post->ID );
					delete_post_meta($post->ID, '_wp_old_slug', $slug);
					unset($meta[$key]);
				}
			}
		}

		if ( !in_array($url36,$meta)) {
			static::debug('adding slug ' . $url36 . ' to ' . $post->ID );
			add_post_meta($post->ID, '_wp_old_slug', $url36);
		}

		if ( !in_array($url62,$meta)) {
			static::debug('adding slug ' . $url62 . ' to ' . $post->ID );
			add_post_meta($post->ID, '_wp_old_slug', $url62);
		}

		return true;
	}

	public function shortlink () {
		if (is_singular())
			printf ('<link rel="shortlink" href="%s" />%s', $this->shorturl() , "\n");
	}

	/**
	 * our very own shorturl function
	 */
	public static function shorturl ( $shortlink = '', $id = '', $context = '', $allow_slugs = '' ) {
		global $post;
		if (empty($post) || !isset($post->ID) || empty($post->ID))
			return $shortlink;

		$epoch = get_the_time('U', $post->ID);
		$url = pmlnr_base::epoch2url($epoch);

		$base = rtrim( get_bloginfo('url'), '/' ) . '/';
		return $base.$url;
	}

	/*
	public static function fix_youtube ( $cache, $url, $attr, $postid ) {
		if ( strstr($url, 'youtube.com')) {
			$search = 'watch?v=';
			$id = substr( $url, strrpos($url,$search) + strlen($search) );
			return '<iframe src="https://www.youtube.com/embed/'.$id.'?html5=1"></iframe>';
		}

		return $cache;
	}
	*/

	/**
	 *
	 */
	public function template_redirect() {
		global $wp_query;

		if (!is_singular())
			return false;

		foreach ($this->endpoints as $endpoint ) {
			if ( isset( $wp_query->query_vars[ $endpoint ]) && method_exists ( $this , $endpoint ) ) {
				header('Content-Type: text/plain;charset=utf-8');
				echo $this->$endpoint();
				exit;
			}
		}

		return true;
	}

	public static function export_yaml ( $postid = false ) {
		//pmlnr_base::debug('exporting YAML');
		if (!$postid)
			return false;

		$post = get_post($postid);

		if (!pmlnr_base::is_post($post))
			return false;

		$filename = $post->post_name;

		$flatroot = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'flat';
		$flatdir = $flatroot . DIRECTORY_SEPARATOR . $filename;
		$flatfile = $flatdir . DIRECTORY_SEPARATOR . 'item.md';
		//pmlnr_base::debug('YAML flat file: ' . $flatfile);

		$post_timestamp = get_the_modified_time( 'U', $post->ID );
		if ( @file_exists($flatfile) ) {
			$file_timestamp = @filemtime ( $flatfile );
			if ( $file_timestamp == $post_timestamp ) {
				return true;
			}
		}

		$mkdir = array ( $flatroot, $flatdir );
		foreach ( $mkdir as $dir ) {
			if ( !is_dir($dir)) {
				if (!mkdir( $dir )) {
					static::debug_log('Failed to create ' . $dir . ', exiting YAML creation');
					return false;
				}
			}
		}

		// get all the attachments
		$attachments = get_children( array (
			'post_parent'=>$post->ID,
			'post_type'=>'attachment',
			'orderby'=>'menu_order',
			'order'=>'asc'
		));

		// 100 is there for sanity
		// hardlink all the attachments; no need for copy
		// unless you're on a filesystem that does not support hardlinks
		if ( !empty($attachments) && count($attachments) < 100 ) {
			$out['attachments'] = array();
			foreach ( $attachments as $aid => $attachment ) {
				$attachment_path = get_attached_file( $aid );
				$attachment_file = basename( $attachment_path);
				$target_file = $flatdir . DIRECTORY_SEPARATOR . $attachment_file;
				pmlnr_base::debug ('should ' . $post->ID . ' have this attachment?: ' . $aid );
				if ( !is_file($target_file))
					link( $attachment_path, $target_file );
			}
		}

		$out = static::yaml();

		// write log
		pmlnr_base::debug ('Exporting #' . $post->ID . ', ' . $post->post_name . ' to ' . $flatfile );
		file_put_contents ($flatfile, $out);
		touch ( $flatfile, $post_timestamp );
		return true;
	}

	/**
	 * show post in YAML format (Grav friendly version)
	 */
	public static function yaml ( $postid = false ) {

		if (!$postid) {
			global $post;
		}
		else {
			$post = get_post($postid);
		}

		if (!pmlnr_base::is_post($post))
			return false;


		$postdata = self::raw_post_data($post);
		//pmlnr_base::debug('YAML raw data: . ' .  json_encode($postdata));
		if (empty($postdata))
			return false;

		$excerpt = false;
		if (isset($postdata['excerpt']) && !empty($postdata['excerpt'])) {
			$excerpt = $postdata['excerpt'];
			unset($postdata['excerpt']);
		}

		$content = $postdata['content'];
		//$content = wordwrap ( $content, 80, "\r\n", false);
		unset($postdata['content']);

		$out = yaml_emit($postdata,  YAML_UTF8_ENCODING );
		if($excerpt) {
			$out .= "\n" . $excerpt . "\n";
		}

		$out .= "---\n" . $content;

		return $out;
	}

	/**
	 * raw data for various representations, like JSON or YAML
	 */
	public static function raw_post_data ( &$post = null ) {
		$post = pmlnr_base::fix_post($post);

		if ($post === false)
			return false;

		$cat = get_the_category( $post->ID );
		if ( !empty($cat) && isset($cat[0])) {
			$category = $cat[0];
		}

		//$format = self::get_type($post);

		$taglist = '';
		$t = get_the_tags( $post->ID );
		$tags = array();
		if ( !empty( $t ))
			foreach ( $t as $tag )
				array_push($tags, $tag->name);
		$tags = array_unique($tags);

		$parsedown = new ParsedownExtra();
		$excerpt = $post->post_excerpt;

		$content = $post->post_content;
		$content = self::insert_post_relations($content, $post);

		$search = array ( '”', '“', '’', '–', "\x0D" );
		$replace = array ( '"', '"', "'", '-', '' );
		$excerpt = str_replace ( $search, $replace, $excerpt );
		$excerpt = strip_tags ( $parsedown->text ( $excerpt ) );
		$content = str_replace ( $search, $replace, $content );

		//$search = array ("\n");
		//$replace = array ("");
		//$description = trim ( str_replace( $search, $replace, $excerpt), "'\"" );

		// fix all image attachments: resized -> original
		$urlparts = parse_url(site_url());
		$domain = $urlparts ['host'];
		$wp_upload_dir = wp_upload_dir();
		$uploadurl = str_replace( '/', "\\/", trim( str_replace( site_url(), '', $wp_upload_dir['url']), '/'));

		$pregstr = "/((https?:\/\/". $domain .")?\/". $uploadurl ."\/.*\/[0-9]{4}\/[0-9]{2}\/)(.*)-([0-9]{1,4})×([0-9]{1,4})\.([a-zA-Z]{2,4})/";

		preg_match_all( $pregstr, $content, $resized_images );

		if ( !empty ( $resized_images[0]  )) {
			foreach ( $resized_images[0] as $cntr => $imgstr ) {
				//$location = $resized_images[1][$cntr];
				$done_images[ $resized_images[2][$cntr] ] = 1;
				$fname = $resized_images[2][$cntr] . '.' . $resized_images[5][$cntr];
				$width = $resized_images[3][$cntr];
				$height = $resized_images[4][$cntr];
				$r = $fname . '?resize=' . $width . ',' . $height;
				$content = str_replace ( $imgstr, $r, $content );
			}
		}

		$pregstr = "/(https?:\/\/". $domain .")?\/". $uploadurl ."\/.*\/[0-9]{4}\/[0-9]{2}\/(.*?)\.([a-zA-Z]{2,4})/";

		preg_match_all( $pregstr, $content, $images );
		if ( !empty ( $images[0]  )) {

			foreach ( $images[0] as $cntr=>$imgstr ) {
				//$location = $resized_images[1][$cntr];
				if ( !isset($done_images[ $images[1][$cntr] ]) ){
					if ( !strstr($images[1][$cntr], 'http'))
						$fname = $images[2][$cntr] . '.' . $images[3][$cntr];
					else
						$fname = $images[1][$cntr] . '.' . $images[2][$cntr];

					$content = str_replace ( $imgstr, $fname, $content );
				}
			}
		}

		$author_id = $post->post_author;
		$author =  get_the_author_meta ( 'display_name' , $author_id );
		//$author_url = get_the_author_meta ( 'user_url' , $author_id );

		$meta = array();
		$slugs = get_post_meta($post->ID, '_wp_old_slug', false);
		foreach ($slugs as $slug ) {
			if ( strlen($slug) > 6 )
				$meta['slugs'][] = $slug;
		}

		$meta_to_store = array('author','geo_latitude','geo_longitude','twitter_tweet_id', 'twitter_rt_id', 'twitter_rt_user_id', 'twitter_rt_time', 'twitter_reply_id', 'twitter_reply_user_id', 'instagram_id', 'instagram_url', 'twitter_id', 'twitter_permalink', 'twitter_in_reply_to_user_id', 'twitter_in_reply_to_screen_name','twitter_in_reply_to_status_id','fbpost->ID','webmention_url', 'webmention_type');

		foreach ( $meta_to_store as $meta_key ) {
			$meta_entry = get_post_meta($post->ID, $meta_key, true);
			if ( !empty($meta_entry) && $meta_entry != false ) {
				$meta[ $meta_key ] = $meta_entry;
				if ($meta_key == 'author' )
					$author = $meta_entry;
			}
		}

		if ( isset($meta))

		$out = array (
			'title' => trim(get_the_title( $post->ID )),
			'modified_date' => get_the_modified_time('c', $post->ID),
			'date' => get_the_time('c', $post->ID),
			'slug' => $post->post_name,
			'id' => $post->ID,
			'permalink' => get_permalink( $post ),
			'shortlink' => wp_get_shortlink( $post->ID ),
			'taxonomy' => array (
				'tag' => $tags,
				'category' => $category->name,
				//'type' => $format,
			),
			'postmeta' => $meta,
			'author' => $author,
		);

		$webmention_url = get_post_meta ( $post->ID, 'webmention_url', true);
		if (!empty($webmention_url)) {
			$webmention_type = get_post_meta ( $post->ID, 'webmention_type', true);
			if ($webmention_type != 'u-like-of' && $webmention_type != 'u-repost-of')
				$webmention_type = 'u-in-reply-to';

			$out['webmention'] = array (
				'type' => $webmention_type,
				'url' => $webmention_url,
			);
		}

		// get all the attachments
		$attachments = get_children( array (
			'post_parent'=>$post->ID,
			'post_type'=>'attachment',
			'orderby'=>'menu_order',
			'order'=>'asc'
		));

		// 100 is there for sanity
		// hardlink all the attachments; no need for copy
		// unless you're on a filesystem that does not support hardlinks
		if ( !empty($attachments) && count($attachments) < 100 ) {
			$out['attachments'] = array();
			foreach ( $attachments as $aid => $attachment ) {
				$attachment_path = get_attached_file( $aid );
				$attachment_file = basename( $attachment_path);
				array_push($out['attachments'], $attachment_file);
				//$target_file = $flatdir . DIRECTORY_SEPARATOR . $attachment_file;
				//error_log ('should ' . $post->ID . ' have this attachment?: ' . $aid );
				//if ( !is_file($target_file))
				//	link( $attachment_path, $target_file );
			}
		}

		// syndication links
		$_syndicated = get_post_meta ( $post->ID, 'syndication_urls', true );
		if ( !empty ($_syndicated ) ) {
			$out['syndicated'] = explode("\n", trim($_syndicated));
		}

		if($post->post_excerpt) {
			$out['excerpt'] = $excerpt;
		}

		$out['content'] = $content;

		return $out;
	}


	//public function flickr_larger_picture ( $content ) {
		//// better flickr pictures
		//preg_match_all('/farm[0-9]\.staticflickr.com\/[0-9]+\/[0-9]+_[0-9a-zA-Z]+_[a-z]{1}\.jpg/s', $content, $matches);

		//if ( !empty ( $matches[0] ) ) {
			//foreach ( $matches[0] as $to_replace ) {
				//$clean = str_replace('_m.jpg', '_c.jpg', $to_replace);
				//$content = str_replace ( $to_replace, $clean, $content );
			//}
		//}

		//return $content;
	//}


	/**
	 * Get the source's images and save them locally, for posterity, unless we can't.
	 *
	 */
	public function side_load_md_images( $post_id, $content = '' ) {
		$content = wp_unslash( $content );

		// match all markdown images
		if ( preg_match_all('/\!\[.*?\]\((.*?) ?"?.*?"?\)\{.*?\}/', $content, $matches) && current_user_can( 'upload_files' ) ) {

			foreach ( $matches[0] as $cntr => $image ) {
				$image_src = $matches[1][$cntr];

				// Don't try to sideload a file without a file extension, leads to WP upload error.
				if ( ! preg_match( '/[^\?]+\.(?:jpe?g|jpe|gif|png)(?:\?|$)/i', $image_src ) ) {
					continue;
				}

				if ( !pmlnr_base::is_url_external($image_src) ) {
					continue;
				}

				// Sideload image, which gives us a new image src.
				$new_src = media_sideload_image( $image_src, $post_id, null, 'src' );

				if ( ! is_wp_error( $new_src ) ) {
					$content = str_replace( $image_src, $new_src, $content );
				}
			}
		}

		// Edxpected slashed
		return wp_slash( $content );
	}
}

if ( !isset( $petermolnareu_theme ) || empty ( $petermolnareu_theme ) ) {
	$petermolnareu_theme = new petermolnareu();
}


