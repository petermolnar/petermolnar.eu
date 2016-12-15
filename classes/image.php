<?php

namespace PETERMOLNAR\IMAGE;
use PETERMOLNAR;

\add_action( 'init', 'PETERMOLNAR\IMAGE\init' );

	/* init function, should be used in the theme init loop */
function init (  ) {

	// adaptify all the things
	\add_filter( 'the_content', 'PETERMOLNAR\IMAGE\adaptify', 7 );


	// GD or the built-in image editor destroys EXIF when it downsizes images,
	// so I want WP to fail if Imagick failes and to not fall back at all
	\add_filter ( 'wp_image_editors', function ( $arr ) {
		return array ( 'WP_Image_Editor_Imagick' );
	} );

	\add_filter( 'wp_resized2cache_imagick',
		'PETERMOLNAR\IMAGE\watermark', 10, 2 );
}

/***
 *
 */
function watermark ( $imagick, $resized ) {
	$upload_dir = \wp_upload_dir();

	$watermarkfile = $upload_dir['basedir']
		. DIRECTORY_SEPARATOR . 'watermark.png';

	if ( ! file_exists ( $watermarkfile ) ) {
		\PETERMOLNAR\debug ( "watermark file not found");
		return $imagick;
	}

	$meta = \WP_EXTRAEXIF\exif_cache( $resized );
	$yaml = parse_ini_file ( __DIR__ . '/../data.ini' );

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
		\PETERMOLNAR\debug( 'watermarking not needed here', 5 );
		return $imagick;
	}

	\PETERMOLNAR\debug( 'watermark present and it looks like my photo, adding watermark to image ', 5 );
	$watermark = new \Imagick( $watermarkfile );
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
		$watermark->rotateImage(new \ImagickPixel('none'), -90);
	}

	$imagick->compositeImage($watermark, \IMAGICK::COMPOSITE_OVER, $x , $y );
	$watermark->clear();
	$watermark->destroy();
	return $imagick;
}



/**
 * adaptify all images
 */
function adaptify( $content ) {

	if ( empty( $content ) )
		return $content;

	$images = md_images( $content );
	if ( empty( $images[0] ) )
		return $content;

	foreach ( $images[0] as $cntr => $md ) {

		$skip = false;
		if ( !empty( $images[4][$cntr] ) ) {
			$extras = explode( " ", $images[4][$cntr] );
			foreach ( $extras as $extra ) {
				if ( strstr( $extra, '.align') || strstr( $extra, '.noadapt')) {
					$file = pathinfo( $images[2][$cntr] );
					$target = site_url( \WP_RESIZED2CACHE\CACHENAME
						. "/{$file['filename']}.{$file['extension']}" );
					$content = str_replace( $images[2][$cntr], $target, $content );
					$skip = true;
				}
			}
		}
		if ( $skip )
			continue;

		$alt = $images[1][$cntr];
		$url = $images[2][$cntr];

		$title = '';
		if ( isset( $images[3][$cntr] ) )
			$title = $images[3][$cntr];

		$class = ( 1 == count( $images[0] ) ) ? 'u-photo' : '';

		$adaptive = adaptive( $url, $alt, $title, $class );
		$content = str_replace ( $md, $adaptive, $content );
	}

	return $content;
}

/**
 *
 */
function md_images( &$text ) {
	/*
	 * 1 => alt
	 * 2 => image
	 * 3 => title, optional
	 * 4 => classes and ids, optional
	 */
	//preg_match_all('/!\[(.*?)\]\((.*?) ?[\'"]?(.*?)[\'"]?\)\{(.*?)\}/is',
	preg_match_all('/!\[(.*?)\]\((.*?\.(?:jpe?g|png))(?:\s+[\'"]?(.*?)[\'"]?)?\)(?:\{(.*?)\})?/is',
		$text, $matches);

	return $matches;
}

/**
 *
 *
function find_thid ( $resized ) {

	global $wpdb;
	$dbname = "{$wpdb->prefix}postmeta";
	$req = false;

	$q = $wpdb->prepare( "SELECT `post_id` FROM `{$dbname}` WHERE "
		."`meta_value` LIKE '%%%s%%' AND "
		."`meta_key` = '_wp_attachment_metadata' LIMIT 1",
	basename( $resized ) );

	try {
		$req = $wpdb->get_var( $q );
	}
	catch (Exception $e) {
		\PETERMOLNAR\debug('Something went wrong: ' . $e->getMessage(), 4);
	}

	return $req;
}
*/

function is_photo ( $jpg ) {
	$path = pathinfo( $jpg );
	$upload_dir = \wp_upload_dir();
	$jpg = $upload_dir['basedir'] . DIRECTORY_SEPARATOR
		. $path['filename'] . '.' .  $path['extension'];

	$config = parse_ini_file ( __DIR__ . '/../data.ini' );
	$exif = \WP_EXTRAEXIF\exif_cache( $jpg );

	if ( ! isset( $exif ) || empty( $exif ) )
		return false;

	if ( ! isset( $exif['copyright'] )
		|| empty( $exif['copyright'] )
	)
		return false;

	$author = false;

	// Gimp edited images don't have camera :(
	//if ( isset( $exif['camera'] && in_array( trim( $exif['camera'] ), $config['cameras'] ) )
		//$camera = true;

	foreach ( $config['copyright'] as $copy ) {
		if ( stristr( $exif['copyright'], $copy ) ) {
			//$author = true;
			//break;
			return true;
		}
	}

	//if ( $camera && $author )
		//return true;

	return false;

}



/**
 * adaptive image shortcode function
 *
 */
function adaptive ( $img, $alt = '', $title = '', $class = '' ) {

	$file = pathinfo( $img );

	$target = site_url( \WP_RESIZED2CACHE\CACHENAME
		. "/{$file['filename']}.{$file['extension']}" );

	$sim = 0;
	similar_text ( strtolower( $file['filename'] ) , strtolower( $alt ), $sim );
	$caption = '';
	if ( $sim < 90 ) {
		$caption = $alt;
	}

	$test = \WP_RESIZED2CACHE\CACHE
		. "{$file['filename']}_z.{$file['extension']}";
	// small files, don't make them responsive
	if ( ! is_file( $test ) ) {
		return "<img src=\"{$target}\" title=\"{$title}\" alt=\"{$alt}\"
			class=\"adaptive adaptimg\" />";
	}

	$sizes = \WP_RESIZED2CACHE\sizes();
	ksort( $sizes, SORT_NUMERIC );

	$default = site_url( \WP_RESIZED2CACHE\CACHENAME
		. "/{$file['filename']}_z.{$file['extension']}" );


	$srcset = array();
	foreach ( $sizes as $size => $name ) {
		$downsized = "{$file['filename']}_{$name}.{$file['extension']}";
		$test = \WP_RESIZED2CACHE\CACHE . $downsized;
		if ( ! is_file( $test ) )
			continue;

		$downsized = site_url( \WP_RESIZED2CACHE\CACHENAME
		. "/{$file['filename']}_{$name}.{$file['extension']}" );

		array_push( $srcset, "{$downsized} {$size}w" );
	}

	$srcset = join( ', ', $srcset );

	if ( \is_feed()) {
		$r = "<img src=\"{$default}\" title=\"{$title}\" alt=\"{$alt}\" />";
	}
	else {
		$r = "<p class=\"photo\"><a class=\"adaptive {$class}\" href=\"{$target}\">
				<img
					src=\"{$default}\"
					class=\"adaptimg\"
					title=\"{$title}\"
					alt=\"{$alt}\"
					srcset=\"{$srcset}\" />
				</a>";

		if ( $caption )
			$r .= "<span class=\"caption\">{$caption}</span>";

		$r .= '</p>';
	}



	return $r;
}
