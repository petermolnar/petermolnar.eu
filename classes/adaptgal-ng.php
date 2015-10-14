<?php

include_once ( dirname(__FILE__) . '/utils.php' );

class adaptive_images {

	const prefix = 'adaptive_';
	const expire = 300;
	const sizes = '360,540,980,1280';

	public $dpx = array();
	protected $extra_exif = array();

	public function __construct ( ) {
		$sizes = explode(',',self::sizes);

		$cntr = 1;
		foreach ($sizes as $size) {
			$this->dpix[$cntr++] = $size;
		}

		$this->extra_exif = array (
			'lens' => 'LensID',
		);

		//add_shortcode('adaptimg', array ( &$this, 'adaptimg' ) );
	}

	/* init function, should be used in the theme init loop */
	public function init (  ) {
		foreach ( $this->dpix as $dpix => $size )
			add_image_size ( self::prefix . $dpix, $size, $size, false );

		add_filter( 'image_make_intermediate_size',array ( &$this, 'sharpen' ),10);
		add_filter( 'jpeg_quality', array( &$this, 'jpeg_quality' ) );
		add_filter( 'wp_editor_set_quality', array( &$this, 'jpeg_quality' ) );
		add_filter( 'wp_read_image_metadata', array(&$this, 'read_extra_exif'), 1, 3 );
		add_action( 'rss2_item', array(&$this,'rss_media') );

		//add_filter( 'the_content', array ( &$this, 'adaptify' ), 2 );
		//add_filter( 'the_content', array( &$this, 'featured_image'), 1 );
		add_filter( 'the_content', array( &$this, 'insert_featured_image'), 10 );
	}

	/**
	 * adaptive image shortcode function
	 */
	public function adaptive( &$thid, &$post = null ) {
		if (empty($thid))
			return false;

		if ( !pmlnr_utils::is_post($post))
			global $post;

		if ( $cached = wp_cache_get ( $thid, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$meta = self::get_extended_meta($thid);

		$fallback = $meta['sizes']['medium']['src'];
		$try = array ( 'medium', self::prefix . '2', self::prefix . '3' );
		foreach ( $try as $test ) {

			if (isset($meta['sizes'][$test]['src']) && !empty($meta['sizes'][$test]['src']))
				$t = $meta['sizes'][$test]['src'];
			else
				continue;

			if ( $t != $meta['src'] )
				$fallback = $t;
		}

		$as = $this->dpix;
		foreach ( $this->dpix as $dpix => $size ) {
			$id = self::prefix . $dpix;
			if (isset($meta['sizes'][$id]['src']) && !empty($meta['sizes'][$id]['src']))
				$srcset[] = $meta['sizes'][$id]['src'] . ' ' . $as[$dpix] . "w";
		}

		if ( isset($meta['parent']) && !empty($meta['parent']) && pmlnr_utils::is_post($post) && ( $meta['parent'] != $post->ID || !is_singular()) ) {
			$target = get_permalink ( $meta['parent'] );
		}
		else {
			end($this->dpix);
			$id = self::prefix . key($this->dpix);
			$target = $meta['sizes'][$id]['src'];
		}

		$target = pmlnr_utils::fix_url($target);

		$class="adaptimg";
		if ( self::is_u_photo($post)) {
			$class .=" u-photo";
		}

		if ( is_feed())
			$r = sprintf('<img src="%s" title="%s" alt="%s" />', $fallback, $meta['image_meta']['title'], $meta['image_meta']['alt'] );
		else
			$r = sprintf('
		<a class="adaptlink" href="%s">
			<img src="%s" id="img-%s" class="adaptive %s" title="%s" alt="%s" srcset="%s" itemprop="image" />
		</a>', $target, $fallback, $thid, $class, $meta['image_meta']['title'], $meta['image_meta']['alt'], join ( ', ', $srcset ) );

		wp_cache_set ( $thid, $r, __CLASS__ . __FUNCTION__, self::expire );

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
		preg_match_all("/<img.*wp-image-(\d*)[^\>]*>/", $html, $inline_images);

		if ( !empty ( $inline_images[0]  )) {
			foreach ( $inline_images[0] as $cntr => $imgstr ) {
				$thid = $inline_images[1][$cntr];
				$adaptive = $this->adaptive($thid);
				//$r = '[adaptimg aid=' . $aid .' share=0 standalone=1]';
				$html = str_replace ( $imgstr, $adaptive, $html );
			}
		}

		// match all markdown images
		preg_match_all('/\!\[(.*?)\]\((.*?) ?"?(.*?)"?\)\{(.*?)\}/', $html, $markdown_images);

		if ( !empty ( $markdown_images[0]  )) {
			$excludes = array ( '.noadapt', '.alignleft', '.alignright' );
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

		wp_cache_set ( $hash, $html, __CLASS__ . __FUNCTION__, self::expire );

		return $html;
	}


	/**
	 *
	 */
	public static function get_extended_meta ( &$thid ) {
		if ( empty ( $thid ) )
			return false;

		if ( $cached = wp_cache_get ( $thid, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$meta = wp_get_attachment_metadata($thid);
		$attachment = get_post( $thid );

		if ( !empty ( $attachment->post_parent ) ) {
			$parent = get_post( $attachment->post_parent );
			$meta['parent'] = $parent->ID;
		}

		$src = wp_get_attachment_image_src ($thid, 'full');
		$meta['src'] = pmlnr_utils::fix_url($src[0]);

		foreach ( $meta['sizes'] as $size => $data ) {
			$src = wp_get_attachment_image_src ($thid, $size);
			$src = pmlnr_utils::fix_url($src[0]);
			$meta['sizes'][$size]['src'] = $src;
		}

		if ( empty($meta['image_meta']['title']))
			$meta['image_meta']['title'] = esc_attr($attachment->post_title);

		$slug = sanitize_title ( $meta['image_meta']['title'] , $thid );
		if ( is_numeric( substr( $slug, 0, 1) ) )
			$slug = 'img-' . $slug;
		$meta['image_meta']['slug'] = $slug;

		$meta['image_meta']['alt'] = '';
		$alt = get_post_meta($thid, '_wp_attachment_image_alt', true);
		if ( !empty($alt))
			$meta['image_meta']['alt'] = strip_tags($alt);

		wp_cache_set ( $thid, $meta, __CLASS__ . __FUNCTION__, self::expire );

		return $meta;
	}

	/**
	 *
	 */
	public function insert_featured_image ( $src ) {
		global $post;

		if (!pmlnr_utils::is_post($post))
			return $src;

		if (!self::is_u_photo($post))
			return $src;

		$hash = sha1($src);
		if ( $cached = wp_cache_get ( $hash, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$thid = get_post_thumbnail_id( $post->ID );
		// this way it will get cached, thumbnail or no thumbnail as well
		if ( !empty($thid) && !is_feed() ) {
			$adaptive = $this->adaptive($thid, $post);
			$src = $src . $adaptive;
		}

		if ( self::is_photo($thid) ) {
			$src = $src . static::photo_exif( $thid );
		}

		wp_cache_set ( $hash, $src, __CLASS__ . __FUNCTION__, self::expire );

		return $src;
	}

	/**
	 *
	 */
	public function photo_exif ( &$thid ) {
		if (empty($thid))
			return false;

		if ( $cached = wp_cache_get ( $thid, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$return = false;

		$meta = self::get_extended_meta($thid);

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

			$return = join(',',$r);
		}

		wp_cache_set ( $thid, $return, __CLASS__ . __FUNCTION__, self::expire );

		return $return;
	}

	/**
	 * detect if the post is a photo made by me
	 */
	public static function is_photo (&$thid) {
		if ( empty($thid))
			return false;

		if ( $cached = wp_cache_get ( $thid, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$return = false;

		$rawmeta = wp_get_attachment_metadata( $thid );

		if ( isset( $rawmeta['image_meta'] ) && !empty($rawmeta['image_meta'])) {
			$my_devs = array ( 'PENTAX K-5 II s', 'NIKON D80' );
			if ( isset($rawmeta['image_meta']['camera']) && !empty($rawmeta['image_meta']['camera']) && in_array(trim($rawmeta['image_meta']['camera']), $my_devs)) {
				$return = true;
			}
			elseif (isset($rawmeta['image_meta']['copyright']) && !empty($rawmeta['image_meta']['copyright']) && ( stristr($rawmeta['image_meta']['copyright'], 'Peter Molnar') || stristr($rawmeta['image_meta']['copyright'], 'petermolnar.eu'))) {
					$return = true;
			}
		}

		wp_cache_set ( $thid, $return, __CLASS__ . __FUNCTION__, self::expire );

		return $return;
	}

	/**
	 * detect if the post is either a photo or a short post with a featured image
	 */
		public static function is_u_photo ( &$post ) {
		if (! pmlnr_utils::is_post($post) );
			global $post;

		if (! pmlnr_utils::is_post($post) )
			return false;

		$thid = get_post_thumbnail_id( $post->ID );
		if ( ! $thid )
			return false;

		$post_length = strlen( $post->post_content );
		$is_photo = self::is_photo($thid);

		if ( $post_length > ARTICLE_MIN_LENGTH )
			return false;

		if ( $is_photo || $post_length < ARTICLE_MIN_LENGTH )
			return true;

		return false;
	}

	/**
	 *
	 */
	public static function rss_media ( ) {

		global $post;

		if (empty($post) || !is_object($post))
			return false;

		$thid = get_post_thumbnail_id( $post->ID );
		if ( ! $thid )
			return false;

		if ( $cached = wp_cache_get ( $thid, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$asize = 'adaptive_3';
		$img = wp_get_attachment_image_src( $thid, $asize );
		$meta = wp_get_attachment_metadata($thid);
		if ( !isset($meta['sizes'][$asize]))
			return false;

		$upload_dir = wp_upload_dir();
		$cached = WP_CONTENT_DIR . '/cache/' . $meta['sizes'][$asize]['file'];
		$file = $upload_dir['basedir'] . '/' . $meta['sizes'][$asize]['file'];

		if ( file_exists($cached))
			$fsize = filesize($cached);
		elseif ( file_exists($file) )
			$fsize = filesize($file);
		else
			return false;

		$mime = $meta['sizes'][$asize]['mime-type'];
		$str = sprintf('<enclosure url="%s" type="%s" length="%s" />',pmlnr_utils::fix_url($img[0]),$mime,$fsize);

		wp_cache_set ( $thid, $str, __CLASS__ . __FUNCTION__, self::expire );

		echo $str;
	}

	/**
	 * better jpgs
	 */
	public static function jpeg_quality () {
		$jpeg_quality = (int)92;
		return $jpeg_quality;
	}

	/**
	 * additional EXIF which only exiftool can read
	 */
	public function read_extra_exif ( $meta, $filepath ='', $sourceImageType = '' ) {

		if (empty($filepath) || !is_file($filepath) || !is_readable($filepath))
			return $meta;

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
			static::debug_log('Extracting extra EXIF for ' . $filepath );
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
	 * adaptive sharpen images w imagemagick
	 */
	static public function sharpen( $resized ) {

		if (!class_exists('Imagick'))
			return $resized;
		/*
		preg_match ( '/(.*)-([0-9]+)x([0-9]+)\.([0-9A-Za-z]{2,4})/', $resized, $details );

		 * 0 => original var
		 * 1 => full original file path without extension
		 * 2 => resized size w
		 * 3 => resized size h
		 * 4 => extension
		 */

		$size = @getimagesize($resized);

		if ( !$size )
			return $resized;

		$fname = basename( $resized );
		$cachedir = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'cache';
		$cached = $cachedir . DIRECTORY_SEPARATOR . $fname;

		if ( $size[2] != IMAGETYPE_JPEG ) {
			static::debug_log( "moving " . $cached );
			if (copy( $resized, $cached)) {
				static::debug_log(  "removing " . $resized );
				unlink( $resized );
			}
		}
		else {
			static::debug_log( "adaptive sharpen " . $resized );
			$imagick = new Imagick($resized);
			$imagick->unsharpMaskImage(0,0.5,1,0);
			$imagick->setImageFormat("jpg");
			$imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
			$imagick->setImageCompressionQuality(static::jpeg_quality());
			$imagick->writeImage($cached);
			$imagick->destroy();
			static::debug_log( "removing " . $resized );
			unlink ($resized);
		}

		return $resized;
	}

	/**
	 *
	 */
	public static function debug_log ( $msg ) {
		if (defined('WP_DEBUG') && WP_DEBUG == true )
			error_log(  __CLASS__ . ": " . $msg );
	}



}
