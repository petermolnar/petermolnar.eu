<?php
		if ( !is_admin() ) {
			add_action('after_setup_theme', array(&$this, 'buffer_start'));
			add_action('shutdown', array(&$this, 'buffer_end'));
		}

		//add_action( 'init', array( &$this, 'rewrites'));
		//add_action( 'publish_post', array(&$this, 'doyaml' ) );
		//add_action( 'publish_future_post', array(&$this, 'doyaml' ));
		//add_action( 'future_to_publish', array(&$this, 'doyaml' ));

		/*
		global $wp_rewrite;
		$wp_rewrite->set_category_base('');
		*/

		/*
		add_theme_support( 'infinite-scroll', array(
			'container' => 'main-content',
			'render'	=> array($this, 'infinite_scroll_render'),
			'posts_per_page' => 8,
			'footer' => false,
		));
		*/

		// http://codex.wordpress.org/Post_Formats
		//add_theme_support( 'post-formats', array(
		//	'image', 'aside', 'video', 'audio', 'quote', 'link',
		//) );


		// auto-insert featured image
		//add_filter( 'the_content', 'adaptive_images::featured_image', 1 );

		//if ( pmlnr_utils::islocalhost() )
		//	add_filter( 'the_content', 'html_entity_decode', 9 );
		//else

		add_filter( 'the_content', array( &$this, 'post_remote_relation'), 1 );

		// remove x-pingback
		//add_filter('wp_headers', array(&$this, 'remove_x_pingback'));

		//add_filter( 'jetpack_implode_frontend_css', '__return_false' );

		//add_filter( 'pre_option_link_manager_enabled', '__return_true' );

		// webmentions fine tune
		//add_filter("webmention_endpoint", array(&$this, 'ephemeral_webmention_endpoint'));
		//add_filter("webmention_key", array(&$this, 'ephemeral_webmention_key'));

		//register_post_type( 'fotodir', array(
			//'label' => 'Fotodir',
			//'public' => true,
			//'exclude_from_search' => true,
			//'show_ui' => true,
			//'menu_position' => 20,
			//'menu_icon' => 'dashicons-images-alt',
			//'hierarchical' => true,
			//'supports' => array ( 'title' ),
			//'can_export' => false,
		//));

		//register_taxonomy( 'fotodir_category', 'fotodir', array (
			//'label' => 'Category',
			//'public' => true,
			//'show_ui' => true,
			//'hierarchical' => true,
			//'show_admin_column' => true,
			//'rewrite' => array( 'slug' => 'foto' ),
		//));


	public function ephemeral_webmention_endpoint ( $endpoint ) {
		// site_url("?webmention=endpoint")
		$key = $this->ephemeral_webmention_key();
		$endpoint = site_url("?".$key."=endpoint");
		return $endpoint;
	}

	public function ephemeral_webmention_key ( $key = 'webmention' ) {
		$timestamp = get_option('_ephemeral_webmention_timestamp');
		$time = time();

		if ( !$timestamp || $timestamp < ( $time - 300) ) {
			$timestamp = $time;
			update_option ('_ephemeral_webmention_timestamp', $timestamp);
		}

		$key = 'webmention-' . crc32( $timestamp );
		return $key;
	}

	/**
	 *
	 */
	public static function post_remote_relation ( $content ) {
		global $post;

		if (!is_object($post) || !isset($post->ID))
			return $content;

		$r = array();

		$to_check = array (
			'u-in-reply-to' => __("This is a reply to"),
			'u-repost-of' => __("This is a repost of"),
		);

		foreach ($to_check as $relation => $title ) {
			$rel = get_post_meta( $post->ID, $relation, true );
			if ( $rel ) {
				if ( strstr($rel, "\n" ))
					$rel = explode ("\n", $rel);
				else
					$rel = explode (" ", $rel);

				foreach ( $rel as $url ) {
					$url = trim($url);
					$l = sprintf ( "%s: [%s](%s){.%s}\n\n", $title, $url, $url, $relation );
					$r[] = $l;
				}
			}
		}

		if (!empty($r))
			$content = join("\n",$r) . $content;

		return $content;
	}


		/*
		add_meta_box(
			'cc_licence',
			esc_html__( 'Creative Commons', 'petermolnareu' ),
			array(&$this, 'post_meta_display_cc'),
			'post',
			'normal',
			'default'
		);
		*/

	/**
	 * meta field for CC licence
	 *
	public function post_meta_display_cc ( $object, $box ) {
		wp_nonce_field( basename( __FILE__ ), $this->theme_constant );
		$meta = get_post_meta( $object->ID, 'cc', true );
		$default = $meta ? $meta : 'by';
		$cc  = array (
			'by' => __('Attribution'),
			'by-sa' => __('Attribution-ShareAlike'),
			'by-nd' => __('Attribution-NoDerivatives'),
			'by-nc' => __('Attribution-NonCommercial'),
			'by-nc-sa' => __('Attribution-NonCommercial-ShareAlike'),
			'by-nc-nd' => __('Attribution-NonCommercial-NoDerivatives'),
		);

		?>
		<p>
			<?php
				foreach ($cc as $licence => $name ) {
					$selected = ($licence == $default ) ? ' checked="checked"' : '';
					$ccid = 'cc-' . $licence;
					printf ( '<input class="post-format" id="%s" type="radio" value="%s" name="cc"></input>', $ccid, $licence, $selected );
					printf ('<label class="post-format-icon" for="%s">%s</label><br />', $ccid, $name );
				}
			?>
		</p>
		<?php
	}*/


		/*
		if ( static::shorturl_enabled ) {
			$r = static::shortdomain . $post->ID;
		}
		else {
			$url = rtrim( get_bloginfo('url'), '/' ) . '/';
			$r = $url.'?p='.$post->ID;
		}
		*/


	/**
	 *
	 *
	public static function meta () {
		global $post;
		global $wp;
		$m = $e = $l = array();

		$e['content-language'] = get_bloginfo( 'language' );
		$img = get_bloginfo('template_directory') . '/images/favicon.png';

		if (is_singular()) {
			$m['author'] = get_the_author();
			if ( $author_url = get_the_author_meta('user_url'))
				$l['author_url'] = $author_url;

			$m['description'] = strip_tags(get_the_excerpt());

			$tags = get_the_tags();
			if ( !empty( $tags )) {
				foreach ( $tags as $tag ) {
					$taglist[$tag->slug] = $tag->name;
				}
				$m['keywords'] = str_replace('"', "'", join (',', $taglist));
			}

			$thid = get_post_thumbnail_id( $post->ID );
			if ( $thid ) {
				$i = adaptive_images::imagewithmeta( $thid );
				$img = $i['largeurl'];
			}

			$m['date_modified'] = get_the_modified_time( 'c', $post->ID );
			$m['date'] = get_the_time( 'c', $post->ID );
		}

		$m['image'] = $img;

		// classic meta
		ksort($m);
		foreach ($m as $property => $content )
			printf( '<meta property="%s" content="%s" />%s', $property, $content, "\n" );

		// http-equiv meta
		ksort($e);
		foreach ($e as $property => $content )
			printf( '<meta http-equiv="%s" content="%s" />%s', $property, $content, "\n" );

		// link rels
		ksort($l);
		foreach ($l as $property => $content )
			printf( '<link rel="%s" href="%s" />%s', $property, $content, "\n" );

	}
	*/

add_filter ( 'the_content_feed', array(&$this, 'feed_stats'), 1, 2 );
	public function feed_stats ( $content, $feed_type ) {
		$content .= '<img src="https://petermolnar.eu/wp-content/plugins/simple-feed-stats/tracker.php?sfs_tracking=true&sfs_type=open" alt="" />';

		return $content;
	}

	public function infinite_scroll_render() {
		while( have_posts() ) {
			the_post();
			$post_id = get_the_ID();
			$categories = get_the_terms( $post_id, 'category' );
			$category = ( is_array($categories) ) ? array_pop($categories) : null;

			if ( isset($category->slug) && !empty($category->slug) && file_exists( dirname(__FILE__) . '/partials/element-' . $category->slug . '.php' ))
				get_template_part( '/partials/element-' . $category->slug );
			else
				get_template_part( '/partials/element-journal' );
		}
	}
