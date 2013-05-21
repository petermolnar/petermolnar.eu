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

		public function __construct () {
			$this->theme_url = $this->replace_if_ssl( get_bloginfo("stylesheet_directory") );
			$this->js_dir = $this->theme_url . '/assets/js/';
			$this->css_dir = $this->theme_url . '/assets/css/';
			$this->font_dir = $this->theme_url . '/assets/font/';
			$this->image_dir = $this->theme_url . '/assets/image/';
			$this->info = wp_get_theme( );

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

			/* set up CSS, JS and fonts */
			if (!is_admin()) {

				/* JS */
				//wp_register_style( $handle, $src, $deps, $ver, $media )
				//wp_register_script( $handle, $src, $deps, $ver, $in_footer );

				wp_register_script('jquery.touchSwipe', $this->js_dir . 'jquery.touchSwipe.min.js', array( 'jquery' ), '1.6.3' );
				wp_enqueue_script( 'jquery' );
				wp_enqueue_script( 'jquery.touchSwipe' );

				/* CSS */
				wp_register_style( 'reset',	$this->css_dir . 'reset.css', false, '1.0' );
				wp_register_style( 'googlefonts', 'http://fonts.googleapis.com/css?family=Open+Sans' , array('reset' ), '1.0' );
				wp_register_style( 'style',	$this->theme_url . '/style.css' , array('reset', 'googlefonts' ), '3.0' );

				wp_enqueue_style( 'reset' );
				wp_enqueue_style( 'googlefonts' );
				wp_enqueue_style( 'style' );

				/* syntax highlighter */
				wp_register_script( 'rainbow' , $this->js_dir . 'rainbow-custom.min.js', false, '1.2' );
				wp_register_style( 'rainbow-obsidian',	$this->css_dir . 'obsidian.css', false, '1.0' );

				/* adaptgal */
				wp_register_script('jquery.adaptgal', $this->js_dir . 'adaptgal.js', array( 'jquery' ), '1.0' );
			}

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
			add_filter( 'the_content', 'wpautop', 20 );
			add_filter( 'the_content', 'shortcode_unautop', 100 );

			/* set & register image sizes for adaptgal */
			foreach ( $this->image_sizes as $resolution => $sizes ) {
				add_image_size( self::thumb_prefix . $resolution, $sizes[ self::thumb_prefix ], $sizes[ self::thumb_prefix ], true);
				add_image_size( self::lthumb_prefix . $resolution, $sizes[ self::lthumb_prefix ], $sizes[ self::lthumb_prefix ], true);
				add_image_size( self::std_prefix . $resolution, $sizes[ self::std_prefix ], $sizes[ self::std_prefix ], false);
			}

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
		private function replace_if_ssl ( $url ) {
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

				$out .= '
				<nav class="sidebar-postlist">
					<h3 class="postlist-title">'. $title .'</h3>
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
					'caller_get_posts'=>1
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
				}
			}


			$out .= '
			<section class="sidebar">
				<nav class="sidebar-postlist">
					<h3 class="postlist-title">'. __( "Related posts" ) . '</h3>
					<ul class="postlist">
					'. $list .'
					</ul>
				</nav>
			</section>';
			//wp_reset_query();

			return $out;
		}

		/**
		 *
		 *
		 */
		public function syntax_highlight ( $atts ,  $content = null ) {
			wp_enqueue_script( 'rainbow' );
			wp_enqueue_style( 'rainbow-obsidian' );

			extract( shortcode_atts(array(
				'lang' => 'generic'
			), $atts));

			if ( empty( $content ) ) {
				$return = false;
			}
			else {
				$return = '<pre><code data-language="' . $lang . '">' . $content . '</code></pre>';
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
					$img['title'] = strip_tags ( attribute_escape($_post->post_title) );
					$img['alttext'] = strip_tags ( get_post_meta($_post->id, '_wp_attachment_image_alt', true) );
					$img['caption'] = strip_tags ( attribute_escape($_post->post_excerpt) );
					$img['description'] = strip_tags ( attribute_escape($_post->post_content) );
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
					$thumbnail = wp_get_attachment_image_src( $aid, $th . $resolution );
					if ( $thumbnail[3] != true ) {
						$thumbnail = wp_get_attachment_image_src( $aid, 'thumbnail' );
					}
					$preview = wp_get_attachment_image_src( $aid, self::std_prefix . $resolution );
					$bgimages[ $th ][ $resolution ][ $aid ] = '#'. $galtype . '-' . $th . $aid .' { background-image: url('. $thumbnail[0] .'); }';
					$bgimages[ self::std_prefix ][ $resolution ][ $aid ] = '#'. $galtype . '-' . self::std_prefix . $aid .' { background-image: url('. $preview[0] .'); }';
				}
			}

			$cntr = 0;
			$resolutions = array_keys( $this->image_sizes );
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
				$std = wp_get_attachment_image_src( $aid, 'medium' );
				$thumbid = $galtype . '-' . self::thumb_prefix . $aid;
				$previewid = $galtype . '-' . self::std_prefix . $aid;
				if (!empty($img['description'])) $description = '<span class="thumb-description">'. $img['description'] .'</span>';

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
				<br class="clear" />
			</section>';

			wp_enqueue_script ( 'jquery.adaptgal' );

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
				$std = wp_get_attachment_image_src( $aid, 'medium' );
				$thumbid = $galtype . '-' . self::lthumb_prefix . $aid;
				$previewid = $galtype . '-' . self::std_prefix . $aid;
				if (!empty($img['description'])) $description = '<span class="thumb-description">'. $img['description'] .'</span>';

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


	}
}

if ( !$petermolnareu_theme ) {
	$petermolnareu_theme = new petermolnareu();
}

?>
