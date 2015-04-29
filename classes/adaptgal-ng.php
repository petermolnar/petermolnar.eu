<?php

include_once ( dirname(__FILE__) . '/utils.php' );

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
	const prefix = 'adaptive_';
	const wprefix = 'adaptive_w_';
	const hprefix = 'adaptive_h_';
	const sprefix = 'adaptive_s_';

	protected $sizes = array();
	protected $imgdata = array();

	public function __construct ( ) {
		/* display width => image size */
		$this->dpix = array (
			1 => 540,
			2 => 980,
			3 => 1920,
		);

		// adaptimg shortcode
		add_shortcode('adaptimg', array ( &$this, 'adaptimg' ) );
	}

	/* init function, should be used in the theme init loop */
	public function init (  ) {
		foreach ( $this->dpix as $dpix => $size ) {
			// width dependent: prefix, max width, no height set, no crop
			add_image_size ( self::wprefix . $dpix, $size, 0, false );
			// height dependent: prefix, no width, max height set, no crop
			add_image_size ( self::hprefix . $dpix, 0, $size, false );
			// restrict both: prefix, max width set, max height set, no crop
			//add_image_size ( self::sprefix . $dpix, $size, $size, false );
		}

		// adaptimg shortcode
		add_filter('the_content', array ( &$this, 'adaptify' ), 2 );

		// sharpen all the images
		add_filter('image_make_intermediate_size',array ( &$this, 'sharpen' ),10);

		// set jpeg quality a littlebit better
		add_filter( 'jpeg_quality', array( &$this, 'jpeg_quality' ) );
		add_filter( 'wp_editor_set_quality', array( &$this, 'jpeg_quality' ) );
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

		extract( shortcode_atts(array(
			'aid' => false,
			'title' => '',
		), $atts));

		if ( empty ( $aid ) )
			return false;

		$img = $this->get_imagemeta( $aid );

		if ( !empty($title)) $img['title'] = $title;

		$type = 'w';
		$keys = array_keys ( $img['src'][$type] );
		$fallback = $img['src'][$type][ $keys[0] ][0];

		foreach ( $img['src'][$type] as $dpix => $src )
			$srcset[] = $src[0] . ' ' . $dpix . "w";

		if ( isset($img['parent']) && !empty($img['parent']) && $img['parent'] != $post->ID ) {
			$l = get_permalink ( $img['parent'] );
		}
		else {
			$t = end( $img['src']['w']);
			if ( $t[1] > $t[2] )
				$l = end( $img['src']['w']);
			else
				$l = end( $img['src']['h']);

			$l = $l[0];
		}

		$r = sprintf('
		<a class="adaptlink" href="%s">
			<picture class="adaptive">
				<img src="%s" id="%s" class="adaptimg" title="%s" alt="%s" srcset="%s" />
			</picture>
		</a>', $l, $fallback, $img['slug'], $img['title'], $img['alttext'], join ( ', ', $srcset ) );

		return $r;
	}

	/**
	 * adaptive image shortcode function
	 */
	public function srcset( $aid ) {

		if ( empty ( $aid ) )
			return false;

		$__post = get_post( $aid );

		foreach ( $this->dpix as $dpix => $size ) {
			$img['src']['w'][$size] = wp_get_attachment_image_src( $aid, self::wprefix . $dpix );
			$img['src']['h'][$size] = wp_get_attachment_image_src( $aid, self::hprefix . $dpix );
			$img['src']['s'][$size] = wp_get_attachment_image_src( $aid, self::sprefix . $dpix );
		}
		$type = 'w';
		$keys = array_keys ( $img['src'][$type] );

		$fallback = $img['src'][$type][ $keys[0] ][0];

		foreach ( $img['src'][$type] as $dpix => $src )
			$srcset[] = $src[0] . ' ' . $dpix . "w";


		if ( isset($__post->post_parent) && !empty ( $__post->post_parent ) && $__post->post_parent != $aid ) {
			$l= get_permalink ( $__post->post_parent );
		}
		else {
			$t = end( $img['src']['w']);

			if ( $t[1] > $t[2] )
				$l = end( $img['src']['w']);
			else
				$l = end( $img['src']['h']);

			$l = $l[0];
		}

		$r = array (
			'srcset' => $srcset,
			'fallback' => $fallback,
			'target' => $l,
		);

		return $r;
	}

	/*
	 *
	 */
	private function get_imagemeta ( $imgid ) {
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
			$img['src']['w'][$size] = wp_get_attachment_image_src( $imgid, self::wprefix . $dpix );
			$img['src']['h'][$size] = wp_get_attachment_image_src( $imgid, self::hprefix . $dpix );
			$img['src']['s'][$size] = wp_get_attachment_image_src( $imgid, self::sprefix . $dpix );
		}

		$img['src']['o'] = wp_get_attachment_image_src( $imgid, 'full' );

		return $img;
	}

	/**
	 * adaptify all images
	 */
	public static function adaptive_embedded( $html ) {
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

				$r = '[adaptimg aid=' . $id .']';
				$html = str_replace ( $imgstr, $r, $html );
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

		if ($size[2] != IMAGETYPE_JPEG)
			return $resized;


		error_log(  __CLASS__ . ": adaptive sharpen starting on " . $resized );

		$imagick = new Imagick($resized);
		//$imagick->adaptiveSharpenImage(0, 0.6);
		//$imagick->sharpenImage(0, 1);
		$imagick->unsharpMaskImage(0,0.5,1,0);
		$imagick->setImageFormat("jpg");
		$imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
		$imagick->setImageCompressionQuality(static::jpeg_quality());
		$imagick->writeImage($resized);
		$imagick->destroy();
		error_log(  __CLASS__ . ": adaptive sharpen done on " . $resized );


		return $resized;
	}

	static public function cachedimage ( $image ) {

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
		$img['url'] = pmlnr_utils::absolute_url($aimg[0]);

		$aimg = wp_get_attachment_image_src( $aid, 'medium' );
		$img['mediumurl'] = pmlnr_utils::absolute_url($aimg[0]);

		$aimg = wp_get_attachment_image_src( $aid, 'large' );
		$img['largeurl'] = pmlnr_utils::absolute_url($aimg[0]);

		$aimg = wp_get_attachment_image_src( $aid, 'thumbnail' );
		$img['thumbnail'] = pmlnr_utils::absolute_url($aimg[0]);

		return $img;
	}

	/**
	 *
	 */
	public static function featured_image ( $src ) {
		global $post;
		$thid = get_post_thumbnail_id( $post->ID );
		if ( ! $thid )
			return $src;


		$format = get_post_format ( $post->ID );

		if ( empty($format)) {
			if ($kind = wp_get_post_terms( $post->ID, 'kind', array( 'fields' => 'all' ) )) {
				if(is_array($kind)) $kind = array_pop( $kind );
				if (is_object($kind)) $kind = $kind->slug;

				if ($kind == 'photo')
					$format = 'image';
				else
					$format = $kind;
			}
		}

		if (!empty($format) && $format != 'standard' ) {
			$img = static::imagewithmeta( $thid );
			$a = sprintf ( '![%s](%s "%s"){.adaptimg #%s}' , $img['alt'], $img['url'], $img['title'], $thid );
			$src = $src . "\n" . $a;

			if ( $format == 'image' )
				$src = $src . static::photo_exif( $post, $thid );
		}

		return static::adaptive_embedded( $src );
	}

	/**
	 *
	 */
	public static function photo_exif ( &$post, &$thid ) {
		$thmeta = wp_get_attachment_metadata( $thid );
		if ( isset( $thmeta['image_meta'] ) && !empty($thmeta['image_meta']) &&
			 isset($thmeta['image_meta']['camera']) && !empty($thmeta['image_meta']['camera']) ):
			$thmeta = $thmeta['image_meta'];

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
			return join(',',$displaymeta);
		endif;
	}

	/**
	 *
	 */
	public static function adaptify ( $html ) {
		global $post;

		//$adaptify = get_post_meta ($post->ID, 'adaptify', true);

		//if ( $adaptify && $adaptify == '1')
		$html = static::adaptive_embedded($html);

		return $html;
	}

}
