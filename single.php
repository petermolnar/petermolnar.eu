<?php

if ( $_SERVER['SERVER_ADDR'] == $_SERVER['REMOTE_ADDR'] || $_SERVER['REMOTE_ADDR'] == '127.0.0.1' ) {
	include_once ( dirname(__FILE__) . '/flat.php');
	exit;
}


the_post();

get_header();

/* get the content */
ob_start();
the_content();
$content = ob_get_clean();

$thid = ( has_post_thumbnail () ) ? get_post_thumbnail_id( $post->ID ) : false;
$hstyle = '';

$meta['post_format'] = get_post_format();

$meta['webactions'] = pmlnr_md::parsedown ( pmlnr_article::meta() );

//$meta['theme'] = ( $meta['post_format'] == 'image' ) ? 'dark' : 'light';
$meta['theme'] = 'light';

$meta['show']['header'] = true;
$meta['show']['minstoread'] = false;
//$meta['show']['commentcntr'] = false;
//$meta['filter']['adaptify'] = false;
//$meta['filter']['tweetify'] = false;

switch ( $meta['post_format'] ) {
	case 'quote':
	case 'video':
	case 'audio':
	case 'aside':
		$meta['show']['header'] = false;
		break;
	case 'image':
	case 'link':
		break;
	default:
		if ( !empty( $thid ) ) {
			$bgimg = wp_get_attachment_image_src( $thid, 'large' );
			if ( $bgimg[1] > 720 )
				$hstyle = 'class="article-header" style="background-image:url('.$bgimg[0].');"';

			/*
			$featimg = get_the_post_thumbnail( $post->ID, 'medium', array(
				'alt'	=> trim(strip_tags( $post->post_title )),
				'title'	=> trim(strip_tags( $post->post_title )),
				'class'	=> "u-photo alignleft",
			));
			*/
		}
		$meta['show']['minstoread'] = true;
		break;
}

?>
<section class="content-body content-<?php echo $meta['theme'] ?>">
	<article id="post-<?php the_ID(); ?>" class="h-entry">

		<!-- article meta -->
		<header <?php echo $hstyle; ?>>
			<div class="content-inner">
			<?php
				if ( $meta['show']['minstoread'])
					echo pmlnr_article::minstoread( $content );

				echo pmlnr_article::pubdate();

				$hide = ( $meta['show']['header']) ? '' : 'hide';
				echo pmlnr_article::title( $hide );

				echo pmlnr_article::photo(true);
				echo pmlnr_article::author(true);

				if ( !empty ( $meta['webactions'] )) echo $meta['webactions'];
			?>
			</div>
		</header>
		<!-- end article meta ->

		<!-- article content -->
		<div class="e-content">
			<div class="content-inner">
				<?php
					echo $content;
				?>
				<br class="clear" />
			</div>
		</div>
		<!-- end article content -->

		<!-- article footer -->
		<footer>
			<div class="content-inner">
			<?php
				if ( empty ( $meta['webactions'] ) ) {
					echo pmlnr_article::tags();
					echo pmlnr_article::reply();
					echo pmlnr_article::share();
					echo pmlnr_article::siblings();
				}
			?>
			</div>
		</footer>
		<!-- end article footer -->

	</article>
</section>

<?php

get_footer();
