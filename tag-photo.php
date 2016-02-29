<?php

define('HEIGHT', 300);

add_action( 'wp_enqueue_scripts', 'pmlnr_portfolio_scripts' );

function pmlnr_portfolio_scripts () {
	//wp_enqueue_style( 'magnific-popup' );
	//wp_enqueue_script ('magnific-popup');
	wp_enqueue_style( 'Justified-Gallery' );
	wp_enqueue_script ('Justified-Gallery');
}

global $query_string;
$posts_per_page = 24;
$_query_string = $query_string;
$_query_string = $query_string . '&posts_per_page=42';

query_posts( $_query_string );

$twigvars['site'] = pmlnr_site::template_vars();
$header = $petermolnareu_theme->twig->loadTemplate('partial_header.html');
echo $header->render($twigvars);


?>
<section class="content-body" id="portfolio">
<?php

if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();

		if (!has_post_thumbnail($post))
			continue;

		$thid = get_post_thumbnail_id( $post->ID );
		$src = wp_get_attachment_image_src ($thid, 'adaptive_3');
		$s = pmlnr_base::fix_url($src[0]);
		//$target = wp_get_attachment_image_src ($thid, 'large');
		//$t = pmlnr_base::fix_url($target[0]);

		$twigvars['post'] = pmlnr_post::template_vars($post);

		//$twigvars['post']['tile_target'] =
		$twigvars['post']['tile_img'] = $s;
		$twigvars['post']['tile_alt'] = htmlspecialchars(strip_tags($twigvars['post']['content']));

		$twig = $petermolnareu_theme->twig->loadTemplate('element-tile.html');
		echo $twig->render($twigvars);
	}
}

?>
</section>

<script>

	jQuery("#portfolio").justifiedGallery({
		margins: 1,
		captions: true,
		rowHeight: <?php echo HEIGHT ?>,
		//maxRowHeight: "120%",
		lastRow: "justify",
		captionSettings: {
			animationDuration: 500,
			visibleOpacity: 0.8,
			nonVisibleOpacity: 0.4
		},
		cssAnimation: true,
	});


	//jQuery("#portfolio").magnificPopup({
		//delegate: 'a',
		//type: 'image',
		//tLoading: 'Loading image #%curr%...',
		//mainClass: 'mfp-img-mobile',
		//gallery: {
			//enabled: true,
			//navigateByImgClick: true,
			//preload: [0,1] // Will preload 0 - before current, and 1 after the current image
		//},
		//image: {
			//tError: '<a href="%url%">The image #%curr%</a> could not be loaded.',
			//titleSrc: function(item) {
				//return item.el.attr('caption');
			//}
		//}
	//});

</script>

<?php

$header = $petermolnareu_theme->twig->loadTemplate('partial_footer.html');
echo $header->render($twigvars);
