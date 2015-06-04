<?php

include_once ( dirname(__FILE__) . '/utils.php' );

class adaptive_images {

	const prefix = 'adaptive_';

	protected $sizes = array();
	protected $imgdata = array();
	protected $dpx = array();

	protected $extra_exif = array();

	public function __construct ( ) {
		$this->dpix = array (
			1 => 360,
			2 => 540,
			3 => 980,
			4 => 1280,
		);

		$this->extra_exif = array (
			'lens' => 'LensID',
		);

		add_shortcode('adaptimg', array ( &$this, 'adaptimg' ) );
	}

	/* init function, should be used in the theme init loop */
	public function init (  ) {
		foreach ( $this->dpix as $dpix => $size )
			add_image_size ( self::prefix . $dpix, $size, $size, false );

		add_filter( 'the_content', array ( &$this, 'adaptify' ), 2 );
		add_filter( 'the_content', array( &$this, 'featured_image'), 1 );
		add_filter( 'image_make_intermediate_size',array ( &$this, 'sharpen' ),10);
		add_filter( 'jpeg_quality', array( &$this, 'jpeg_quality' ) );
		add_filter( 'wp_editor_set_quality', array( &$this, 'jpeg_quality' ) );

		add_filter('wp_read_image_metadata', array(&$this, 'extra_exif'), 1, 3 );
	}

	/**
	 * better jpgs
	 */
	public static function jpeg_quality () {
		$jpeg_quality = (int)92;
		return $jpeg_quality;
	}

	/**
	 * adaptive image shortcode function
	 */
	public function adaptimg( $atts , $content=null ) {
		global $post;

		//error_log ('DEBUG ' . json_encode($atts));

		extract( shortcode_atts(array(
			'aid' => false,
			'title' => '',
		), $atts));

		if ( empty ( $aid ) )
			return false;

		$img = $this->get_imagemeta( $aid );

		if ( !empty($title)) $img['title'] = $title;

		$fallback = $img['src']['a'][1];
		$try = array ( 'm', 2, 3 );

		foreach ( $try as $test ) {

			if (isset($img['src']['a'][ $test ]))
				$t = $img['src']['a'][ $test ];
			elseif (isset($img['src'][ $test ]))
				$t = $img['src'][ $test ];
			else
				continue;

			if ( $t != $img['src']['o'] )
				$fallback = $t;
		}

		$as = $this->dpix;
		foreach ( $img['src']['a'] as $dpix => $src ) {
			$srcset[] = static::fix_url($src) . ' ' . $as[$dpix] . "w";
		}

		if ( isset($img['parent']) && !empty($img['parent']) && ( $img['parent'] != $post->ID || !is_singular()) ) {
			$target = get_permalink ( $img['parent'] );
		}
		else {
			$target = end( $img['src']['a']);
		}

		$r = sprintf('
		<a class="adaptlink" href="%s">
			<picture class="adaptive">
				<img src="%s" id="%s" class="adaptimg" title="%s" alt="%s" srcset="%s" />
			</picture>
		</a>', $target, $fallback, $img['slug'], $img['title'], $img['alttext'], join ( ', ', $srcset ) );

		return $r;
	}


	/*
	 *
	 */
	public function get_imagemeta ( $imgid ) {
		$img = array();
		$__post = get_post( $imgid );

		$img['title'] = esc_attr($__post->post_title);
		$img['alttext'] = strip_tags ( get_post_meta($__post->id, '_wp_attachment_image_alt', true) );
		$img['caption'] = esc_attr($__post->post_excerpt);
		$img['description'] = esc_attr($__post->post_content);
		//$img['slug'] =  ( empty ( $__post->post_name ) ) ? $imgid : esc_attr( $__post->post_name );
		$img['slug'] =  sanitize_title ( $__post->post_title , $imgid );
		if ( is_numeric( substr( $img['slug'], 0, 1) ) )
			$img['slug'] = 'img-' . $img['slug'];

		if ( !empty ( $__post->post_parent ) ) {
			$parent = get_post( $__post->post_parent );
			$img['parent'] = $parent->ID;
		}

		foreach ( $this->dpix as $dpix => $size ) {
			//$img['src']['w'][$size] = wp_get_attachment_image_src( $imgid, self::wprefix . $dpix );
			//$img['src']['h'][$size] = wp_get_attachment_image_src( $imgid, self::hprefix . $dpix );
			//$img['src']['s'][$size] = wp_get_attachment_image_src( $imgid, self::sprefix . $dpix );
			$size = wp_get_attachment_image_src( $imgid, self::prefix . $dpix );
			$img['src']['a'][$dpix] = static::fix_url($size[0]);
		}

		$size = wp_get_attachment_image_src( $imgid, 'full' );
		$img['src']['o'] = static::fix_url($size[0]);

		$size = wp_get_attachment_image_src( $imgid, 'medium' );
		$img['src']['m'] = static::fix_url($size[0]);

		$size = wp_get_attachment_image_src( $imgid, 'large' );
		$img['src']['l'] = static::fix_url($size[0]);

		return $img;
	}

	/**
	 * adaptify all images
	 */
	public function adaptive_embedded( $html ) {
		if (pmlnr_utils::islocalhost())
			return ($html);

		// match all wp inserted images
		preg_match_all("/<img.*wp-image-(\d*)[^\>]*>/", $html, $inline_images);

		if ( !empty ( $inline_images[0]  )) {
			foreach ( $inline_images[0] as $cntr=>$imgstr ) {
				$aid = $inline_images[1][$cntr];
				//$r = $this->adaptimg($aid);
				$r = '[adaptimg aid=' . $aid .' share=0 standalone=1]';
				//$r = do_shortcode( '[adaptimg aid=' . $aid .' share=0 standalone=1]');
				$html = str_replace ( $imgstr, $r, $html );
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
					$r = '[adaptimg aid=' . $id .']';
					$html = str_replace ( $imgstr, $r, $html );
				}
			}
		}

		return $html;
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
			error_log(  __CLASS__ . ": moving " . $cached );
			if (copy( $resized, $cached)) {
				error_log(  __CLASS__ . ": removing " . $resized );
				unlink( $resized );
				error_log(  __CLASS__ . ": creating symlink " . $resized );
				symlink ( $cached, $resized );
			}
			else {
				error_log(  __CLASS__ . ": could not remove " . $resized );
			}
		}
		else {
			error_log(  __CLASS__ . ": adaptive sharpen " . $resized );
			$imagick = new Imagick($resized);
			//$imagick->adaptiveSharpenImage(0, 0.6);
			//$imagick->sharpenImage(0, 1);
			$imagick->unsharpMaskImage(0,0.5,1,0);
			$imagick->setImageFormat("jpg");
			$imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
			$imagick->setImageCompressionQuality(static::jpeg_quality());
			$imagick->writeImage($cached);
			$imagick->destroy();
			error_log(  __CLASS__ . ": removing " . $resized );
			unlink ($resized);
			error_log(  __CLASS__ . ": creating symlink " . $resized );
			symlink ( $cached, $resized );
		}

		return $resized;
	}

	/**
	 *
	 */
	public static function imagewithmeta( $aid ) {
		if ( empty ( $aid ) )
			return false;

		$__post = get_post( $aid );
		$img = array ();

		$img['id'] = $aid;
		$img['title'] = esc_attr($__post->post_title);
		$img['alt'] = strip_tags ( get_post_meta($__post->id, '_wp_attachment_image_alt', true) );
		if ( empty ($img['alt'])) $img['alt'] = $img['title'];

		$img['caption'] = esc_attr($__post->post_excerpt);
		$img['description'] = esc_attr($__post->post_content);
		$img['slug'] =  sanitize_title ( $__post->post_title , $aid );
			if ( is_numeric( substr( $img['slug'], 0, 1) ) )
				$img['slug'] = 'img-' . $img['slug'];

		$aimg = wp_get_attachment_image_src( $aid, 'full' );
		$img['url'] = static::fix_url($aimg[0]);

		$aimg = wp_get_attachment_image_src( $aid, 'medium' );
		$img['mediumurl'] = static::fix_url($aimg[0]);

		$aimg = wp_get_attachment_image_src( $aid, 'large' );
		$img['largeurl'] = static::fix_url($aimg[0]);

		$aimg = wp_get_attachment_image_src( $aid, 'thumbnail' );
		$img['thumbnail'] = static::fix_url($aimg[0]);



		return $img;
	}

	/**
	 *
	 */
	public function featured_image ( $src ) {
		global $post;
		if (!is_object($post) || !isset($post->ID))
			return $src;

		$thid = get_post_thumbnail_id( $post->ID );
		if ( ! $thid )
			return $src;

		$format = get_post_format ( $post->ID );

		if ( empty($format)) {
			$kind = wp_get_post_terms( $post->ID, 'kind', array( 'fields' => 'all' ) );
			if (!is_wp_error($kind)) {
				if(is_array($kind)) $kind = array_pop( $kind );
				if (is_object($kind) && isset($kind->slug)) $kind = $kind->slug;

				if ($kind == 'photo')
					$format = 'image';
				else
					$format = $kind;
			}
			else {
				$format = false;
			}
		}

		if (!empty($format) && $format != 'standard' ) {
			$img = static::imagewithmeta( $thid );
			$a = sprintf ( '![%s](%s "%s"){.adaptimg #%s}' , $img['alt'], $img['url'], $img['title'], $thid );
			$src = $src . "\n" . $a;

			if ( $format == 'image' )
				$src = $src . static::photo_exif( $post, $thid );
		}

		return $this->adaptive_embedded( $src );
	}

	/**
	 *
	 */
	public function photo_exif ( &$post, &$thid ) {
		$rawmeta = wp_get_attachment_metadata( $thid );
		$file = get_attached_file ($thid );

		/*
		$extra = $this->extra_exif;
		$regenerate = false;
		foreach ( array_keys($extra) as $metakey ) {
			if (!isset($rawmeta['image_meta'][$metakey])) {
				$regenerate = true;
			}
		}

		if ($regenerate) {
			if (!function_exists('wp_generate_attachment_metadata')) {
				include( ABSPATH . 'wp-admin/includes/image.php' );
			}
			$rawmeta = wp_generate_attachment_metadata( $thid, $file );
			wp_update_attachment_metadata( $thid,  $rawmeta );
		}
		*/

		if ( isset( $rawmeta['image_meta'] ) && !empty($rawmeta['image_meta']) &&
			 isset($rawmeta['image_meta']['camera']) && !empty($rawmeta['image_meta']['camera']) ):
			$thmeta = $rawmeta['image_meta'];

			//shutter speed
			if ( (1 / $thmeta['shutter_speed'] ) > 1) {
				$shutter_speed = "1/";
				if ((number_format((1 / $thmeta['shutter_speed']), 1)) == 1.3 or
					 number_format((1 / $thmeta['shutter_speed']), 1) == 1.5 or
					 number_format((1 / $thmeta['shutter_speed']), 1) == 1.6 or
					 number_format((1 / $thmeta['shutter_speed']), 1) == 2.5)
						$shutter_speed .= number_format((1 / $thmeta['shutter_speed']), 1, '.', '');

				else
					$shutter_speed .= number_format((1 / $thmeta['shutter_speed']), 0, '.', '');
			}
			else {
				$shutter_speed = $thmeta['shutter_speed'];
			}

			$displaymeta = array (
				//'created_timestamp' => sprintf ( __('Taken at: %s'), str_replace('T', ' ', date("c", $thmeta['created_timestamp']))),
				'camera' => '<i class="icon-camera spacer"></i>'. $thmeta['camera'],
				'iso' => sprintf (__('<i class="icon-sensitivity spacer"></i>ISO %s'), $thmeta['iso'] ),
				'focal_length' => sprintf (__('<i class="icon-focallength spacer"></i>%smm'), $thmeta['focal_length'] ),
				'aperture' => sprintf ( __('<i class="icon-aperture spacer"></i>f/%s'), $thmeta['aperture']),
				'shutter_speed' => sprintf( __('<i class="icon-clock spacer"></i>%s sec'), $shutter_speed),
				//'lens' => sprintf( __('<i class="icon-aperture spacer"></i>%s'), $thmeta['lens']),
			);
			/*
			//$cc = get_post_meta ( $post->ID, 'cc', true );
			//if ( empty ( $cc ) ) $cc = 'by';
			$cc = 'by-nc-nd';

			$ccicons = explode('-', $cc);
			$cci[] = '<i class="icon-cc"></i>';
			foreach ( $ccicons as $ccicon ) {
				$cci[] = '<i class="icon-cc-'. strtolower($ccicon) . '"></i>';
			}

			$cc = sprintf('<div class="inlinelist"><a href="http://creativecommons.org/licenses/%s/4.0">%s</a>%s</div>', $cc, join( $cci,'' ), join( ', ', $displaymeta ));

			return $cc;
			*/
			//static::extra_exif ( $thmeta, $file );


			return join(',',$displaymeta);
		endif;
	}

	/**
	 *
	 */
	public function adaptify ( $html ) {
		global $post;

		//$adaptify = get_post_meta ($post->ID, 'adaptify', true);

		//if ( $adaptify && $adaptify == '1')
		$html = $this->adaptive_embedded($html);

		return $html;
	}

	/**
	 *
	 */
	public static function fix_url ( $url ) {
		$surl = rtrim( get_bloginfo('url'), '/' );
		$url = $surl . str_replace ( $surl, '', $url );
		$url = str_replace ( array('http://', 'https://'), '//', $url );
		//$url = str_replace ('/files/', '/cache/', $url );

		return $url;
	}

	/**
	 *
	 */
	public function extra_exif ( $meta, $filepath ='', $sourceImageType = '' ) {

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
			error_log('Extracting extra EXIF for ' . $filepath );
			$cmd = 'exiftool -s -' . join(' -', $args) . ' ' . $filepath;

			exec( $cmd, $exif, $retval);

			if ($retval == 0 ) {
				error_log ('EXIF: ' . json_encode($exif));
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

}
