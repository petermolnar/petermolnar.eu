<?php

define('ARTICLE_MIN_LENGTH', 1100);

$dirname = dirname(__FILE__);

require_once ($dirname . '/lib/parsedown/Parsedown.php');
require_once ($dirname . '/lib/parsedown-extra/ParsedownExtra.php');
require_once ($dirname . '/lib/lessphp/lessc.inc.php');
require_once ($dirname . '/lib/simple_html_dom/simple_html_dom.php');
require_once ($dirname . '/lib/Twig/lib/Twig/Autoloader.php');
Twig_Autoloader::register();

require_once ($dirname . '/classes/base.php');
require_once ($dirname . '/classes/image.php');
require_once ($dirname . '/classes/cleanup.php');
require_once ($dirname . '/classes/markdown.php');
require_once ($dirname . '/classes/post.php');
require_once ($dirname . '/classes/author.php');
require_once ($dirname . '/classes/site.php');

class petermolnareu {
	const menu_header = 'header';
	private $endpoints = array ('yaml');
	public $twig = null;
	public $twigloader = null;
	private $twigcache = WP_CONTENT_DIR . '/cache/twig';
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

		if (!is_dir($this->twigcache))
			mkdir($this->twigcache);

		$this->twigloader = new Twig_Loader_Filesystem( dirname(__FILE__) . '/twig');
		$this->twig = new Twig_Environment($this->twigloader, array(
			'cache' => $this->twigcache,
			'auto_reload' => true,
			'autoescape' => false,
		));

		new pmlnr_image();
		new pmlnr_cleanup();
		new pmlnr_markdown();
		new pmlnr_post();
		new pmlnr_author();
		new pmlnr_site();
		//new pmlnr_formats();

		add_image_size ( 'headerbg', 720, 0, false );

		// init all the things!
		add_action( 'init', array( &$this, 'init'));

		// add css & js
		add_action( 'wp_enqueue_scripts', array(&$this,'register_css_js'),10);

		// Add meta boxes on the 'add_meta_boxes' hook.
		add_action( 'add_meta_boxes', array(&$this, 'post_meta_add' ));
		add_action( 'save_post', array(&$this, 'post_meta_save' ) );

		add_action('restrict_manage_posts', array(&$this, 'type_dropdown'));

	}

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

		// this is because custom taxonomy
		add_filter('parse_query', array(&$this, 'convert_id_to_term_in_query'));

		// I want to upload svg
		add_filter('upload_mimes', array(&$this, 'cc_mime_types'));

		// my own post formats
		register_taxonomy( 'kind', 'post', array (
			'label' => 'Type',
			'public' => true,
			'show_ui' => true,
			'hierarchical' => true,
			'show_admin_column' => true,
			'rewrite' => array( 'slug' => 'type' ),
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

		// Magnific popup
		wp_register_style( 'magnific-popup', $base_url . '/lib/Magnific-Popup/dist/magnific-popup.css' , false );
		wp_register_script( 'magnific-popup', $base_url . '/lib/Magnific-Popup/dist/jquery.magnific-popup.min.js' , array('jquery'), null, false );


		// justified gallery
		wp_register_style( 'Justified-Gallery', $base_url . '/lib/Justified-Gallery/dist/css/justifiedGallery.min.css' , false );

		wp_register_script( 'Justified-Gallery', $base_url . '/lib/Justified-Gallery/dist/js/jquery.justifiedGallery.min.js' , array('jquery'), null, false );

		// syntax highlight
		wp_register_style( 'prism', $css_url . '/prism.css', false, null );
		wp_enqueue_style( 'prism' );
		wp_register_script( 'prism' , $js_url . '/prism.js', false, null, true );
		wp_enqueue_script( 'prism' );

		// srcset fallback
		wp_register_script( 'picturefill' , $base_url . '/lib/picturefill/dist/picturefill.min.js', false, null, true );
		wp_enqueue_script( 'picturefill' );

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
	 *
	 */
	public static function export_yaml ( $postid = false ) {

		if (!$postid)
			return false;

		$post = get_post($postid);

		if (!pmlnr_base::is_post($post))
			return false;

		$filename = $post->post_name;

		$flatroot = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'flat';
		$flatdir = $flatroot . DIRECTORY_SEPARATOR . $filename;
		$flatfile = $flatdir . DIRECTORY_SEPARATOR . 'item.md';

		$post_timestamp = get_the_modified_time( 'U', $post->ID );
		$file_timestamp = 0;

		if ( @file_exists($flatfile) ) {
			$file_timestamp = @filemtime ( $flatfile );
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

		touch($flatdir, $post_timestamp);

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

		$comments = get_comments ( array( 'post_id' => $post->ID ) );
		//$socials = array ('facebook', 'flickr', 'fivehpx');
		if ( $comments ) {
			foreach ($comments as $comment) {
				$cf_timestamp = 0;

				$cfile = $flatdir . DIRECTORY_SEPARATOR . 'comment_' . $comment->comment_ID . '.yml';

				$c_timestamp = strtotime( $comment->comment_date );
				if ( @file_exists($cfile) ) {
					$cf_timestamp = @filemtime ( $cfile );
					if ( $c_timestamp == $cf_timestamp ) {
						continue;
					}
				}

				$c = array (
					'id' =>  (int)$comment->comment_ID,
					'author' => $comment->comment_author,
					'author_email' => $comment->comment_author_email,
					'author_url' => $comment->comment_author_url,
					'date' => $comment->comment_date,
					//'content' => $comment->comment_content,
					'useragent' => $comment->comment_agent,
					'type' => $comment->comment_type,
					'user_id' => (int)$comment->user_id,
				);

				if ( $avatar = get_comment_meta ($comment->comment_ID, "avatar", true))
					$c['avatar'] = $avatar;

				$social = pmlnr_base::preg_value($comment->comment_agent,'/Keyring_(.*?)_Reactions/' );

				if ($social) {
					$social = strtolower($social);
					if ( $smeta = get_comment_meta ($comment->comment_ID, "keyring-${social}_reactions", true))
						$c['keyring_reactions_importer'] = json_encode($smeta);
				}

				$cout = yaml_emit($c, YAML_UTF8_ENCODING );
				$cout .= "---\n" . pmlnr_markdown::html2markdown($comment->comment_content);

				pmlnr_base::debug ('Exporting comment #' . $comment->comment_ID. ' to ' . $cfile );
				file_put_contents ($cfile, $cout);
				touch ( $cfile, $c_timestamp );
			}
		}

		if ( $file_timestamp == $post_timestamp ) {
			return true;
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
	 *
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

		if (empty($postdata))
			return false;

		$excerpt = false;
		if (isset($postdata['excerpt']) && !empty($postdata['excerpt'])) {
			$excerpt = $postdata['excerpt'];
			unset($postdata['excerpt']);
		}

		$content = $postdata['content'];

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

		$search = array ( '”', '“', '’', '–', "\x0D" );
		$replace = array ( '"', '"', "'", '-', '' );
		$excerpt = str_replace ( $search, $replace, $excerpt );
		$excerpt = strip_tags ( $parsedown->text ( $excerpt ) );
		$content = str_replace ( $search, $replace, $content );


		// fix all image attachments: resized -> original
		$urlparts = parse_url(site_url());
		$domain = $urlparts ['host'];
		$wp_upload_dir = wp_upload_dir();
		$uploadurl = str_replace( '/', "\\/", trim( str_replace( site_url(), '', $wp_upload_dir['url']), '/'));

		$pregstr = "/((https?:\/\/". $domain .")?\/". $uploadurl ."\/.*\/[0-9]{4}\/[0-9]{2}\/)(.*)-([0-9]{1,4})×([0-9]{1,4})\.([a-zA-Z]{2,4})/";

		preg_match_all( $pregstr, $content, $resized_images );

		if ( !empty ( $resized_images[0]  )) {
			foreach ( $resized_images[0] as $cntr => $imgstr ) {
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

	/**
	 * Get the source's images and save them locally, for posterity, unless we can't.
	 *
	 */
	public function side_load_md_images( $post_id, $content = '' ) {
		$content = wp_unslash( $content );

		// match all markdown images
		$matches = pmlnr_base::extract_md_images($content);

		if ( !empty($matches) && current_user_can( 'upload_files' ) ) {

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
	public static function template_vars ( $post = null ) {
			$post = pmlnr_base::fix_post($post);
			return array(
				'site' => pmlnr_site::template_vars(),
				'post' => pmlnr_post::template_vars( $post ),
			);
	}
}

if ( !isset( $petermolnareu_theme ) || empty ( $petermolnareu_theme ) ) {
	$petermolnareu_theme = new petermolnareu();
}


