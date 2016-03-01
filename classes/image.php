<?php

class pmlnr_image extends pmlnr_base {

	const prefix = 'adaptive_';
	const sizes = '360,540,980,1280';

	private $dpx = array();
	private $extra_exif = array();

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
				$srcset[] = $meta['sizes'][$id]['src'] . ' ' . $as[$dpix] . "w";
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
			$r = sprintf('<a href="%s"><img src="%s" id="img-%s" class="adaptive %s" title="%s" alt="%s" srcset="%s" sizes="(max-width: 42em) 100vw" /></a>', $target, $fallback['src'], $thid, $class, $meta['image_meta']['title'], $meta['image_meta']['alt'], join ( ', ', $srcset ) );
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
			if ( isset($meta['geo_latitude']) && !empty($meta['geo_latitude']) && isset($meta['geo_longitude']) && !empty($meta['geo_longitude']))
				$location = sprintf ( __('<i class="icon-location spacer"></i><a href="http://maps.google.com/?q=%s,%s"><span class="h-geo geo p-location"><span class="p-latitude">%s</span>, <span class="p-longitude">%s</span></span></a>'), $meta['geo_latitude'], $meta['geo_longitude'], $meta['geo_latitude'], $meta['geo_longitude'] );

			$r['location'] = $location;

			$return = '<aside class="exif"><ul><li>' . join('</li><li>',$r) . '</li></ul></aside>';


		}



		wp_cache_set ( $thid, $return, __CLASS__ . __FUNCTION__, static::expire );

		return $return;
	}

}
