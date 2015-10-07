<?php

$dirname = dirname(__FILE__);

include_once ($dirname . '/lib/parsedown/Parsedown.php');
include_once ($dirname . '/lib/parsedown-extra/ParsedownExtra.php');

include_once ($dirname . '/classes/adaptgal-ng.php');
include_once ($dirname . '/classes/utils.php');

/**
 *
 */
if ( !function_exists ( 'preg_value' ) ) {
	function preg_value ( $string, $pattern, $index = 1 ) {
		preg_match( $pattern, $string, $results );
		if ( isset ( $results[ $index ] ) && !empty ( $results [ $index ] ) )
			return $results [ $index ];
		else
			return false;
	}
}


/**
 *
 */
class petermolnareu {
	public $theme_constant = 'petermolnareu';
	const menu_header = 'header';

	//const shortdomain = 'http://pmlnr.eu/';
	//const shorturl_enabled = false;

	public $webmention_types = null;

	public function __construct () {
		// compile theme file if needed
		$dirname = dirname(__FILE__);

		// autocompile LESS to CSS
		$lessfile = $dirname . '/style.less';
		$lessmtime = filemtime( $lessfile );
		$cssfile = $dirname . '/style.css';
		$cssmtime = filemtime( $cssfile );

		if ($cssmtime < $lessmtime ) {
			include_once ($dirname . '/lib/lessphp/lessc.inc.php');
			$less = new lessc;
			$less->compileFile( $lessfile, $cssfile );
			touch ( $cssfile, $lessmtime );
		}

		$this->adaptive_images = new adaptive_images();
		//$this->parsedown = new pmlnr_md();
		//$this->utils = new pmlnr_utils();

		add_image_size ( 'headerbg', 720, 0, false );

		// init all the things!
		add_action( 'init', array( &$this, 'init'));
		add_action( 'init', array( &$this->adaptive_images, 'init'));

		// add css & js
		add_action( 'wp_enqueue_scripts', array(&$this,'register_css_js'));

		// replace shortlink
		//remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );
		//add_action( 'wp_head', array(&$this, 'shortlink'));

		add_action( 'widgets_init', array( &$this, 'widgets_init' ) );

		// cleanup
		remove_action('wp_head', 'rsd_link');
		remove_action('wp_head', 'wlwmanifest_link');
		remove_action('wp_head', 'index_rel_link'); // Index link
		remove_action('wp_head', 'parent_post_rel_link', 10, 0); // Prev link
		remove_action('wp_head', 'start_post_rel_link', 10, 0);
		remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
		remove_action('wp_head', 'adjacent_posts_rel_link', 10, 0);
		remove_action('wp_head', 'wp_generator');
		remove_action('wp_head', 'rel_canonical');
		remove_action('admin_print_styles', 'print_emoji_styles' );
		remove_action('wp_head', 'print_emoji_detection_script', 7 );
		remove_action('admin_print_scripts', 'print_emoji_detection_script' );
		remove_action('wp_print_styles', 'print_emoji_styles' );

		// RSS will be added by hand
		remove_action( 'wp_head', 'feed_links', 2 );
		remove_action( 'wp_head','feed_links_extra', 3);

		// add graphmeta, because world
		add_action('wp_head',array(&$this, 'graphmeta'));


		// Add meta boxes on the 'add_meta_boxes' hook.
		add_action( 'add_meta_boxes', array(&$this, 'post_meta_add' ));
		add_action( 'save_post', array(&$this, 'post_meta_save' ) );

		// export yaml + md format
		//add_action( 'save_post', array(&$this, 'exportyaml' ) );

		add_action('restrict_manage_posts', array(&$this, 'type_dropdown'));

		//$statuses = array('new', 'draft', 'auto-draft', 'pending', 'private', 'future' );
		//foreach ($statuses as $status) {
			//add_action("{$status}_to_publish", array(&$this, "publish"));
		//}
		//add_action( 'publish_future_post', array(&$this, "publish"));
	}

	public function init () {

		// cleanup
		remove_filter( 'the_content', 'wpautop' );
		remove_filter( 'the_excerpt', 'wpautop' );
		remove_filter( 'the_content', 'make_clickable', 12 );
		remove_filter( 'comment_text', 'make_clickable', 9);
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		add_filter( 'tiny_mce_plugins', array(&$this, 'disable_emojicons_tinymce') );


		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'menus' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list') );

		// add main menus
		register_nav_menus( array(
			self::menu_header => __( self::menu_header , $this->theme_constant ),
		) );

		// enable custom uploads
		//add_filter('upload_mimes', array( &$this, 'custom_upload_mimes' ) );

		// additional user meta fields
		add_filter('user_contactmethods', array( &$this, 'add_user_meta_fields'));

		// shortlink replacement
		//add_filter( 'get_shortlink', array(&$this, 'shorturl'), 1, 4 );

		// replace img inserts with Markdown
		add_filter( 'image_send_to_editor', array( &$this, 'media_string_html2md'), 10 );

		// markdown
		add_filter( 'the_content', array( &$this, 'parsedown'), 8, 1 );
		add_filter( 'the_excerpt', array( &$this, 'parsedown'), 8, 1 );

		//
		add_filter( 'content_save_pre' , array(&$this, 'sanitize_content') , 10, 1);

		// replace shitty default <title></title>
		add_filter('wp_title', array(&$this, 'nice_title',),10,1);

		// add webmention box
		add_filter( 'the_content', array( &$this, 'insert_post_relations'), 7, 1 );

		// add the webmention box value to the webmention links list
		add_filter ('webmention_links', array(&$this, 'webmention_links'), 1, 2);

		add_filter('parse_query', array(&$this, 'convert_id_to_term_in_query'));

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
	 * Bloody emojis
	 */
	public function disable_emojicons_tinymce( $plugins ) {
		if ( is_array( $plugins ) )
			return array_diff( $plugins, array( 'wpemoji' ) );
		else
			return array();
	}


	/**
	 * register & queue css & js
	 */
	public function register_css_js () {
		$base_url = get_bloginfo('template_directory');
		$js_url = $base_url . '/js';
		$css_url = $base_url . '/css';

		/* enqueue CSS */
		wp_register_style( 'style', $base_url . '/style.css' , false );
		wp_enqueue_style( 'style' );
		// $this->css_version ( dirname(__FILE__) . '/style.css' ) );

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

		/* CDN scripts */
		//wp_deregister_script( 'jquery' );
		//wp_register_script( 'jquery', 'https://code.jquery.com/jquery-1.11.0.min.js', false, null, false );
		//wp_enqueue_script( 'jquery' );

		// cleanup
		wp_dequeue_script( 'mediaelement' );
		wp_dequeue_script( 'wp-mediaelement' );
		wp_dequeue_style ('wp-mediaelement');
		wp_dequeue_script ('devicepx');
		wp_dequeue_style ('open-sans-css');
		//wp_deregister_style ('open-sans-css');
	}

	/**
	 * add cc field
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
		wp_nonce_field( basename( __FILE__ ), $this->theme_constant );
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
		if ( !isset( $_POST[ $this->theme_constant ] ))
			return $post_id;

		 if (!wp_verify_nonce( $_POST[ $this->theme_constant ], basename( __FILE__ ) ) )
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
			$new = filter_var($_POST[$key], $san[$key]);
			$curr = get_post_meta( $post_id, $key, true );

			if ( !empty($new) )
				$r = update_post_meta( $post_id, $key, $new, $curr );
			elseif ( empty($new) && !empty($curr) )
				$r = delete_post_meta( $post_id, $key );
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
		if (preg_match_all("/\b(?:http|https)\:\/\/?[a-zA-Z0-9\.\/\?\:@\-_=#]+\.[a-zA-Z0-9\.\/\?\:@\-_=#]*/i", $post->post_content, $matches)) {
			$xlinks = $matches[0];
			$links = array_merge($links, $xlinks);
		}

		// additional meta content links
		$webmention_url = get_post_meta( $post->ID, 'webmention_url', true );
		if (!empty($metacontent))
			array_unshift($links, $webmention_url);

		//$links = array_unique( $links );
		//pingback(join(' ', $links), $post->ID);

		return $links;
	}

	/**
	 * extend allowed mime types
	 *
	 * @param array $existing_mimes Array containing existing mime types
	 *
	public function custom_upload_mimes ( $existing_mimes=array() ) {
		$existing_mimes['svg'] = 'image/svg+xml';
		$existing_mimes['webp'] = 'image/webp';

		return $existing_mimes;
	}

	/**
	 * additional user fields
	 */
	public function add_user_meta_fields ($profile_fields) {

		$profile_fields['pgp'] = __('URL to PGP key for the email address above', $this->theme_constant);
		$profile_fields['github'] = __('Github username', $this->theme_constant);
		$profile_fields['mobile'] = __('Mobile phone number', $this->theme_constant);
		$profile_fields['linkedin'] = __('LinkedIn username', $this->theme_constant);
		$profile_fields['flickr'] = __('Flickr username', $this->theme_constant);
		$profile_fields['tubmlr'] = __('Tumblr blog URL', $this->theme_constant);
		$profile_fields['500px'] = __('500px username', $this->theme_constant);
		$profile_fields['instagram'] = __('instagram username', $this->theme_constant);
		$profile_fields['skype'] = __('skype username', $this->theme_constant);
		$profile_fields['twitter'] = __('twitter username', $this->theme_constant);


		return $profile_fields;
	}

	/**
	 *
	 *
	public function shortlink () {
		if (is_singular())
			printf ('<link rel="shortlink" href="%s" />%s', $this->shorturl() , "\n");
	}

	/**
	 * replace original shortlink
	 *
	public function shorturl ( $shortlink = '', $id = '', $context = '', $allow_slugs = '' ) {
		global $post;

		if (empty($post) || !isset($post->ID) || empty($post->ID)) {
			return $shortlink;
		}

		$url = rtrim( get_bloginfo('url'), '/' ) . '/';
		$r = $url.'?p='.$post->ID;

		return $r;
	}

	/**
	 * remove hidious quote chars and other exotic things
	 */
	function sanitize_content( $content ) {
		$search = array( '”', '“', '’', '–' );
		$replace = array ( '"', '"', "'", '-' );

		$content = str_replace( $search, $replace, $content );
		return $content;
	}

	/**
	 * pingback should die
	 *
	function remove_x_pingback($headers) {
		unset($headers['X-Pingback']);
		return $headers;
	}

	/**
	 * replace HTML img insert with Markdown Extra syntax
	 */
	public static function media_string_html2md( $str ) {
		if ( !strstr ( $str, '<img' ) )
			return $str;


		$src = preg_value ( $str, '/src="([^"]+)"/' );
		$title = preg_value ( $str, '/title="([^"]+)"/' );
		$alt = preg_value ( $str, '/alt="([^"]+)"/' );
		if ( empty ( $alt ) && !empty ( $title ) ) $alt = $title;
		$wpid = preg_value ( $str, '/wp-image-(\d*)/' );
		$src = preg_value ( $str, '/src="([^"]+)"/' );
		$cl = preg_value ( $str, '/class="([^"]+)?(align(left|right|center))([^"]+)?"/', 2 );
		if (!empty($cl)) $cl = ' .' . $cl;

		if (!empty($title)) $title = ' ' . $title;
		if (!empty($wpid)) $imgid = '#img-' . $wpid;


		$img = sprintf ('![%s](%s%s){%s%s}', $alt, $src, $title, $imgid, $cl);
		//$img = '!['.$alt.']('. $src .''. $title .'){#img-'. $wpid .''.$cl.'}';
		return $img;
	}

	/**
	 * parsedown
	 */
	public static function parsedown ( $md ) {

		if ( empty ( $md ) )
			return false;

		$parsedown = new ParsedownExtra();
		$parsedown->setBreaksEnabled(true);
		$md = $parsedown->text ( $md );

		return $md;
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
				$img = adaptive_images::imagewithmeta( $thid );
				$og['og:image'] = $img['largeurl'];
				$og['twitter:image:src'] = $img['largeurl'];
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
	 * new utils - no formatting, no html, just data
	 */

	public static function author_social ( $author_id = 1 ) {
		$list = [];

		$socials = array (
			'github'   => 'https://github.com/%s',
			//'linkedin' => 'https://www.linkedin.com/in/%s',
			//'twitter'  => 'https://twitter.com/%s',
			'flickr'   => 'https://www.flickr.com/people/%s',
			//'500px'	=> 'https://500px.com/%s',
			//'instagram'=> 'https://instagram.com/%s',
			//'skype'=> 'callto:%s',
		);

		foreach ( $socials as $silo => $pattern ) {
			$socialmeta = get_the_author_meta ( $silo , $author_id );

			if ( !empty($socialmeta) )
				$list[ $silo ] = sprintf ( $pattern, $socialmeta );

		}

		return $list;
	}

	/**
	 *
	 */
	public static function insert_post_relations( $content ) {
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

		if ( !empty($webmention_url)):
			$rel = str_replace('u-', '', $cl );
			//$add = "\n##### $h";
			$add = "\n[$webmention_url]($webmention_url){.$cl}\n";
			if (!empty($webmention_rsvp))
				$add .= '<data class="p-rsvp" value="' . $webmention_rsvp .'">'. $rsvps[ $webmention_rsvp ] .'</data>';

			$content .= $add;
		endif;

		return $content;
	}

	/**
	 *
	 */
	public static function post_get_tags_array ( ) {
		$r = [];

		$tags = get_the_tags();
		if ( $tags )
			foreach( $tags as $tag )
				$r[ $tag->name ] = get_tag_link( $tag->term_id );

		return $r;
	}

	/**
	 *
	 */
	public static function post_get_syndicates ( ) {
		global $post;
		$parsed = [];

		$syndicates = get_post_meta ( get_the_ID(), 'syndication_urls', true );

		if ( !$syndicates )
			return $parsed;

		$syndicates = explode( "\n", $syndicates );

		foreach ($syndicates as $syndicate ) {
			// example https://(www.)(facebook).(com)/(...)/(post_id)
			preg_match ( '/^http[s]?:\/\/(www\.)?([0-9A-Za-z]+)\.([0-9A-Za-z]+)\/(.*)\/(.*)$/', $syndicate, $split);

			if ( !empty($split) && isset($split[2]) && !empty($split[2]) && isset($split[3]) && !empty($split[3]))
				$parsed[$split[2]] = $split;
		}

		return $parsed;
	}

	/**
	 *
	 */
	public static function post_get_replylist ( ) {

		$syndicates = static::post_get_syndicates();
		$reply = [];

		if (empty($syndicates))
			return $reply;

		foreach ($syndicates as $silo => $syndicate ) {
			if ($silo == 'twitter') {
				//$rurl = sprintf ('https://twitter.com/intent/tweet?in_reply_to=%s',  $syndicate[5]);
				continue;
			}
			else {
				$reply[ $silo ] = $syndicate[0];
			}
		}

		return $reply;
	}

	/**
	 *
	 */
	public static function post_get_sharelist ( ) {
		global $post;

		$share = [];

		$syndicates = static::post_get_syndicates();

		$url = urlencode( get_permalink() );
		$title = urlencode( get_the_title() );
		$description = urlencode( get_the_excerpt() );

		$media = ( $thid = get_post_thumbnail_id( $post->ID )) ? wp_get_attachment_image_src($thid,'large', true) : false;
		$media_url = ( ! $media ) ? false : urlencode($media[0]);

		if (!empty($syndicates)) {
			foreach ($syndicates as $silo => $syndicate ) {
				//if ($silo == 'twitter') {
					//$rurl = sprintf ( 'https://twitter.com/intent/retweet?tweet_id=%s', $syndicate[5]);
				//}
				if ($silo == 'facebook') {
					$rurl = sprintf ( 'https://www.facebook.com/share.php?u=%s', urlencode($syndicate[0]) );
				}
				else {
					continue;
				}

				if ($rurl)
					$share[$silo] = $rurl;
			}
		}

		if (!isset($share['facebook']))
			$share['facebook'] = sprintf ('https://www.facebook.com/share.php?u=%s', $url );

		if (!isset($share['twitter']))
			$share['twitter'] = sprintf('https://twitter.com/share?url=%s&text=%s', $url, $title );

		$share['googleplus'] = sprintf('https://plus.google.com/share?url=%s', $url );

		$share['tumblr'] = sprintf('http://www.tumblr.com/share/link?url=%s&title=%s&description=%s', $url, $title, $description );

		$share['pinterest'] = sprintf('https://pinterest.com/pin/create/bookmarklet/?media=%s&url=%s&description=%s&is_video=false', $media_url, $url, $title );

		// short url / webmention
		$share['webmention'] = $url;

		return $share;
	}

	public static function makesyndication () {
		global $nxs_snapAvNts;
		global $post;

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
			'name' => __( 'Subscribe', $this->theme_constant ),
			'id' => 'subscribe',
			'before_widget' => '',
			'after_widget'  => '',
			'before_title'  => '',
			'after_title'   => '',
		) );
	}

	/**
	 * export to yaml on the fly
	 *
	public static function doyaml ( $postid = false ) {

		if (!$postid)
			return;

		$post = get_post($postid);

		// this is for structural reasons
		$cat = get_the_category( $post->ID );
		if ( empty($cat) || !isset($cat[0]) || empty($cat[0]) || !is_object($cat[0]) )
			return false;

		$category = $cat[0]->cat_name;
		$category_slug = $cat[0]->slug;

		// setup flat directories
		$flatpdir = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'flat';
		$flatcdir = $flatpdir . DIRECTORY_SEPARATOR . $category_slug;
		$flatdir = $flatcdir . DIRECTORY_SEPARATOR . $post->post_name;
		$flatfile = $flatdir . DIRECTORY_SEPARATOR . 'item.md';

		// current timestamp in DB
		$post_timestamp = get_the_modified_time( 'U' );

		// check file existance
		if ( @file_exists($flatfile) ) {
			// compare timestamps
			$file_timestamp = @filemtime ( $flatfile );
			// only do the rest if the post was modified since
			// the last export
			if ( $file_timestamp == $post_timestamp ) {
				return;
			}
		}

		// make directories, if neccessary
		$mkdir = array ( $flatpdir, $flatcdir, $flatdir );
		foreach ( $mkdir as $dir ) {
			if ( !is_dir($dir)) {
				if (!mkdir( $dir )) {
					error_log('Failed to create ' . $flatdir . ', exiting YAML creation');
					return false;
				}
			}
		}

		// check for featured image
		$thid = ( has_post_thumbnail () ) ? get_post_thumbnail_id( $post->ID ) : false;
		if ( $thid )
			$img = pmlnr_utils::absolute_url(wp_get_attachment_url ( $thid ));

		// getting format
		$format = self::get_type($post->ID);
		if ( empty($format)) $format = 'article';

		// building taglist
		$taglist = '';
		$tags = get_the_tags();
		if ( !empty( $tags )) {
			foreach ( $tags as $tag ) {
				$taglist[$tag->slug] = $tag->name;
			}
			$taglist = join ("\n      - ", $taglist);
		}

		// parsing excerpt & building content
		$parsedown = new ParsedownExtra();

		$content = $post->post_content;
		$excerpt = $post->post_excerpt;

		$search = array ( '”', '“', '’', '–', "\x0D" );
		$replace = array ( '"', '"', "'", '-', '' );

		$content = str_replace ( $search, $replace, $content );

		$excerpt = str_replace ( $search, $replace, $excerpt );
		$excerpt = strip_tags ( $parsedown->text ( $excerpt ) );

		$search = array ("\n");
		$replace = array ("");
		$description = trim ( str_replace( $search, $replace, $excerpt), "'\"" );

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

		$twsummary = (empty($thid)) ? 'summary' : 'summary_large_image';

		// this is where the actual output starts
		$out = "---\n";
		$out .=  "title: " . str_replace( '–', '-', get_the_title()) . "\n";
		$out .=  "published: true\n";
		$out .=  "modified_date: " . get_the_modified_time('Y-m-d H:i') . "\n";
		$out .=  "publish_date: " . get_the_time('Y-m-d H:i') . "\n";
		$out .=  "slug: " . $post->post_name . "\n";
		$out .=  "id: " . $post->ID  . "\n";
		$out .=  "permalink: " . get_the_permalink() . "\n";
		$out .=  "shortlink: " . wp_get_shortlink() . "\n";
		$out .=  "taxonomy: \n";
		$out .=  "    tag: \n";
		$out .=  "      - " .$taglist . "\n";

		$webmention_url = get_post_meta ( $post->ID, 'webmention_url', true);
		if (!empty($webmention_url)) {
			$webmention_type = get_post_meta ( $post->ID, 'webmention_type', true);
			switch ($webmention_type) {
				case 'u-like-of':
					$out .=  "    u-like-of: " .  $webmention_url . "\n";
					break;
				case 'u-repost-of':
					$out .=  "    u-repost-of: " .  $webmention_url . "\n";
					break;
				default:
					$out .=  "    u-in-reply-to: " .  $webmention_url . "\n";
					break;
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
			foreach ( $attachments as $aid => $attachment ) {
				$attachment_path = get_attached_file( $aid );
				$attachment_file = basename( $attachment_path);
				$target_file = $flatdir . DIRECTORY_SEPARATOR . $attachment_file;
				error_log ('should ' . $post->ID . ' have this attachment?: ' . $aid );
				if ( !is_file($target_file))
					link( $attachment_path, $target_file );
			}
		}
		elseif ( !empty($attachments) ) {
			error_log ('something is messed up; #' . $post->ID . ' wanted to save all this: ' . json_encode($attachments) );
		}

		// syndication links
		$_syndicated = get_post_meta ( $post->ID, 'syndication_urls', true );
		if ( !empty ($_syndicated ) ) {
			$synlinks = join (', ', explode("\n", trim($_syndicated)));
			$out .=  "syndicated: [{$synlinks}]\n";
		}

		// excerpt separator
		$out .= "---\n";

		if($post->post_excerpt) {
			$out .= $excerpt . "\n\n===\n";
		}

		$out .= $content;

		// write log
		error_log ('Exporting #' . $post->ID . ', ' . $post->post_name . ' to ' . $flatfile );
		file_put_contents ($flatfile, $out);
		touch ( $flatfile, $post_timestamp );

		//$date = date('c');
		//exec( "cd ${flatpdir}; git add *; git commit -m 'adding ${flatfile} @ ${date}'" );
	}
	*/

	public static function exportyaml ( $postid = false ) {

		if (!$postid)
			return;

		$post = get_post($postid);

		$filename = $post->post_name;

		$flatroot = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'flat';
		$flatdir = $flatroot . DIRECTORY_SEPARATOR . $filename;
		$flatfile = $flatdir . DIRECTORY_SEPARATOR . 'item.md';

		$post_timestamp = get_the_modified_time( 'U' );
		if ( @file_exists($flatfile) ) {
			$file_timestamp = @filemtime ( $flatfile );
			if ( $file_timestamp == $post_timestamp ) {
				return;
			}
		}

		$mkdir = array ( $flatroot, $flatdir );
		foreach ( $mkdir as $dir ) {
			if ( !is_dir($dir)) {
				if (!mkdir( $dir )) {
					error_log('Failed to create ' . $dir . ', exiting YAML creation');
					return false;
				}
			}
		}

		$cat = get_the_category( $post->ID );
		if ( !empty($cat) && isset($cat[0])) {
			$category = $cat[0];
		}

		$format = self::get_type($post->ID);

		$taglist = '';
		$t = get_the_tags();
		$tags = array();
		if ( !empty( $t ))
			foreach ( $t as $tag )
				array_push($tags, $tag->name);
		$tags = array_unique($tags);


		$parsedown = new ParsedownExtra();
		$excerpt = $post->post_excerpt;
		$content = $post->post_content;
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

		$out = array (
			'title' => str_replace( '–', '-', get_the_title()),
			'modified_date' => get_the_modified_time('c'),
			'date' => get_the_time('c'),
			'slug' => $post->post_name,
			'id' => $post->ID,
			'permalink' => get_the_permalink(),
			'shortlink' => wp_get_shortlink(),
			'taxonomy' => array (
				'tag' => $tags,
				'category' => $category->name,
				'type' => $format,
			),

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
				$target_file = $flatdir . DIRECTORY_SEPARATOR . $attachment_file;
				error_log ('should ' . $post->ID . ' have this attachment?: ' . $aid );
				if ( !is_file($target_file))
					link( $attachment_path, $target_file );
			}
		}

		// syndication links
		$_syndicated = get_post_meta ( $post->ID, 'syndication_urls', true );
		if ( !empty ($_syndicated ) ) {
			$out['syndicated'] = explode("\n", trim($_syndicated));
		}

		$out = yaml_emit($out,  YAML_UTF8_ENCODING );
		if($post->post_excerpt) {
			$out .= "\n" . $excerpt . "\n";
		}

		$out .= "---\n" . $content;

		// write log
		error_log ('Exporting #' . $post->ID . ', ' . $post->post_name . ' to ' . $flatfile );
		file_put_contents ($flatfile, $out);
		touch ( $flatfile, $post_timestamp );
	}

	/**
	 * my own format manager because the built-in sucks
	 */
	public static function get_type ( $postid = false ) {
		if (empty($postid) || !is_numeric($postid))
			global $post;
		else
			$post = get_post( $postid );

		if (!$post || empty($post) || !is_object($post))
			return false;

		$return = 'article';
		$kind = wp_get_post_terms( $post->ID, 'kind', array( 'fields' => 'all' ) );

		if (is_wp_error($kind))
			return false;

		if(is_array($kind))
			$kind = array_pop( $kind );

		if (is_object($kind) && isset($kind->slug))
			$return = $kind->slug;

		return $return;
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

}

if ( !isset( $petermolnareu_theme ) || empty ( $petermolnareu_theme ) ) {
	$petermolnareu_theme = new petermolnareu();
}


