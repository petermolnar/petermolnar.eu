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

	const shortdomain = 'http://pmlnr.eu/';
	const shorturl_enabled = true;

	public function __construct () {

		$this->adaptive_images = new adaptive_images();
		//$this->parsedown = new pmlnr_md();
		//$this->utils = new pmlnr_utils();

		// init all the things!
		add_action( 'init', array( &$this, 'init'));
		add_action( 'init', array( &$this->adaptive_images, 'init'));
		//add_action( 'init', array( &$this, 'rewrites'));

		// add css & js
		add_action( 'wp_enqueue_scripts', array(&$this,'register_css_js'));

		// replace shortlink
		remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );
		add_action( 'wp_head', array(&$this, 'shortlink'));

		// cleanup
		// no link to the Really Simple Discovery service endpoint, EditURI link
		remove_action('wp_head', 'rsd_link');
		// no link to the Windows Live Writer manifest file.
		remove_action('wp_head', 'wlwmanifest_link');
		// no index link
		remove_action('wp_head', 'index_rel_link'); // Index link
		// no parent post link
		remove_action('wp_head', 'parent_post_rel_link', 10, 0); // Prev link
		// no start post link
		remove_action('wp_head', 'start_post_rel_link', 10, 0);
		//
		remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
		// no relational links for the posts adjacent to the current post.
		remove_action('wp_head', 'adjacent_posts_rel_link', 10, 0);
		// no generator
		remove_action('wp_head', 'wp_generator');
		// no canonical link
		remove_action('wp_head', 'rel_canonical');

		// Add meta boxes on the 'add_meta_boxes' hook.
		add_action( 'add_meta_boxes', array(&$this, 'post_meta_add' ));
		add_action( 'save_post', array(&$this, 'post_meta_save' ) );

	}

	public function init () {
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'menus' );
		add_theme_support( 'automatic-feed-links' );

		// http://codex.wordpress.org/Post_Formats
		add_theme_support( 'post-formats', array(
			'image', 'aside', 'video', 'audio', 'quote', 'link',
		) );

		add_theme_support( 'html5', array(
			'search-form', 'comment-form', 'comment-list'
		) );

		// add main menus
		register_nav_menus( array(
			self::menu_header => __( self::menu_header , $this->theme_constant ),
		) );

		// cleanup
		remove_filter( 'the_content', 'wpautop' );
		remove_filter( 'the_excerpt', 'wpautop' );

		// enable custom uploads
		add_filter('upload_mimes', array( &$this, 'custom_upload_mimes' ) );

		// auto-insert featured image
		add_filter( 'the_content', 'adaptive_images::featured_image', 1 );

		// additional user meta fields
		add_filter('user_contactmethods', array( &$this, 'add_user_meta_fields'));

		// shortlink replacement
		add_filter( 'get_shortlink', array(&$this, 'shorturl'), 1, 4 );

		// replace img inserts with Markdown
		add_filter( 'image_send_to_editor', array( &$this, 'rebuild_media_string'), 10 );

		// markdown

		//if ( pmlnr_utils::islocalhost() )
		//	add_filter( 'the_content', 'html_entity_decode', 9 );
		//else
		add_filter( 'the_content', array( &$this, 'parsedown'), 8, 1 );
		add_filter( 'the_content', array( &$this, 'post_remote_relation'), 1 );

		// sanitize content before saving
		add_filter( 'content_save_pre' , array(&$this, 'sanitize_content') , 10, 1);

		// remove x-pingback
		add_filter('wp_headers', array(&$this, 'remove_x_pingback'));

		add_filter('wp_title', array(&$this, 'nice_title',),10,1);

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

		/* syntax highlight */
		wp_register_style( 'prism', $css_url . '/prism.css', false, null );
		wp_enqueue_style( 'prism' );
		wp_register_script( 'prism' , $js_url . '/prism.js', false, null, true );
		wp_enqueue_script( 'prism' );

		/* CDN scripts */
		wp_deregister_script( 'jquery' );
		wp_register_script( 'jquery', 'https://code.jquery.com/jquery-1.11.0.min.js', false, null, false );
		wp_enqueue_script( 'jquery' );
	}

	/**
	 * add cc field
	 */
	public function post_meta_add () {
		add_meta_box(
			'cc_licence',
			esc_html__( 'Creative Commons', 'petermolnareu' ),
			array(&$this, 'post_meta_display_cc'),
			'post',
			'normal',
			'default'
		);
	}

	/**
	 * meta field for CC licence
	 */
	public function post_meta_display_cc ( $object, $box ) {
		wp_nonce_field( basename( __FILE__ ), $this->theme_constant );
		$meta = get_post_meta( $object->ID, 'cc', true );
		$default = $meta ? $meta : 'by';
		$cc  = array (
			'by' => __('Attribution'),
			'by-sa' => __('Attribution-ShareAlike'),
			'by-nd' => __('Attribution-NoDerivatives'),
			'by-nc' => __('Attribution-NonCommercial'),
			'by-nc-sa' => __('Attribution-NonCommercial-ShareAlike'),
			'by-nc-nd' => __('Attribution-NonCommercial-NoDerivatives'),
		);

		?>
		<p>
			<?php
				foreach ($cc as $licence => $name ) {
					$selected = ($licence == $default ) ? ' checked="checked"' : '';
					$ccid = 'cc-' . $licence;
					printf ( '<input class="post-format" id="%s" type="radio" value="%s" name="cc"%s></input>', $ccid, $licence, $selected );
					printf ('<label class="post-format-icon" for="%s">%s</label><br />', $ccid, $name );
				}
			?>
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
			'cc' => FILTER_SANITIZE_STRING,
		);

		foreach ($san as $key => $filter) {
			$new = filter_var($_POST[$key], $san[$key]);
			$curr = get_post_meta( $post_id, $key, true );

			if ( !empty($new) )
				$r = update_post_meta( $post_id, $key, $new );
			elseif ( empty($new) && !empty($curr) )
				$r = delete_post_meta( $post_id, $key );
		}
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
	 * additional user fields
	 */
	public function add_user_meta_fields ($profile_fields) {

		$profile_fields['github'] = __('Github username', $this->theme_constant);
		$profile_fields['mobile'] = __('Mobile phone number', $this->theme_constant);
		$profile_fields['linkedin'] = __('LinkedIn username', $this->theme_constant);
		$profile_fields['flickr'] = __('Flickr username', $this->theme_constant);
		$profile_fields['tubmlr'] = __('Tumblr blog URL', $this->theme_constant);
		$profile_fields['500px'] = __('500px username', $this->theme_constant);


		return $profile_fields;
	}

	/**
	 *
	 */
	public function shortlink () {
		if (is_singular())
			printf ('<link rel="shortlink" href="%s" />%s', $this->shorturl() , "\n");
	}

	/**
	 * replace original shortlink
	 */
	public function shorturl ( $shortlink = '', $id = '', $context = '', $allow_slugs = '' ) {
		global $post;

		if (empty($post) || !isset($post->ID) || empty($post->ID)) {
			return $shortlink;
		}

		if ( static::shorturl_enabled ) {
			$r = static::shortdomain . $post->ID;
		}
		else {
			$url = rtrim( get_bloginfo('url'), '/' ) . '/';
			$r = $url.'?p='.$post->ID;
		}

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
	 */
	function remove_x_pingback($headers) {
		unset($headers['X-Pingback']);
		return $headers;
	}

	/**
	 * replace HTML img insert with Markdown Extra syntax
	 */
	public static function rebuild_media_string( $str ) {
		if ( !strstr ( $str, '<img' ) )
			return $str;


		$src = preg_value ( $str, '/src="([^"]+)"/' );
		$title = preg_value ( $str, '/title="([^"]+)"/' );
		$alt = preg_value ( $str, '/alt="([^"]+)"/' );
		if ( empty ( $alt ) && !empty ( $title ) ) $alt = $title;
		$wpid = preg_value ( $str, '/wp-image-(\d*)/' );
		$src = preg_value ( $str, '/src="([^"]+)"/' );
		$cl = preg_value ( $str, '/class="([^"]+)?(align(left|right|center))([^"]+)?"/', 2 );

		$img = '!['.$alt.']('. $src .' '. $title .'){#img-'. $wpid .' .'.$cl.'}';
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
		$og = array();

		$locale = get_post_meta( $post->ID, 'locale' );
		if (!$locale) $locale = get_bloginfo( 'language' );
		$og['og:locale'] = $locale;

		$og['twitter:card'] = 'summary_large_image';

		$title = get_the_title();
		$og['og:title'] = $title;
		$og['twitter:title'] = $title;

		$type = ( is_singular()) ? 'article' : 'website';
		$og['og:type'] = $type;

		$url = ( is_home() ) ? get_bloginfo('siteurl') : get_permalink();
		$og['og:url'] = $url;

		$og['og:site_name'] = get_bloginfo('name');

		if (is_singular()) {
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
		}

		ksort($og);

		foreach ($og as $property => $content )
			printf( '<meta property="%s" content="%s" />%s', $property, $content, "\n" );
	}

	/**
	 * pagination
	 */
	public static function paginate() {
		global $wp_query;
		$wp_query->query_vars['paged'] > 1 ? $current = $wp_query->query_vars['paged'] : $current = 1;

		$pargs = array(
			'format'     => 'page/%#%',
			'current'    => $current,
			'end_size'   => 1,
			'mid_size'   => 2,
			'prev_next'  => True,
			'prev_text'  => __('«'),
			'next_text'  => __('»'),
			'type'       => 'list',
			'total'      => $wp_query->max_num_pages,
		);
		echo paginate_links( $pargs );
	}

	/**
	 * new utils - no formatting, no html, just data
	 */

	public static function author_social ( $author_id = 1 ) {
		$list = [];

		$socials = array (
			'github'   => 'https://github.com/%s',
			'linkedin' => 'https://www.linkedin.com/in/%s',
			'twitter'  => 'https://twitter.com/%s',
			'flickr'   => 'https://www.flickr.com/people/%s',
			'500px'    => 'https://500px.com/%s',
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
			switch ($silo) {
				case 'twitter':
					$rurl = sprintf ('https://twitter.com/intent/tweet?in_reply_to=%s',  $syndicate[5]);
					break;
				default:
					$rurl = $syndicate[0];
					break;
			}
			$reply[ $silo ] = $rurl;
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
		if (empty($syndicates))
			return $share;

		$url = urlencode( get_permalink() );
		$title = urlencode( get_the_title() );
		$desciption = urlencode( get_the_excerpt() );

		$media = ( $thid = get_post_thumbnail_id( $post->ID )) ? wp_get_attachment_image_src($thid,'large', true) : false;
		$media_url = ( ! $media ) ? false : urlencode($media[0]);

		foreach ($syndicates as $silo => $syndicate ) {
			switch ($silo) {
				case 'twitter':
					$rurl = sprintf ( 'https://twitter.com/intent/retweet?tweet_id=%s', $syndicate[5]);
					break;
				case 'facebook':
					$rurl = sprintf ( 'https://www.facebook.com/share.php?u=%s', urlencode($syndicate[0]) );
					break;
				default:
					$rurl = false;
					break;
			}

			if ($rurl)
				$share[$silo] = $rurl;
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

	/**
	 *
	 */
	public static function post_remote_relation ( $content ) {
		global $post;
		$r = array();

		$to_check = array (
			'u-in-reply-to' => __("This is a reply to"),
			'u-repost-of' => __("This is a repost of"),
		);

		foreach ($to_check as $relation => $title ) {
			$rel = get_post_meta( $post->ID, $relation, true );
			if ( $rel ) {
				if ( strstr($rel, "\n" ))
					$rel = explode ("\n", $rel);
				else
					$rel = explode (" ", $rel);

				foreach ( $rel as $url ) {
					$url = trim($url);
					$l = sprintf ( "%s: [%s](%s){%s}\n", $title, $url, $url, $relation );
					$r[] = $l;
				}
			}
		}

		if (!empty($r))
			$content = join("\n",$r) . $content;

		return $content;
	}


}

if ( !isset( $petermolnareu_theme ) || empty ( $petermolnareu_theme ) ) {
	$petermolnareu_theme = new petermolnareu();
}


