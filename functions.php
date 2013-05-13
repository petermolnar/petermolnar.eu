<?php

if ( ! class_exists( 'petermolnareu' ) ) {

	class petermolnareu  {
		const theme_constant = 'petermolnareu';
		const _js_dir  = '/assets/js/';
		public $js_dir = '';
		const _css_dir  = '/assets/css/';
		public $css_dir = '';
		const _font_dir  = '/assets/font/';
		public $font_dir = '';
		const _image_dir  = '/assets/image/';
		public $image_dir = '';
		public $theme_url = '';

		const _std_prefix = 'src';
		const _thumb_prefix = 'thumb';
		public $image_sizes = array();
		const _basesize = 300;
		const _menu_header = 'header';
		const _menu_portfolio = 'portfolio';

		private $dynCSS = '';

		public function css () {
			echo '<style>' . $this->dynCSS . '</style>';
		}

		public function __construct () {
			$this->theme_url = $this->replace_if_ssl( get_bloginfo("stylesheet_directory") );
			$this->js_dir = $this->theme_url . self::_js_dir;
			$this->css_dir = $this->theme_url . self::_css_dir;
			$this->font_dir = $this->theme_url . self::_font_dir;
			$this->image_dir = $this->theme_url . self::_image_dir;

			$this->image_sizes = array (
				300 => array (
					self::_thumb_prefix => 20,
					self::_std_prefix => 200,
				),
				460 => array (
					self::_thumb_prefix => 30,
					self::_std_prefix => 300,
				),
				720 => array (
					self::_thumb_prefix => 54,
					self::_std_prefix => 540,
				),
				1600 => array (
					self::_thumb_prefix => 120,
					self::_std_prefix => 1200,
				)
			);

			/* set up CSS, JS and fonts */
			if (!is_admin()) {

				/* JS */
				/* webp for everyone */
				wp_register_script('webpjs', $this->js_dir . 'webpjs-0.0.2.min.js', false);
				wp_register_script('jquery.touchSwipe', $this->js_dir . 'jquery.touchSwipe.min.js', array( 'jquery' ) );

				wp_enqueue_script( 'jquery' );
				wp_enqueue_script ( 'jquery.touchSwipe' );
				//wp_enqueue_script( 'webpjs' );

				/* CSS */
				wp_register_style ( 'reset',	$this->css_dir . 'reset.css', false );
				wp_enqueue_style( 'reset' );
				wp_register_style ( 'googlefonts',	'http://fonts.googleapis.com/css?family=Open+Sans:400italic,600italic,400,600' , array ( 'reset' ) );
				wp_enqueue_style( 'googlefonts' );
				wp_register_style ( 'style',	$this->theme_url . '/style.css' , array('reset', 'googlefonts' ) );
				wp_enqueue_style( 'style' );

			}

			/* set theme supports */
			add_theme_support( 'post-thumbnails' );
			add_theme_support( 'menus' );
			add_theme_support( 'post-formats', array( 'gallery', 'image' ) );

			/* add main menus */
			register_nav_menus( array(
				self::_menu_header => __( self::_menu_header , self::theme_constant ),
				self::_menu_portfolio => __( self::_menu_portfolio, self::theme_constant ),
			) );

			/* enable SVG uploads */
			add_filter('upload_mimes', array( &$this, 'custom_upload_mimes' ) );

			/* modify css & js versioning */
			add_filter( 'script_loader_src', array( &$this, 'modify_asset_version' ) );
			add_filter( 'style_loader_src', array ( &$this, 'modify_asset_version' ) );

			/* add syntax highlighting */
			add_shortcode('code', array ( &$this, 'syntax_highlight' ) );

			/* adaptgal */
			add_shortcode('adaptgal', array ( &$this, 'adaptgal' ) );
			add_shortcode('wp-galleriffic', array ( &$this, 'adaptgal' ) );

			/* move wpautop filter to AFTER shortcode is processed */
			remove_filter( 'the_content', 'wpautop' );
			//add_filter( 'the_content', 'wpautop' , 99);
			//add_filter( 'the_content', 'shortcode_unautop',1 );

			foreach ( $this->image_sizes as $resolution => $sizes ) {
				add_image_size( self::_thumb_prefix . $resolution, $sizes[ self::_thumb_prefix ], $sizes[ self::_thumb_prefix ], true);
				add_image_size( self::_std_prefix . $resolution, $sizes[ self::_std_prefix ], $sizes[ self::_std_prefix ], false);
			}

		}

		/**
		 *
		 *
		 */
		public function modify_asset_version ( $src ) {
			global $wp_version;

			$version_str = '?ver='.$wp_version;
			$version_str_offset = strlen( $src ) - strlen( $version_str );

			if( substr( $src, $version_str_offset ) == $version_str )
				return substr( $src, 0, $version_str_offset );
			else
				return $src;
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

				'facebook'=>array (
					'url'=>'http://www.facebook.com/share.php?u=' . $link . '&t=' . $title,
					'name'=>'Facebook',
					'title'=>'Share',
					'icon'=>'f',
				),

				'twitter'=>array (
					'url'=>'http://twitter.com/home?status=' .$title . ' - ' . $link,
					'name'=>'Twitter',
					'title'=>'Tweet',
					'icon'=>'t',
				),

				'googleplus'=>array (
					'url'=>'https://plusone.google.com/_/+1/confirm?hl=en&url=' . $link,
					'name'=>'GooglePlus',
					'title'=>'+1',
					'icon'=>'g',
				),
			);

			if ($comment) {
				$share['comment'] = array (
					'url'=>get_permalink( $post->ID ),
					'name'=>'comment',
					'title'=>'Leave comment',
					'icon'=>'c',
				);
			}

			foreach ($share as $site=>$details) {
				$out .= '<li class="icon-element"><a class="icon" href="' . $details['url'] . '" title="' . $details['title'] . '">'. $details['icon'] .'</a></li>';
			}

			$out = '
				<nav class="share">
					<ul class="icons-list">
					'. $out .'
					</ul>
				</nav>';

			echo $out;
		}


		/**
		 * Returns unordered list of current category's posts
		 *
		 */
		public function list_posts( $limit=-1 , $from=false ) {
			global $post;
			$out = '';
			$categories = get_the_category( $post->ID );

			foreach ($categories as $category) {
				if ( $limit == -1 && !$from )
					$title = $category->name;
				elseif ( ! $from )
					$title = 'Last '. $limit . ' of ' . $category->name;
				else
					$title = 'More of ' . $category->name;

				$posts = get_posts( array( 'category' => $category->cat_ID , 'orderby' => 'date' , 'order' => 'DESC' , 'numberposts' => $limit ));

				if ( $from != false )
				{
					for ($i=0; $i<$from; $i++)
					{
						array_shift ( $posts );
					}
				}


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
					}

					$out .= '
					<aside class="sidebar-widget">
						<nav class="sidebar-postlist">
							<h3 class="postlist-title">'. $title .'</h3>
							<ul class="postlist">
							'. $list .'
							</ul>
						</nav>
					</aside>';
				}
			}
			return $out;
		}

		public function syntax_highlight ( $atts ,  $content = null ) {
			/* syntax highlight */
			wp_enqueue_script( 'rainbow' ,			$this->js_dir  . 'rainbow-custom.min.js' );
			wp_enqueue_style( 'rainbow-obsidian',	$this->css_dir . 'obsidian.css', false, false );

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
		private function list_images_attachments ( &$post, &$images ) {

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

					///* Get the intermediate image sizes and add the full size to the array. */
					//$sizes = get_intermediate_image_sizes();
					//$sizes[] = 'full';
					//
					///* Loop through each of the image sizes. */
					//foreach ( $sizes as $size ) {
					//	/* Get the image source, width, height, and whether it's intermediate. */
					//	$_img = wp_get_attachment_image_src( $aid, $size );
					//	if ( !empty( $_img ) && ( true == $_img[3] || 'full' == $size ) )
					//		$img['sizes'][ $size ] = $_img;
					//}

					$images[ $aid ] = $img;
				}
			}
		}


		/**
		 * adaptgal output
		 *
		 * @param $atts
		 * @param $content
		 *
		 */
		public function adaptgal( $atts , $content = null ) {
			extract( shortcode_atts(array(
				'postid' => false,
			), $atts));

			if ( $postid == false )
				global $post;
			else
				$post = get_post( $postid );

			$output = '';

			$images = array();
			$this->list_images_attachments ( $post, $images );
			$thumbstyles = array();
			$previewstyles = array();

			foreach ($images as $aid => $img ) {
				$std = wp_get_attachment_image_src( $aid, 'medium' );
				$thumbid = self::_thumb_prefix . $aid;
				$previewid = self::_std_prefix . $aid;
				if (!empty($img['description'])) $description = '<span class="thumb-description">'. $img['description'] .'</span>';

				$thumbs[ $aid] = '
				<li>
					<a id="'. $thumbid .'" href="#'. self::_std_prefix . $aid .'">
						'. $img['title'] .'
					</a>
				</li>';

				$previews[ $aid] = '
				<figure id="'. $previewid .'">
					<img src="'. $std[0] .'" title="'. $img['title'] .'" alt="'. $img['alttext'] . '" />
					<figcaption>'. $img['caption'] . $description .'</figcaption>
				</figure>';

				foreach ( $this->image_sizes as $resolution => $sizes ) {
					$thumbnail = wp_get_attachment_image_src( $aid, self::_thumb_prefix . $resolution );
					$thumbstyles[ $resolution ][ $aid ] = '#'.$thumbid.' { background-image: url('. $thumbnail[0] .'); }';
					$preview = wp_get_attachment_image_src( $aid, self::_std_prefix . $resolution );
					$previewstyles[ $resolution ][ $aid ] = '#'.$previewid.' { background-image: url('. $preview[0] .'); }';
				}
			}

			$cntr = 0;
			foreach ( $thumbstyles as $resolution => $backgrounds ) {
				$eq = "\n" . join( "\n", $thumbstyles[ $resolution ] ) . "\n" . join( "\n", $previewstyles[ $resolution ] );

				if ( $cntr != 0 ) {
					$mediaqueries .= '
					@media ( min-width : '. $resolution .'px ) {
						'. $eq .'
					}';
				}
				//elseif ( $cntr == ( sizeof( $thumbstyles ) -1 ) ) {
				//	$mediaqueries .= '
				//	/* "retina" machines
				//	@media ( min-width : '. $resolution .'px ) and (-webkit-min-device-pixel-ratio: 1.5),
				//			( min-width : '. $resolution .'px ) and ( min-resolution: 220dpi ) {
				//		'. $eq .'
				//	}';
				//}
				else {
					$mediaqueries .= $eq;
				}
				$cntr++;
			}

			$output = '
			<style>'. $mediaqueries .'</style>
			<section class="adaptgal">
				<nav class="adaptgal-slideshow"><a id="adaptgal-slideshow-control" href="#">]</a></nav>
				<div class="adaptgal-previews">
					'. join( "\n", $previews ) .'
					<div class="adaptgal-loading">&nbsp;</div>
				</div>
				<nav class="adaptgal-thumbs">
					<ul>'. join( "\n", $thumbs ) .'</ul>
				</nav>
			</section>
			<nav class="adaptgal-links">'. wp_nav_menu( array( 'container' => '' , 'theme_location' => self::_menu_portfolio, 'echo' => false  ) ) .'</nav>';

			$output .= "
			<script>
				jQuery(document).ready(function($) {
					var hash = window.location.hash.substring(1);
					var \$thumbs = \$('.adaptgal-thumbs ul li a');
					var \$previews = \$('.adaptgal-previews figure');
					var \$slideshow_control = \$('#adaptgal-slideshow-control');
					var slideshow_running = false;
					var slideshow_timeout = false;
					var internalclick = false;
					var slideshow_on = 'adaptgal-slideshow-on';
					var \$active = false;
					var \$loading = \$( '.adaptgal-loading' );
					function slideshow( first ) {
						internalclick = true;
						slideshow_timeout = setTimeout(slideshow, 3000);
						\$loading.animate({width:'100%'}, 3000).animate({width:'0%'}, 1);
						if ( !first ) {
							next( \$active );
						}
					}
					function next ( e ) {
						\$test = \$('a[href=\"#' + \$(e).attr('id') +'\"]').parent().next().children();
						if ( \$test.length > 0 ) {
							\$next = \$test.first();
						}
						else {
							\$next = \$thumbs.first();
						}
						\$next.trigger('click');
					}
					function prev ( e ) {
						\$test = \$('a[href=\"#' + \$(e).attr('id') +'\"]').parent().prev().children();
						if ( \$test.length > 0 ) {
							\$prev = \$test.first();
						}
						else {
							\$prev = \$thumbs.last();
						}
						\$prev.trigger('click');
					}
					\$slideshow_control.click( function (e) {
						state = !slideshow_running;
						slideshow_startstop( state );
						return false;
					});
					function slideshow_startstop  ( state ) {
						if ( !state ) {
							\$slideshow_control.removeClass ( slideshow_on );
							clearTimeout( slideshow_timeout );
							\$loading.stop(true, false).animate({width:'0%'}, 100);
						}
						else {
							\$slideshow_control.addClass ( slideshow_on );
							slideshow( true );
						}
						slideshow_running = state;
					}
					\$thumbs.click( function (event) {
						// if the click is real click, quit slideshow
						if ( !internalclick ) {
							slideshow_startstop  ( false );
						}
						\$active = \$( \$(this).attr('href') );
						\$thumbs.removeClass('adaptgal-active');
						$(this).addClass('adaptgal-active');
						location.href = $(this).attr('href');
						internalclick = false;
					});
					// swipe reactions, only one finger!
					\$previews.swipe( {
						swipeLeft:function(event, direction, distance, duration, fingerCount) {
							prev( \$(this) );
						},
						swipeRight:function(event, direction, distance, duration, fingerCount) {
							next( \$(this) );
						},
						threshold:0,
						fingers:1
					});
					// init the first element or activate the one set by anchor hash
					if ( \$active == false ) {
						if ( ! hash ) {
							\$first = \$thumbs.first();
						}
						else {
							\$first = \$('a[href=\"#' + hash +'\"]');
						}
						\$first.trigger('click');
					}
				});
			</script>";


/*
			$output .= "
			<script>
				jQuery(document).ready(function($) {
					var hash = window.location.hash.substring(1);
					var \$thumbs = $('.adaptgal-thumbs ul li a');
					var \$previews = $('.adaptgal-previews figure');
					var \$active = false;
					if ( ! hash ) {
							\$active = \$thumbs.first();
					}
					else {
						\$active = \$('a[href=\"#' + hash +'\"]');
					}
					// attach click event
					\$thumbs.click( function (event) {
						\$active = \$(this);
						\$thumbs.removeClass('adaptgal-active');
						$(this).addClass('adaptgal-active');
						location.href = $(this).attr('href');
					});
					(function Loop(){
						var traverse = function(){
							\$active.trigger('click');
						};
						setTimeout(traverse,0);
					})();
					function next ( event ) {
						alert ( 'swiped' );
						\$test = \$('a[href=\"#' + \$(this).attr('id') +'\"]').parent().next().children();
						if ( \$test.length > 0 ) {
							\$next = \$test.first();
						}
						else {
							\$next = \$thumbs.first();
						}
						\$next.trigger('click');
					}
					function prev ( event ) {
						\$test = \$('a[href=\"#' + \$(this).attr('id') +'\"]').parent().prev().children();
						if ( \$test.length > 0 ) {
							\$next = \$test.first();
						}
						else {
							\$next = \$thumbs.last();
						}
						\$next.trigger('click');
					}
					\$previews.on( 'swiperight', next() );
					\$previews.on( \"swipeleft\", function( event ) {
						\$test = \$('a[href=\"#' + \$(this).attr('id') +'\"]').parent().prev().children();
						if ( \$test.length > 0 ) {
							\$next = \$test.first();
						}
						else {
							\$next = \$thumbs.last();
						}
						\$next.trigger('click');
					} );
				});
			</script>";

*/
			return $output;
		}

	}
}

if ( !$petermolnareu_theme ) {
	$petermolnareu_theme = new petermolnareu();
}

?>
