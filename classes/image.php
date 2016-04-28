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

		$this->extra_exif = array (
			'lens' => 'LensID',
		);

		add_action( 'init', array( &$this, 'init'));

		add_action( 'delete_attachment', array (&$this, 'delete_from_cache'));
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

		// extract additional images sizes
		add_filter( 'wp_read_image_metadata', array(&$this, 'read_extra_exif'), 1, 3 );

		// insert featured image as adaptive
		add_filter( 'the_content', array( &$this, 'adaptify'), 7 );
		add_filter( 'the_content', array( &$this, 'insert_featured_image'), 10 );
		add_filter( 'image_size_names_choose', array( &$this, 'extend_image_sizes') );

		// set higher jpg quality
		add_filter( 'jpeg_quality', array( &$this, 'jpeg_quality' ) );
		add_filter( 'wp_editor_set_quality', array( &$this, 'jpeg_quality' ) );

		// sharpen resized images on upload
		add_filter( 'image_make_intermediate_size',array ( &$this, 'sharpen' ),10);

		// don't strip meta
		add_filter( 'image_strip_meta', '__return_false', 1 );

		add_filter ( 'wp_image_editors', array ( &$this, 'wp_image_editors' ));
	}

	public function wp_image_editors ( $arr ) {
		return array ( 'WP_Image_Editor_Imagick' );
	}

	/**
	 * called on attachment deletion and takes care of removing the moved files
	 *
	 */
	public static function delete_from_cache ( $aid = null ) {
		static::debug( "DELETE is called and aid is: " . $aid, 5 );
		if ($aid === null)
			return false;

		$attachment = get_post( $aid );

		if ( static::is_post($attachment)) {
			$meta = wp_get_attachment_metadata($aid);

			if (isset($meta['sizes']) && !empty($meta['sizes'])) {
				foreach ( $meta['sizes'] as $size => $data ) {
					$file = static::cachedir . DIRECTORY_SEPARATOR . $data['file'];
					if ( isset($data['file']) && is_file($file)) {
						static::debug( " removing " . $file, 5 );
						unlink ($file);
					}
				}
			}
		}

		return $aid;
	}

	/**
	 * better jpgs
	 */
	public static function jpeg_quality () {
		$jpeg_quality = (int)92;
		return $jpeg_quality;
	}

	/*
	public static function is_photo_resized( $resized ) {

		if ( empty( $resized ) )
			return false;

		$path = pathinfo ( $resized );
		$basefname = $path['filename'];
		$ext = $path['extension'];

		$pattern = '/^(.*?)-(?:\d+)x(?:\d+)$/i';
		$replacement = '$1.$2';

		$fname = preg_replace($pattern, $replacement, $basefname);
		$fname = "{$fname}{$ext}";
		pmlnr_base::debug('Testing for: ' . $fname, 4);

		global $wpdb;
		$dbname = "{$wpdb->prefix}postmeta";


		try {
			$thid = $wpdb->get_var( "SELECT `post_id` FROM `{$dbname}` WHERE `meta_key` = '_wp_attached_file' AND `meta_value` = '{$fname}' LIMIT 1");
		}
		catch (Exception $e) {
			pmlnr_base::debug('Something went wrong: ' . $e->getMessage(), 4);
		}

		if ( empty( $thid ))
			return false;

		return static::is_photo( $thid );
	}
	*/

	/**
	 * adaptive sharpen images w imagemagick
	 */
	static public function sharpen( $resized ) {

		if (!class_exists('Imagick')) {
			static::debug('Please install Imagick extension; otherwise this plugin will not work as well as it should.', 4);
			return;
		}

		if (!is_dir(static::cachedir)) {
			if (!mkdir(static::cachedir)) {
				static::debug('failed to create ' . static::cachedir, 4);
			}
		}

		/*
		preg_match ( '/(.*)-([0-9]+)x([0-9]+)\.([0-9A-Za-z]{2,4})/', $resized, $details );

		 * 0 => original var
		 * 1 => full original file path without extension
		 * 2 => resized size w
		 * 3 => resized size h
		 * 4 => extension
		 */

		$size = @getimagesize($resized);

		if ( !$size ) {
			static::debug("Unable to get size for: {$resized}", 4);
			return $resized;
		}

		//$cachedir = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'cache';

		$fname = basename( $resized );
		$cached = static::cachedir . DIRECTORY_SEPARATOR . $fname;

		if ( $size[2] == IMAGETYPE_JPEG && class_exists('Imagick')) {
			static::debug( "adaptive sharpen " . $resized, 6 );

			$watermarkfile = get_template_directory() . DIRECTORY_SEPARATOR . 'watermark.png';
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

			try {
				$imagick = new Imagick($resized);
				$imagick->unsharpMaskImage(0,0.5,1,0);
				$imagick->setImageFormat("jpg");
				$imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
				$imagick->setImageCompressionQuality(static::jpeg_quality());
				$imagick->setInterlaceScheme(Imagick::INTERLACE_PLANE);

				// only watermark my own images, others should not have this obviously
				if ( file_exists ( $watermarkfile ) && true === $is_photo ) {

					static::debug( 'watermark present and it looks like my photo, adding watermark to image ', 5 );
					$watermark = new Imagick( $watermarkfile );
					$iWidth = $imagick->getImageWidth();
					$iHeight = $imagick->getImageHeight();
					$wWidth = $watermark->getImageWidth();
					$wHeight = $watermark->getImageHeight();

					$nWidth = round($iWidth * 0.16);
					$nHeight = round($wHeight * ( $nWidth / $wWidth ));
					$watermark->scaleImage($nWidth, $nHeight);

					$x = round($iWidth - $nWidth) - round( $iWidth * 0.01 );
					$y = round($iHeight - $nHeight) - round( $iHeight * 0.01 );
					$imagick->compositeImage($watermark, imagick::COMPOSITE_OVER, $x , $y );
				}

				$imagick->writeImage($cached);
				$imagick->clear();
				$imagick->destroy();
			}
			catch (Exception $e) {
				static::debug( 'something went wrong with imagemagick: ',  $e->getMessage(), 4 );
				return $resized;
			}

			static::debug( "removing " . $resized, 5 );
			unlink ($resized);

		}
		else {
			static::debug( "moving " . $cached, 5 );
			if (copy( $resized, $cached)) {
				static::debug(  "removing " . $resized, 5 );
				unlink( $resized );
			}
			else {
				static::debug( "\tmove failed, passing on this", 4 );
			}
		}

		return $resized;
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
	 * additional EXIF which only exiftool can read
	 *
	 */
	public function read_extra_exif ( $meta, $filepath ='', $sourceImageType = '' ) {

		if (empty($filepath) || !is_file($filepath) || !is_readable($filepath)) {
			static::debug ( "{$filepath} doesn't exist" );
			return $meta;
		}

		if ( $sourceImageType != IMAGETYPE_JPEG )
			return $meta;

		$extra = $this->extra_exif;
		$rextra = array_flip($extra);

		$args = $metaextra = array();

		foreach ($extra as $metaid => $exiftoolID ) {
			if (!isset($meta[ $metaid ])) {
				$args[] = $exiftoolID;
			}
		}

		if (!empty($args)) {
			static::debug('Extracting extra EXIF for ' . $filepath );
			$cmd = 'exiftool -s -' . join(' -', $args) . ' ' . $filepath;

			exec( $cmd, $exif, $retval);

			if ($retval == 0 ) {
				foreach ( $exif as $cntr => $data ) {
					$data = explode (' : ', $data );
					$data = array_map('trim', $data);
					$metaextra[ $rextra[ $data[0] ] ] = $data[1];
				}
			}
		}

		$meta = array_merge($meta, $metaextra);

		return $meta;
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

		$meta = static::get_extended_thumbnail_meta($thid);
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

				if ( $t['src'] != $meta['src'] )
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

		//$target = static::fix_url($target);

		$class="adaptimg";
		if ( $post != null && static::is_u_photo($post)) {
			$class .=" u-photo";
		}

		if ( is_feed()) {
			$r = sprintf('<img src="%s" title="%s" alt="%s" />', $fallback['src'], $meta['image_meta']['title'], $meta['image_meta']['alt'] );
		}
		/*
		elseif (static::is_amp()) {
			$r = sprintf('
		<a href="%s">
			<amp-img src="%s" title="%s" alt="%s" srcset="%s" width="%s" height="%s" />
		</a>', $target, $fallback['src'], $meta['image_meta']['title'], $meta['image_meta']['alt'], join ( ', ', $srcset ), $fallback['width'], $fallback['height'] );
		}
		*/
		else {
			$r = sprintf('<a href="%s"><img src="%s" id="img-%s" class="adaptive %s" title="%s" alt="%s" srcset="%s" sizes="(max-width: 42em) 100vw, 60vw" /></a>', $target, $fallback['src'], $thid, $class, $meta['image_meta']['title'], $meta['image_meta']['alt'], join ( ', ', $srcset ) );
			//$r = sprintf('<a href="%s"><img src="%s" id="img-%s" class="adaptive %s" title="%s" alt="%s" srcset="%s" sizes="42em" /></a>', $target, $fallback['src'], $thid, $class, $meta['image_meta']['title'], $meta['image_meta']['alt'], join ( ', ', $srcset ) );
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
				$id = false;
				$adaptify = true;
				$alt = $markdown_images[1][$cntr];
				$url = $markdown_images[2][$cntr];
				$title = $markdown_images[3][$cntr];
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
	public function insert_featured_image ( $src ) {
		global $post;

		if (!static::is_post($post))
			return $src;

		if (!static::is_u_photo($post))
			return $src;

		if ( $cached = wp_cache_get ( $post->ID, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$thid = get_post_thumbnail_id( $post->ID );
		// this way it will get cached, thumbnail or no thumbnail as well
		if ( !empty($thid) && !is_feed() ) {
			$adaptive = $this->adaptive($thid, $post);
			$src = $src . $adaptive;
		}

		if ( static::is_photo($thid) ) {
			$src = $src . static::photo_exif( $thid, $post->ID );
		}

		wp_cache_set ( $post->ID, $src, __CLASS__ . __FUNCTION__, static::expire );

		return $src;
	}

	/**
	 *
	 */
	public static function photo_exif ( &$thid, $post_id ) {
		if (empty($thid))
			return false;

		if ( $cached = wp_cache_get ( $thid, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$return = false;

		$meta = static::get_extended_thumbnail_meta($thid);
		if ( isset($meta['image_meta']) && !empty($meta['image_meta'])) {

			$meta = $meta['image_meta'];
			$r = array();

			if ( isset($meta['camera']) && !empty($meta['camera'])) {
				$r['camera'] = '<i class="icon-camera spacer"></i>'. $meta['camera'];
				//wp_set_post_terms( $post_id, $meta['camera'] , 'exif_camera' , false );
			}

			if ( isset($meta['focal_length']) && !empty($meta['focal_length'])) {
				$r['focal_length'] = sprintf (__('<i class="icon-focallength spacer"></i>%smm'), $meta['focal_length'] );
				//wp_set_post_terms( $post_id, $meta['focal_length'] , 'exif_focal_length' , false );
			}

			if ( isset($meta['aperture']) && !empty($meta['aperture'])) {
				$r['aperture'] = sprintf ( __('<i class="icon-aperture spacer"></i>f/%s'), $meta['aperture']);
				//wp_set_post_terms( $post_id, $meta['aperture'] , 'exif_aperture' , false );
			}

			if ( isset($meta['shutter_speed']) && !empty($meta['shutter_speed'])) {
				if ( (1 / $meta['shutter_speed'] ) > 1) {
					$shutter_speed = "1/";
					if ((number_format((1 / $meta['shutter_speed']), 1)) == 1.3 or
						number_format((1 / $meta['shutter_speed']), 1) == 1.5 or
						number_format((1 / $meta['shutter_speed']), 1) == 1.6 or
						number_format((1 / $meta['shutter_speed']), 1) == 2.5)
							$shutter_speed .= number_format((1 / $meta['shutter_speed']), 1, '.', '');
					else
						$shutter_speed .= number_format((1 / $meta['shutter_speed']), 0, '.', '');
				}
				else {
					$shutter_speed = $meta['shutter_speed'];
				}
				$r['shutter_speed'] = sprintf( __('<i class="icon-clock spacer"></i>%s sec'), $shutter_speed);
				//wp_set_post_terms( $post_id, $shutter_speed , 'exif_shutter_speed' , false );
			}

			if ( isset($meta['iso']) && !empty($meta['iso'])) {
				$r['iso'] = sprintf (__('<i class="icon-sensitivity spacer"></i>ISO %s'), $meta['iso'] );
				//wp_set_post_terms( $post_id, $meta['iso'] , 'exif_iso' , false );
			}

			if ( isset($meta['lens']) && !empty($meta['lens'])) {
				$r['iso'] = sprintf (__('<i class="icon-lens spacer"></i>%s'), $meta['lens'] );
			}

			$location = '';
			if ( isset($meta['geo_latitude']) && !empty($meta['geo_latitude']) && isset($meta['geo_longitude']) && !empty($meta['geo_longitude'])) {
				$location = sprintf ( __('<i class="icon-location spacer"></i><a href="http://maps.google.com/?q=%s,%s"><span class="h-geo geo p-location"><span class="p-latitude">%s</span>, <span class="p-longitude">%s</span></span></a>'), $meta['geo_latitude'], $meta['geo_longitude'], $meta['geo_latitude'], $meta['geo_longitude'] );

				$r['location'] = $location;
			}

			$return = '<aside class="exif"><ul><li>' . join('</li><li>',$r) . '</li></ul></aside>';


		}



		wp_cache_set ( $thid, $return, __CLASS__ . __FUNCTION__, static::expire );

		return $return;
	}

}
