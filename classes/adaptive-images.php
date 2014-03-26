<?php

class adaptive_images {

	/*
	 * here I define a matrix of desired picture sizes for
	 * a specific minimal screen width pixel number
	 *
	 * the exact numbers are mostly outcome of experiments and
	 * calculations
	 *
	 * the actual size is always scaled, these are only important
	 * not to strech scaling too much
	 *
	 */

	const a_thumb = 'th'; /* for thumbnails */
	const a_stnd = 'stnd'; /* for in-text images */
	const a_hd = 'hd';  /* for large images */

	const cache_group = 'adaptive_images';
	const cache_time = 86400;

	const default_prefix = 'adaptive';

	var $theme = null;
	var $sharesize = '';

	public function __construct ( $theme = null ) {

		$this->theme = $theme;

		$this->image_sizes = array (
			/* legacy, below 720px */
			460 => array (
				self::a_thumb => 80,
				self::a_stnd => 120,
				self::a_hd => 640,
			),
			/* normal, between 720px and 1200px*/
			720 => array (
				self::a_thumb => 160,
				self::a_stnd => 240,
				self::a_hd => 1024,
			),
			/* anything larger than fullHD */
			1200 => array (
				self::a_thumb => 210,
				self::a_stnd => 320,
				self::a_hd => 1200,
			)
		);

		$this->sharesize = self::a_hd . "720";
	}


	/* init function, should be used in the theme init loop */
	public function init (  ) {

		/* set & register image sizes
		 * will register all the sizes from above
		*/
		foreach ( $this->image_sizes as $resolution => $sizes ) {
			foreach ( $sizes as $prefix => $size ) {
				$crop = ( $prefix == self::a_thumb )? true : false;
				add_image_size (
					$prefix . $resolution, // name
					$sizes[ $prefix ], // width
					$sizes[ $prefix ], // height
					$crop
				);
			}
		}

		add_shortcode('adaptive_gal', array ( &$this, 'adaptgal' ) );
		add_shortcode('adaptive_img', array ( &$this, 'adaptimg' ) );

		$post_types = get_post_types( );
		/* cache invalidation hooks */
		foreach ( $post_types as $post_type ) {
			add_action( 'new_to_publish_' .$post_type , array( &$this , 'cclear' ), 0 );
			add_action( 'draft_to_publish' .$post_type , array( &$this , 'cclear' ), 0 );
			add_action( 'pending_to_publish' .$post_type , array( &$this , 'cclear' ), 0 );
			add_action( 'private_to_publish' .$post_type , array( &$this , 'cclear' ), 0 );
			add_action( 'publish_' . $post_type , array( &$this , 'cclear' ), 0 );
		}

		/* invalidation on some other ocasions as well */
		add_action( 'deleted_post', array( &$this , 'cclear' ), 0 );
		add_action( 'edit_post', array( &$this , 'cclear' ), 0 );

	}

	/* initialization for any shortcode function;
	 * getting postid
	 */
	private function _init ( &$atts ) {

		extract( shortcode_atts(array(
			'postid' => false,
		), $atts));

		if ( $postid == false )
			global $post;
		else
			$post = get_post( $postid );

		return $post;
	}

	/* adaptive gallery shortcode function */
	public function adaptgal( $atts , $content = null ) {

		$post = $this->_init ( $atts );

		$ckey = $post->ID;
		$cached = wp_cache_get( $ckey, self::cache_group );
		if ( $cached != false ) {
			$images = $cached['images'];
			$bgdata = $cached['bgdata'];
			$css = $cached['css'];
		}
		else {
			$images = $this->image_attachments ( $post );
			$bgdata = $this->bgdata ( array_keys( $images ) );
			$css = $this->build_css ( $bgdata );

			$cache = array (
				'images' => $images,
				'bgdata' => $bgdata,
				'css' => $css
			);

			wp_cache_set( $ckey, $cache, self::cache_group, self::cache_time );
		}

		$single = ( sizeof( $images ) <= 10) ? ' single' : '';

		foreach ($images as $imgid => $img ) {

			$th_id = self::default_prefix . "-" . $imgid . "-" . self::a_thumb;
			$src_id = self::default_prefix . "-" . $imgid . "-" . self::a_hd;
			$src_src = $bgdata[ array_shift( array_keys($this->image_sizes) ) ][ $imgid ][ self::a_hd ];

			//$description = ( !empty($img['description']) ) ? '<span class="thumb-description">'. $img['description'] .'</span>' : '';
			if ( $this->theme != null ) {
				$s = wp_get_attachment_image_src( $imgid, $this->sharesize );
				$caption = $this->theme->share( $s[0], $img['title'], false, get_permalink( $post ) );
			}
			else {
				$caption = '';
			}

			$th_list[ $imgid ] = '<li><a id="'. $th_id .'" href="#'. $src_id .'">'. $img['title'] .'</a></li>';

			$src_list[ $imgid ] = '<figure id="'. $src_id .'">
				<img src="'. $src_src .'" title="'. $img['title'] .'" alt="'. $img['alttext'] . '" />
				<figcaption>'.$caption.'</figcaption>
			</figure>';

		}

		$output = '
		<section class="adaptgal" id="adaptgal-'. $post->ID.'">
			'. $css .'
			<div class="adaptgal-previews">'. join( "", $src_list ) .'</div>
			<nav class="adaptgal-thumbs'. $single .'"><ul>'. join( "", $th_list ) .'</ul></nav>
			<br class="clear" />
		</section>';

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery.touchSwipe' );
		wp_enqueue_script( 'jquery.adaptive-images' );

		return $output;
	}


	/* adaptive gallery shortcode function */
	public function adaptimg( $atts , $content = null ) {

		extract( shortcode_atts(array(
			'aid' => false,
		), $atts));

		if ( empty ( $aid ) )
			return false;

		$ckey = $aid;
		$cached = wp_cache_get( $ckey, self::cache_group );
		if ( $cached != false ) {
			$images = $cached['images'];
			$bgdata = $cached['bgdata'];
			$css = $cached['css'];
		}
		else {
			$img = array();
				$_post = get_post( $aid );
				/* set the titles and alternate texts */
				$img['title'] = esc_attr($_post->post_title);
				$img['alttext'] = strip_tags ( get_post_meta($_post->id, '_wp_attachment_image_alt', true) );
				$img['caption'] = esc_attr($_post->post_excerpt);
				$img['description'] = esc_attr($_post->post_content);
			$images[ $aid ] = $img;
			$bgdata = $this->bgdata ( array_keys( $images ), self::a_stnd );
			$css = $this->build_css ( $bgdata );

			$cache = array (
				'images' => $images,
				'bgdata' => $bgdata,
				'css' => $css
			);

			wp_cache_set( $ckey, $cache, self::cache_group, self::cache_time );
		}

		$_id = self::default_prefix . "-" . $aid . "-" . self::a_stnd;
		$_src = $bgdata[ array_shift( array_keys($this->image_sizes) ) ][ $aid ][ self::a_stnd ];

		return $css .'<figure id="'. $_id .'"><img src="'. $_src .'" title="'. $img['title'] .'" alt="'. $img['alttext'] . '" /></figure>';
	}

	/* get all image attachments for a post
	 *
	 * @param $post WordPress post object
	 *
	 * @return array of images, key is attachment id
	*/
	private function image_attachments ( &$post ) {
		$images = array();

		/* get image type attachments for the post by ID */
		$attachments = get_children( array (
			'post_parent'=>$post->ID,
			'post_type'=>'attachment',
			'post_mime_type'=>'image',
			'orderby'=>'menu_order',
			'order'=>'asc'
		) );

		if ( !empty($attachments) ) {
			foreach ( $attachments as $imgid => $attachment ) {
				$img = array();
				$_post = get_post( $imgid );

				/* set the titles and alternate texts */
				$img['title'] = esc_attr($_post->post_title);
				$img['alttext'] = strip_tags ( get_post_meta($_post->id, '_wp_attachment_image_alt', true) );
				$img['caption'] = esc_attr($_post->post_excerpt);
				$img['description'] = esc_attr($_post->post_content);
				$images[ $imgid ] = $img;
			}
		}

		return $images;
	}

	/* creates the required CSS for a specified set of images
	 *
	 * @param $bgdata return of $this->bgdata array for set of attachments
	 * @param $cssprefix replace the default prefixing of elements
	 *
	 */
	private function build_css ( &$bgdata, $cssprefix=self::default_prefix ) {

		/* css naming conventions */
		$naming = '#' . $cssprefix . "-";
		$ctr=0;
		$mq = '';

		/* join the backgrounds into areas of CSS media queries */
		foreach ( $bgdata as $resolution => $imgdata ) {

			unset ( $imgcss );
			foreach ( $imgdata as $imgid => $sizes ) {
				foreach ( $sizes as $prefix => $url ) {
					$imgcss .= $naming . $imgid . '-' . $prefix . ' { background-image: url('. $url .'); }';
				}
			}
			if ( $ctr == 0 ) {
				$mq .= $imgcss;
			}
			elseif ( $ctr != ( sizeof ( $this->image_sizes ) - 1 ) ) {
				$mq .= '@media ( min-width : '. $resolution .'px ) and ( max-width : '. ( $resolutions[$ctr+1] ) .'px ) {
						'. $imgcss .'
					}';
			}
			else {
				$mq .= '@media ( min-width : '. $resolution .'px ) {
						'. $imgcss .'
					}';
			}
			$ctr++;
		}

		$mq =  '<style scoped="scoped">' . $mq . '</style>';
		return $mq;

	}

	/* collects attachment image url for all required resolution & site to an array
	 *
	 * @param $imgids array of attachment IDs to get data for
	 * @param $sizeonly either string or key-based array for sizes to include
	 *
	 * @return array of image data resolution -> attachment id -> prefix -> url
	 */
	private function bgdata ( &$imgids, $sizeonly=null ) {

		if ( $sizeonly != null ) {
			if ( ! is_array ( $sizeonly ) )
				$_only [ $sizeonly ] = 1;
			else
				$_only = $sizeonly;
		}
		else {
			$_only = array_pop ( $this->image_sizes );
		}

		/* alway use arrays, easier */
		if ( ! is_array ( $imgids ) ) $imgids = array ( $imgids );

		/* build array per resolution of every image with as background for that size */
		foreach ( $imgids as $imgid ) {
			foreach ( $this->image_sizes as $resolution => $sizes ) {
				foreach ( $sizes as $prefix => $size ) {
					if ( array_key_exists ( $prefix, $_only ) ) {
						$img = wp_get_attachment_image_src( $imgid, $prefix . $resolution );
						$__bgimages[$resolution][$imgid][$prefix] = $img[0];
					}
				}
			}
		}

		return $__bgimages;

	}


	public function cclear ( $post_id = false, $force = false ) {

		/* exit if no post_id is specified */
		if ( empty ( $post_id ) && $force === false ) {
			return false;
		}

		wp_cache_delete ( $post_id, self::cache_group );

	}

	// TODO
	//public function clean () {
	//
	//}
}


?>
