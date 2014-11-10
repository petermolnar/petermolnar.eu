<?php
$parsedown = new ParsedownExtra();

the_post();
$thid = ( has_post_thumbnail () ) ? get_post_thumbnail_id( $post->ID ) : false;
if ( $thid )
    $img = pmlnr_utils::absolute_url(wp_get_attachment_url ( $thid ));
$format = get_post_format();
if ( empty($format)) $format = 'article';

$tags = get_the_tags();
if ( !empty( $tags )) {
	foreach ( $tags as $tag ) {
		$taglist[$tag->slug] = $tag->name;
	}
	$taglist = '['. join (', ', $taglist) . ']';
}

$category = get_the_category( $id );


ob_start();
the_excerpt();
$excerpt = ob_get_clean();

ob_start();
the_content();
$content = ob_get_clean();

$excerpt = html_entity_decode ( $excerpt );
$content = html_entity_decode ( $content );

$search = array ( '”', '“', '’', '–' );
$replace = array ( '"', '"', "'", '-' );
$content = str_replace ( $search, $replace, $content );
$excerpt = str_replace ( $search, $replace, $excerpt );

$excerpt = strip_tags ( $parsedown->text ( $excerpt ) );
$search = array ("\n");
$replace = array ("");
$description = trim ( str_replace( $search, $replace, $excerpt), "'\"" );

preg_match_all('/((https:\/\/petermolnar.eu)?\/files\/[0-9]{4}\/[0-9]{2}\/)(.*)-([0-9]{1,4})×([0-9]{1,4})\.([a-zA-Z]{2,4})/', $content, $resized_images );
if ( !empty ( $resized_images[0]  )) {
	//print_r ( $resized_images);

	foreach ( $resized_images[0] as $cntr=>$imgstr ) {
		//$location = $resized_images[1][$cntr];
		$done_images[ $resized_images[2][$cntr] ] = 1;
		$fname = $resized_images[2][$cntr] . '.' . $resized_images[5][$cntr];
		$width = $resized_images[3][$cntr];
		$height = $resized_images[4][$cntr];
		$r = $fname . '?resize=' . $width . ',' . $height;
		$content = str_replace ( $imgstr, $r, $content );
	}

}

preg_match_all('/(https?:\/\/petermolnar.eu)?\/files\/[0-9]{4}\/[0-9]{2}\/(.*?)\.([a-zA-Z]{2,4})/', $content, $images );
if ( !empty ( $images[0]  )) {
	foreach ( $images[0] as $cntr=>$imgstr ) {
		//$location = $resized_images[1][$cntr];
		if ( !isset($done_images[ $images[1][$cntr] ]) ){
			if ( strstr($images[1][$cntr], 'http'))
				$fname = $images[2][$cntr] . '.' . $images[3][$cntr];
			else
				$fname = $images[1][$cntr] . '.' . $images[2][$cntr];
			$content = str_replace ( $imgstr, $fname, $content );
		}
	}
}

$linkmeta = '';
if ( $format == 'link' ) {
	$linkurl = get_post_meta( $post->ID, '_format_link_url', true );
	if ( !empty ( $linkurl ))
	$linkmeta = '
    sourceurl: '. $linkurl ."\n";
}


$twsummary = (empty($thid)) ? 'summary' : 'summary_large_image';

?>---
title: <?php echo str_replace( '–', '-', get_the_title()); ?><?php echo "\n"; ?>
slug: <?php echo $post->post_name; ?><?php echo "\n"; ?>
date: <?php the_time('c'); ?><?php echo "\n"; ?>
id: <?php echo $post->ID ?><?php echo "\n"; ?>
permalink: <?php echo get_the_permalink(); ?><?php echo "\n"; ?>
shortlink: <?php echo wp_get_shortlink(); ?><?php echo "\n"; ?>
taxonomy:<?php echo "\n"; ?>
    category: <?php echo $category[0]->cat_name; ?><?php echo "\n"; ?>
    tag: <?php echo $taglist; ?><?php echo "\n"; ?>
    format: <?php echo $format; ?><?php echo "\n"; ?>
<?php

    /* get the attachments for current post */
    $attachments = get_children( array (
        'post_parent'=>$post->ID,
        'post_type'=>'attachment',
        'post_mime_type'=>'image',
        'orderby'=>'menu_order',
        'order'=>'asc'
    ));

    if ( !empty($attachments) ) {
        foreach ( $attachments as $aid => $attachment ) {
            $a[] = pmlnr_utils::absolute_url( wp_get_attachment_url( $aid ) );
        }

    echo "attachments: " . '['. join (', ', $a) . ']' . "\n";

    }

    /* syndications */
    $syn = getRelSyndicationFromSNAP( false, true );
    if ( !empty($syn) ) {
         foreach ( $syn as $silo => $url ) {
            $syndicated[] = '        ' . $silo . ': ' . $url;
        }
        echo "syndicated: \n" . join ( "\n", $syndicated ) . "\n";
    }

    //$comments = get_comments ( array(
        //'post_id' => $post->ID,
        //'status' => 'approve'
        //));

    //if ( !empty($comments)) {
        //foreach ( $comments as $comment ) {
            //$commlist[] = $comment->comment_ID;
        //}
        //echo "comments: " . '['. join (', ', $a) . ']' . "\n";
    //}

?>---
<?php
if($post->post_excerpt):
	echo $excerpt;
	echo "\n\n";
endif;
?>
===

<?php echo $content; ?>

