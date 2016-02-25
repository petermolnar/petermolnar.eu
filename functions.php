<?php

define('ARTICLE_MIN_LENGTH', 1100);

$dirname = dirname(__FILE__);

require __DIR__ . '/vendor/autoload.php';

require_once ($dirname . '/lib/simple_html_dom/simple_html_dom.php');
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

	const bridgy_silos = array ('twitter', 'facebook', 'instagram', 'flickr' );

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

		new pmlnr_image();
		new pmlnr_cleanup();
		new pmlnr_markdown();
		new pmlnr_post();
		new pmlnr_author();
		new pmlnr_site();
		new pmlnr_comment();

		add_image_size ( 'headerbg', 720, 0, false );

		// init all the things!
		add_action( 'init', array( &$this, 'init'));

		// add css & js
		add_action( 'wp_enqueue_scripts', array(&$this,'register_css_js'),10);

		// Add meta boxes on the 'add_meta_boxes' hook.
		add_action( 'add_meta_boxes', array(&$this, 'post_meta_add' ));
		add_action( 'save_post', array(&$this, 'post_meta_save' ) );

		//add_action('restrict_manage_posts', array(&$this, 'type_dropdown'));

		add_action ( 'comment_post', array(&$this, 'comment_webmention'),8,2);

		//add_action( 'widgets_init', array(&$this, 'widgets_init') );

		add_action( 'transition_post_status', array( &$this, 'on_publish' ), 99, 5 );

		add_action( 'posse_to_smtp', array( 'petermolnareu', 'posse_to_smtp' ), 99, 3 );
	}

	/**
	 *
	 */
	public function init () {

		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'menus' );
		add_theme_support( 'html5', array( 'search-form' ) );
		add_theme_support( 'title-tag' );

		// add main menus
		register_nav_menus( array(
			self::menu_header => __( self::menu_header , 'petermolnareu' ),
		) );

		// replace default <title></title>
		add_filter('wp_title', array(&$this, 'nice_title',),10,1);

		// add the webmention box value to the webmention links list
		add_filter ('webmention_links', array(&$this, 'webmention_links'), 1, 2);

		// add the webmention box value to the webmention links list
		add_filter ('bridgy_publish_urls', array(&$this, 'bridgy_publish_urls'), 1, 2);

		// I want to upload svg
		add_filter('upload_mimes', array(&$this, 'cc_mime_types'));

		/*
		// my own post formats
		register_taxonomy( 'kind', 'post', array (
			'label' => 'Type',
			'public' => true,
			'show_ui' => true,
			'hierarchical' => true,
			'show_admin_column' => true,
			'rewrite' => array( 'slug' => 'format' ),
		));

		add_filter('parse_query', array(&$this, 'convert_id_to_term_in_query'));

		*/

		// add comment endpoint to query vars
		add_filter( 'query_vars', array( &$this, 'add_query_var' ) );
		add_rewrite_endpoint ( pmlnr_comment::comment_endpoint(), EP_ROOT );

		add_filter ('press_this_save_post', array (&$this, 'extract_replies'), 2);
		add_filter ('enable_press_this_media_discovery', '__return_false' );

		add_filter( 'embed_oembed_html', array ( &$this, 'custom_oembed_filter' ), 10, 4 ) ;

		add_filter ('wp_url2snapshot_urls', array ( &$this, 'wp_url2snapshot_urls' ), 2, 2 );

		add_image_size ( 'thumbnail-large', 180, 180, true );
	}

	public function wp_url2snapshot_urls ( $urls, $post = null ) {
		$post = pmlnr_base::fix_post( $post );

		// additional meta content links
		$webmention_url = get_post_meta( $post->ID, 'webmention_url', true );
		if (!empty($webmention_url)) {
			array_push($urls, $webmention_url);
		}

		return $urls;
	}

	public function custom_oembed_filter($html, $url, $attr, $post_ID) {
		$return = '<div class="video-container">'.$html.'</div>';
		return $return;
	}

	public function extract_replies ( $post ) {

		parse_str ( parse_url( $_SERVER['HTTP_REFERER'], PHP_URL_QUERY ), $ref );

		$type = 'reply';
		pmlnr_base::debug ( var_export ( $ref, 1 ) );
		if ( is_array( $ref ) && isset( $ref['type'] ) ) {
			$type = $ref['type'];
			pmlnr_base::debug ( $type );
		}

		switch ( $type ) {
			case 'fav':
			case 'like':
			case 'u-like-of':
				$type = 'u-like-of';
				break;
			case 'repost':
				$type = 'u-repost-of';
				break;
			default:
				$type = 'u-in-reply-to';
				break;
		}

		$m = array();
		$match = preg_match_all('/(?:\b|>)(https?:\/\/(?:mobile|m)?\.?(?:twimg\.com|t\.co|twitter\.com|twtr\.io)[^\[<]+)(?:\b|<)/', $post['post_content'], $m );

		if ( $match && !empty( $m ) && isset( $m[0] ) && !empty($m[0]) ) {
			$m = array_pop ( $m[0] );
			add_post_meta( $post['ID'], 'webmention_url', $m );
			add_post_meta( $post['ID'], 'webmention_type', $type );
			$post['post_title'] = '';
		}

		$m = array();
		$match = preg_match_all('/(?:\b|>)(https?:\/\/(?:www)?\.?(?:flickr.com)[^\[<]+)(?:\b|<)/', $post['post_content'], $m );

		if ( $match && !empty( $m ) && isset( $m[0] ) && !empty($m[0]) ) {
			$m = array_pop ( $m[0] );
			add_post_meta( $post['ID'], 'webmention_url', $m );
			add_post_meta( $post['ID'], 'webmention_type', $type );
			//$post['post_title'] = '';
		}

		//$m = array();
		//$match = preg_match_all('/(?:\b|>)(https?:\/\/(?:www)?\.?(?:flickr.com)[^\[<]+)(?:\b|<)/', $post['post_content'], $m );

		//if ( $match && !empty( $m ) && isset( $m[0] ) && !empty($m[0]) ) {
			//$m = array_pop ( $m[0] );
			//update_post_meta( $post['ID'], 'webmention_url', $m );
			//update_post_meta( $post['ID'], 'webmention_type', 'u-like-of' );
			//$post['post_title'] = '';
		//}

		return $post;
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
	 */
	public function register_css_js () {
		$base_url = get_bloginfo("template_directory");
		$js_url = "{$base_url}/js";
		$css_url = "{$base_url}/css";

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

		// srcset fallback
		wp_register_script( "picturefill" , "{$base_url}/lib/picturefill/dist/picturefill.min.js", false, null, true );
		wp_enqueue_script( "picturefill" );

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
	public function post_meta_display_bridgy ( $object, $box ) {
		wp_nonce_field( basename( __FILE__ ), 'petermolnareu' );

		$_tpl = '<h3>{{ silo }}</h3>
		<dl>
			<dt><label for="{{ silo }}_send">Send to {{ silo }}</label></dt>
			<dd><span><input type="checkbox" name="{{ silo }}_send" value="1"></span></dd>

			<dt><label for="{{ silo }}_content">Alternative content for {{ silo }}</label></dt>
			<dd><textarea cols="80" name="{{ silo }}_content" id="{{ silo }}_content"></textarea></dd>
		</p>';

		$_tpl_done = '<h3>{{ silo }}</h3>
		<p>Already posted to: {{ url }}</p>';

		$supported = array ('twitter', 'facebook', 'instagram', 'flick');

		foreach ($supported as $silo) {
			$existing = get_post_meta($object->ID, "bridgy_response_{$silo}", true );

			if (!empty($existing)) {
				$html = str_replace('{{ url }}', json_encode($existing), $_tpl_done);
			}
			else {
				$html = $_tpl;
			}

			$html = str_replace('{{ silo }}', $silo, $html);
			echo $html;
		}
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
			<span><input type="radio" name="<?php echo $typefield ?>" value=""><?php _e('clear') ?></span>
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
	 * magical bridgy magic
	 */
	public function bridgy_publish_urls ($links, $postid) {
		if (empty($postid))
			return $links;

		$post = get_post( $postid );
		if (!pmlnr_base::is_post($post))
			return $links;

		$webmention_url = get_post_meta( $post->ID, 'webmention_url', true );
		if (empty($webmention_url))
			return $links;

		//pmlnr_base::debug("bridgy-magic: we should extend this: " . json_encode($links) );

		foreach ( static::bridgy_silos as $silo ) {
			if (stristr($webmention_url, $silo)) {
				pmlnr_base::debug("extending bridgy_publish with {$silo} because {$webmention_url}");
				$links[$silo] = 'yes';
			}
		}

		//pmlnr_base::debug("bridgy-magic: we extended: " . json_encode($links) );

		return $links;
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
			if (!strstr($url, '500px.com') && !strstr($url, 'instagram.com') && !strstr($url, 'tumblr.com'))
				$synds[] = $url;
		}

		$_syndicated = join("\n", $synds);
		if (!empty($_syndicated))
			update_post_meta ( $post->ID, 'syndication_urls', $_syndicated, $_syndicated_original );

	}

	/**
	 * these are all for custom post type
	 *
	 *
	 *
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
	*/


	/**
	 *
	 */
	public function cc_mime_types($mimes) {
		$mimes['svg'] = 'image/svg+xml';
		return $mimes;
	}

	/**
	 * old data to new data
	 */
	public static function migrate_stuff ($post) {
		$post = pmlnr_base::fix_post($post);

		$singlemention_url = get_post_meta($post->ID, 'webmention_url', true);
		$singlemention_type = get_post_meta($post->ID, 'webmention_type', true);
		if (empty($singlemention_type)) $singlemention_type = 'u-in-reply-to';
		$singlemention_rsvp = get_post_meta($post->ID, 'webmention_rsvp', true);
		if (empty($singlemention_rsvp)) $singlemention_rsvp = false;

		if (empty($singlemention_url)) {
			$twitter_reply_user = get_post_meta( $post->ID, 'twitter_in_reply_to_user_id', true);
			$twitter_reply_id = get_post_meta( $post->ID, 'twitter_in_reply_to_status_id', true);
			if ( $twitter_reply_user && $twitter_reply_id ) {
				$r = 'https://twitter.com/' . $twitter_reply_user . '/status/' . $twitter_reply_id;
				update_post_meta($post->ID, 'webmention_url', $r);
				update_post_meta($post->ID, 'webmention_type', 'u-in-reply-to');
			}

			$twitter_url = get_post_meta( $post->ID, 'twitter_permalink', true);
			if ( $twitter_url ) {
				update_post_meta($post->ID, 'webmention_url', $twitter_url);
				update_post_meta($post->ID, 'webmention_type', 'u-repost-of');
			}
		}

		/*
		$multimention = $multimention_curr = get_post_meta($post->ID, 'webmentions', true);
		if (!is_array($multimention))
			$multimention = array();

		$m = array ();
		$m['url'] = $singlemention_url;
		$m['type'] = $singlemention_type;
		if ($singlemention_rsvp != false )
			$m['rsvp'] = $singlemention_rsvp;

		$found = false;
		foreach ($multimention as $n => $mention) {
			if ($mention['url'] == $singlemention_url) {
				$found = true;
				$multimention[$n]['type'] = $singlemention_type;
				if ($singlemention_rsvp != false )
					$multimention[$n]['rsvp'] = $singlemention_rsvp;
			}
		}

		if (!$found) {
			array_push($multimention, $m);
			pmlnr_base::debug('adding multimention:' . json_encode($multimention));
			$u = update_post_meta($post->ID, 'webmentions', $multimention, $multimention_curr );
			if (is_wp_error($u))
				pmlnr_base::debug('huh? ' . $u->get_error_message());
		}
		*/
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

		// this runs on _any_ status
		$format = pmlnr_base::post_format ( $post );

		if ( 'photo' == $format )
			static::autotag_by_photo ( $post, $yaml, $format );

		// only on publish from now on
		if ( 'publish' != $new_status )
			return false;

		// these will run on update
		if ( class_exists('WP_Webmention_Again_Sender'))
			do_action( WP_Webmention_Again_Sender::cron );

		if ( $new_status == $old_status )
			return false;

		// these will only run on fresh publish
		$yaml = pmlnr_base::get_yaml();

		if ( in_array( $format, $yaml['smtp_categories']) ) {
			$args = array (
				'post' => $post,
				//'yaml' => $yaml,
				//'format' => $format
			);

			wp_schedule_single_event( time() + 120, 'posse_to_smtp', $args );
			//static::posse_to_smtp ( $post, $yaml, $format );
		}

	}

	/**
	 *
	 */
	public static function autotag_by_photo ( $post, $yaml = null, $format = null ) {
		$taxonomy = 'post_tag';

		$thid = get_post_thumbnail_id( $post->ID );

		if ( empty($thid) )
			return false;

		$meta = pmlnr_base::get_extended_thumbnail_meta ( $thid );
		if ( isset( $meta['image_meta'] ) && isset ( $meta['image_meta']['keywords'] ) && !empty( $meta['image_meta']['keywords'] ) ) {
			$keywords = $meta['image_meta']['keywords'];
			$keywords[] = 'photo';
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
	public static function posse_to_smtp ( $post ) {
		pmlnr_base::debug( "POSSE #{$post->ID} to SMTP" );

		$post = pmlnr_base::fix_post($post);
		if ( ! pmlnr_base::is_post( $post ) ) {
			pmlnr_base::debug( "this is not a post." );
			return false;
		}

		if ( 'post' != $post->post_type ){
			pmlnr_base::debug( "this is not a post type post." );
			return false;
		}


		// only on publish from now on
		if ( 'publish' != $post->post_status ){
			pmlnr_base::debug( "this is not a published post." );
			return false;
		}


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

		$sent = get_post_meta ( $post->ID, $meta_key, true );
		if ( !is_array( $sent ) )
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

		$title = get_bloginfo('url') . " // " . $template_vars['title'];

		$content = '<!DOCTYPE html>
		<html>
			<head>
				<meta charset="utf-8" />
			</head>
			<body>
				<h1>'. $template_vars['title'] .'</h1>
				%s
				'. $template_vars['content'] .'
				<hr />
				<p>
					Az oldalon: <a href="'. $template_vars['url'] .'">'. $template_vars['url'] .'</a>
				</p>
				<p>
					Ha le akarsz iratkozni, <a href="mailto:'. $email . '">szólj</a>.
				</p>
			</body>
		</html>';

		$url = get_post_meta ( $post->ID, 'webmention_url', true);
		if ( $url )
			$url = '<h2><a href="'.$url.'">'.$url.'</a></h2>';

		$content = sprintf ( $content, $url );

		add_filter( 'wp_mail_content_type', array( __CLASS__, 'set_html_content_type') );

		foreach ( $subscribers as $addr ) {

			if ( in_array( $addr, $sent ))
				continue;

			pmlnr_base::debug( "sending to {$addr}" );
			$s = wp_mail( $addr, $title, $content, $headers);

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

	/*
	public function widgets_init () {
	register_sidebar( array(
		'name'          => 'Home right sidebar',
		'id'            => 'home_right_1',
		'before_widget' => '<div>',
		'after_widget'  => '</div>',
		'before_title'  => '<h2 class="rounded">',
		'after_title'   => '</h2>',
	) );
	}
	*/

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


	public static function maybe_tidy ( $r ) {
		$indenter = new \Gajus\Dindent\Indenter();
		$r = $indenter->indent($r);
		return $r;
	}

}

if ( !isset( $petermolnareu_theme ) || empty ( $petermolnareu_theme ) ) {
	$petermolnareu_theme = new petermolnareu();
}


