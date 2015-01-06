<?php

include_once ( dirname(__FILE__) . '/adaptgal-ng.php' );

class pmlnr_format {
	private $format = '';
	private $post = null;
	private $src = '';

	public function __construct ( ) {
	}

	/* switcher function to be used as filter on the_content
	 *
	 * @param $src string content to filter
	 *
	 * @return string content with added, formatted metadata
	 */
	public function filter ( $src ) {
		global $post;
		$this->post = $post;
		$this->format = get_post_format ( $post->ID );
		$this->src = $src;

		switch ( $this->format ) {
			case 'quote':
			case 'audio':
			case 'video':
			case 'link':
				break;
			case 'image':
				$this->image( );
				break;
			default:
				$this->src = adaptive_images::adaptive_embedded( $this->src );
				break;
		}


		return $this->src;
	}

	/*
	 * image post format formatter
	 *
	 * @return string content plus [adaptimg] shortcode with attachment image id
	 */
	private function image ( $adaptify = true ) {
		$thid = get_post_thumbnail_id( $this->post->ID );
		$r = $this->src;

		//if ( !empty($this->format) && $this->format != 'standard ' && !empty($thid) ) {
		if ( !empty($thid) ) {
			$img = pmlnr_utils::imagewithmeta( $thid );
			$a = sprintf ( '![%s](%s "%s"){.adaptimg #%s}' , $img['alt'], $img['url'], $img['title'], $thid );
			//$a = '[adaptimg aid=' . $thid .']';
			$this->src = $this->src . "\n" . $a;

			if ( is_singular()) {
				$thmeta = wp_get_attachment_metadata( $thid );
				if ( isset( $thmeta['image_meta'] ) && !empty($thmeta['image_meta']) && isset($thmeta['image_meta']['camera']) && !empty($thmeta['image_meta']['camera']) ) {
					$thmeta = $thmeta['image_meta'];

					$displaymeta = array (
						/*
						'copyright' => sprintf ( __("Copyright\n:%s"), $thmeta['copyright']),
						'camera' => sprintf ( __('Camera: %s'), $thmeta['camera']),
						'iso' => sprintf (__('ISO: %s'), $thmeta['iso'] ),
						'focal_length' => sprintf (__('Focal length: %smm'), $thmeta['focal_length'] ),
						'aperture' => sprintf ( __('Aperture: f/%s'), $thmeta['aperture']),
						'shutter_speed' => sprintf( __('Shutter: %ss'), pmlnr_utils::shutter_speed( $thmeta['shutter_speed'] )),
						'created_timestamp' => sprintf ( __('Taken at: %s'), str_replace('T', ' ', date("c", $thmeta['created_timestamp']))),
						*/
						'camera' => '<i class="icon-camera spacer"></i>'. $thmeta['camera'],
						'iso' => sprintf (__('<i class="icon-sensitivity spacer"></i>ISO %s'), $thmeta['iso'] ),
						'focal_length' => sprintf (__('<i class="icon-focallength spacer"></i>%smm'), $thmeta['focal_length'] ),
						'aperture' => sprintf ( __('<i class="icon-aperture spacer"></i>f/%s'), $thmeta['aperture']),
						'shutter_speed' => sprintf( __('<i class="icon-clock spacer"></i>%s sec'), pmlnr_utils::shutter_speed( $thmeta['shutter_speed'] )),
					);

					$cc = get_post_meta ( $this->post->ID, 'cc', true );
					if ( empty ( $cc ) ) $cc = 'by';

					$ccicons = explode('-', $cc);
					$cci[] = '<i class="icon-cc"></i>';
					foreach ( $ccicons as $ccicon ) {
						$cci[] = '<i class="icon-cc-'. strtolower($ccicon) . '"></i>';
					}

					$cc = '<a href="http://creativecommons.org/licenses/'. $cc .'/4.0/">'. join( $cci,'' ) .'</a>';

					$this->src = $this->src . '<div class="inlinelist">' . $cc . join( ', ', $displaymeta ) .'</div>';
				}
			}
		}

		if ( $adaptify )
			$this->src = adaptive_images::adaptive_embedded( $this->src );

		//return $r;
	}

	/*
	 * get metadata for quote format
	 *
	 * @return string HTML5 formatted content with quote data
	 *
	private function quote ( ) {

		$source_name = get_post_meta($this->post->ID, '_format_quote_source_name', true );
		$source_url = get_post_meta($this->post->ID, '_format_quote_source_url', true );

		$cite = '';
		if ( !empty( $source_name ) && !empty ( $source_url) ) {
			//$cite = sprintf ( '<cite class="u-quote-source right"><a class="icon-link-ext-alt right" href="%s">%s</a></cite>', $source_url, $source_name);
			$cite = sprintf ( '[%s](%s){.u-quote-source .right .icon-link-ext-alt}', $source_name, $source_url );
		}
		elseif ( !empty($source_name )) {
			//$cite = sprintf ( '<cite class="u-quote-source right">%s</cite>', $source_name);
			$cite = sprintf ( '(%s){.u-quote-source}', $source_name );
		}

		if ( !strstr ( $this->src, '<blockquote>' ) )
			$r = sprintf ("> %s\n%s", $this->src, $cite );
		else

		$r = nl2br( $this->src . $cite);

		return $this->src;
	}
	*/

	/*
	 * audio post format formatter
	 *
	 * @return string content plus audio meta
	 *
	private function audio () {
		$r = $this->src;
		$audio = get_post_meta( $this->post->ID, '_format_audio_embed', true );
		if ( !empty($audio)) {
			$r = $r . $audio;
		}

		return $r;
	}
	*/

	/*
	 * video post format formatter
	 *
	 * @return string content plus [embed] with video meta
	 *
	private function video () {
		$r = $this->src;
		$video = get_post_meta( $this->post->ID, '_format_video_embed', true );
		if ( !empty($video)) {
			$video = sprintf ( '[embed]%s[/embed]' , $video );
			$r = $r . "\n" . $video;
		}

		return $r;
	}
	*/

	/*
	 * link post format formatter
	 *
	 * @return string link + content
	 *
	private function link () {
		$r = $this->src;

		$url = get_post_meta( $this->post->ID, '_format_link_url', true );
		$title = get_the_title ( $this->post->ID );
		$webmention = get_post_meta( $this->post->ID, '_format_link_webmention', true );

		if ( !empty($url ) && ( empty($webmention) || $webmention == 'none' ) ) {
			//$l = sprintf ( '<p><a class="icon-link-ext-alt" href="%s">%s</a></p>', $url, $title );
			$l = sprintf ( "[%s](%s){.icon-link-ext-alt}\n", $title, $url );
			$r = $l . "\n" . $r;
		}

		return $r;
	}
	*/


}
