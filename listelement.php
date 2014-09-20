<?php

global $is_search;

the_post();

$meta['post_format'] = get_post_format();

$meta['show']['header'] = true;
$meta['show']['minstoread'] = false;
$meta['show']['commentcntr'] = true;
$meta['show']['excerpt'] = false;

switch ( $meta['post_format'] ) {
	case 'link':
	case 'quote':
	case 'video':
	case 'audio':
	case 'aside':
		$meta['show']['header'] = false;
		break;
	case 'image':
		break;
	default:
		$meta['show']['minstoread'] = true;
		$meta['show']['minstoread'] = true;
		$meta['show']['excerpt'] = true;
		break;
}

if ( $is_search ) {
	$meta['show']['excerpt'] = true;
	$meta['show']['header'] = true;
}

/* get the content */
ob_start();
the_content();
$content = ob_get_clean();

/* get the excerpt */
ob_start();
the_excerpt();
$excerpt = ob_get_clean();


?>
<div class="content-inner">
	<article id="post-<?php the_ID(); ?>" class="h-entry article-list-element">

		<!-- article meta -->
		<header <?php echo $hstyle; ?>>
			<?php
				if ( $meta['show']['excerpt'] ) {
					echo pmlnr_article::commentcntr( );
					echo pmlnr_article::minstoread( $content );
				}
				echo pmlnr_article::pubdate();

				if ( $meta['show']['header'] && $meta['show']['excerpt'] )
					$title = 'listmore';
				elseif ( $meta['show']['header'] )
					$title = 'listelement';
				else
					$title = 'hide';

				echo pmlnr_article::title( $title );

				echo pmlnr_article::photo(true);
				echo pmlnr_article::author(true);
			?>
		</header>
		<!-- end article meta ->

		<!-- article content -->
		<?php if ( $meta['show']['excerpt'] ):
			$thumb = '';
			if ( has_post_thumbnail () ) {
				//$aid = get_post_thumbnail_id( $post->ID );
				$thumb = get_the_post_thumbnail( $post->ID, 'thumbnail', array(
					'alt'	=> trim(strip_tags( $post->post_title )),
					'title'	=> trim(strip_tags( $post->post_title )),
					'class'	=> "u-photo alignleft",
				));
				$thumb = sprintf ( '<figure class="article-thumbnail"><a href="%s">%s</a></figure>', get_the_permalink(), pmlnr_utils::replace_if_ssl ( $thumb ) );
			}
		?>
			<div class="e-summary">
				<?php echo $thumb . $excerpt; ?>
				<br class="clear" />
			</div>

		<?php else : ?>
			<div class="e-content">
				<?php echo $content; ?>
				<br class="clear" />
			</div>
		<?php endif; ?>
		<!-- end article content -->

	</article>
</div>
