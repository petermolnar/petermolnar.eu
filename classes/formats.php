<?php
class pmlnr_formats extends pmlnr_base {

	private $endpoints = array ('yaml');

	public function __construct ( ) {
		add_action( 'init', array( &$this, 'init'));
		add_action( 'template_redirect', array(&$this, 'template_redirect') );
		foreach ($this->endpoints as $endpoint ) {
			add_rewrite_endpoint( $endpoint, EP_PERMALINK | EP_PAGES );
		}
	}

	/* init function, should be used in the theme init loop */
	public function init (  ) {
	}

	/**
	 *
	 */
	public function template_redirect() {
		global $wp_query;

		if (!is_singular())
			return false;

		foreach ($this->endpoints as $endpoint ) {
			if ( isset( $wp_query->query_vars[ $endpoint ]) && method_exists ( $this , $endpoint ) ) {
				echo $this->$endpoint();
				exit;
			}
		}

		return true;
	}

	public static function export_yaml ( $postid = false ) {

		if (!$postid)
			return false;

		$post = get_post($postid);

		if (!static::is_post($post))
			return false;

		$filename = $post->post_name;

		$flatroot = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'flat';
		$flatdir = $flatroot . DIRECTORY_SEPARATOR . $filename;
		$flatfile = $flatdir . DIRECTORY_SEPARATOR . 'item.md';

		$post_timestamp = get_the_modified_time( 'U', $post->ID );
		if ( @file_exists($flatfile) ) {
			$file_timestamp = @filemtime ( $flatfile );
			if ( $file_timestamp == $post_timestamp ) {
				return true;
			}
		}

		$mkdir = array ( $flatroot, $flatdir );
		foreach ( $mkdir as $dir ) {
			if ( !is_dir($dir)) {
				if (!mkdir( $dir )) {
					static::debug_log('Failed to create ' . $dir . ', exiting YAML creation');
					return false;
				}
			}
		}

		// get all the attachments
		$attachments = get_children( array (
			'post_parent'=>$post->ID,
			'post_type'=>'attachment',
			'orderby'=>'menu_order',
			'order'=>'asc'
		));

		// 100 is there for sanity
		// hardlink all the attachments; no need for copy
		// unless you're on a filesystem that does not support hardlinks
		if ( !empty($attachments) && count($attachments) < 100 ) {
			$out['attachments'] = array();
			foreach ( $attachments as $aid => $attachment ) {
				$attachment_path = get_attached_file( $aid );
				$attachment_file = basename( $attachment_path);
				$target_file = $flatdir . DIRECTORY_SEPARATOR . $attachment_file;
				static::debug_log ('should ' . $post->ID . ' have this attachment?: ' . $aid );
				if ( !is_file($target_file))
					link( $attachment_path, $target_file );
			}
		}

		$out = static::yaml();

		// write log
		static::debug_log ('Exporting #' . $post->ID . ', ' . $post->post_name . ' to ' . $flatfile );
		file_put_contents ($flatfile, $out);
		touch ( $flatfile, $post_timestamp );
		return true;
	}

	/**
	 * show post in YAML format (Grav friendly version)
	 */
	public static function yaml ( $postid = false ) {

		if (!$postid) {
			global $post;
		}
		else {
			$post = get_post($postid);
		}

		if (!static::is_post($post))
			return false;

		$postdata = self::raw_post_data($post);

		if (empty($postdata))
			return false;

		$excerpt = false;
		if (isset($postdata['excerpt']) && !empty($postdata['excerpt'])) {
			$excerpt = $postdata['excerpt'];
			unset($postdata['excerpt']);
		}

		$content = $postdata['content'];
		unset($postdata['content']);

		$out = yaml_emit($postdata,  YAML_UTF8_ENCODING );
		if($excerpt) {
			$out .= "\n" . $excerpt . "\n";
		}

		$out .= "---\n" . $content;

		return $out;
	}

	/**
	 * raw data for various representations, like JSON or YAML
	 */
	public static function raw_post_data ( &$post = null ) {
		$post = static::fix_post($post);

		if ($post === false)
			return false;

		$cat = get_the_category( $post->ID );
		if ( !empty($cat) && isset($cat[0])) {
			$category = $cat[0];
		}

		$format = self::get_type($post);

		$taglist = '';
		$t = get_the_tags( $post->ID );
		$tags = array();
		if ( !empty( $t ))
			foreach ( $t as $tag )
				array_push($tags, $tag->name);
		$tags = array_unique($tags);

		$parsedown = new ParsedownExtra();
		$excerpt = $post->post_excerpt;

		$content = $post->post_content;
		$content = self::insert_post_relations($content, $post);

		$search = array ( '”', '“', '’', '–', "\x0D" );
		$replace = array ( '"', '"', "'", '-', '' );
		$excerpt = str_replace ( $search, $replace, $excerpt );
		$excerpt = strip_tags ( $parsedown->text ( $excerpt ) );
		$content = str_replace ( $search, $replace, $content );

		//$search = array ("\n");
		//$replace = array ("");
		//$description = trim ( str_replace( $search, $replace, $excerpt), "'\"" );

		// fix all image attachments: resized -> original
		$urlparts = parse_url(site_url());
		$domain = $urlparts ['host'];
		$wp_upload_dir = wp_upload_dir();
		$uploadurl = str_replace( '/', "\\/", trim( str_replace( site_url(), '', $wp_upload_dir['url']), '/'));

		$pregstr = "/((https?:\/\/". $domain .")?\/". $uploadurl ."\/.*\/[0-9]{4}\/[0-9]{2}\/)(.*)-([0-9]{1,4})×([0-9]{1,4})\.([a-zA-Z]{2,4})/";

		preg_match_all( $pregstr, $content, $resized_images );

		if ( !empty ( $resized_images[0]  )) {
			foreach ( $resized_images[0] as $cntr => $imgstr ) {
				//$location = $resized_images[1][$cntr];
				$done_images[ $resized_images[2][$cntr] ] = 1;
				$fname = $resized_images[2][$cntr] . '.' . $resized_images[5][$cntr];
				$width = $resized_images[3][$cntr];
				$height = $resized_images[4][$cntr];
				$r = $fname . '?resize=' . $width . ',' . $height;
				$content = str_replace ( $imgstr, $r, $content );
			}
		}

		$pregstr = "/(https?:\/\/". $domain .")?\/". $uploadurl ."\/.*\/[0-9]{4}\/[0-9]{2}\/(.*?)\.([a-zA-Z]{2,4})/";

		preg_match_all( $pregstr, $content, $images );
		if ( !empty ( $images[0]  )) {

			foreach ( $images[0] as $cntr=>$imgstr ) {
				//$location = $resized_images[1][$cntr];
				if ( !isset($done_images[ $images[1][$cntr] ]) ){
					if ( !strstr($images[1][$cntr], 'http'))
						$fname = $images[2][$cntr] . '.' . $images[3][$cntr];
					else
						$fname = $images[1][$cntr] . '.' . $images[2][$cntr];

					$content = str_replace ( $imgstr, $fname, $content );
				}
			}
		}

		$author_id = $post->post_author;
		$author =  get_the_author_meta ( 'display_name' , $author_id );
		//$author_url = get_the_author_meta ( 'user_url' , $author_id );

		$meta = array();
		$slugs = get_post_meta($post->ID, '_wp_old_slug', false);
		foreach ($slugs as $slug ) {
			if ( strlen($slug) > 6 )
				$meta['slugs'][] = $slug;
		}

		$meta_to_store = array('author','geo_latitude','geo_longitude','twitter_tweet_id', 'twitter_rt_id', 'twitter_rt_user_id', 'twitter_rt_time', 'twitter_reply_id', 'twitter_reply_user_id', 'instagram_id', 'instagram_url', 'twitter_id', 'twitter_permalink', 'twitter_in_reply_to_user_id', 'twitter_in_reply_to_screen_name','twitter_in_reply_to_status_id','fbpost->ID','webmention_url', 'webmention_type');

		foreach ( $meta_to_store as $meta_key ) {
			$meta_entry = get_post_meta($post->ID, $meta_key, true);
			if ( !empty($meta_entry) && $meta_entry != false ) {
				$meta[ $meta_key ] = $meta_entry;
				if ($meta_key == 'author' )
					$author = $meta_entry;
			}
		}

		if ( isset($meta))

		$out = array (
			'title' => trim(get_the_title( $post->ID )),
			'modified_date' => get_the_modified_time('c', $post->ID),
			'date' => get_the_time('c', $post->ID),
			'slug' => $post->post_name,
			'id' => $post->ID,
			'permalink' => get_permalink( $post ),
			'shortlink' => wp_get_shortlink( $post->ID ),
			'taxonomy' => array (
				'tag' => $tags,
				'category' => $category->name,
				'type' => $format,
			),
			'postmeta' => $meta,
			'author' => $author,
		);

		$webmention_url = get_post_meta ( $post->ID, 'webmention_url', true);
		if (!empty($webmention_url)) {
			$webmention_type = get_post_meta ( $post->ID, 'webmention_type', true);
			if ($webmention_type != 'u-like-of' && $webmention_type != 'u-repost-of')
				$webmention_type = 'u-in-reply-to';

			$out['webmention'] = array (
				'type' => $webmention_type,
				'url' => $webmention_url,
			);
		}

		// get all the attachments
		$attachments = get_children( array (
			'post_parent'=>$post->ID,
			'post_type'=>'attachment',
			'orderby'=>'menu_order',
			'order'=>'asc'
		));

		// 100 is there for sanity
		// hardlink all the attachments; no need for copy
		// unless you're on a filesystem that does not support hardlinks
		if ( !empty($attachments) && count($attachments) < 100 ) {
			$out['attachments'] = array();
			foreach ( $attachments as $aid => $attachment ) {
				$attachment_path = get_attached_file( $aid );
				$attachment_file = basename( $attachment_path);
				array_push($out['attachments'], $attachment_file);
				//$target_file = $flatdir . DIRECTORY_SEPARATOR . $attachment_file;
				//error_log ('should ' . $post->ID . ' have this attachment?: ' . $aid );
				//if ( !is_file($target_file))
				//	link( $attachment_path, $target_file );
			}
		}

		// syndication links
		$_syndicated = get_post_meta ( $post->ID, 'syndication_urls', true );
		if ( !empty ($_syndicated ) ) {
			$out['syndicated'] = explode("\n", trim($_syndicated));
		}

		if($post->post_excerpt) {
			$out['excerpt'] = $excerpt;
		}

		$out['content'] = $content;

		return $out;
	}
}
