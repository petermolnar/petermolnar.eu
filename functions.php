<?php

$dirname = dirname(__FILE__);

require_once ($dirname . '/lib/parsedown/Parsedown.php');
require_once ($dirname . '/lib/parsedown-extra/ParsedownExtra.php');

require_once ($dirname . '/classes/adaptgal-ng.php');
require_once ($dirname . '/classes/article-utils.php');
require_once ($dirname . '/classes/utils.php');
//include_once ($dirname . '/classes/format-utils.php');
require_once ($dirname . '/classes/markdown-utils.php');

if ( !function_exists ( 'preg_value' ) ) {
	function preg_value ( $string, $pattern, $index = 1 ) {
		preg_match( $pattern, $string, $results );
		if ( isset ( $results[ $index ] ) && !empty ( $results [ $index ] ) )
			return $results [ $index ];
		else
			return false;
	}
}

class petermolnareu {
	public $theme_constant = 'petermolnareu';
	const menu_header = 'header';

	const shortdomain = 'http://pmlnr.eu/';
	const shorturl_enabled = true;

	public $base_url = '';
	public $js_url = '';
	public $css_url = '';
	public $theme_url = '';
	public $image_sizes = array();
	public $adaptive_images = null;
	public $formatter = null;
	public $parsedown = null;

	private $utils = null;

	private $relative_urls = false;

	public function __construct () {

		$this->adaptive_images = new adaptive_images( $this );
		//$this->utils = new pmlnr_utils();

		// init all the things!
		add_action( 'init', array( &$this, 'init'));
		add_action( 'init', array( &$this->adaptive_images, 'init'));
		add_action( 'init', array( &$this, 'rewrites'));

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
		add_action( 'add_meta_boxes', array(&$this, 'post_meta_boxes' ));
		// add meta box handlers
		add_action( 'save_post', array(&$this, 'save_post_meta' ) );

		// Remove Jetpack 3.2's Implode frontend CSS
		//add_action('wp_footer', array(&$this,'deregister_css_js'));

		/* */
		//add_action( 'init', array(&$this, 'add_custom_taxonomies'), 0 );
		//add_action( 'widgets_init', array( &$this, 'widgets_init' ) );

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

		// enable custom uploads
		add_filter('upload_mimes', array( &$this, 'custom_upload_mimes' ) );

		// add syntax highlighting
		//add_shortcode('code', array ( &$this, 'syntax_highlight' ) );
		//add_shortcode('cc', array ( &$this, 'syntax_highlight' ) );


		// auto-insert featured image
		add_filter( 'the_content', 'pmlnr_article::featured_image', 1 );



		//add_filter( 'the_content', array( $this->utils, 'facebookify'), 1 );
		add_filter( 'the_content', array( $this->utils, 'tweetify'), 1 );


		/* additional user meta */
		add_filter('user_contactmethods', array( &$this, 'add_user_meta_fields'));

		/* better title */
		add_filter( 'wp_title', array(&$this, 'nice_title') );

		/* shortlink replacement */
		add_filter( 'get_shortlink', array(&$this, 'get_shortlink'), 1, 4 );

		/* WordPress SEO cleanup */
		add_filter('wpseo_author_link', array(&$this, 'author_url'));

		/* replace img inserts with Markdown */
		$this->parsedown = new pmlnr_md();
		add_filter( 'image_send_to_editor', array( $this->parsedown, 'rebuild_media_string'), 10 );

		if ( $_SERVER['SERVER_ADDR'] != $_SERVER['REMOTE_ADDR'] && $_SERVER['REMOTE_ADDR'] != '127.0.0.1' ) {
			add_filter( 'the_content', array( $this->parsedown, 'parsedown'), 8 );
		}
		else {
			add_filter( 'the_content', 'html_entity_decode', 9 );
		}

		remove_filter( 'the_content', 'wpautop' );
		remove_filter( 'the_excerpt', 'wpautop' );

		add_filter( 'content_save_pre' , array(&$this, 'sanitize_content') , 10, 1);

		/*
		 * Remove Jetpack 3.2's Implode frontend CSS
		 */
		add_filter( 'jetpack_implode_frontend_css', '__return_false' );
		//add_filter( 'jetpack_implode_frontend_css', '__return_false' );
		//add_filter( 'wp', array(&$this, 'remove_jetpack_rp'), 20 );
		//add_filter( 'jetpack_relatedposts_filter_headline', array(&$this, 'jetpack_related_posts_headline') );

		add_filter('wp_headers', array(&$this, 'remove_x_pingback'));

		//add_filter('the_excerpt_rss', array(&$this, 'rss_thumbnail'));
		//add_filter('the_content_feed', array(&$this, 'rss_thumbnail'));
		add_filter('image_make_intermediate_size','adaptive_images::sharpen',10);

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

		//wp_register_script('indieweb-press-this', $this->js_url . 'press_this.js', false, null, true);
		//wp_enqueue_script( 'indieweb-press-this' );

		/* this is to have reply fields correctly *
		if ( is_singular() && comments_open() && get_option('thread_comments') )
			wp_enqueue_script( 'comment-reply' );
		*/
	}

	/**
	 * deregister & queue css & js
	 *
	public function deregister_css_js () {
		wp_deregister_style( 'jetpack-subscriptions' );
		wp_deregister_style( 'jetpack_css' );
	}*

	public function widgets_init () {
		register_sidebar( array(
			'name' => __( 'Subscribe', $this->theme_constant ),
			'id' => 'subscribe',
			'before_widget' => '',
			'after_widget'  => '',
			'before_title'  => '',
			'after_title'   => '',
		) );
	}*/

	/**
	 * redirect old stuff to prevent broken links
	 */
	public function rewrites () {
		add_rewrite_rule("indieweb-decentralize-web-centralizing", "indieweb-decentralize-web-centralizing-ourselves", "bottom" );
		add_rewrite_rule("/journal/living-without-google-on-android-phone/", "/linux-tech-coding/journal/living-without-google-on-android-phone/", "bottom" );
		add_rewrite_rule("/photoblog(.*)", '/photo$matches[1]', "bottom" );
		add_rewrite_rule("/blog(.*)", '/journal$matches[1]', "bottom" );
		add_rewrite_rule("/wordpress(.*)", '/open-source$matches[1]', "bottom" );
		add_rewrite_rule("/b(.*)", '/blips$matches[1]', "bottom" );
		add_rewrite_rule("/open-source/wp-ffpc(.*)", 'https://github.com/petermolnar/wp-ffpc', "bottom" );
		add_rewrite_rule("/open-source/wordpress/wp-ffpc(.*)", 'https://github.com/petermolnar/wp-ffpc', "bottom" );
		add_rewrite_rule("/open-source/wordpress/(.*)", '/open-source/$matches[1]', "bottom" );
	}

	/**
	 * replace original shortlink
	 */
	public function shorturl () {
		global $post;

		if ( self::shorturl_enabled ) {
			return self::shortdomain . $post->ID;
		}
		else {
			$url = rtrim( get_bloginfo('url'), '/' ) . '/';
			return $url.'?p='.$post->ID;
		}
	}

	public function get_shortlink ( $shortlink, $id, $context, $allow_slugs ) {
		return $this->shorturl();
	}

	public function shortlink () {
		printf ('<link rel="shortlink" href="%s" />%s', $this->shorturl() , "\n");
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
	 * WordPress SEO adds Google Plus url instead of regular author url, replace it
	 */
	public function author_url ( $url ) {
			global $post;
			$aid =  get_the_author_meta( 'ID' );
			return get_the_author_meta ( 'user_url' , $aid );
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
	public function nice_title ( $title ) {
		return trim( str_replace ( array ('&raquo;', '»' ), array ('',''), $title ) );
	}

	private function css_version ( $file ) {
		$version = 0;
		$handle = fopen($file, "r");
		if ($handle) {
			while (($line = fgets($handle)) !== false && empty($version) ) {
				if ( strstr($line,'Version') ) {
					$v = explode("\t",$line);
					if ( !empty($v[2]) )
						$version = $v[2];
						break;
				}
			}
		}
		fclose($handle);

		return $version;
	}

	/**
	 *
	 */
	public static function parsedown ( $md ) {
		$parsedown = new ParsedownExtra();
		$md = $parsedown->text ( $md );
		$md = str_replace ( '&lt; ?php', '&lt;?php', $md );
		return $md;
	}

	/**
	 * Add custom taxonomies
	 *
	 * Additional custom taxonomies can be defined here
	 * http://codex.wordpress.org/Function_Reference/register_taxonomy
	 *
	function add_custom_taxonomies() {
		/*
		// Add new "Locations" taxonomy to Posts
		register_taxonomy('series', 'post', array(
			// Hierarchical taxonomy (like categories)
			'hierarchical' => false,
			// This array of options controls the labels displayed in the WordPress Admin UI
			'labels' => array(
				'name' => _x( 'Series', 'taxonomy general name' ),
				'singular_name' => _x( 'Series', 'taxonomy singular name' ),
				'search_items' =>	__( 'Search Series' ),
				'all_items' => __( 'All Series' ),
				//'parent_item' => __( 'Parent Location' ),
				//'parent_item_colon' => __( 'Parent Location:' ),
				'edit_item' => __( 'Edit Serie' ),
				'update_item' => __( 'Update Serie' ),
				'add_new_item' => __( 'Add New Serie' ),
				'new_item_name' => __( 'New Serie Name' ),
				'menu_name' => __( 'Series' ),
			),
			// Control the slugs used for this taxonomy
			'rewrite' => array(
				'slug' => 'series', // This controls the base slug that will display before each term
				'with_front' => false, // Don't display the category base before "/locations/"
				'hierarchical' => false // This will allow URL's like "/locations/boston/cambridge/"
			),
		));
	}
	*/

	function sanitize_content( $content ) {
		$search = array( '”', '“', '’', '–' );
		$replace = array ( '"', '"', "'", '-' );

		$content = str_replace( $search, $replace, $content );
		return $content;
	}

	function remove_x_pingback($headers) {
		unset($headers['X-Pingback']);
		return $headers;
	}
	/*
	function remove_jetpack_rp() {
		$jprp = Jetpack_RelatedPosts::init();
		$callback = array( $jprp, 'filter_add_target_to_dom' );
		remove_filter( 'the_content', $callback, 40 );
	}

	function jetpack_related_posts_headline ( $headline ) {
		$headline = sprintf( '<h5%s</5>', __('Related posts'));
		return $headline;
	}*/

	function rss_thumbnail($content) {
		global $post;
		if ( has_post_thumbnail( $post->ID ) ){
			$content = '' . get_the_post_thumbnail( $post->ID, 'thumbnail' ) . '' . $content;
		}
		return $content;
	}


	public function post_meta_boxes () {
		/*
		add_meta_box(
			'syndicated_urls',
			esc_html__( 'Syndication Links', 'petermolnareu' ),
			array(&$this, 'post_meta_syndication'),
			'post',
			'normal',
			'default'
		);
		*/

		add_meta_box(
			'cc_licence',
			esc_html__( 'Creative Commons', 'petermolnareu' ),
			array(&$this, 'post_meta_cc'),
			'post',
			'normal',
			'default'
		);
		/*
		add_meta_box(
			'500px_photo_id',      // Unique ID
			esc_html__( '500px photo ID', 'petermolnareu' ),    // Title
				array (&$this, 'meta_500px_photo_id'),   // Callback function
				'post',         // Admin page (or post type)
				'side',         // Context
				'default'         // Priority
		);
		*/
	}

	public function post_meta_syndication ( $object, $box ) {
		 wp_nonce_field( basename( __FILE__ ), 'post_meta_syndication_nonce' );

		$meta = get_post_meta( $object->ID, 'syndication_urls', true );
		?>
		<p>
			<label for="syndication_urls"><?php _e('One URL per line.', 'petermolnareu'); ?></label>
			<textarea name="syndication_urls" id="syndication_urls" style="width:100%; min-height:8em"><?php if (!empty($meta)) echo $meta; ?></textarea>
		</p>
		<?php
	}

	public function post_meta_cc ( $object, $box ) {
		 wp_nonce_field( basename( __FILE__ ), 'post_meta_cc_nonce' );

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


					//printf ('<option value="%s"%s>%s</option>', $cc, $selected, $name );
				}
			?>
		</p>
		<?php
	}


	private function handle_meta ( $post_id, $key, $new_value ) {

		$curr_value = get_post_meta( $post_id, $key, true );

		if ( !empty($new_value) ) {
			$r = update_post_meta( $post_id, $key, $new_value );

		}
		elseif ( empty($new_value) && !empty($curr_value) ) {
			$r = delete_post_meta( $post_id, $key );
		}

		return $r;
	}


	private function clean_syndicated_urls ( ) {

		if ( !isset($_POST['syndication_urls']) || empty($_POST['syndication_urls']))
			return false;

		$urls = explode("\n", $_POST[ 'syndication_urls' ]);
		return join("\n", array_filter( array_unique( array_map( 'pmlnr_utils::clean_url', $urls ) ) ) );

	}

	public function save_post_meta ( $post_id ) {
		/* Verify the nonce before proceeding. */
		$nonce_to_check = array (
			//'post_meta_syndication_nonce',
			'post_meta_cc_nonce'
		);

		foreach ($nonce_to_check as $nonce ) {
			if ( !isset( $_POST[$nonce] ) || !wp_verify_nonce( $_POST[$none], basename( __FILE__ ) ) )
			return $post_id;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return $post_id;

		if ( ! current_user_can( 'edit_page', $post_id ) )
			return $post_id;

		$urls = $this->clean_syndicated_urls();
		//$this->handle_meta( $post_id, 'syndication_urls', $urls);
		$this->handle_meta( $post_id, 'cc', $urls);
	}


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
				$img = pmlnr_utils::imagewithmeta( $thid );
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

}

/**** END OF FUNCTIONS *****/

if ( !isset( $petermolnareu_theme ) || empty ( $petermolnareu_theme ) ) {
	$petermolnareu_theme = new petermolnareu();
}
