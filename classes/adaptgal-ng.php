<?php

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

	const cache_group = 'adaptive_images';
	const cache_time = 86400;
	const cache = false;

	const img_min = 'adaptive_min';
	const img_med = 'adaptive_med';
	const img_lrg = 'adaptive_lrg';
	const img_max = 'adaptive_max';

	const prefix = 'adaptive_';
	const wprefix = 'adaptive_w_';
	const hprefix = 'adaptive_h_';

	protected $sizes = array();
	protected $imgdata = array();

	public function __construct ( ) {
		/* display width => image size */
		$this->dpix = array (
			1 => 700,
			2 => 900,
			3 => 1080
		);
	}


	/* init function, should be used in the theme init loop */
	public function init (  ) {
		foreach ( $this->dpix as $dpix => $size ) {
			// width dependent: prefix, max width, no height set, no crop
			add_image_size ( self::wprefix . $dpix, $size, 0, false );
			// height dependent: prefix, no width, max height set, no crop
			add_image_size ( self::hprefix . $dpix, 0, $size, false );
		}

		//foreach ( $this->sizes as $resolution => $size ) {
			//add_image_size (
				//$resolution,
				//$size,
				//$size,
				//false
			//);

		//}

		add_shortcode('adaptgal', array ( &$this, 'adaptgal' ) );
		add_shortcode('adaptimg', array ( &$this, 'adaptimg' ) );

		$post_types = get_post_types( );
		/* cache invalidation hooks */
		foreach ( $post_types as $post_type ) {
			add_action( 'new_to_publish_' .$post_type , array( &$this , 'cclear' ), 0 );
			add_action( 'draft_to_publish' .$post_type , array( &$this , 'cclear' ), 0 );
			add_action( 'pending_to_publish' .$post_type , array( &$this , 'cclear' ), 0 );
			add_action( 'private_to_publish' .$post_type , array( &$this , 'cclear' ), 0 );
			add_action( 'publish_' . $post_type , array( &$this , 'cclear' ), 0 );
		}

		/* invalidation on some other ocasions as well */
		add_action( 'deleted_post', array( &$this , 'cclear' ), 0 );
		add_action( 'edit_post', array( &$this , 'cclear' ), 0 );

		//add_filter( 'post_thumbnail_html', array( &$this, 'adaptive_embededed' ), 10 );
		//add_filter( 'image_send_to_editor', array( &$this, 'adaptive_embededed' ), 10 );
		//add_filter( 'the_content', array( &$this, 'adaptive_embededed' ), 10 );


	}

	/* adaptive image shortcode function */
	public function adaptimg( $atts , $content=null ) {

		extract( shortcode_atts(array(
			'aid' => false,
			'title' => '',
		), $atts));

		if ( empty ( $aid ) )
			return false;

		$cid = self::prefix . $aid;
		$cached = ( self::cache == 1 ) ? wp_cache_get( $cid, self::cache_group ) : false;

		if ( $cached != false ) {
			return $cached;
		}

		$img = $this->get_imagemeta( $aid );
		if ( !empty($title)) $img['title'] = $title;

		$keys = array_keys ( $img['src']['w'] );
		$fallback = $img['src']['w'][ $keys[0]][0];

		foreach ( $img['src']['w'] as $dpix => $src ) {
			$srcset[] = $src[0] . ' ' . $dpix . "x";
			//$srcset[] = '<source media="(min-width: '.$this->viewport[$key].'px)" srcset="'. $im[0] .'">';
		}

		// '. join ("\n\t\t\t", $srcset) .'

		$r = '
		<picture>
			<img src="'. $fallback .'" id="'. $img['slug'] .'" class="adaptimg" title="'. $img['title'] .'" alt="'. $img['alttext'] . '" srcset="'. join ( ', ', $srcset ) .'" />
		</picture>';

		if ( self::cache == 1 ) {
			wp_cache_set( $cid, $r, self::cache_group, self::cache_time );
		}

		return $r;
	}

	/* adaptive gallery shortcode function */
	public function adaptgal( $atts , $content = null ) {

		extract( shortcode_atts(array(
			'postid' => false,
			'ids' => false,
			'columns' => false
		), $atts));

		if ( $postid == false )
			global $post;
		else
			$post = get_post( $postid );

		if ( !empty( $ids ))
			$ids = explode ( ',' , $ids );

		print_r ( $ids );

/*
		$cached = ( self::cache == 1 ) ? wp_cache_get( $post->ID, self::cache_group ) : false;

		if ( $cached != false ) {
			$images = $cached['images'];
			$bgdata = $cached['bgdata'];
			$css = $cached['css'];
		}
		else {
			if ( $imgids == false ) {
				$images = $this->image_attachments_by_post ( $post );
			}
			else {

				$images = $this->image_attachments_by_ids ( $imgids );
			}

			$bgdata = $this->bgdata ( array_keys( $images ) );
			$css = $this->build_css ( $bgdata, $images );

			$cache = array (
				'images' => $images,
				'bgdata' => $bgdata,
				'css' => $css
			);

			wp_cache_set( $post->ID, $cache, self::cache_group, self::cache_time );
		}
*/
		$r = '<section class="adaptgal-pure">';

		foreach ($images as $imgid => $img ) {
			$r .= do_shortcode( '[adaptimg aid=' . $imgid .' title="'. $img['title'] .'" size="'. self::a_hd .'" share=0]');
		}

		$r .= '</section>';
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

		foreach ( $this->dpix as $dpix => $size ) {
			$img['src']['w'][$dpix] = wp_get_attachment_image_src( $imgid, self::wprefix . $dpix );
			$img['src']['h'][$dpix] = wp_get_attachment_image_src( $imgid, self::hprefix . $dpix );
		}

		return $img;
	}

	/* get all image attachments for a post
	 *
	 * @param $post WordPress post object
	 *
	 * @return array of images, key is attachment id
	*/
	private function image_attachments_by_post ( &$post ) {
		$images = array();

		/* get image type attachments for the post by ID */
		$attachments = get_children( array (
			'post_parent'=>$post->ID,
			'post_type'=>'attachment',
			'post_mime_type'=>'image',
			'orderby'=>'menu_order',
			'order'=>'asc'
		) );

		if ( !empty($attachments) ) {
			foreach ( $attachments as $imgid => $attachment ) {
				$images[ $imgid ] = $this->get_imagemeta( $imgid );
			}
		}

		return $images;
	}

	/* get all image attachments for a post
	 *
	 * @param $post WordPress post object
	 *
	 * @return array of images, key is attachment id
	*/
	private function image_attachments_by_ids ( &$ids ) {
		if ( empty ( $ids ) )
			return false;

		if ( !is_array ( $ids) )
			$ids = array ( $ids );

		$images = array();

		foreach ( $ids as $imgid ) {
			$images[ $imgid ] = $this->get_imagemeta( $imgid );
		}

		return $images;
	}


	/* clear cache entries */
	public function cclear ( $post_id = false, $force = false ) {

		/* exit if no post_id is specified */
		if ( empty ( $post_id ) && $force === false ) {
			return false;
		}

		wp_cache_delete ( $post_id, self::cache_group );

	}

	/* adaptify all images */
	public function adaptive_embededed( $html ) {
		preg_match_all("/<img.*wp-image-(\d*)[^\>]*>/", $html, $inline_images);

		if ( !empty ( $inline_images[0]  )) {
			foreach ( $inline_images[0] as $cntr=>$imgstr ) {
				$aid = $inline_images[1][$cntr];
				//$r = $this->adaptimg($aid);
				$r = do_shortcode( '[adaptimg aid=' . $aid .' size="'. self::a_hd .'" share=0 standalone=1]');
				$html = str_replace ( $imgstr, $r, $html );
			}
		}

		return $html;
	}

}

?>
