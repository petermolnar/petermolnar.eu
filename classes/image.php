<?php

class pmlnr_image extends pmlnr_base {

	const prefix = 'adaptive_';
	const sizes = '360,540,720,980,1280';

	private $dpx = array();
	private $extra_exif = array();

	const cachedir = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'cache';

	/**
	 *
	 */
	public function __construct ( ) {
		$sizes = explode(',',static::sizes);

		$cntr = 1;
		foreach ($sizes as $size) {
			$this->dpix[$cntr++] = $size;
		}

		add_action( 'init', array( &$this, 'init'));
	}

	/**
	 *
	 */
	public static function exif_types () {
		return array ( 'camera', 'focal_length', 'shutter_speed', 'iso', 'aperture' );
	}

	/**
	 * init function, should be used in the theme init loop
	 */
	public function init (  ) {

		// additional image sizes for adaptiveness
		foreach ( $this->dpix as $dpix => $size )
			add_image_size ( static::prefix . $dpix, $size, $size, false );


		// adaptify all the things
		add_filter( 'the_content', array( &$this, 'adaptify'), 7 );

		// no need; part of the content from now on
		//add_filter( 'the_content', array( &$this, 'insert_featured_image'), 2 );

		add_filter( 'image_size_names_choose', array( &$this, 'extend_image_sizes') );

		add_filter( 'wp_resized2cache_imagick',array ( &$this, 'watermark' ),10, 2);




		add_filter ( 'wp_image_editors', function ( $arr ) {
			return array ( 'WP_Image_Editor_Imagick' );
		} );

		add_filter( 'wp_get_attachment_metadata', array ( &$this, 'extend_attachment_meta' ), 1, 2 );

	}

	/**
	 *
	 */
	public static function extend_attachment_meta ( $meta, $thid ) {

		if ( $cached = wp_cache_get ( $thid, __CLASS__ . __FUNCTION__ ) )
			return $cached;


		$attachment = get_post( $thid );

		if ( false === static::is_post( $attachment ) )
			return $meta;

		if ( !isset( $meta['file'] ) || empty( $meta['file'] ) )
			return $meta;

		if ( !empty ( $attachment->post_parent ) )
			$meta['parent'] = $attachment->post_parent;

		$try = array ( 'geo_latitude', 'geo_longitude', 'geo_altitude' );
		foreach ( $try as $kw )
			if ( empty ( $meta['image_meta'][ $kw ] ) )
					$meta['image_meta'][ $kw ] = get_post_meta( $attachment->post_parent, $kw, true );

		$wp_upload_dir = wp_upload_dir();
		$meta['src'] = site_url ( $wp_upload_dir['baseurl'] . '/' . $meta['file'] );

		if ( isset( $meta['sizes'] ) && ! empty( $meta['sizes'] ) ) {
			foreach ( $meta['sizes'] as $size => $data ) {
				$meta['sizes'][$size]['src'] = site_url ( $wp_upload_dir['baseurl'] . '/' . $data['file'] );
				$meta['sizes'][$size]['path'] = $wp_upload_dir['basedir'] . DIRECTORY_SEPARATOR . $data['file'];

				$meta['sizes'][$size]['src_c'] = site_url ( "cache/{$size}/{$meta['file']}" );
				$meta['sizes'][$size]['path_c'] = WP_CONTENT_DIR . DIRECTORY_SEPARATOR
					. 'cache' . DIRECTORY_SEPARATOR
					. $size . DIRECTORY_SEPARATOR
					. $meta['file'];
			}
		}

		if ( empty($meta['image_meta']['title']))
			$meta['image_meta']['title'] = esc_attr( $attachment->post_title );

		$slug = sanitize_title ( $meta['image_meta']['title'] , $thid );
		if ( is_numeric( substr( $slug, 0, 1) ) )
			$slug = 'img-' . $slug;
		$meta['image_meta']['slug'] = $slug;

		$meta['image_meta']['alt'] = '';
		$alt = get_post_meta($thid, '_wp_attachment_image_alt', true);
		if ( !empty($alt))
			$meta['image_meta']['alt'] = strip_tags( $alt );

		wp_cache_set ( $thid, $meta, __CLASS__ . __FUNCTION__, static::expire );

		return $meta;
	}

	/***
	 *
	 */
	public function watermark ( $imagick, $resized ) {

		if (!class_exists('Imagick')) {
			static::debug('Please install Imagick extension; otherwise this plugin will not work as well as it should.', 4);
			return $imagick;
		}

		$watermarkfile = get_template_directory() . DIRECTORY_SEPARATOR . 'watermark.png';
		if ( ! file_exists ( $watermarkfile ) ) {
			static::debug( "no watermark file present at {$watermarkfile}", 6);
			return $imagick;
		}


		$meta = wp_read_image_metadata ( $resized );
		$yaml = static::get_yaml();
		$is_photo = false;

		if (isset($meta['copyright']) && !empty($meta['copyright']) ) {
			foreach ( $yaml['copyright'] as $str ) {
				if ( stristr($meta['copyright'], $str) ) {
					$is_photo = true;
				}
			}
		}

		if ( isset($meta['camera']) && !empty($meta['camera']) && in_array(trim($meta['camera']), $yaml['cameras'])) {
			$is_photo = true;
		}

			// only watermark my own images, others should not have this obviously
		if ( false === $is_photo ) {
			static::debug( "this is not a photo of mine", 6);
			return $imagick;
		}

		static::debug( 'watermark present and it looks like my photo, adding watermark to image ', 5 );
		$watermark = new Imagick( $watermarkfile );
		$iWidth = $imagick->getImageWidth();
		$iHeight = $imagick->getImageHeight();
		$wWidth = $watermark->getImageWidth();
		$wHeight = $watermark->getImageHeight();

		$rotate = ( $iHeight < $iWidth ) ? false : true;

		if ( false == $rotate ) {
			$nWidth = round( $iWidth * 0.16 );
			$nHeight = round( $wHeight * ( $nWidth / $wWidth ) );
			$x = round( $iWidth - $nWidth) - round( $iWidth * 0.01 );
			$y = round( $iHeight - $nHeight) - round( $iHeight * 0.01 );
		}
		else {
			$nWidth = round( $iHeight * 0.16 );
			$nHeight = round( $wHeight * ( $nWidth / $wWidth ) );
			$x = round( $iWidth - $nHeight ) - round( $iWidth * 0.01 );
			$y = round( $iHeight - $nWidth ) - round( $iHeight * 0.01 );
		}

		$watermark->scaleImage( $nWidth, $nHeight );

		if ( $rotate ) {
			$watermark->rotateImage(new ImagickPixel('none'), -90);
		}

		$imagick->compositeImage($watermark, imagick::COMPOSITE_OVER, $x , $y );
		$watermark->clear();
		$watermark->destroy();
		return $imagick;
	}

	/***
	 *
	 */
	public function extend_image_sizes ( $existing ) {
		$a = array();
		foreach ( $this->dpix as $dpix => $size )
			$a[ static::prefix . $dpix ] = "{$size} x {$size}, crop: 0";

		return array_merge( $existing, $a );
	}

	/**
	 * adaptive image shortcode function
	 *
	 */
	public function adaptive( &$thid, $post = null, $max = null ) {
		if (empty($thid))
			return false;

		$post = static::fix_post($post);

		if ($post === false)
			return false;

		//$meta = static::get_extended_thumbnail_meta($thid);
		$meta = wp_get_attachment_metadata( $thid );
		if (empty($meta['sizes']))
			return false;

		if ( $cached = wp_cache_get ( $thid, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		// ultimate fallback to thumbnail, that has to exist
		$fallback = $meta['sizes']['thumbnail'];

		if ( !empty($max) && isset($meta['sizes'][$max]) && isset($meta['sizes'][$max]['src']) && !empty($meta['sizes'][$max]['src']) ) {
			$fallback = $meta['sizes'][$max]['src'];
		}
		else {
			$try = array ( static::prefix . '1', 'medium', static::prefix . '2' );
			foreach ( $try as $test ) {

				if (isset($meta['sizes'][$test]['src']) && !empty($meta['sizes'][$test]['src']))
					$t = $meta['sizes'][$test];
				else
					continue;

				if ( isset( $t['src']) && isset( $meta['src'] ) &&
					$t['src'] != $meta['src'] )
					$fallback = $t;
			}
		}

		$as = $this->dpix;
		$srcset = array();
		foreach ( $this->dpix as $dpix => $size ) {
			$id = static::prefix . $dpix;
			if (isset($meta['sizes'][$id]['src']) && !empty($meta['sizes'][$id]['src']))
				$srcset[] = $meta['sizes'][$id]['src'] . ' ' . ( $as[$dpix] ) . "w";
				//$srcset[] = $meta['sizes'][$id]['src'] . ' ' . $dpix ."x";
		}

		if ( isset($meta['parent']) && !empty($meta['parent']) && $post != null && static::is_post($post) && ( $meta['parent'] != $post->ID || !is_singular()) ) {
			$target = get_permalink ( $meta['parent'] );
		}
		else {
			$r = array_reverse($this->dpix,true);
			foreach ( $r as $id => $size ) {
				$n = static::prefix . $id;
				if ( isset($meta['sizes'][$n]) && !empty($meta['sizes'][$n])) {
					$target = $meta['sizes'][$n]['src'];
					break;
				}
			}

		}

		if (!isset($target) || empty($target)) {
			static::debug('now, this should not happen: ' . $post->ID .' wanted adaptification and did not find $target');
			return false;
		}

		$class="";
		if ( $post != null && static::is_u_photo($post)) {
			$class = "u-photo";
		}

		// TODO: link $fallback['src'] to cache/original image name

		if ( is_feed()) {
			$r = sprintf('<img src="%s" title="%s" alt="%s" />', $fallback['src'], $meta['image_meta']['title'], $meta['image_meta']['alt'] );
		}
		else {
			$r = sprintf('<a class="%s" href="%s"><img src="%s" id="img-%s" class="adaptive adaptimg" title="%s" alt="%s" srcset="%s" /></a>', $class, $target, $fallback['src'], $thid, $meta['image_meta']['title'], $meta['image_meta']['alt'], join ( ', ', $srcset ) );
		}

		wp_cache_set ( $thid, $r, __CLASS__ . __FUNCTION__, static::expire );

		return $r;
	}

	/**
	 * adaptify all images
	 */
	public function adaptify( $html ) {
		if (empty($html))
			return $html;

		$hash = sha1( $html );
		if ( $cached = wp_cache_get ( $hash, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		// match all wp inserted images
		$inline_images = static::extract_wp_images( $html );


		//preg_match_all("/<img.*wp-image-(\d*)[^\>]*>/", $html, $inline_images);

		if ( !empty ( $inline_images[0]  )) {
			foreach ( $inline_images[0] as $cntr => $imgstr ) {
				$thid = $inline_images[1][$cntr];
				$adaptive = $this->adaptive($thid);
				//$r = '[adaptimg aid=' . $aid .' share=0 standalone=1]';
				$html = str_replace ( $imgstr, $adaptive, $html );
			}
		}

		// match all markdown images
		$markdown_images = static::extract_md_images( $html );

		if ( !empty ( $markdown_images[0]  )) {
			$excludes = array ( '.noadapt', '.alignleft', '.alignright' ,'u-photo', 'avatar' );
			foreach ( $markdown_images[0] as $cntr=>$imgstr ) {
				//static::debug ( $markdown_images );
				$id = false;
				$adaptify = true;
				if ( preg_match( '/.*\.gif$/i', $markdown_images[3][$cntr] ) )
					continue;

				$meta = explode(' ', $markdown_images[4][$cntr]);

				foreach ( $meta as $val ) {
					if ( strstr($val, '#')) {
						$id = trim( $val, "#");
						if ( strstr( $id, 'img-'))
							$id = str_replace ( 'img-', '', $id );
					}
					if ( in_array($val, $excludes )) $adaptify = false;
				}

				if ($id && $adaptify) {
					$adaptive = $this->adaptive($id);
					//$r = '[adaptimg aid=' . $id .']';
					$html = str_replace ( $imgstr, $adaptive, $html );
				}
			}
		}

		wp_cache_set ( $hash, $html, __CLASS__ . __FUNCTION__, static::expire );

		return $html;
	}

	/**
	 *
	 */
	public static function extract_wp_images( &$text ) {
		$matches = array();
		preg_match_all("/<img.*wp-image-(\d*)[^\>]*>/", $text, $matches);

		return $matches;
	}

	/**
	 *
	 */
	public static function extract_md_images( &$text ) {
		$matches = array();
		preg_match_all('/\!\[(.*?)\]\((.*?) ?"?(.*?)"?\)\{(.*?)\}/is', $text, $matches);

		return $matches;
	}

}
