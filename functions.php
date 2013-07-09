<?php

if ( ! class_exists( 'petermolnareu' ) ) {

	class petermolnareu {
		const theme_constant = 'petermolnareu';
		const std_prefix = 'src';
		const thumb_prefix = 'thumb';
		const lthumb_prefix = 'lthumb';
		const menu_header = 'header';
		const menu_portfolio = 'portfolio';

		public $js_dir = '';
		public $css_dir = '';
		public $font_dir = '';
		public $image_dir = '';
		public $theme_url = '';
		public $image_sizes = array();
		public $info = array();
		private $urlfilters = array ();

		public function __construct () {
			$this->theme_url = $this->replace_if_ssl( get_bloginfo("stylesheet_directory") );
			$this->js_dir = $this->theme_url . '/assets/js/';
			$this->css_dir = $this->theme_url . '/assets/css/';
			$this->font_dir = $this->theme_url . '/assets/font/';
			$this->image_dir = $this->theme_url . '/assets/image/';
			$this->info = wp_get_theme( );

			$this->urlfilters = array(
				'post_link', // Normal post link
				'post_type_link', // Custom post type link
				'page_link', // Page link
				'attachment_link', // Attachment link
				//'get_shortlink', // Shortlink
				'post_type_archive_link', // Post type archive link
				'get_pagenum_link', // Paginated link
				'get_comments_pagenum_link', // Paginated comment link
				'term_link', // Term link, including category, tag
				'search_link', // Search link
				'day_link', // Date archive link
				'month_link',
				'year_link',

				// site location
				'option_siteurl',
				'blog_option_siteurl',
				'option_home',
				'admin_url',
				'home_url',
				'includes_url',
				'site_url',
				'site_option_siteurl',
				//'network_home_url',
				//'network_site_url',

				// debug only filters
				'get_the_author_url',
				'get_comment_link',
				'wp_get_attachment_image_src',
				'wp_get_attachment_thumb_url',
				'wp_get_attachment_url',
				'wp_login_url',
				'wp_logout_url',
				'wp_lostpassword_url',
				//'get_stylesheet_uri',
				// 'get_stylesheet_directory_uri',
				// 'plugins_url',
				// 'plugin_dir_url',
				// 'stylesheet_directory_uri',
				// 'get_template_directory_uri',
				// 'template_directory_uri',
				//'get_locale_stylesheet_uri',
				//'script_loader_src', // plugin scripts url
				//'style_loader_src', // plugin styles url
				//'get_theme_root_uri'
				// 'home_url'
			);

			$this->image_sizes = array (
				460 => array (
					self::thumb_prefix => 60,
					self::lthumb_prefix => 120,
					self::std_prefix => 640,
				),
				720 => array (
					self::thumb_prefix =>120,
					self::lthumb_prefix => 240,
					self::std_prefix => 1024,
				),
				1600 => array (
					self::thumb_prefix => 180,
					self::lthumb_prefix => 320,
					self::std_prefix => 1200,
				)
			);

			/* theme init */
			add_action( 'init', array( &$this, 'init'));
			/* set up CSS, JS and fonts */
			add_action( 'wp_enqueue_scripts', array(&$this,'register_css_js'));
		}

		public function init () {

			/* set theme supports */
			add_theme_support( 'post-thumbnails' );
			add_theme_support( 'menus' );
			add_theme_support( 'post-formats', array( 'gallery', 'image' ) );

			/* add main menus */
			register_nav_menus( array(
				self::menu_header => __( self::menu_header , self::theme_constant ),
				self::menu_portfolio => __( self::menu_portfolio, self::theme_constant ),
			) );

			/* enable SVG uploads */
			add_filter('upload_mimes', array( &$this, 'custom_upload_mimes' ) );

			/* modify css & js versioning */
			//add_filter( 'script_loader_src', array( &$this, 'modify_asset_version' ) );
			//add_filter( 'style_loader_src', array ( &$this, 'modify_asset_version' ) );

			/* add syntax highlighting */
			add_shortcode('code', array ( &$this, 'syntax_highlight' ) );
			add_shortcode('cc', array ( &$this, 'syntax_highlight' ) );

			/* adaptgal */
			add_shortcode('adaptgal', array ( &$this, 'adaptgal' ) );
			//add_shortcode('wp-galleriffic', array ( &$this, 'adaptgal' ) );

			/* photogal */
			add_shortcode('photogal', array ( &$this, 'photogal' ) );

			/* unautop please */
			remove_filter( 'the_content', 'wpautop' );
			add_filter( 'the_content', array( &$this, 'legacy' ), 1);
			add_filter( 'the_content', array( &$this, 'lightbox' ), 2);
			add_filter( 'the_content', 'wpautop', 20 );
			add_filter( 'the_content', 'shortcode_unautop', 100 );
			add_filter( 'the_content', array( &$this, 'fix_urls'), 100);

			/* set & register image sizes for adaptgal */
			foreach ( $this->image_sizes as $resolution => $sizes ) {
				add_image_size( self::thumb_prefix . $resolution, $sizes[ self::thumb_prefix ], $sizes[ self::thumb_prefix ], true);
				add_image_size( self::lthumb_prefix . $resolution, $sizes[ self::lthumb_prefix ], $sizes[ self::lthumb_prefix ], true);
				add_image_size( self::std_prefix . $resolution, $sizes[ self::std_prefix ], $sizes[ self::std_prefix ], false);
			}

			if ( ! is_feed()  && ! get_query_var( 'sitemap' ) ) {
				foreach ( $this->urlfilters as $filter ) {
					add_filter( $filter, 'wp_make_link_relative' );
				}
			}
		}


		public function register_css_js () {
			/* register styles */
			wp_register_style( 'reset', $this->css_dir . 'reset.css', false, null );
			wp_register_style( 'style', $this->theme_url . '/style.css' , array('reset'), $this->info->version );
			wp_register_style( 'lightbox', $this->css_dir . 'jquery.lightbox-0.5.css', false, null );
			wp_register_style( 'prism', $this->css_dir . 'prism.css', false, null );

			/* CDN jquery */
			wp_deregister_script( 'jquery' );
			wp_register_script( 'jquery', $this->replace_if_ssl( 'http://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js' ), false, null, true );

			/* pre-register scripts */
			wp_register_script( 'jquery.lightbox',	$this->js_dir . 'jquery.lightbox-0.5.min.js', array( 'jquery' ), null, true );
			wp_register_script( 'jquery.lightbox.images', $this->js_dir . 'jquery.lightbox.images.js', array( 'jquery', 'jquery.lightbox' ), null, true );
			wp_register_script( 'prism' , $this->js_dir . 'prism.js', false, null, true );
			wp_register_script( 'jquery.touchSwipe', $this->js_dir . 'jquery.touchSwipe.min.js', array('jquery'), null, true );
			wp_register_script( 'jquery.adaptgal', $this->js_dir . 'adaptgal.js', array('jquery', 'jquery.touchSwipe'), null, true );

			/* enqueue CSS */
			wp_enqueue_style( 'reset' );
			wp_enqueue_style( 'style' );
		}

		/**
		 *
		 *
		 */
		public function modify_asset_version ( $src ) {
			/*
			//global $wp_version;

			$version = $this->info->Version;
			$version_str = '?ver='.$version;
			$version_str_offset = strlen( $src ) - strlen( $version_str );

			if( substr( $src, $version_str_offset ) == $version_str )
				return substr( $src, 0, $version_str_offset );
			else
				return $src;
			*/
			//
			//$qm = substr( $src, '?' );
			//$base = ($qm == false ) ? $src : substr( $src, 0, $qm );
			//return $base . '?' . $this->info->Version;
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
		public function share ( $link , $title, $comment=false ) {
			global $post;
			$class='opacity75 icon-share';

			$share = array (

				'twitter'=>array (
					'url'=>'http://twitter.com/home?status=' .$title . ' - ' . $link,
					'title'=>__('Tweet', self::theme_constant),
				),

				'facebook'=>array (
					'url'=>'http://www.facebook.com/share.php?u=' . $link . '&t=' . $title,
					'title'=>__('Share', self::theme_constant),
				),

				'googleplus'=>array (
					'url'=>'https://plusone.google.com/_/+1/confirm?hl=en&url=' . $link,
					'title'=>__('+1', self::theme_constant),
				),
			);

			if ($comment) {
				$share['comment'] = array (
					'url'=>get_permalink( $post->ID ),
					'title'=>__('comment', self::theme_constant),
				);
			}

			$out = '';
			foreach ($share as $site=>$details) {
				$out .= '<li><a class="'. $site .'" href="' . $details['url'] . '" title="' . $details['title'] . '">'. $details['title'] .'</a></li>';
			}

			$out = '
				<nav class="share">
					<ul>
					'. $out .'
					</ul>
				</nav>';

			echo $out;
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
		 * gets array of image attachments for a post
		 *
		 * @var mixed $post Reference of the post with the attachments
		 * @var array $images Reference of the array to return the images in
		 *
		 */
		private function list_images_attachments ( &$post ) {
			$images = array();

			/* get image type attachments for the post by ID */
			$attachments = get_children( array (
				'post_parent'=>$post->ID,
				'post_type'=>'attachment',
				'post_mime_type'=>'image',
				'orderby'=>'menu_order',
				'order'=>'asc'
			) );

			if ( !empty($attachments) )
			{
				foreach ( $attachments as $aid => $attachment ) {
					$img = array();

					$_post = get_post($aid);

					/* set the titles and alternate texts */
					$img['title'] = esc_attr($_post->post_title);
					$img['alttext'] = strip_tags ( get_post_meta($_post->id, '_wp_attachment_image_alt', true) );
					$img['caption'] = esc_attr($_post->post_excerpt);
					$img['description'] = esc_attr($_post->post_content);
					$images[ $aid ] = $img;
				}
			}

			return $images;
		}

		/**
		 *
		 *
		 */
		private function galleries_init ( &$atts ) {

			extract( shortcode_atts(array(
				'postid' => false,
			), $atts));

			if ( $postid == false )
				global $post;
			else
				$post = get_post( $postid );

			return $post;
		}

		/**
		 *
		 *
		 */
		private function galleries_bgstyles ( &$images, $galtype = 'adaptgal' ) {
			$bgimages = array();

			switch ( $galtype ) {
				case 'photogal':
					$th = self::lthumb_prefix;
					break;
				default:
					$th = self::thumb_prefix;
					break;
			}

			foreach ($images as $aid => $img ) {
				foreach ( $this->image_sizes as $resolution => $sizes ) {
					//$thumbnail = $this->replace_if_ssl( wp_get_attachment_image_src( $aid, $th . $resolution ) );
					$thumbnail = wp_get_attachment_image_src( $aid, $th . $resolution );
					if ( $thumbnail[3] != true ) {
						//$thumbnail = $this->replace_if_ssl( wp_get_attachment_image_src( $aid, 'thumbnail' ) );
						$thumbnail = wp_get_attachment_image_src( $aid, 'thumbnail' );
					}
					//$preview = $this->replace_if_ssl( wp_get_attachment_image_src( $aid, self::std_prefix . $resolution ) );
					$preview = wp_get_attachment_image_src( $aid, self::std_prefix . $resolution );
					$bgimages[ $th ][ $resolution ][ $aid ] = '#'. $galtype . '-' . $th . $aid .' { background-image: url('. $thumbnail[0] .'); }';
					$bgimages[ self::std_prefix ][ $resolution ][ $aid ] = '#'. $galtype . '-' . self::std_prefix . $aid .' { background-image: url('. $preview[0] .'); }';
				}
			}

			$cntr = 0;
			$resolutions = array_keys( $this->image_sizes );
			$mediaqueries = '';
			foreach ( $bgimages[ $th ] as $resolution => $backgrounds ) {
				$eq = "\n" . join( "\n", $bgimages[ $th ][ $resolution ] ) . "\n" . join( "\n", $bgimages[ self::std_prefix ][ $resolution ] );

				if ( $cntr == 0 ) {
					//$mediaqueries .= '
					//@media ( max-width : '. ( $resolutions[ $cntr + 1 ] - 1 ) .'px ) {
					//	'. $eq .'
					//}';
					$mediaqueries .= $eq;
				}
				elseif ( $cntr != ( sizeof ( $bgimages[ $th ] ) -1 ) ) {
					$mediaqueries .= '
					@media ( min-width : '. $resolution .'px ) and ( max-width : '. ( $resolutions[ $cntr + 1 ] - 1 ) .'px ) {
						'. $eq .'
					}';
				}
				else {
					$mediaqueries .= '
					@media ( min-width : '. $resolution .'px ) {
						'. $eq .'
					}';
				}
				$cntr++;
			}

			return $mediaqueries;
		}

		/**
		 * adaptgal output
		 *
		 * @param $atts
		 * @param $content
		 *
		 */
		public function adaptgal( $atts , $content = null ) {
			$galtype = 'adaptgal';
			$post = $this->galleries_init ( $atts );
			$images = $this->list_images_attachments ( $post );
			$elements = array();

			foreach ($images as $aid => $img ) {
				//$std = $this->replace_if_ssl( wp_get_attachment_image_src( $aid, 'medium' ) );
				$std = wp_get_attachment_image_src( $aid, 'medium' );
				$thumbid = $galtype . '-' . self::thumb_prefix . $aid;
				$previewid = $galtype . '-' . self::std_prefix . $aid;
				$description = (!empty($img['description'])) ? '<span class="thumb-description">'. $img['description'] .'</span>' : '';

				$elements[ self::thumb_prefix ][ $aid] = '
				<li>
					<a id="'. $thumbid .'" href="#'. $previewid .'">'. $img['title'] .'</a>
				</li>';

				$elements[ self::std_prefix ][ $aid] = '
				<figure id="'. $previewid .'">
					<img src="'. $std[0] .'" title="'. $img['title'] .'" alt="'. $img['alttext'] . '" />
					<figcaption>'. $img['caption'] . $description .'</figcaption>
				</figure>';
			}

			$output = '
			<style>'. $this->galleries_bgstyles ( $images ) .'</style>
			<section class="adaptgal" id="adaptgal-'. $post->ID.'">
				<section class="adaptgal-images">
					<nav class="adaptgal-slideshow"><a id="adaptgal-slideshow-control" href="#">]</a></nav>
					<div class="adaptgal-previews">
						'. join( "\n", $elements[ self::std_prefix ] ) .'
						<div class="adaptgal-loading">&nbsp;</div>
					</div>
					<nav class="adaptgal-thumbs">
						<ul>'. join( "\n", $elements[ self::thumb_prefix ] ) .'</ul>
					</nav>
				</section>
				<nav class="adaptgal-links">'. wp_nav_menu( array( 'container' => '' , 'theme_location' => self::menu_portfolio, 'echo' => false  ) ) .'</nav>
				<div class="clear">&nbsp;</div>
			</section>';

			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery.touchSwipe' );
			wp_enqueue_script( 'jquery.adaptgal' );

			return $output;
		}

		/**
		 * photogal output
		 *
		 * @param $atts
		 * @param $content
		 *
		 */
		public function photogal( $atts , $content = null ) {
			$galtype = 'photogal';
			$post = $this->galleries_init ( $atts );
			$images = $this->list_images_attachments ( $post );
			$elements = array();

			$nimages = sizeof ( $images );
			if ( $nimages <= 4 )  {
				$calculated = 'width: 49.6%; padding-bottom: 49.6%;';
			}
			elseif ( $nimages > 4 && $nimages <= 9 )  {
				$calculated = 'width: 32.6%; padding-bottom: 32.6%;';
			}
			elseif ( $nimages > 9 && $nimages <= 16 ) {
				$calculated = 'width: 24.6%; padding-bottom: 24.6%;';
			}
			else {
				$calculated = 'width: 19.6%; padding-bottom: 19.6%;';
			}

			foreach ($images as $aid => $img ) {
				//$std = $this->replace_if_ssl( wp_get_attachment_image_src( $aid, 'medium' ) );
				$std = wp_get_attachment_image_src( $aid, 'medium' );
				$thumbid = $galtype . '-' . self::lthumb_prefix . $aid;
				$previewid = $galtype . '-' . self::std_prefix . $aid;
				$description = (!empty($img['description'])) ? '<span class="thumb-description">'. $img['description'] .'</span>' : '';

				$elements[ $aid] = '
				<li>
					<div style="'.$calculated.'">
						<a id="'. $thumbid .'" href="#'. $previewid .'">'. $img['title'] .'</a>
					</div>
					<figure id="'. $previewid .'">
						<img src="'. $std[0] .'" title="'. $img['title'] .'" alt="'. $img['alttext'] . '" />
						<figcaption>'. $img['caption'] . $description .'</figcaption>
						<a class="photogal-close" href="#photogal-'. $post->ID .'">&nbsp;</a>
					</figure>
				</li>';

			}

			$output = '
			<style>'. $this->galleries_bgstyles ( $images, $galtype ) .'</style>
			<section class="photogal" id="photogal-'. $post->ID .'">
				<ul>'. join( "\n", $elements ) .'</ul>
				<br class="clear" />
			</section>';

			return $output;
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

			/* replace strings within `` to monotype string */
			$matches = array();
			preg_match_all ( "'`(.*?)`'si", $src , $matches, PREG_SET_ORDER );

			foreach ($matches as $match ) {
				$shortcode = '<code>'.$match[1].'</code>';
				$src = str_replace ( $match[0], $shortcode, $src );
			}


			return $src;
		}

		/**
		 * replaces all non secure absolute url to relative, therefore making it secure
		 */
		public function fix_urls ( $src ) {
			if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' )
				$_SERVER['HTTPS'] = 'on';

			if ( isset($_SERVER['HTTPS']) && (( strtolower($_SERVER['HTTPS']) == 'on' )  || ( $_SERVER['HTTPS'] == '1' ) )) {
				$nonsecurl = str_replace ( 'https://', 'http://',  get_bloginfo('url') );
				$securl = str_replace ( 'http://', 'https://',  get_bloginfo('url') );
				$src = str_replace ( $nonsecurl, '', $src  );
			}

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

}

if ( !isset( $petermolnareu_theme ) || empty ( $petermolnareu_theme ) ) {
	$petermolnareu_theme = new petermolnareu();
}

?>
