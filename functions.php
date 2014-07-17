<?php

include_once ('classes/adaptive-images.php');
include_once ('lib/parsedown/Parsedown.php');
include_once ('lib/parsedown-extra/ParsedownExtra.php');

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
	const twitteruser = 'petermolnar';
	const fbuser = 'petermolnar.eu';
	const shortdomain = 'http://pmlnr.eu/';
	const shorturl_enabled = true;
	const cache_group = 'theme_meta';
	const cache_time = 86400;
	const cache = 0;

	const markdown = true;

	private $markdown_post_types = array();

	public $base_url = '';
	public $js_url = '';
	public $css_url = '';
	public $font_url = '';
	public $image_url = '';
	public $theme_url = '';
	public $image_sizes = array();
	public $adaptive_images = null;
	private $parsedown = null;

	private $relative_urls = false;

	public function __construct () {
		$this->base_url = $this->replace_if_ssl( get_bloginfo("url") );
		$this->theme_url = $this->replace_if_ssl( get_bloginfo("stylesheet_directory") );
		$this->js_url = $this->theme_url . '/assets/js/';
		$this->css_url = $this->theme_url . '/assets/css/';
		$this->font_url = $this->theme_url . '/assets/font/';
		$this->image_url = $this->theme_url . '/assets/image/';

		$this->adaptive_images = new adaptive_images( $this );

		$this->parsedown = new ParsedownExtra();

		$this->markdown_post_types = array ( 'post', 'page' );
		//$this->urlfilters = array(
			//'post_link', // Normal post link
			//'post_type_link', // Custom post type link
			//'page_link', // Page link
			//'attachment_link', // Attachment link

			//'post_type_archive_link', // Post type archive link
			//'get_pagenum_link', // Paginated link
			//'get_comments_pagenum_link', // Paginated comment link
			//'term_link', // Term link, including category, tag
			//'search_link', // Search link
			//'day_link', // Date archive link
			//'month_link',
			//'year_link',
			//'get_comment_link',
			//'wp_get_attachment_image_src',
			//'wp_get_attachment_thumb_url',
			//'wp_get_attachment_url',
		//);

		add_action( 'init', array( &$this, 'init'));
		add_action( 'init', array( &$this->adaptive_images, 'init'));
		add_action( 'init', array( &$this, 'rewrites'));

		add_action( 'wp_enqueue_scripts', array(&$this,'register_css_js'));
		//add_action( 'admin_enqueue_scripts', array(&$this,'admin_register_css_js') );

		/* replace shortlink */
		remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );
		add_action( 'wp_head', array(&$this, 'shortlink'));

		/* cleanup */
		remove_action('wp_head', 'rsd_link'); // Display the link to the Really Simple Discovery service endpoint, EditURI link
		remove_action('wp_head', 'wlwmanifest_link'); // Display the link to the Windows Live Writer manifest file.
		remove_action('wp_head', 'index_rel_link'); // Index link
		remove_action('wp_head', 'parent_post_rel_link', 10, 0); // Prev link
		remove_action('wp_head', 'start_post_rel_link', 10, 0); // Start link
		remove_action('wp_head', 'adjacent_posts_rel_link', 10, 0); // Display relational links for the posts adjacent to the current post.
		remove_action('wp_head', 'wp_generator'); // Display the XHTML generator that is generated on the wp_head hook, WP version
		remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
		remove_action('wp_head', 'rel_canonical');
	}

	public function init () {
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'menus' );
		add_theme_support( 'automatic-feed-links' );

		/* http://codex.wordpress.org/Post_Formats */
		add_theme_support( 'post-formats', array(
			'image', 'aside', 'video', 'audio', 'quote', 'link', 'gallery', 'status'
		) );

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support( 'html5', array(
			'search-form', 'comment-form', 'comment-list', 'gallery', 'caption'
		) );

		/* add main menus */
		register_nav_menus( array(
			self::menu_header => __( self::menu_header , $this->theme_constant ),
		) );

		/* enable custom uploads */
		add_filter('upload_mimes', array( &$this, 'custom_upload_mimes' ) );

		/* add syntax highlighting */
		add_shortcode('code', array ( &$this, 'syntax_highlight' ) );
		add_shortcode('cc', array ( &$this, 'syntax_highlight' ) );

		/* overwrite gallery shortcode */
		remove_shortcode('gallery');
		add_shortcode('gallery', array (&$this->adaptive_images, 'adaptgal' ) );

		/* legacy shortcode handler *
		add_filter( 'the_content', array( &$this, 'legacy' ), 1);*/

		/* display markdown */
		add_filter( 'the_content', array(&$this, 'markdown_on_the_fly'), 1 );

		/* post type additional data */
		add_filter( 'the_content', array(&$this, 'add_post_format_data'), 1 );

		/* relative urls *
		if ( $this->relative_urls ) {
			add_filter( 'the_content', array( &$this, 'replace_if_ssl'), 100);
			if ( ! is_feed()  && ! get_query_var( 'sitemap' ) )
				foreach ( $this->urlfilters as $filter )
					add_filter( $filter, 'wp_make_link_relative' );
		}*/

		//remove_filter( 'the_content', 'wpautop' );
		//add_filter( 'the_content', 'wpautop' , 12);
		//add_filter( 'the_content', 'shortcode_unautop' , 12);

		/* have links in the admin *
		add_filter( 'pre_option_link_manager_enabled', '__return_true' );*/

		/* additional user meta */
		add_filter('user_contactmethods', array( &$this, 'add_user_meta_fields'));

		/* better title */
		add_filter( 'wp_title', array(&$this, 'nice_title') );

		/* shortlink replacement */
		add_filter( 'get_shortlink', array(&$this, 'get_shortlink'), 1, 4 );

		/* WordPress SEO cleanup */
		add_filter('wpseo_author_link', array(&$this, 'author_url'));

		//add_filter( 'post_thumbnail_html', array( &$this, 'image_to_shortcode'), 10 );
		//add_filter( 'image_send_to_editor', array( &$this, 'image_to_shortcode'), 10 );

		//add_filter( 'post_thumbnail_html', array( &$this, 'rebuild_media_string'), 10 );
		add_filter( 'image_send_to_editor', array( &$this, 'rebuild_media_string'), 10 );

	}

	public function markdown_on_the_fly ( $html ) {
		return $this->parsedown->text ( $html );
	}

	/**
	 * register & queue css & js
	 */
	public function register_css_js () {

		/* enqueue CSS */
		wp_register_style( 'style', $this->theme_url . '/style.css' , false, $this->css_version ( dirname(__FILE__) . '/style.css' ) );

		/* syntax highlight */
		wp_register_style( 'prism', $this->css_url . 'prism.css', false, null );
		wp_register_script( 'prism' , $this->js_url . 'prism.js', false, null, true );

		/* CDN scripts */
		wp_deregister_script( 'jquery' );
		wp_register_script( 'jquery', $this->replace_if_ssl( 'http://code.jquery.com/jquery-1.11.0.min.js' ), false, null, false );
		wp_enqueue_script( 'jquery' );

		/* for adaptive image class, TODO move here */
		wp_register_script( 'jquery.adaptive-images', $this->js_url . 'adaptive-images.js', array('jquery'), null, true );


		wp_enqueue_style( 'style' );
		wp_enqueue_style( 'prism' );
		wp_enqueue_script( 'prism' );

		/* this is to have reply fields correctly */
		if ( is_singular() && comments_open() && get_option('thread_comments') )
			wp_enqueue_script( 'comment-reply' );

	}

	/**
	 * register & queue css & js
	 *
	public function admin_register_css_js () {
		wp_register_script( 'js-markdown-extra' , $this->theme_url . '/lib/js-markdown-extra/js-markdown-extra.js' );
		wp_enqueue_script( 'js-markdown-extra' );
	}
	*/

	/**
	 * redirect old stuff to prevent broken links
	 */
	public function rewrites () {
		add_rewrite_rule("indieweb-decentralize-web-centralizing", "indieweb-decentralize-web-centralizing-ourselves", "bottom" );
		add_rewrite_rule("/journal/living-without-google-on-android-phone/", "/linux-tech-coding/journal/living-without-google-on-android-phone/", "bottom" );
		add_rewrite_rule("/wordpress(.*)", '/open-source$matches[1]', "bottom" );
		add_rewrite_rule("/b(.*)", '/blips$matches[1]', "bottom" );
		add_rewrite_rule("/open-source/wordpress/(.*)", '/open-source/$matches[1]', "bottom" );
		add_rewrite_rule("/blog(.*)", '/journal$matches[1]', "bottom" );
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
		echo '<link rel="shortlink" href="'. $this->shorturl() . '" />'."\n";
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
	 * replaces http:// with https:// in an url if server is currently running on https
	 *
	 * @param string $url URL to check
	 *
	 * @return string URL with correct protocol
	 *
	 */
	public function replace_if_ssl ( $url ) {
		if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' )
			$_SERVER['HTTPS'] = 'on';

		if ( isset($_SERVER['HTTPS']) && (( strtolower($_SERVER['HTTPS']) == 'on' )  || ( $_SERVER['HTTPS'] == '1' ) ))
			$url = str_replace ( 'http://' , 'https://' , $url );

		return $url;
	}

	/**
	 * updated share function: retweet/reshare/reshit if SNAP entry or something else is
	 * available
	 */
	public function share_ ( $link , $title, $comment=false, $parent=false ) {
		global $post;
		global $nxs_snapAvNts;

		$link = urlencode($link);
		$title = urlencode($title);
		$desciption = urlencode(get_the_excerpt());
		$type = get_post_type ( $post );
		$media = wp_get_attachment_image_src(get_post_thumbnail_id( $post->ID ),'large', true);
		$media_url = ( ! $media ) ? false : $media[0];

		$snap_options = get_option('NS_SNAutoPoster');

		/* all SNAP entries are in separate meta entries for the post based on the service name's "code" */
		if ( !empty ( $nxs_snapAvNts ) && is_array ( $nxs_snapAvNts ) ) {
			foreach ( $nxs_snapAvNts as $key => $serv ) {
				$mkey = 'snap'. $serv['code'];
				$urlkey = $serv['lcode'].'URL';
				$okey = $serv['lcode'];
				$s = strtolower($serv['name']);
				$metas = maybe_unserialize(get_post_meta($post->ID, $mkey, true ));
				if ( !empty( $metas ) && is_array ( $metas ) ) {
					foreach ( $metas as $cntr => $m ) {
						$pgID = false;
						if ( isset ( $m['isPosted'] ) && $m['isPosted'] == 1 ) {
							/* postURL entry will only be used if there's no urlmap set for the service above
							 * this is due to either missing postURL values or buggy entries */
							$pgIDs[ $s ] = $m['pgID'];
						}
						$surl[ $s ] = $snap_options[$okey][$cntr][$urlkey];
					}
				}
			}
		}
		/* Twitter */
		$service = 'twitter';
		$repost_id = get_post_meta($post->ID, 'twitter_rt_id', true );
		$repost_uid = get_post_meta($post->ID, 'twitter_rt_user_id', true );
		$tw = get_post_meta( $post->ID, 'twitter_tweet_id', true );
		if ( !empty( $pgIDs[ $service ] ) ) {
			$url = 'https://twitter.com/intent/retweet?tweet_id=' . $pgIDs[ $service ];
			$txt = __( 'reweet', $this->theme_constant );
		}
		elseif ( !empty($repost_id) && !empty($repost_uid) ) {
			$url = 'https://twitter.com/intent/retweet?tweet_id=' . $repost_id;
			$txt = __( 'reweet', $this->theme_constant );
		}
		elseif ( !empty($tw) ) {
			$url = 'https://twitter.com/intent/retweet?tweet_id=' . $tw;
			$txt = __( 'reweet', $this->theme_constant );
		}
		else {
			$url = 'https://twitter.com/share?url='. $link .'&text='. $title;
			$txt = __( 'tweet', $this->theme_constant );
		}
		$shlist[] = '<a class="icon-'. $service .'" href="' . $url . '">'. $txt .'</a>';

		/* Facebook */
		$service = 'facebook';
		if ( !empty( $pgIDs[ $service ] ) ) $pgIDs[$service] = explode ( '_', $pgIDs[$service] );
		if ( is_array ( $pgIDs[$service] ) && !empty($pgIDs[$service][1]) ) {
			//https://www.facebook.com/sharer.php?s=100&p[url]=http://www.example.com/&p[images][0]=/images/image.jpg&p[title]=Title&p[summary]=Summary
			//$url = 'https://www.facebook.com/sharer/sharer.php?' . urlencode ('s=99&p[0]='. $pgIDs[$service][0] .'&p[1]='. $pgIDs[$service][1] );
			// '&p[images][0]='.  $media_url . '&p[title]=' . $title . '&p[summary]=' ) . $desciption;
			$base = '%BASE%/posts/%pgID%';
			$search = array('%BASE%', '%pgID%' );
			$replace = array ( $surl[ $service ], $pgIDs[$service][1] );
			$url =  'http://www.facebook.com/share.php?u=' . str_replace ( $search, $replace, $base );
			$txt = __( 'reshare', $this->theme_constant );
		}
		else {
			$url = 'http://www.facebook.com/share.php?u=' . $link . '&t=' . $title;
			$txt = __( 'share', $this->theme_constant );
		}

		$shlist[] = '<a class="icon-'. $service .'" href="' . $url . '">'. $txt .'</a>';

		/* Google Plus */
		$service = 'googleplus';
		$url = 'https://plus.google.com/share?url=' . $link;
		$txt = __( '+1', $this->theme_constant );
		$shlist[] = '<a class="icon-'. $service .'" href="' . $url . '">'. $txt .'</a>';

		/* Tumblr */
		$service = 'tumblr';
		$url = 'http://www.tumblr.com/share/link?url='.$link.'&name='.$title.'&description='. $desciption;
		$txt = __( 'share', $this->theme_constant );
		$shlist[] = '<a class="icon-'. $service .'" href="' . $url . '">'. $txt .'</a>';

		/* Pinterest */
		if ( $media_url ) {
			$purl = ( $parent != false ) ? urlencode($parent) : $link;
			$service = 'pinterest';
			$url = 'https://pinterest.com/pin/create/bookmarklet/?media='. $media_url .'&url='. $purl .'&is_video=false&description='. $title;
			$txt = __( 'pin', $this->theme_constant );
			$shlist[] = '<a class="icon-'. $service .'" href="' . $url . '">'. $txt .'</a>';
		}

		/* comment link */
		if ($comment) {
			$service = 'comment';
			$url = get_permalink( $post->ID ) . "#comments";
			$txt = __( 'comment', $this->theme_constant );
			$shlist[] = '<a rel="discussion" class="icon-'. $service .'" href="' . $url . '">'. $txt .'</a>';
		}

		/* shorturl */
		$service = 'url';
		$txt = $url = wp_get_shortlink();
		$shlist[] = '<a class="icon-globe" href="' . $url . '">'. $txt .'</a>';

		$out = '
			<action do="post" with="'. get_the_permalink() .'" class="share">
			<h6>' . __('Share:', $this->theme_constant ) . '</h6>
			<ul><li>'. implode( '</li><li>', $shlist ) .'</li></ul>
			</action>';

		return $out;
	}


	/**
	 * Returns unordered list of current category's posts
	 *
	 *
	public function list_posts( $category, $limit=-1 , $from=0 ) {
		$req = ( $limit == -1 ) ? -1 : $from + $limit;
		$category_meta = get_metadata ( 'taxonomy' , $category->term_id, '');
		$category_meta['order-by'] = empty ( $category_meta['order-by'] ) ? 'date' : $category_meta['order-by'];

		$q = array (
			'category' => $category->cat_ID,
			'orderby' => $category_meta['order-by'],
			'order' => 'DESC' ,
			'numberposts' => $req
		);
		$posts = get_posts( $q );

		if ( $from != false )
			for ($i=0; $i<$from; $i++)
				array_shift ( $posts );

		if ( !empty ( $posts ))
		{
			$list = '';
			foreach ($posts as $post) {
				$post_title = htmlspecialchars(stripslashes($post->post_title));
				$list .= '
						<li>
							'. $this->article_time( $post ) .'
							<a href="' . get_permalink($post->ID) . '" title="'. $post_title .'" >
								' . $post_title . '
							</a>
						</li>';
				$i++;
			}

			$out = '
			<nav class="sidebar-postlist">
				<ul class="postlist">
				'. $list .'
				</ul>
			</nav>';
		}

		return $out;
	}
	*/

	/**
	 * related posts, based shared tags
	 *
	 */
	public function related_posts ( $_post, $onlysiblings = false, $limit = 12 ) {
		$args = array( 'fields' => 'ids' );
		$tags = wp_get_post_tags($_post->ID, $args );
		$categories = wp_get_post_categories ( $_post->ID, $args );

		$list = $out = '';

		if ( !empty($tags) ) {

			$args=array(
				'tag__in' => $tags,
				'post__not_in' => array($_post->ID),
				'posts_per_page'=>$limit,
				'ignore_sticky_posts'=>1,
				'orderby' => 'rand',
			);

			if ( $onlysiblings ) {
				$args['category__in'] = $categories;
			}

			$_query = new WP_Query( $args );

			while( $_query->have_posts() ) {
				$_query->the_post();

				$post_title = get_the_title();

				$list .= '
						<li>
							<a href="' . get_permalink() . '" title="'. $post_title .'" >
								' . $post_title . '
							</a>
						</li>';
				wp_reset_postdata();
			}
		}

		if ( !empty($list)) {
			$out = '
				<h2 class="postlist-title">'. __( "Some related posts" ) . '</h2>
				<nav class="sidebar-postlist">
					<ul class="postlist">
					'. $list .'
					</ul>
				</nav>';
		}

		return $out;
	}

	/**
	 * syntax highlight with prism (it's not that PRISM, don't worry)
	 * http://prismjs.com/
	 *
	 */
	public function syntax_highlight ( $atts ,  $content = null ) {
		wp_enqueue_script( 'prism' );
		wp_enqueue_style( 'prism' );

		extract( shortcode_atts(array(
			'lang' => 'none'
		), $atts));

		if ( empty( $content ) ) {
			$return = false;
		}
		else {
			/*
			$cl = array ('<br />', '<p>', '</p>' );
			$content = str_replace ( $cl, '', $content );
			$search = array( '<', '>', '&lt;br /&gt;' );
			$replace = array( '&lt;', '&gt;', '' );
			$content = str_replace ( $search, $replace, $content );
			$return = '<pre class="line-numbers"><code class="language-' . $lang . '">' . trim(str_replace( "\t", "  ", $content ) ) . '</code></pre>';
			*/
			$return = '<pre class="line-numbers"><code class="language-' . $lang . '">' . $content . '</code></pre>';
		}

		return $return;

	}

	/**
	 * replacing legacy code & formatting with newer ones
	 *
	 */
	public function legacy ( $src ) {
		/* old syntax highlight, seach for <code> tags */
		$matches = array();
		preg_match_all ( "'<code>(.*?)</code>'si", $src , $matches, PREG_SET_ORDER );

		foreach ($matches as $match ) {
			$shortcode = '[code]'.trim($match[1]).'[/code]';
			$src = str_replace ( $match[0], $shortcode, $src );
		}

		return $src;
	}

	/**
	 * display article pubdate
	 */
	public function article_time (&$post = false) {
		if ( !$post )
			global $post;

		ob_start();
		?>
		<time class="article-pubdate dt-published" datetime="<?php echo get_the_time( 'c', $post->ID ); ?>"><?php echo get_the_time( get_option('date_format'), $post->ID ); ?> <?php echo get_the_time( get_option('time_format'), $post->ID ); ?></time>
		<time class="hide dt-updated" datetime="<?php echo get_the_modified_time( 'c', $post->ID ); ?>"><?php echo get_the_time( get_option('date_format'), $post->ID ); ?> <?php echo get_the_time( get_option('time_format'), $post->ID ); ?></time>
		<?php
		$content = ob_get_clean();
		return $content;
	}

	/**
	 * function name speaks for itself
	 */
	public function replace_images_with_adaptive ( $html ) {
		return $this->adaptive_images->adaptive_embededed( $html );
	}

	/**
	 * Twitter link all @ starting string
	 */
	public function tweetify($content) {

		preg_match_all('/@([0-9a-zA-Z_]+)/', $content, $twusers);

		if ( !empty ( $twusers[0] ) && !empty ( $twusers[1] )) {
			foreach ( $twusers[1] as $cntr=>$twname ) {
				$repl = $twusers[0][$cntr];
				$content = str_replace ( $repl, '<a href="https://twitter.com/'.$twname.'" rel="nofollow">@'.$twname.'</a>', $content );
			}
		}

		preg_match_all('/#([0-9a-zA-Z_-]+)/', $content, $hashtags);
		if ( !empty ( $hashtags[0] ) && !empty ( $hashtags[1] )) {
			foreach ( $hashtags[1] as $cntr=>$tagname ) {
				$repl = $hashtags[0][$cntr];
				$content = str_replace ( $repl, '<a href="https://twitter.com/hashtag/'. $tagname.'?src=hash" rel="nofollow">#'.$tagname.'</a>', $content );
			}
		}

		$content = $this->linkify ( $content );

		return $content;
	}

	/**
	 * auto-link all plain text links, exclude anything in html tags
	 */
	public function linkify ( $content ) {
		$content = preg_replace('$(https?://[a-z0-9_./?=&#-]+)(?![^<>]*>)$i', ' <a href="$1" target="_blank">$1</a> ', $content." ");
		$content = preg_replace('$(www\.[a-z0-9_./?=&#-]+)(?![^<>]*>)$i', '<a target="_blank" href="http://$1"  target="_blank">$1</a> ', $content." ");
		return $content;
	}

	/**
	 *  author vcard
	 */
	public function author ( $short=false, $uid = false ) {
		if ( $short ) {
			global $post;
			$aid =  get_the_author_meta( 'ID' );
			$aemail = get_the_author_meta ( 'user_email' , $aid );
			$aname = get_the_author_meta ( 'display_name' , $aid );
			$gravatar = md5( strtolower( trim(  $aemail )));
			$class = 'p-author h-card vcard';
		}
		else {
			$aid = ($uid==false)? 1 : $uid;
			$aemail = get_the_author_meta ( 'user_email' , $aid );
			$aname = get_the_author_meta ( 'display_name' , $aid );
			$gravatar = md5( strtolower( trim(  $aemail )));
			$class = 'h-card vcard';
		}

		$out = '<span class="'. $class .'">
				<a class="fn p-name url u-url" href="'. get_the_author_meta ( 'user_url' , $aid ) .'">'. $aname .'</a>
				<img class="photo avatar u-photo u-avatar" src="https://s.gravatar.com/avatar/'.$gravatar.'?s=64" style="width:12px; height:12px;" alt="Photo of '. $aname .'"/>';

		if ( !$short ) {
			$out .= '<a rel="me" class="u-email email" href="mailto:'.$aemail.'" title="'.$aname.' email address">'.$aemail.'</a>';

			/* social */
			$tw = get_the_author_meta ( 'twitter' , $aid );
			if ( !empty ($tw)) {
				$socials['twitter'] = '<a rel="me" class="u-twitter x-twitter url u-url" href="https://twitter.com/'.$tw.'" title="'.$aname.' @ Twitter">'.$tw.'</a>';
			}

			$l = rtrim(get_the_author_meta ( 'linkedin' , $aid ), '/');
			if ( !empty ($l)) {
				$lname = substr( $l , strrpos($l, '/') + 1);
				$socials['linkedin'] = '<a rel="me" class="u-linkedin x-linkedin url u-url" href="'.$l.'" title="'.$aname.' @ LinkedIn">'.$lname.'</a>';
			}

			$gh = get_the_author_meta ( 'github' , $aid );
			if ( !empty ($gh)) {
				$socials['googleplus'] = '<a rel="me" class="u-github x-github url u-url" href="https://github.com/'.$gh.'" title="'.$aname.' @ Github">'.$gh.'</a>';
			}

			/*
			$fb =  rtrim(get_the_author_meta ( 'facebook' , $aid ), '/');
			if ( !empty ($fb)) {
				$fbname = substr( $fb , strrpos($fb, '/') + 1);
				$socials['facebook'] = '<a rel="me" class="u-facebook x-facebook url u-url" href="'.$fb.'" title="'.$aname.' @ Facebook">'.$fbname.'</a>';
			}

			$g = rtrim( get_the_author_meta ( 'googleplus' , $aid ), '/' );
			if ( !empty ($g)) {
				$gname = substr( $g , strrpos($g, '/') + 1);
				$socials['googleplus'] = '<a rel="me" class="u-googleplus x-googleplus url u-url" href="'.$g.'" title="'.$aname.' @ Google+">'.$gname.'</a>';
			}
			*/

			if ( !empty($socials)) {
				$out .= '<span class="spacer">Find me:</span>';
				$out .= join ( "\n", $socials);
			}
		}
		$out .= '</span>';
		return $out;
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
	 * get webmention/retweet/reply data and display origin link
	 */
	public function repost_data() {
		global $post;

		/* Twitter retweet *
		$repost_id = get_post_meta($post->ID, 'twitter_rt_id', true );
		$repost_uid = get_post_meta($post->ID, 'twitter_rt_user_id', true );
		if ( !empty($repost_id) && !empty($repost_uid) ) {
			$origin = 'https://twitter.com/'. $repost_uid .'/status/'. $repost_id; ?>
				<p class="urel"><?php _e("Retweeted from: ") ?><a class="u-repost-of" href="<?php echo $origin; ?>" ><?php echo $origin; ?></a></p>
			<?php
		}
		unset ( $repost_id, $repost_uid );
		*/

		/* Twitter reply */
		$reply_id = get_post_meta($post->ID, 'twitter_reply_id', true );
		$reply_uid = get_post_meta($post->ID, 'twitter_reply_user_id', true );
		if ( !empty($reply_id) && !empty($reply_uid) ) {
			$origin = 'https://twitter.com/'. $reply_uid .'/status/'. $reply_id; ?>
				<p class="urel"><?php _e("In reply to: ") ?><a rel="in-reply-to" class="u-in-reply-to" href="<?php echo $origin; ?>" ><?php echo $origin; ?></a></p>
			<?php
		}
		unset ( $reply_id, $reply_uid );

		/* General reply */
		$reply_url = get_post_meta($post->ID, 'u-in-reply-to', true );
		if ( !empty($reply_url) ) { ?>
				<p class="urel"><?php _e("In reply to: ") ?><a rel="in-reply-to" class="u-in-reply-to" href="<?php echo $reply_url; ?>" ><?php echo $reply_url; ?></a></p>
			<?php
		}
		unset ( $reply_url );

		/* General repost */
		$repost_url = get_post_meta($post->ID, 'u-repost-of', true );
		if ( !empty($repost_url) ) { ?>
				<p class="urel"><?php _e("Repost of: ") ?><a class="u-repost-of" href="<?php echo $repost_url; ?>" ><?php echo $repost_url; ?></a></p>
			<?php
		}
		unset ( $repost_url );

		/* link meta */
		$url = get_post_meta($post->ID, '_format_link_url', true );
		$title = get_the_title ($post->ID );
		$webmention = get_post_meta($post->ID, '_format_link_webmention', true );
		if ( !empty($url ) && !empty($webmention) && $webmention != 'none' ) {
			switch ($webmention) {
				case 'rsvp-yes':
				case 'rsvp-no':
				case 'reply':
					?> <p  class="urel"><?php _e('This is a reply to: ', $this->theme_constant )?><a class="u-in-reply-to icon-link-ext-alt" href="<?php echo $url ?>"><?php echo $url ?></a></p><?php
					break;
				case 'repost':
					?> <p class="urel"><?php _e('Reposted from: ', $this->theme_constant )?><a class="u-repost-of icon-link-ext-alt" href="<?php echo $url ?>"><?php echo $url ?></a></p><?php
					break;
				case 'like':
					?> <p class="urel"><?php _e('Like of: ', $this->theme_constant )?><a class="u-like u-like-of icon-thumbs-up" href="<?php echo $url ?>"><?php echo $title ?></a></p><?php
					break;
			}

			if ( strstr( $webmention, 'rsvp-' ) ) {
				switch ($webmention) {
					case 'rsvp-yes':
						?><data class="p-rsvp" value="yes"><?php _e("I'll attend!", $this->theme_constant ); ?></data><?php
						break;
					case 'rsvp-no':
						?><data class="p-rsvp" value="no"><?php _e("I cannot make it.", $this->theme_constant ); ?></data><?php
						break;
				}
			}
		}
		unset ($url, $title, $webmention, $data);

	}

	/**
	 * additional post format data for: https://github.com/petermolnar/wp-post-formats ( fork of https://github.com/crowdfavorite/wp-post-formats )
	 */
	public function add_post_format_data ( $src ) {
		global $post;
		$format = get_post_format ( $post->ID );
		//$ameta = $this->article_meta();

		/* quote format */
		$source_name = get_post_meta($post->ID, '_format_quote_source_name', true );
		$source_url = get_post_meta($post->ID, '_format_quote_source_url', true );
		if ( !empty( $source_name ) && !empty ( $source_url) ) {
			$cite = '<cite><a class="u-quote-source u-like-of icon-link-ext-alt" href="'. $source_url .'">'. $source_name .'</a></cite>';
		}
		elseif ( !empty($source_name )) {
			$cite = '<cite class="u-quote-source">'. $source_name .'</cite>';
		}

		if ( $format == 'quote' && !strstr ( $src, '<blockquote>' ) ) {
			$src = '<blockquote>'. $src .'<p class="alignright">'. $cite .'</p></blockquote>';
		}
		elseif ( $format == 'quote' ) {
			$src .= '<p class="alignright">'. $cite .'</p>';
		}
		unset ($source_name, $source_url);

		/* image meta */
		$img = get_post_thumbnail_id( $post->ID );
		if ( !empty($format) && $format != 'standard ' && $format != 'gallery' && !empty($img) ) {
			//if ( empty($src)) $src = '<h3>'. get_the_title() .'</h3>';

			/* replace all images with adaptive; if there's a hit, don't insert
			 * the features image automatically
			 */
			$asrc = $this->replace_images_with_adaptive ( $src );
			if ( strlen($src) == strlen($asrc) && !empty($img) )
				$src .= '[adaptimg aid=' . $img .' size=hd share=0 standalone=1]';
			else
				$src = $asrc;
			unset ( $asrc );
		}
		unset ( $img );

		/* audio meta */
		$audio = get_post_meta($post->ID, '_format_audio_embed', true );
		if ( !empty($audio)) {
				$src .= $audio;
		}
		unset ( $audio );

		/* video meta */
		$video = get_post_meta($post->ID, '_format_video_embed', true );
		if ( !empty($video)) {
			if ( strstr( $video, 'ted'))
				$src .= '['. $video .']';
			else //(strstr( $video, 'youtube'))
				$src .= '[embed]'. $video .'[/embed]';
		}
		unset ( $video );

		/* link meta */
		$url = get_post_meta($post->ID, '_format_link_url', true );
		$title = get_the_title ($post->ID );
		$webmention = get_post_meta($post->ID, '_format_link_webmention', true );
		if ( !empty($url ) && ( empty($webmention) || $webmention == 'none' ) ) {
				$src = '<p><a class="icon-link-ext-alt" href="'.$url.'">'. $title .'</a></p>' . $src;
		}
		unset ($url, $title, $webmention);

		return $src;
	}

	/**
	 * additional user fields
	 */
	public function add_user_meta_fields ($profile_fields) {

		$profile_fields['github'] = 'Github username';
		$profile_fields['mobile'] = 'Mobile phone number';
		$profile_fields['linkedin'] = 'LinkedIn profile URL';

		return $profile_fields;
	}

	/**
	 *
	 */
	public function nice_title ( $title ) {
		return trim( str_replace ( array ('&raquo;', '»' ), array ('',''), $title ) );
	}

	//public function wpunautop ( $s ) {
		////remove any new lines already in there
		//$s = str_replace( "\n", "", $s);

		////remove all <p>
		//$s = str_replace("<p>", "", $s);

		////replace <br /> with \n
		//$s = str_replace(array("<br />", "<br>", "<br/>"), "\n", $s);

		////replace </p> with \n\n
		//$s = str_replace("</p>", "\n\n", $s);

		//return $s;
	//}

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
	 * display & other meta information for categories
	 */
	public function category_meta( &$category = null ) {
		$default = array (
			'custom-template' => 'default',
			'posts-per-page' => 12,
			'show-sidebar' => 0,
			'show-pagination' => 1,
			'order-by' => 'date',
			'sidebar-entries' => 12,
			'columns' => 0,
			'siblings' => false,
			'theme' => 'light',
		);

		if ( empty($category)) {
			return $default;
		}


		$cid = 'category_' . $category->slug;
		$cached = ( self::cache == 1 ) ? wp_cache_get( $cid, self::cache_group ) : false;
		if ( $cached != false )  return  $cached;

		switch ( $category->slug ) {
			case 'blips':
				$category_meta = array (
					'custom-template' => 'status',
					'posts-per-page' => 12,
					'show-sidebar' => 0,
					'columns' => 0,
					'siblings' => false,
					'show-pagination' => 1,
					'sidebar-entries' => 0,
					'theme' => 'light',
				);
				break;
			case 'photoblog':
				$category_meta = array (
					'custom-template' => 'default',
					'posts-per-page' => 2,
					'show-sidebar' => 0,
					'show-pagination' => 1,
					'columns' => 0,
					'siblings' => true,
					'sidebar-entries' => 0,
					'theme' => 'dark',
				);
				break;
			case 'portfolio':
				$category_meta = array (
					'custom-template' => 'gallery',
					'posts-per-page' => -1,
					'show-sidebar' => 0,
					'show-pagination' => 0,
					'order-by' => 'modified',
					'columns' => 0,
					'siblings' => false,
					'theme' => 'dark',
				);
				break;
			default:
				$category_meta = $default;
		}

		wp_cache_set( $cid, $category_meta, self::cache_group, self::cache_time );
		return $category_meta;
	}

	/**
	 * display & other meta information for posts, based on category, post format, etc.
	 */
	public function article_meta( ) {
		global $post;

		if ( empty($post))
			return false;

		$singular = is_singular();

		$cid = ($singular) ? 'article_' : 'article_list_';
		$cid .= $post->ID;

		$cached = ( self::cache == 1 ) ? wp_cache_get( $cid, self::cache_group ) : false;
		if ( $cached != false ) return  $cached;

		$ameta = array();

		$post_format = get_post_format();
		if ( $post_format === false )
			$post_format = get_post_type();

		$ameta['post-format'] = $post_format;
		$c = get_the_category( $post->ID );
		$ameta['category'] = array_shift( $c );
		$ameta['category_meta'] = $this->category_meta( $ameta['category'] );
		$ameta['theme'] = $ameta['category_meta']['theme'];
		$ameta['header'] = 'normal';
		$ameta['adaptify'] = false;
		$ameta['footer'] = ($singular) ? true : false;
		$ameta['siblings'] = false;
		$ameta['content_type'] = ( $singular ) ? 'e-content' : 'e-summary';
		$ameta['limitwidth'] = true;
		$ameta['class'] = ($singular) ? ' journal ' : ' content-inner article-list-element';
		$ameta['featimg'] = false;
		$ameta['showccntr'] = ($singular) ? false : true;
		$ameta['showtags'] = ($singular) ? true : false;
		$ameta['sidebar'] = false;

		switch ( $post_format ) {
			case 'link':
			case 'quote':
			case 'status':
			case 'image':
			case 'video':
			case 'audio':
			case 'aside':
				$ameta['header'] = 'pubdate';
				$ameta['adaptify'] = true;
				$ameta['content_type'] = 'e-content';
			break;
			case 'gallery':
				//$ameta['header'] = ($singular) ? 'small' : 'none';
				//$ameta['content_type'] = ($singular) ? 'e-content' : 'image';

				$ameta['footer'] = false;
				$ameta['showccntr'] = false;
				$ameta['limitwidth'] = false;
				switch ( $ameta['category']->slug ) {
					case 'photoblog':
						$ameta['footer'] = ($singular) ? true :false ;
						$ameta['siblings'] = true;
						$ameta['content_type'] = 'e-content';
						$ameta['class'] =  '';
						$ameta['header'] = 'small';
						break;
					default:
						$ameta['content_type'] = ($singular) ? 'e-content' : 'image';
						$ameta['class'] =  ($singular) ? '' : 'photoblog-preview';
						$ameta['header'] = ($singular) ? 'small' : 'none';
						break;
				}
			break;
			case 'page':
				$ameta['header'] = 'none';
				$ameta['footer'] = false;
				$ameta['showccntr'] = false;
				$ameta['showtags'] = false;
				$ameta['color'] = 'dark';
			break;
		default:
			$ameta['featimg'] = true;
			$ameta['sidebar'] = ($singular) ? true : false;
			break;
		}

		wp_cache_set( $cid, $ameta, self::cache_group, self::cache_time );
		return $ameta;
	}


	function rebuild_media_string( $str ) {
		if ( strstr ( $str, '<img' ) ) {
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
		else {
			return $str;
		}
	}

}

/**** END OF FUNCTIONS *****/

if ( !isset( $petermolnareu_theme ) || empty ( $petermolnareu_theme ) ) {
	$petermolnareu_theme = new petermolnareu();
}

?>
