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

	const a_thumb = 'th'; /* for thumbnails */
	const a_lthumb = 'lth'; /* for thumbnails */
	const a_stnd = 'stnd'; /* for in-text images */
	const a_hd = 'hd';  /* for large images */

	const cache_group = 'adaptive_images';
	const cache_time = 86400;

	const default_prefix = 'adaptive';

	const cache = 1;
	private $sharesize = '';
	const middlesize = 720;
	public $image_sizes = array();
	//public $prefixes = array();

	public function __construct ( ) {
		//$this->prefixes = array ( self::a_thumb, self::a_stnd, self::a_hd );

		$this->image_sizes = array (
			/* legacy, below 720px */
			460 => array (
				self::a_thumb => 60,
				self::a_lthumb => 220,
				self::a_stnd => 120,
				self::a_hd => 640,
			),
			/* normal, between 720px and 1200px*/
			720 => array (
				self::a_thumb => 90,
				self::a_lthumb => 380,
				self::a_stnd => 240,
				self::a_hd => 1024,
			),
			/* anything larger than fullHD */
			1400 => array (
				self::a_thumb => 120,
				self::a_lthumb => 540,
				self::a_stnd => 400,
				self::a_hd => 1200,
			)
		);

		$this->sharesize = self::a_hd . "1200";
	}


	/* init function, should be used in the theme init loop */
	public function init (  ) {

		/* set & register image sizes
		 * will register all the sizes from above
		*/
		foreach ( $this->image_sizes as $resolution => $sizes ) {
			foreach ( $sizes as $prefix => $size ) {
				$crop = ( $prefix == self::a_thumb || $prefix == self::a_lthumb ) ? true : false;
				add_image_size (
					$prefix . $resolution, // name
					$sizes[ $prefix ], // width
					$sizes[ $prefix ], // height
					$crop
				);
			}
		}

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

	/* initialization for any shortcode function;
	 * getting postid
	 */
	private function _init ( &$atts ) {

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

		return array ( 'post' => $post, 'imgids' => $ids, 'columns' => $columns );
	}


	public function adaptgal ( $atts, $content = null ) {
		global $post;
		$category = array_shift( get_the_category( $post->ID ) );
		$template = $category->slug;

		switch ( $template ) {
			case 'photoblog':
				return $this->adaptgal_pure ( $atts, $content );
			default:
				return $this->adaptgal_classic ( $atts, $content );
		}
	}

	/* adaptive gallery shortcode function */
	public function adaptgal_classic ( $atts , $content = null ) {

		$atts = $this->_init ( $atts );
		$post = $atts['post'];
		$imgids = $atts['imgids'];
		$colums = $atts['columns'];

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

		if ($colums == 1 || sizeof( $images ) <= 10 )
			$single = ' single';
		else
		$single = '';

		foreach ($images as $imgid => $img ) {
			$th_id = $img['slug'] . "-" . self::a_thumb;
			$src_id = $img['slug'];

			$keys = array_keys($this->image_sizes);
			$src_src = $bgdata[ array_shift( $keys ) ][ $imgid ][ self::a_hd ];

			$caption = $this->share( $img['sharesrc'][0], $img['title'], get_permalink( $post ), $img['description'] );
			//$caption = $img['title'];

			$th_list[ $imgid ] = '<li><a id="'. $th_id .'" href="#'. $src_id .'">'. $img['title'] .'</a></li>';

			$src_list[ $imgid ] = '<figure id="'. $src_id .'">
				<img src="'. $src_src .'" title="'. $img['title'] .'" alt="'. $img['alttext'] . '" />
				<figcaption>'.$caption.'</figcaption>
			</figure>';

		}

		$output = '
		<section class="adaptgal" id="adaptgal-'. $post->ID.'">
			'. $css .'
			<div class="adaptgal-previews">'. join( "", $src_list ) .'</div>
			<nav class="adaptgal-thumbs'. $single .'"><ul>'. join( "", $th_list ) .'</ul></nav>
		</section>';

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery.adaptive-images' );

		return $output;
	}

	/* adaptive image shortcode function */
	public function adaptimg( $atts , $content=null ) {

		extract( shortcode_atts(array(
			'aid' => false,
			'title' => false,
			'size' => null,
			'share' => false,
			'standalone' => false,
		), $atts));

		if ( empty ( $aid ) )
			return false;

		if ( $size === null )
			$size = self::a_lthumb;

		$cid = $aid . $size;

		$cached = ( self::cache == 1 ) ? wp_cache_get( $cid, self::cache_group ) : false;

		if ( $cached != false ) {
			$images = $cached['images'];
			$bgdata = $cached['bgdata'];
			$css = $cached['css'];
		}
		else {

			$img = $this->get_imagemeta( $aid );

			$images[ $aid ] = $img;
			$keys =  array_keys( $images );
			$bgdata = $this->bgdata ( $keys, $size );
			$css = $this->build_css ( $bgdata, $images );

			$cache = array (
				'images' => $images,
				'bgdata' => $bgdata,
				'css' => $css
			);

			wp_cache_set( $cid, $cache, self::cache_group, self::cache_time );
		}

		$img = array_shift( $images );
		$_id = ( $size == self::a_hd ) ? $img['slug'] : $img['slug'] . '-' . $size;
		$_src = $bgdata[ self::middlesize ][ $aid ][ $size ];

		$cl = array();
		if ( $share )
			$caption = $this->share( $img['sharesrc'][0], $img['title'], get_permalink( $post ), $img['description'] );
		elseif ( ! empty ( $title ))
			$caption = $title;
		elseif ( $standalone ) {
			$caption = '';
			$cl[] = 'adaptimg';
		}
		else
			$caption = $img['title'];

		return '<figure id="'. $_id .'" class="'. implode(" ", $cl ) .'">
			'. $css .'
			<img src="'. $_src .'" title="'. $img['title'] .'" alt="'. $img['alttext'] . '" />
			<figcaption>'. $caption .'</figcaption>
		</figure>';

	}

	/* adaptive gallery shortcode function */
	public function adaptgal_pure( $atts , $content = null ) {

		$atts = $this->_init ( $atts );
		$post = $atts['post'];
		$imgids = $atts['imgids'];
		$colums = $atts['columns'];

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

		$r = '<section class="adaptgal-pure">';

		foreach ($images as $imgid => $img ) {
			$r .= do_shortcode( '[adaptimg aid=' . $imgid .' title="'. $img['title'] .'" size="'. self::a_hd .'" share=1]');
		}

		$r .= '</section>';
		return $r;
	}

	/*
	 *
	 */
	private function get_imagemeta ( $imgid, $getsharesrc = true ) {
		$img = array();
		$__post = get_post( $imgid );

		$img['title'] = esc_attr($__post->post_title);
		$img['alttext'] = strip_tags ( get_post_meta($__post->id, '_wp_attachment_image_alt', true) );
		$img['caption'] = esc_attr($__post->post_excerpt);
		$img['description'] = esc_attr($__post->post_content);
		//echo "<!-- " . var_export( $__post, true) . '-->';
		//$img['slug'] =  ( empty ( $__post->post_name ) ) ? $imgid : esc_attr( $__post->post_name );
		$img['slug'] =  sanitize_title ( $__post->post_title , $imgid );

		if ( is_numeric( substr( $img['slug'], 0, 1) ) )
			$img['slug'] = 'img-' . $img['slug'];

		//$img['slug'] =  $imgid;

		if ( $getsharesrc )
			$img['sharesrc'] = wp_get_attachment_image_src( $imgid, $this->sharesize );

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

	/* creates the required CSS for a specified set of images
	 *
	 * @param $bgdata return of $this->bgdata array for set of attachments
	 * @param $cssprefix replace the default prefixing of elements
	 *
	 */
	private function build_css ( &$bgdata, &$images ) {

		/* css naming conventions */
		//$naming = '#' . self::default_prefix . "-";
		$ctr=0;
		$mq = '';
		$resolutions = array_keys ( $this->image_sizes );

		/* join the backgrounds into areas of CSS media queries */
		foreach ( $bgdata as $resolution => $imgdata ) {

			$imgcss = '';
			foreach ( $imgdata as $imgid => $sizes ) {
				$_id = $images[$imgid]['slug'];
				foreach ( $sizes as $prefix => $url ) {
					// skip prefixing the preview images for nicer urls
					$cssprefix = ( $prefix == self::a_hd ) ? '' : '-' . $prefix;

					$imgcss .= " #" . $_id . $cssprefix . ' { background-image: url('. $url .'); } ';
				}
			}
			if ( $ctr == 0 ) {
				$mq .= $imgcss;
			}
			// last one
			elseif ( $ctr == sizeof ( $this->image_sizes ) - 1 ) {
				//$mq .= '@media ( min-width : 1400px ) {
				$mq .= '@media ( min-width : '. $resolution .'px ) {
						'. $imgcss .'
					}';

			}
			// middle steps
			else {
				//$mq .= '@media ( min-width : 720px ) and ( max-width : 1399px ) {
				$mq .= '@media ( min-width : '. $resolution .'px ) and ( max-width : '. ( $resolutions[$ctr+1] - 1 ) .'px ) {
						'. $imgcss .'
					}';
			}
			$ctr++;
		}

		$mq =  '<style scoped="scoped">' . $mq . '</style>';
		return $mq;

	}

	/* collects attachment image url for all required resolution & site to an array
	 *
	 * @param $imgids array of attachment IDs to get data for
	 * @param $sizeonly either string or key-based array for sizes to include
	 *
	 * @return array of image data resolution -> attachment id -> prefix -> url
	 */
	private function bgdata ( &$imgids, $single = false ) {

		/* alway use arrays, easier */
		if ( ! is_array ( $imgids ) ) $imgids = array ( $imgids );

		/* build array per resolution of every image with as background for that size */
		foreach ( $imgids as $imgid ) {


			foreach ( $this->image_sizes as $resolution => $sizes ) {
				if ( ! $single ) {
					foreach ( $sizes as $prefix => $size ) {
						$img = wp_get_attachment_image_src( $imgid, $prefix . $resolution );
						$__bgimages[$resolution][$imgid][$prefix] = $img[0];
					}
				}
				else {
					$prefix = $single;
					$img = wp_get_attachment_image_src( $imgid, $prefix . $resolution );
					$__bgimages[$resolution][$imgid][$prefix] = $img[0];
				}
			}
		}

		return $__bgimages;

	}


	/* clear cache entries */
	public function cclear ( $post_id = false, $force = false ) {

		/* exit if no post_id is specified */
		if ( empty ( $post_id ) && $force === false ) {
			return false;
		}

		wp_cache_delete ( $post_id, self::cache_group );

	}

	/* share for the frenetic social networks */
	private function share ( $imgsrc , $title, $postlink, $description='' ) {

		$src = urlencode($imgsrc);
		$title = urlencode($title);
		$postlink = urlencode( $postlink );
		$description = urlencode($description);

		$share = array (

			'twitter'=>array (
				'url'=>'https://twitter.com/share?url='. $src .'&text='. $title,
				'title'=>__('Tweet'),
			),

			'facebook'=>array (
				'url'=>'http://www.facebook.com/share.php?u=' . $src . '&t=' . $title,
				'title'=>__('Share on Facebook'),
			),

			'googleplus'=>array (
				'url'=>'https://plus.google.com/share?url=' . $src,
				'title'=>__('Share on Google+'),
			),

			'tumblr'=>array (
				'url'=>'http://www.tumblr.com/share/link?url='.$src.'&name='.$title.'&description='. $postlink,
				'title'=>__('Share on Tumblr'),
			),

			'pinterest' => array (
				'url'=>'https://pinterest.com/pin/create/bookmarklet/?media='. $src .'&url='. $postlink .'&is_video=false&description='. $title,
				'title'=>__('Pin on Pinterest'),
			),

			'download' => array (
				'url'=>$imgsrc,
				'title'=>__('View large image'),
			)
		);

		$out = '';
		foreach ($share as $site=>$details) {
				$st = 'icon-' . $site;
				$out .= '<li><a class="'. $st .'" href="' . $details['url'] . '" title="' . $details['title'] . '"></a></li>';
		}

		$out = '<nav class="share">
				<ul>
				'. $out .'
				</ul>
			</nav>';

		return $out;
	}

	public function adaptive_embededed( $html ) {
		preg_match_all("/<img.*wp-image-(\d*)[^\>]*>/", $html, $inline_images);
		if ( !empty ( $inline_images[0]  )) {
			foreach ( $inline_images[0] as $cntr=>$imgstr ) {
				$aid = $inline_images[1][$cntr];
				$r = do_shortcode( '[adaptimg aid=' . $aid .' size="'. self::a_hd .'" share=0 standalone=1]');
				$html = str_replace ( $imgstr, $r, $html );
			}
		}

		return $html;
	}

}


?>
