<?php
/*
Template Name: Tiled Photos
*/
?>
<?php get_header(); ?>

<section class="content-body content-light" id="main-content">

<?php the_post(); ?>

<?php

if (is_user_logged_in()):

	$tdir = get_bloginfo('template_directory');

	wp_register_style( 'magnific-popup', $tdir . '/lib/Magnific-Popup/dist/magnific-popup.css' );
	wp_enqueue_style( 'magnific-popup' );

	wp_register_script( 'magnific-popup', $tdir . '/lib/Magnific-Popup/dist/jquery.magnific-popup.min.js' , array('jquery', 'Justified-Gallery'), null, false );
	wp_enqueue_script ('magnific-popup');

	wp_register_style( 'Justified-Gallery', $tdir . '/lib/Justified-Gallery/dist/css/justifiedGallery.min.css' , false );
	wp_enqueue_style( 'Justified-Gallery' );
	wp_register_script( 'Justified-Gallery', $tdir . '/lib/Justified-Gallery/dist/js/jquery.justifiedGallery.min.js', array('jquery'), null, false );
	wp_enqueue_script ('Justified-Gallery');

	$post_terms = wp_get_object_terms( $post->ID, 'fotodir_category' );

	if (empty($post_terms))
		die('?');

	$term = array_pop($post_terms);

	$spath = $term->name . DIRECTORY_SEPARATOR . $post->post_title;
	$spath = '/shr/foto/' . $spath;

	$tmppath = DIRECTORY_SEPARATOR . 'foto-cache' . DIRECTORY_SEPARATOR . $post->post_name;
	$curl = WP_CONTENT_URL . $tmppath;
	$cpath = WP_CONTENT_DIR .  $tmppath;

	if ( !is_dir($cpath)) {
		if (!mkdir($cpath, 0755, true )) {
			error_log(__CLASS__ . ' could not create folder: ' . $cpath);
			return false;
		}
	}


	if ( is_dir ( $spath )) {

		if ( $handle = opendir( $spath )) {

			$imgs = array();
			while (false !== ($file = readdir($handle))) {
				if(($file != ".") and ($file != "..") and ($file != "index.php")) {
					$files[] = $file; // put in array.
				}
			}

			natsort($files);

			foreach ($files as $entry ) {

				$entry = $spath . DIRECTORY_SEPARATOR . $entry;
				$size = @getimagesize($entry);

				if ( !$size ) {
					continue;
				}

				if ( $size[2] != IMAGETYPE_JPEG ) {
					continue;
				}

				$file = pathinfo ($entry);
				$sizes = explode(',',adaptive_images::sizes);

				$dpix = $resized = $to_resize = array();
				$downsize = false;
				foreach ($sizes as $cntr => $size) {
					$c = $cntr + 1;
					$dpix[$c] = $size;

					$downsized = $file['filename'] . '-' . $size . '.' . $file['extension'];
					$resized[$c] = $downsized;
					$downsized_path = $cpath . DIRECTORY_SEPARATOR . $downsized;

					if (!is_file($downsized_path))
						$to_resize[$c] = $downsized_path;
				}

				if (!empty($to_resize)) {
					foreach ($to_resize as $sizeid => $downsized_path) {
						$s = $dpix[ $sizeid ];
						$imagick = new Imagick(realpath($entry));

						$orientation = $imagick->getImageOrientation();

						switch($orientation) {
							case imagick::ORIENTATION_BOTTOMRIGHT:
								$imagick->rotateimage("#000", 180); // rotate 180 degrees
								break;

							case imagick::ORIENTATION_RIGHTTOP:
								$imagick->rotateimage("#000", 90); // rotate 90 degrees CW
								break;

							case imagick::ORIENTATION_LEFTBOTTOM:
								$imagick->rotateimage("#000", -90); // rotate 90 degrees CCW
								break;
						}

						$imagick->resizeImage($s, $s, Imagick::FILTER_LANCZOS, 1, true);
						$imagick->setImageFormat("jpg");
						$imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
						$imagick->setImageCompressionQuality(92);
						$imagick->writeImage($downsized_path);
						$imagick->destroy();
					}
				}

				$target = $curl . DIRECTORY_SEPARATOR . end( $resized );
				$srcsize = @getimagesize( $cpath . DIRECTORY_SEPARATOR . $resized[1]);
				$src = $curl . DIRECTORY_SEPARATOR . $resized[1];
				$imgs[] = sprintf('<a href="%s"><img src="%s" width="%s" height="%s" /></a>', $target, $src, $srcsize[0], $srcsize[1] );

			}

			$r = sprintf ('<div id="justified-gallery">%s</div>', join('', $imgs));
			echo $r;

			closedir($handle);
		}
	}

endif;

?>

</section>
<?php get_footer();

?>
			<script>
				jQuery("#justified-gallery").justifiedGallery({
					margins: 2,
					captions: false,
					rowHeight: 200,
				});

				jQuery("#justified-gallery").magnificPopup({
					delegate: 'a',
					type: 'image',
					tLoading: 'Loading image #%curr%...',
					mainClass: 'mfp-img-mobile',
					gallery: {
						enabled: true,
						navigateByImgClick: true,
						preload: [0,1] // Will preload 0 - before current, and 1 after the current image
					},
					image: {
						tError: '<a href="%url%">The image #%curr%</a> could not be loaded.',
						titleSrc: function(item) {
							return item.el.attr('caption');
						}
					}
				});

			</script>
