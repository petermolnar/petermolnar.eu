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
			'geo_latitude' => 'GPSLatitude',
			'geo_longitude' => 'GPSLongitude',
			'geo_altitude' => 'GPSAltitude',
			'title' => 'Title',
		);

		add_action( 'init', array( &$this, 'init'));
	}

	public static function ascii_image ( $thid ) {
		if ( empty( $thid ) )
			return false;

		static::debug ( "getting  ASCII for $thid", 7);
		$cached = get_post_meta ( $thid, 'ascii', true );

		if ( $cached )
			return $cached;

		$src = wp_get_attachment_image_src ( $thid, 'full' );

		if ( empty ( $src ) )
			return false;

		$wp_upload_dir = wp_upload_dir();
		$fname = explode( '/', $src[0] );
		$fname = end( $fname );
		$path = $wp_upload_dir['basedir'] . DIRECTORY_SEPARATOR . $fname;
		$cmd = '/usr/src/img2txt/img2txt.py --targetAspect 0.5 ' . $path;

		static::debug ( "getting  ASCII for {$path}", 5);

		exec( $cmd, $ascii, $retval);

		if ($retval == 0 ) {
			$ascii = preg_replace ( '/.*?pre>(.*?)[\n\r]<\/.*/ms', '$1', join("\n", $ascii ) );

			add_post_meta ( $thid, 'ascii', $ascii, true);
		}
		else {
			static::debug ( "return code: {$retval}, with message: " . json_encode($ascii), 4);
		}

		return $ascii;
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
		add_filter( 'the_content', array( &$this, 'insert_featured_image'), 2 );
		add_filter( 'image_size_names_choose', array( &$this, 'extend_image_sizes') );

		add_filter( 'wp_resized2cache_imagick',array ( &$this, 'watermark' ),10, 2);

		add_filter ( 'wp_image_editors', array ( &$this, 'wp_image_editors' ));

		add_filter ( 'wp_flatexport_post', array ( &$this, 'flatexport_exif' ), 31, 2 );
		add_filter ( 'wp_flatexport_featured_image', array ( &$this, 'flatexport_featured_image' ), 1, 2 );
	}

	/**
	 *
	 */
	public function flatexport_featured_image ( $text, $post ) {
		if ( ! static::is_post ( $post ) )
			return $text;

		if (!static::is_u_photo($post))
			return $text;

		if ( $cached = wp_cache_get ( $post->ID, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$thid = get_post_thumbnail_id( $post->ID );
		$return = $text;

		if ( !empty($thid) ) {
			//$ascii = static::ascii_image ( $thid );
			//$return = "\n\n" . trim( $text ) . "\n```asciiphoto\n" . $ascii . "\n```";

			$return = "\n\n" . trim( $text ) . "\n";

		}

		wp_cache_set ( $post->ID, $return, __CLASS__ . __FUNCTION__, static::expire );

		return $return;
	}


	/**
	 *
	 */
	public function flatexport_exif ( $text, $post ) {
		if ( ! static::is_post ( $post ) )
			return $text;

		if (!static::is_post($post))
			return $text;

		if (!static::is_u_photo($post))
			return $text;

		if ( $cached = wp_cache_get ( $post->ID, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$thid = get_post_thumbnail_id( $post->ID );
		$return = $text;

		if ( !empty($thid) ) {
			$meta = static::get_extended_thumbnail_meta( $thid );
			if ( isset($meta['image_meta']) && !empty($meta['image_meta'])) {
				$meta = $meta['image_meta'];
				$r = array();

				if ( isset($meta['camera']) && !empty($meta['camera']))
					$r['camera'] = $meta['camera'];

				if ( isset($meta['focal_length']) && !empty($meta['focal_length']))
					$r['focal length'] = sprintf (__('%smm'), $meta['focal_length'] );

				if ( isset($meta['aperture']) && !empty($meta['aperture']))
					$r['aperture'] = sprintf ( __('f/%s'), $meta['aperture']);

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
					$r['shutter speed'] = sprintf( __('%s sec'), $shutter_speed);
				}

				if ( isset($meta['iso']) && !empty($meta['iso']))
					$r['ISO'] = $meta['iso'];

				if ( isset($meta['lens']) && !empty($meta['lens']))
					$r['lens'] =  $meta['lens'];
			}

			$return .=  "\n\nEXIF\n----\n";

			foreach ( $r as $name => $value ) {
				$return .= "- {$name}: {$value}\n";
			}

		}

		$return = rtrim( $return );

		wp_cache_set ( $post->ID, $return, __CLASS__ . __FUNCTION__, static::expire );

		return $return;

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
		if ( ! file_exists ( $watermarkfile ) )
			return $imagick;


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
		if ( false === $is_photo )
			return $imagick;

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
	 * if imagemagick doesn't work, please break
	 */
	public function wp_image_editors ( $arr ) {
		return array ( 'WP_Image_Editor_Imagick' );
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
			$cmd = 'exiftool -s -' . join(' -', $args) . ' ' . $filepath;
			static::debug('Extracting extra EXIF for ' . $filepath . ' with command ' . $cmd );

			exec( $cmd, $exif, $retval);

			if ($retval == 0 ) {
				foreach ( $exif as $cntr => $data ) {
					$data = explode (' : ', $data );
					$data = array_map('trim', $data);
					if ( $data[0] == 'GPSLatitude' || $data[0] == 'GPSLongitude' )
						$data[1] = static::exif_gps2dec( $data[1] );
					elseif ( $data[0] == 'GPSAltitude' )
						$data[1] = static::exif_gps2alt( $data[1] );

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

		$class="";
		if ( $post != null && static::is_u_photo($post)) {
			$class = "u-photo";
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
			$r = sprintf('<a class="%s" href="%s"><img src="%s" id="img-%s" class="adaptive adaptimg" title="%s" alt="%s" srcset="%s" sizes="(max-width: 42em) 100vw, 60vw" /></a>', $class, $target, $fallback['src'], $thid, $meta['image_meta']['title'], $meta['image_meta']['alt'], join ( ', ', $srcset ) );
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

		// add the image itself; prefer markdown
		if ( !empty($thid) && !is_feed() ) {
			$meta = static::get_extended_thumbnail_meta( $thid );

			$adaptive = "![{$meta['image_meta']['title']}]({$meta['src']}){#img-{$thid}}";

			$src = $src . "\n\n" . $adaptive . "\n";

		}

		// add exif; is_u_photo is checked already
		$src = $src . static::photo_exif( $thid, $post->ID );

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

			if ( isset($meta['camera']) && !empty($meta['camera']))
				$r['camera'] = '<i class="icon-camera spacer"></i>'. $meta['camera'];

			if ( isset($meta['focal_length']) && !empty($meta['focal_length']))
				$r['focal_length'] = sprintf (__('<i class="icon-focallength spacer"></i>%smm'), $meta['focal_length'] );

			if ( isset($meta['aperture']) && !empty($meta['aperture']))
				$r['aperture'] = sprintf ( __('<i class="icon-aperture spacer"></i>f/%s'), $meta['aperture']);

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
			}

			if ( isset($meta['iso']) && !empty($meta['iso']))
				$r['iso'] = sprintf (__('<i class="icon-sensitivity spacer"></i>ISO %s'), $meta['iso'] );

			if ( isset($meta['lens']) && !empty($meta['lens']))
				$r['lens'] = sprintf (__('<i class="icon-lens spacer"></i>%s'), $meta['lens'] );


			$location = '';
			if ( isset($meta['geo_latitude']) && !empty($meta['geo_latitude']) && isset($meta['geo_longitude']) && !empty($meta['geo_longitude'])) {
				$location = sprintf ( __('<i class="icon-location spacer"></i><a href="http://maps.google.com/?q=%s,%s"><span class="h-geo geo p-location"><span class="p-latitude">%s</span>, <span class="p-longitude">%s</span></span></a>'), $meta['geo_latitude'], $meta['geo_longitude'], $meta['geo_latitude'], $meta['geo_longitude'] );

				$r['location'] = $location;
			}

			if ( isset($meta['created_timestamp']) && !empty($meta['created_timestamp']))
				$r['timestamp'] = sprintf (__('<i class="icon-clock spacer"></i>%s'), date( "r", $meta['created_timestamp'] ) );

			$return = '<aside class="exif"><ul><li>' . join('</li><li>',$r) . '</li></ul></aside>';
		}



		wp_cache_set ( $thid, $return, __CLASS__ . __FUNCTION__, static::expire );

		return $return;
	}

	/**
	 *
	 */
	public static function exif_gps2dec ( $string ) {
		//103 deg 20' 38.33" E
		preg_match( "/([0-9.]+)\s?+deg\s?+([0-9.]+)'\s?+([0-9.]+)\"\s?+([NEWS])/", trim($string), $matches );

		$dd = $matches[1] + ( ( ( $matches[2] * 60 ) + ( $matches[3] ) ) / 3600 );
		if ( $matches[4] == "S" || $matches[4] == "W" )
			$dd = $dd * -1;
		return round($dd,6);
	}

	/**
	 *
	 */
	public static function exif_gps2alt ( $string ) {
		//2062.6 m Above Sea Level
		preg_match( "/([0-9.]+)\s?+m/", trim($string), $matches );

		$alt = $matches[1];
		if ( stristr( $string, 'below') )
			$alt = $alt * -1;
		return $alt;
	}

	/**
	 *
	 */
	public static function autotag_by_photo ( $post ) {
		static::debug ( "autotag triggered");
		$post = static::fix_post($post);

		if ( false === $post ) {
			static::debug ( "false post");
			return false;
		}

		$thid = get_post_thumbnail_id( $post->ID );

		if ( empty($thid) ) {
			static::debug ( "not thid");
			return false;
		}

		$meta = static::get_extended_thumbnail_meta ( $thid );

		if ( isset( $meta['image_meta'] ) && isset ( $meta['image_meta']['keywords'] ) && !empty( $meta['image_meta']['keywords'] ) ) {

			$keywords = $meta['image_meta']['keywords'];

			// add photo tag
			$keywords[] = 'photo';

			if ( isset ( $meta['image_meta']['camera'] ) && ! empty ( $meta['image_meta']['camera'] ) ) {

				// add camera
				$keywords[] = $meta['image_meta']['camera'];

				// add camera manufacturer
				if ( strstr( $meta['image_meta']['camera'], ' ' ) ) {
					$manufacturer = ucfirst ( strtolower ( substr ( $meta['image_meta']['camera'], 0, strpos( $meta['image_meta']['camera'], ' ') ) ) ) ;
					$keywords[] = $manufacturer;
				}

			}

			static::add_tags ( $post, $keywords );

		}

		// content
		if ( empty ( $post->post_content ) && ! empty( $meta['image_meta']['caption'] ) ) {
			static::debug ( "appending post #{$post->ID} content with image caption" );
			$modcontent = $meta['image_meta']['caption'];
			static::replace_content ( $post, $modcontent );
			$post->post_content = $modcontent;
		}

		// content
		if ( empty ( $post->post_title ) && ! empty( $meta['image_meta']['title'] ) ) {
			static::debug ( "appending post #{$post->ID} title with image caption" );
			static::replace_title ( $post, $meta['image_meta']['title'] );
			$post->post_title = $meta['image_meta']['title'];
		}

		// GPS
		$try = array ( 'geo_latitude', 'geo_longitude', 'geo_altitude' );
		foreach ( $try as $kw ) {
			$curr = get_post_meta ( $post->ID, $kw, true );
			static::debug("Current {$kw} for {$post->ID} is: ${curr}");

			if ( isset ( $meta['image_meta'][ $kw ] ) && !empty( $meta['image_meta'][ $kw ] ) ) {
				if ( empty ( $curr ) ) {
					static::debug("Adding {$kw} to {$post->ID} from exif");
					add_post_meta( $post->ID, $kw, $meta['image_meta'][ $kw ], true );
				}
				elseif ( $curr != $meta['image_meta'][ $kw ] ) {
					static::debug("Updating {$kw} to {$post->ID} from exif");
					update_post_meta( $post->ID, $kw, $meta['image_meta'][ $kw ], $curr );
				}
			}
		}

		// force post to Flickr
		$snap_flickr = array (
			array (
				"doFL" => 1,
				"msgTFrmt" => "%TITLE%",
				"msgFrmt" => "Originally posted to: %URL%

%RAWTEXT%",
				"isAutoImg" => "A",
				"imgToUse" => "",
				"do" => 1,
			)
		);

		if ( get_post_meta ( $post->ID, 'snapFL', true ) )
			update_post_meta ( $post->ID, 'snapFL', $snap_flickr );
		else
			add_post_meta ( $post->ID, 'snapFL', $snap_flickr, true );

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
