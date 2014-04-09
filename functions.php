<?php

include_once ('classes/theme-cleanup.php');
include_once ('classes/adaptive-images.php');

class petermolnareu {
	const theme_constant = 'petermolnareu';
	const menu_header = 'header';

	public $base_url = '';
	public $js_dir = '';
	public $css_dir = '';
	public $font_dir = '';
	public $image_dir = '';
	public $theme_url = '';
	public $image_sizes = array();
	public $info = array();
	public $urlfilters = array ();
	private $cleanup = null;
	//private $adaptive_galleries = null;
	private $adaptive_images = null;
	//public $imgprefixes = array();

	public function __construct () {
		$this->base_url = $this->replace_if_ssl( get_bloginfo("url") );
		$this->theme_url = $this->replace_if_ssl( get_bloginfo("stylesheet_directory") );
		$this->js_dir = $this->theme_url . '/assets/js/';
		$this->css_dir = $this->theme_url . '/assets/css/';
		$this->font_dir = $this->theme_url . '/assets/font/';
		$this->image_dir = $this->theme_url . '/assets/image/';
		$this->info = wp_get_theme( );

		/* cleanup class */
		$this->cleanup = new theme_cleaup();

		/* adaptive galleries class */
		//$this->adaptive_galleries = new adaptive_galleries();
		$this->adaptive_images = new adaptive_images( $this );
		//$this->imgprefixes = $this->adaptive_images->prefixes;

		/* theme init */
		add_action( 'init', array( &$this, 'init'));
		add_action( 'init', array( &$this->cleanup, 'filters'));
		//add_action( 'init', array( &$this->adaptive_galleries, 'init'));
		add_action( 'init', array( &$this->adaptive_images, 'init'));

		/* set up CSS, JS and fonts */
		add_action( 'wp_enqueue_scripts', array(&$this,'register_css_js'));
	}

	public function init () {
		/* set theme supports */
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'menus' );
		add_theme_support( 'post-formats', array( 'gallery', 'image', 'status', 'aside' ) );

		/* add main menus */
		register_nav_menus( array(
			self::menu_header => __( self::menu_header , self::theme_constant ),
			//adaptive_galleries::menu_portfolio => __( adaptive_galleries::menu_portfolio, self::theme_constant )
		) );

		/* enable SVG uploads */
		add_filter('upload_mimes', array( &$this, 'custom_upload_mimes' ) );

		/* add syntax highlighting */
		add_shortcode('code', array ( &$this, 'syntax_highlight' ) );
		add_shortcode('cc', array ( &$this, 'syntax_highlight' ) );

		/* legacy shortcode handler */
		add_filter( 'the_content', array( &$this, 'legacy' ), 1);

		/* lightbox all the things! */
		//add_filter( 'the_content', array( &$this, 'lightbox' ), 2);

		add_shortcode('photogal', array ( &$this->adaptive_images, 'adaptgal' ) );
		add_shortcode('wp-galleriffic', array ( &$this->adaptive_images, 'adaptgal' ) );

	}

	public function register_css_js () {
		/* enqueue CSS */

		wp_register_style( 'reset', $this->css_dir . 'reset.css', false, null );
		wp_enqueue_style( 'reset' );

		wp_register_style( 'style', $this->theme_url . '/style.css' , array('reset'), $this->info->version );
		wp_enqueue_style( 'style' );

		/* register styles for later optional use */
		//wp_register_style( 'lightbox', $this->css_dir . 'jquery.lightbox-0.5.css', false, null );

		/* syntax highlight */
		wp_register_style( 'prism', $this->css_dir . 'prism.css', false, null );
		wp_register_script( 'prism' , $this->js_dir . 'prism.js', false, null, true );

		/* CDN scripts */
		wp_deregister_script( 'jquery' );
		wp_register_script( 'jquery', $this->replace_if_ssl( 'http://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js' ), false, null, true );
		wp_enqueue_script( 'jquery' );

		wp_register_script( 'jquery.touchSwipe', $this->js_dir . 'jquery.touchSwipe.min.js', array('jquery'), null, true );

		wp_register_script( 'jquery.adaptive-images', $this->js_dir . 'adaptive-images.js', array('jquery','jquery.touchSwipe'), null, true );
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
	 *
	 *
	 */
	public function share ( $link , $title, $comment=false, $parent=false ) {
		global $post;
		//$class='opacity75 icon-share';
		$link = urlencode($link);
		$title = urlencode($title);
		$desciption = urlencode(get_the_excerpt());
		$type = get_post_type ( $post );

		$share = array (

			'twitter'=>array (

				'url'=>'https://twitter.com/share?url='. $link .'&text='. $title,
				'title'=>__('Tweet', self::theme_constant),
			),

			'facebook'=>array (
				'url'=>'http://www.facebook.com/share.php?u=' . $link . '&t=' . $title,
				'title'=>__('Share', self::theme_constant),
			),

			'googleplus'=>array (
				'url'=>'https://plus.google.com/share?url=' . $link,
				'title'=>__('+1', self::theme_constant),
			),

			'tumblr'=>array (
				'url'=>'http://www.tumblr.com/share/link?url='.$link.'&name='.$title.'&description='. $desciption,
				'title'=>__('share', self::theme_constant),
			),

		);

		if ( $parent != false ) {
			$share['pinterest']  = array (
				'url'=>'https://pinterest.com/pin/create/bookmarklet/?media='. $link .'&url='. urlencode($parent) .'&is_video=false&description='. $title,
				'title'=>__('pin', self::theme_constant),
			);
		}


		if ($comment) {
			$share['comment'] = array (
				'url'=>get_permalink( $post->ID ) . "#comments",
				'title'=>__('comment', self::theme_constant),
			);
		}

		$out = '';
		foreach ($share as $site=>$details) {
				$st = 'icon-' . $site;

				$out .= '<li><a class="'. $st .'" href="' . $details['url'] . '" title="' . $details['title'] . '">&nbsp;</a></li>';
		}

		$out = '
			<nav class="share">
				<ul>
				'. $out .'
				</ul>
			</nav>';


		return $out;
	}


	/**
	 * Returns unordered list of current category's posts
	 *
	 */
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

	/**
	 *
	 *
	 */
	public function related_posts ( $_post ) {
		$tags = wp_get_post_tags($_post->ID);
		$list = '';

		if ($tags) {
			$tag_ids = array();
			foreach($tags as $tag) {
				$tag_ids[] = $tag->term_id;
				$tags_names[] = $tag->name;
			}


			$args=array(
				'tag__in' => $tag_ids,
				'post__not_in' => array($_post->ID),
				'posts_per_page'=>12,
				'ignore_sticky_posts'=>1
			);

			$_query = new wp_query( $args );

			while( $_query->have_posts() ) {
				$_query->the_post();

				$post_title = htmlspecialchars( stripslashes( get_the_title() ) );

				$list .= '
						<li>
							<a href="' . get_permalink() . '" title="'. $post_title .'" >
								' . $post_title . '
							</a>
						</li>';
				wp_reset_postdata();
			}
		}

		$out = '
		<section class="sidebar">
			<nav class="sidebar-postlist">
				<h3 class="postlist-title">'. __( "Related posts" ) . '</h3>
				<ul class="postlist">
				'. $list .'
				</ul>
			</nav>
		</section>';

		return $out;
	}

	/**
	 *
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
			$search = array( '<', '>' );
			$replace = array( '&lt;', '&gt;' );
			$content = str_replace ( $search, $replace, $content );
			$return = '<pre class="line-numbers"><code class="language-' . $lang . '">' . trim(str_replace( "\t", "  ", $content ) ) . '</code></pre>';
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

		/* replace strings within `` to monotype string *
		$matches = array();
		preg_match_all ( "'`(.*?)`'si", $src , $matches, PREG_SET_ORDER );

		foreach ($matches as $match ) {
			$shortcode = '<code>'.$match[1].'</code>';
			$src = str_replace ( $match[0], $shortcode, $src );
		}
		 */

		return $src;
	}

	/**
	 * auto-lightbox
	 */
	public function lightbox ( $src ) {

		$matches = array();
		preg_match_all('!http://[a-z0-9\-\.\/]+\.(?:jpe?g|png)!Ui' , $src , $matches);
		if ( !empty ( $matches ) ) {
			wp_enqueue_style( 'lightbox' );
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery.lightbox' );
			wp_enqueue_script( 'jquery.lightbox.images' );
		}

		return $src;
	}

}



/**** END OF FUNCTIONS *****/
if ( !isset( $petermolnareu_theme ) || empty ( $petermolnareu_theme ) ) {
	$petermolnareu_theme = new petermolnareu();
}

?>
