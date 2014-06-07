<?php

include_once ('classes/theme-cleanup.php');
include_once ('classes/adaptive-images.php');

class petermolnareu {
	const theme_constant = 'petermolnareu';
	const menu_header = 'header';
	const twitteruser = 'petermolnar';
	const fbuser = 'petermolnar.eu';
	const shortdomain = 'http://pmlnr.eu/';
	const shorturl_enabled = true;

	public $base_url = '';
	public $js_dir = '';
	public $css_dir = '';
	public $font_dir = '';
	public $image_dir = '';
	public $theme_url = '';
	public $image_sizes = array();
	public $info = array();
	public $urlfilters = array ();
	private $cleanup = null;
	public $adaptive_images = null;

	public function __construct () {
		$this->base_url = $this->replace_if_ssl( get_bloginfo("url") );
		$this->theme_url = $this->replace_if_ssl( get_bloginfo("stylesheet_directory") );
		$this->js_dir = $this->theme_url . '/assets/js/';
		$this->css_dir = $this->theme_url . '/assets/css/';
		$this->font_dir = $this->theme_url . '/assets/font/';
		$this->image_dir = $this->theme_url . '/assets/image/';
		$this->info = wp_get_theme( );

		$this->cleanup = new theme_cleaup();
		$this->adaptive_images = new adaptive_images( $this );
		add_action( 'init', array( &$this, 'init'));
		add_action( 'init', array( &$this->cleanup, 'filters'));
		add_action( 'init', array( &$this->adaptive_images, 'init'));
		add_action( 'wp_enqueue_scripts', array(&$this,'register_css_js'));
		add_action( 'init', array( &$this, 'rewrites'));

		/* custom post types */
		add_action( 'init', array(&$this, 'add_post_types' ));

		/* excerpt letter counter */
		add_action( 'admin_head-post.php',  array(&$this, 'excerpt_count_js'));
		add_action( 'admin_head-post-new.php',  array(&$this, 'excerpt_count_js' ));

		/* replace shortlink */
		remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );
		add_action( 'wp_head', array(&$this, 'shortlink'));
		add_filter( 'get_shortlink', array(&$this, 'get_shortlink'), 1, 4 );
	}

	public function init () {
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'menus' );
		add_theme_support( 'automatic-feed-links' );

		/* http://codex.wordpress.org/Post_Formats */
		add_theme_support( 'post-formats', array(
			'image', 'aside', 'video', 'audio', 'quote', 'link', 'gallery', 'status'
		) );

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support( 'html5', array(
			'search-form', 'comment-form', 'comment-list', 'gallery', 'caption'
		) );

		/* add main menus */
		register_nav_menus( array(
			self::menu_header => __( self::menu_header , self::theme_constant ),
		) );

		/* enable custom uploads */
		add_filter('upload_mimes', array( &$this, 'custom_upload_mimes' ) );

		/* add syntax highlighting */
		add_shortcode('code', array ( &$this, 'syntax_highlight' ) );
		add_shortcode('cc', array ( &$this, 'syntax_highlight' ) );

		/* legacy shortcode handler */
		add_filter( 'the_content', array( &$this, 'legacy' ), 1);
		add_filter( 'the_content', 'shortcode_unautop', 100 );

		/* post type additional data */
		add_filter( 'the_content', array(&$this, 'add_post_format_data'), 1 );

		/* Link all @name to Twitter */
		//add_filter('the_content', array( &$this, 'twtreplace'));
		//add_filter('comment_text', array( &$this, 'twtreplace'));

		/* overwrite gallery shortcode */
		remove_shortcode('gallery');
		add_shortcode('gallery', array (&$this->adaptive_images, 'adaptgal' ) );

		/* have links *
		add_filter( 'pre_option_link_manager_enabled', '__return_true' );*/

		/* additional user meta */
		add_filter('user_contactmethods', array( &$this, 'add_user_meta_fields'));

	}

	/**
	 * register & queue css & js
	 */
	public function register_css_js () {
		/* enqueue CSS */

		wp_register_style( 'reset', $this->css_dir . 'reset.css', false, null );
		wp_enqueue_style( 'reset' );

		wp_register_style( 'style', $this->theme_url . '/style.css' , array('reset'), $this->info->version );
		wp_enqueue_style( 'style' );

		/* syntax highlight */
		wp_register_style( 'prism', $this->css_dir . 'prism.css', false, null );
		wp_register_script( 'prism' , $this->js_dir . 'prism.js', false, null, true );

		/* CDN scripts */
		wp_deregister_script( 'jquery' );
		wp_register_script( 'jquery', $this->replace_if_ssl( 'http://code.jquery.com/jquery-1.11.0.min.js' ), false, null, false );
		wp_enqueue_script( 'jquery' );

		/* for adaptive image class, TODO move here */
		wp_register_script( 'jquery.adaptive-images', $this->js_dir . 'adaptive-images.js', array('jquery'), null, true );

		/* this is to have reply fields correctly */
		if ( (!is_admin()) && is_singular() && comments_open() && get_option('thread_comments') )
			wp_enqueue_script( 'comment-reply' );
	}

	/**
	 * replaces http:// with https:// in an url if server is currently running on https
	 *
	 * @param string $url URL to check
	 *
	 * @return string URL with correct protocol
	 *
	 */
	public function replace_if_ssl ( $url ) {
		if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' )
			$_SERVER['HTTPS'] = 'on';

		if ( isset($_SERVER['HTTPS']) && (( strtolower($_SERVER['HTTPS']) == 'on' )  || ( $_SERVER['HTTPS'] == '1' ) ))
			$url = str_replace ( 'http://' , 'https://' , $url );

		return $url;
	}

	/**
	 * extend allowed mime types
	 *
	 * @param array $existing_mimes Array containing existing mime types
	 */
	public function custom_upload_mimes ( $existing_mimes=array() ) {
		$existing_mimes['svg'] = 'image/svg+xml';
		$existing_mimes['webp'] = 'image/webp';

		return $existing_mimes;
	}

	/**
	 * updated share function: retweet/reshare/reshit if SNAP entry or something else is
	 * available
	 */
	public function share_ ( $link , $title, $comment=false, $parent=false ) {
		global $post;
		global $nxs_snapAvNts;

		$link = urlencode($link);
		$title = urlencode($title);
		$desciption = urlencode(get_the_excerpt());
		$type = get_post_type ( $post );
		$media = wp_get_attachment_image_src(get_post_thumbnail_id( $post->ID ),'large', true);
		$media_url = ( ! $media ) ? false : $media[0];

		$snap_options = get_option('NS_SNAutoPoster');

		/* all SNAP entries are in separate meta entries for the post based on the service name's "code" */
		foreach ( $nxs_snapAvNts as $key => $serv ) {
			$mkey = 'snap'. $serv['code'];
			$urlkey = $serv['lcode'].'URL';
			$okey = $serv['lcode'];
			$s = strtolower($serv['name']);
			$metas = maybe_unserialize(get_post_meta($post->ID, $mkey, true ));
			if ( !empty( $metas ) && is_array ( $metas ) ) {
				foreach ( $metas as $cntr => $m ) {
					$pgID = false;
					if ( isset ( $m['isPosted'] ) && $m['isPosted'] == 1 ) {
						/* postURL entry will only be used if there's no urlmap set for the service above
						 * this is due to either missing postURL values or buggy entries */
						$pgIDs[ $s ] = $m['pgID'];
					}
					$surl[ $s ] = $snap_options[$okey][$cntr][$urlkey];
				}
			}
		}

		/* Twitter */
		$service = 'twitter';
		$repost_id = get_post_meta($post->ID, 'twitter_rt_id', true );
		$repost_uid = get_post_meta($post->ID, 'twitter_rt_user_id', true );
		$tw = get_post_meta( $post->ID, 'twitter_tweet_id', true );
		if ( !empty( $pgIDs[ $service ] ) ) {
			$url = 'https://twitter.com/intent/retweet?tweet_id=' . $pgIDs[ $service ];
			$txt = __( 'Reweet', self::theme_constant );
		}
		elseif ( !empty($repost_id) && !empty($repost_uid) ) {
			$url = 'https://twitter.com/intent/retweet?tweet_id=' . $repost_id;
			$txt = __( 'Reweet', self::theme_constant );
		}
		elseif ( !empty($tw) ) {
			$url = 'https://twitter.com/intent/retweet?tweet_id=' . $tw;
			$txt = __( 'Reweet', self::theme_constant );
		}
		else {
			$url = 'https://twitter.com/share?url='. $link .'&text='. $title;
			$txt = __( 'Tweet', self::theme_constant );
		}
		$shlist[] = '<a class="icon-'. $service .'" href="' . $url . '">'. $txt .'</a>';

		/* Facebook */
		$service = 'facebook';
		if ( !empty( $pgIDs[ $service ] ) ) $pgIDs[$service] = explode ( '_', $pgIDs[$service] );
		if ( is_array ( $pgIDs[$service] ) && !empty($pgIDs[$service][1]) ) {
			//https://www.facebook.com/sharer.php?s=100&p[url]=http://www.example.com/&p[images][0]=/images/image.jpg&p[title]=Title&p[summary]=Summary
			//$url = 'https://www.facebook.com/sharer/sharer.php?' . urlencode ('s=99&p[0]='. $pgIDs[$service][0] .'&p[1]='. $pgIDs[$service][1] );
			// '&p[images][0]='.  $media_url . '&p[title]=' . $title . '&p[summary]=' ) . $desciption;
			$base = '%BASE%/posts/%pgID%';
			$search = array('%BASE%', '%pgID%' );
			$replace = array ( $surl[ $service ], $pgIDs[$service][1] );
			$url =  'http://www.facebook.com/share.php?u=' . str_replace ( $search, $replace, $base );
			$txt = __( 'Reshare', self::theme_constant );
		}
		else {
			$url = 'http://www.facebook.com/share.php?u=' . $link . '&t=' . $title;
			$txt = __( 'Share', self::theme_constant );
		}

		$shlist[] = '<a class="icon-'. $service .'" href="' . $url . '">'. $txt .'</a>';

		/* Google Plus */
		$service = 'googleplus';
		$url = 'https://plus.google.com/share?url=' . $link;
		$txt = __( '+1', self::theme_constant );
		$shlist[] = '<a class="icon-'. $service .'" href="' . $url . '">'. $txt .'</a>';

		/* Tumblr */
		$service = 'tumblr';
		$url = 'http://www.tumblr.com/share/link?url='.$link.'&name='.$title.'&description='. $desciption;
		$txt = __( 'share', self::theme_constant );
		$shlist[] = '<a class="icon-'. $service .'" href="' . $url . '">'. $txt .'</a>';

		/* Pinterest */
		if ( $media_url ) {
			$purl = ( $parent != false ) ? urlencode($parent) : $link;
			$service = 'pinterest';
			$url = 'https://pinterest.com/pin/create/bookmarklet/?media='. $media_url .'&url='. $purl .'&is_video=false&description='. $title;
			$txt = __( 'pin', self::theme_constant );
			$shlist[] = '<a class="icon-'. $service .'" href="' . $url . '">'. $txt .'</a>';
		}

		/* comment link */
		if ($comment) {
			$service = 'comment';
			$url = get_permalink( $post->ID ) . "#comments";
			$txt = __( 'comment', self::theme_constant );
			$shlist[] = '<a rel="discussion" class="icon-'. $service .'" href="' . $url . '">'. $txt .'</a>';
		}

		$out = '
			<action do="post" with="'. get_the_permalink() .'" class="share">
			<h6>' . __('Share:', self::theme_constant ) . '</h6>
			<ul><li>'. implode( '</li><li>', $shlist ) .'</li></ul>
			</action>';

		return $out;
	}


	/**
	 * Returns unordered list of current category's posts
	 *
	 */
	public function list_posts( $category, $limit=-1 , $from=0 ) {
		$req = ( $limit == -1 ) ? -1 : $from + $limit;
		$category_meta = get_metadata ( 'taxonomy' , $category->term_id, '');
		$category_meta['order-by'] = empty ( $category_meta['order-by'] ) ? 'date' : $category_meta['order-by'];

		$q = array (
			'category' => $category->cat_ID,
			'orderby' => $category_meta['order-by'],
			'order' => 'DESC' ,
			'numberposts' => $req
		);
		$posts = get_posts( $q );

		if ( $from != false )
			for ($i=0; $i<$from; $i++)
				array_shift ( $posts );

		if ( !empty ( $posts ))
		{
			$list = '';
			foreach ($posts as $post) {
				$post_title = htmlspecialchars(stripslashes($post->post_title));
				$list .= '
						<li>
							<a href="' . get_permalink($post->ID) . '" title="'. $post_title .'" >
								' . $post_title . '
							</a>
						</li>';
				$i++;
			}

			$out = '
			<nav class="sidebar-postlist">
				<ul class="postlist">
				'. $list .'
				</ul>
			</nav>';
		}

		return $out;
	}

	/**
	 * related posts, based shared tags
	 *
	 */
	public function related_posts ( $_post, $onlysiblings = false ) {
		$args = array( 'fields' => 'ids' );
		$tags = wp_get_post_tags($_post->ID, $args );
		$categories = wp_get_post_categories ( $_post->ID, $args );

		$list = '';

		if ( !empty($tags) ) {

			$args=array(
				'tag__in' => $tags,
				'post__not_in' => array($_post->ID),
				'posts_per_page'=>12,
				'ignore_sticky_posts'=>1
			);

			if ( $onlysiblings ) {
				$args['category__in'] = $categories;
			}

			$_query = new WP_Query( $args );

			while( $_query->have_posts() ) {
				$_query->the_post();

				$post_title = get_the_title();

				$list .= '
						<li>
							<a href="' . get_permalink() . '" title="'. $post_title .'" >
								' . $post_title . '
							</a>
						</li>';
				wp_reset_postdata();
			}
		}

		$out = '<nav class="sidebar-postlist">
				<h3 class="postlist-title">'. __( "Related posts" ) . '</h3>
				<ul class="postlist">
				'. $list .'
				</ul>
			</nav>';

		return $out;
	}

	/**
	 * syntax highlight with prism (it's not that PRISM, don't worry)
	 * http://prismjs.com/
	 *
	 */
	public function syntax_highlight ( $atts ,  $content = null ) {
		wp_enqueue_script( 'prism' );
		wp_enqueue_style( 'prism' );

		extract( shortcode_atts(array(
			'lang' => 'none'
		), $atts));

		if ( empty( $content ) ) {
			$return = false;
		}
		else {
			$search = array( '<', '>' );
			$replace = array( '&lt;', '&gt;' );
			$content = str_replace ( $search, $replace, $content );
			$return = '<pre class="line-numbers"><code class="language-' . $lang . '">' . trim(str_replace( "\t", "  ", $content ) ) . '</code></pre>';
		}

		return $return;

	}

	/**
	 * replacing legacy code & formatting with newer ones
	 *
	 */
	public function legacy ( $src ) {
		/* old syntax highlight, seach for <code> tags */
		$matches = array();
		preg_match_all ( "'<code>(.*?)</code>'si", $src , $matches, PREG_SET_ORDER );

		foreach ($matches as $match ) {
			$shortcode = '[code]'.trim($match[1]).'[/code]';
			$src = str_replace ( $match[0], $shortcode, $src );
		}

		return $src;
	}

	/**
	 * character counter for text content field & excerpt field
	*/
	public function excerpt_count_js(){
		echo '<script>jQuery(document).ready(function(){

				if( jQuery("#excerpt").length ) {
					jQuery("#postexcerpt .handlediv").after("<input type=\'text\' value=\'0\' maxlength=\'3\' size=\'3\' id=\'excerpt_counter\' readonly=\'\' style=\'background:#fff; position:absolute;top:0.2em;right:2em; color:#666;\'>");
					jQuery("#excerpt_counter").val(jQuery("#excerpt").val().length);
					jQuery("#excerpt").keyup( function() {
						jQuery("#excerpt_counter").val(jQuery("#excerpt").val().length);
					});
				}

				if( jQuery("#wp-word-count").length ) {
					jQuery("#wp-word-count").after("<td id=\'wp-character-count\'>Character count: <span class=\'character-count\'>0</span></td>");
					jQuery("#wp-character-count .character-count").html(jQuery("#wp-content-wrap .wp-editor-area").val().length);
					jQuery("#wp-content-wrap .wp-editor-area").keyup( function() {
						jQuery("#wp-character-count .character-count").html(jQuery("#wp-content-wrap .wp-editor-area").val().length);
					});
				}

		});</script>';
	}

	/**
	 * from: http://dimox.net/wordpress-breadcrumbs-without-a-plugin/
	 */
	public function dimox_breadcrumbs() {

		/* === OPTIONS === */
		$text['home']	 = 'Home'; // text for the 'Home' link
		$text['category'] = '%s'; // text for a category page
		$text['search']	= 'Search for "%s"'; // text for a search results page
		$text['tag']	 = '%s'; // text for a tag page
		$text['author']	= '%s'; // text for an author page
		$text['404']	 = 'Error 404'; // text for the 404 page

		$show_current	= 1; // 1 - show current post/page/category title in breadcrumbs, 0 - don't show
		$show_on_home	= 0; // 1 - show breadcrumbs on the homepage, 0 - don't show
		$show_home_link = 0; // 1 - show the 'Home' link, 0 - don't show
		$show_title	 = 1; // 1 - show the title for the links, 0 - don't show
		$delimiter	 = ' &raquo; '; // delimiter between crumbs
		$before		 = '<span class="current">'; // tag before the current crumb
		$after		 = '</span>'; // tag after the current crumb
		/* === END OF OPTIONS === */

		global $post;
		$home_link	= home_url('/');
		$link_before = '<span typeof="v:Breadcrumb">';
		$link_after	= '</span>';
		$link_attr	= ' rel="v:url" property="v:title"';
		$link		 = $link_before . '<a' . $link_attr . ' href="%1$s">%2$s</a>' . $link_after;
		$parent_id	= $parent_id_2 = $post->post_parent;
		$frontpage_id = get_option('page_on_front');

		if (is_home() || is_front_page()) {

			if ($show_on_home == 1) echo '<nav class="breadcrumbs"><div class="inner"><a href="' . $home_link . '">' . $text['home'] . '</a></div></nav>';

		} else {

			echo '<nav class="breadcrumbs" xmlns:v="http://rdf.data-vocabulary.org/#"><div class="inner">';
			if ($show_home_link == 1) {
				echo '<a href="' . $home_link . '" rel="v:url" property="v:title">' . $text['home'] . '</a>';
				if ($frontpage_id == 0 || $parent_id != $frontpage_id) echo $delimiter;
			}

			if ( is_category() ) {
				$this_cat = get_category(get_query_var('cat'), false);
				if ($this_cat->parent != 0) {
					$cats = get_category_parents($this_cat->parent, TRUE, $delimiter);
					if ($show_current == 0) $cats = preg_replace("#^(.+)$delimiter$#", "$1", $cats);
					$cats = str_replace('<a', $link_before . '<a' . $link_attr, $cats);
					$cats = str_replace('</a>', '</a>' . $link_after, $cats);
					if ($show_title == 0) $cats = preg_replace('/ title="(.*?)"/', '', $cats);
					echo $cats;
				}
				if ($show_current == 1) echo $before . sprintf($text['category'], single_cat_title('', false)) . $after;

			} elseif ( is_search() ) {
				echo $before . sprintf($text['search'], get_search_query()) . $after;

			} elseif ( is_day() ) {
				echo sprintf($link, get_year_link(get_the_time('Y')), get_the_time('Y')) . $delimiter;
				echo sprintf($link, get_month_link(get_the_time('Y'),get_the_time('m')), get_the_time('F')) . $delimiter;
				echo $before . get_the_time('d') . $after;

			} elseif ( is_month() ) {
				echo sprintf($link, get_year_link(get_the_time('Y')), get_the_time('Y')) . $delimiter;
				echo $before . get_the_time('F') . $after;

			} elseif ( is_year() ) {
				echo $before . get_the_time('Y') . $after;

			} elseif ( is_single() && !is_attachment() ) {
				if ( get_post_type() != 'post' ) {
					$post_type = get_post_type_object(get_post_type());
					$slug = $post_type->rewrite;
					printf($link, $home_link . '/' . $slug['slug'] . '/', $post_type->labels->singular_name);
					if ($show_current == 1) echo $delimiter . $before . get_the_title() . $after;
				} else {
					$cat = get_the_category(); $cat = $cat[0];
					$cats = get_category_parents($cat, TRUE, $delimiter);
					if ($show_current == 0) $cats = preg_replace("#^(.+)$delimiter$#", "$1", $cats);
					$cats = str_replace('<a', $link_before . '<a' . $link_attr, $cats);
					$cats = str_replace('</a>', '</a>' . $link_after, $cats);
					if ($show_title == 0) $cats = preg_replace('/ title="(.*?)"/', '', $cats);
					echo $cats;
					if ($show_current == 1) echo $before . get_the_title() . $after;
				}

			} elseif ( !is_single() && !is_page() && get_post_type() != 'post' && !is_404() ) {
				$post_type = get_post_type_object(get_post_type());
				echo $before . $post_type->labels->singular_name . $after;

			} elseif ( is_attachment() ) {
				$parent = get_post($parent_id);
				$cat = get_the_category($parent->ID); $cat = $cat[0];
				if ($cat) {
					$cats = get_category_parents($cat, TRUE, $delimiter);
					$cats = str_replace('<a', $link_before . '<a' . $link_attr, $cats);
					$cats = str_replace('</a>', '</a>' . $link_after, $cats);
					if ($show_title == 0) $cats = preg_replace('/ title="(.*?)"/', '', $cats);
					echo $cats;
				}
				printf($link, get_permalink($parent), $parent->post_title);
				if ($show_current == 1) echo $delimiter . $before . get_the_title() . $after;

			} elseif ( is_page() && !$parent_id ) {
				if ($show_current == 1) echo $before . get_the_title() . $after;

			} elseif ( is_page() && $parent_id ) {
				if ($parent_id != $frontpage_id) {
					$breadcrumbs = array();
					while ($parent_id) {
						$page = get_page($parent_id);
						if ($parent_id != $frontpage_id) {
							$breadcrumbs[] = sprintf($link, get_permalink($page->ID), get_the_title($page->ID));
						}
						$parent_id = $page->post_parent;
					}
					$breadcrumbs = array_reverse($breadcrumbs);
					for ($i = 0; $i < count($breadcrumbs); $i++) {
						echo $breadcrumbs[$i];
						if ($i != count($breadcrumbs)-1) echo $delimiter;
					}
				}
				if ($show_current == 1) {
					if ($show_home_link == 1 || ($parent_id_2 != 0 && $parent_id_2 != $frontpage_id)) echo $delimiter;
					echo $before . get_the_title() . $after;
				}

			} elseif ( is_tag() ) {
				echo $before . sprintf($text['tag'], single_tag_title('', false)) . $after;

			} elseif ( is_author() ) {
				global $author;
				$userdata = get_userdata($author);
				echo $before . sprintf($text['author'], $userdata->display_name) . $after;

			} elseif ( is_404() ) {
				echo $before . $text['404'] . $after;

			} elseif ( has_post_format() && !is_singular() ) {
				echo get_post_format_string( get_post_format() );
			}

			if ( get_query_var('paged') ) {
				if ( is_category() || is_day() || is_month() || is_year() || is_search() || is_tag() || is_author() ) echo ' (';
				echo __('Page') . ' ' . get_query_var('paged');
				if ( is_category() || is_day() || is_month() || is_year() || is_search() || is_tag() || is_author() ) echo ')';
			}

			echo '</div></nav><!-- .breadcrumbs -->';

		}
	} // end dimox_breadcrumbs()


	/**
	 * display article pubdate
	 */
	public function article_time () {
		global $post;
		?>
		<time class="article-pubdate dt-published" pubdate="<?php the_time( 'r' ); ?>"><?php the_time( get_option('date_format') ); ?> <?php the_time( get_option('time_format') ); ?></time>
		<time class="hide dt-updated" pubdate="<?php the_modified_time( 'r' ); ?>"><?php the_time( get_option('date_format') ); ?><?php the_time( get_option('time_format') ); ?></time>
		<?php
	}

	/**
	 * function name speaks for itself
	 */
	public function replace_images_with_adaptive ( $html ) {
		return $this->adaptive_images->adaptive_embededed( $html );
	}

	/**
	 * Twitter link all @ starting string
	 */
	public function twtreplace($content) {

		//$twtreplace = preg_replace('/([^a-zA-Z0-9-_&])@([0-9a-zA-Z_]+)/',"$1<a href=\"http://twitter.com/$2\" target=\"_blank\" rel=\"nofollow\">@$2</a>",$content);
		$exceptions = array ( 'media' => 1, 'import' => 1 );
		preg_match_all('/@([0-9a-zA-Z_]+)/', $content, $twusers);

		if ( !empty ( $twusers[0] ) && !empty ( $twusers[1] )) {
			foreach ( $twusers[1] as $cntr=>$twname ) {
				$repl = $twusers[0][$cntr];
				if ( ! isset($exceptions[$twname]) )
					$content = str_replace ( $repl, '<a href="https://twitter.com/'.$twname.'" rel="nofollow">@'.$twname.'</a>', $content );
			}
		}

		//preg_match_all('/#([0-9a-zA-Z_-]+)/', $content, $hashtags);
		//if ( !empty ( $hashtags[0] ) && !empty ( $hashtags[1] )) {
			//foreach ( $hashtags[1] as $cntr=>$tagname ) {
				//$repl = $hashtags[0][$cntr];
				//$content = str_replace ( $repl, '<a href="https://twitter.com/hashtag/'. $tagname.'?src=hash" rel="nofollow">#'.$tagname.'</a>', $content );
			//}
		//}

		return $content;
	}

	/**
	 * auto-link all plain text links, exclude anything in html tags
	 */
	public function linkify ( $content ) {
		//$content = preg_replace('$(https?://[a-z0-9_./?=&#-]+)(?![^<>]*>)$i', ' <a href="$1" target="_blank">$1</a> ', $content." ");
		//$content = preg_replace('$(www\.[a-z0-9_./?=&#-]+)(?![^<>]*>)$i', '<a target="_blank" href="http://$1"  target="_blank">$1</a> ', $content." ");
		return $content;
	}

	/**
	 *  Peter Molnar vcard
	 */
	public function author ( $short=false, $uid = false ) {
		if ( $short ) {
			global $post;
			$aid =  get_the_author_meta( 'ID' );
			$aemail = get_the_author_meta ( 'user_email' , $aid );
			$aname = get_the_author_meta ( 'display_name' , $aid );
			$gravatar = md5( strtolower( trim(  $aemail )));
			$class = 'p-author h-card vcard';
		}
		else {
			$aid = ($uid==false)? 1 : $uid;
			$aemail = get_the_author_meta ( 'user_email' , $aid );
			$aname = get_the_author_meta ( 'display_name' , $aid );
			$gravatar = md5( strtolower( trim(  $aemail )));
			$class = 'h-card vcard';
		}

		$out = '<span class="'. $class .'">
				<a class="fn p-name url u-url" href="'. get_the_author_meta ( 'user_url' , $aid ) .'">'. $aname .'</a>
				<img class="photo avatar u-photo u-avatar" src="https://s.gravatar.com/avatar/'.$gravatar.'?s=64" style="width:12px; height:12px;" alt="Photo of '. $aname .'"/>';
		if ( !$short ) {
			$out .= '<a rel="me" class="u-email email" href="mailto:'.$aemail.'" title="'.$aname.' email address">'.$aemail.'</a>';

			/* social */
			$fb =  rtrim(get_the_author_meta ( 'facebook' , $aid ), '/');
			if ( !empty ($fb)) {
				$fbname = substr( $fb , strrpos($fb, '/') + 1);
				$socials['facebook'] = '<a rel="me" class="u-facebook x-facebook url u-url" href="'.$afb.'" title="'.$aname.' @ Facebook">'.$fbname.'</a>';
			}

			$tw = get_the_author_meta ( 'twitter' , $aid );
			if ( !empty ($tw)) {
				$socials['twitter'] = '<a rel="me" class="u-twitter x-twitter url u-url" href="https://twitter.com/'.$tw.'" title="'.$aname.' @ Twitter">'.$tw.'</a>';
			}

			$g = rtrim( get_the_author_meta ( 'googleplus' , $aid ), '/' );
			if ( !empty ($g)) {
				$gname = substr( $g , strrpos($g, '/') + 1);
				$socials['googleplus'] = '<a rel="me" class="u-googleplus x-googleplus url u-url" href="'.$g.'" title="'.$aname.' @ Google+">'.$gname.'</a>';
			}

			$l = rtrim(get_the_author_meta ( 'linkedin' , $aid ), '/');
			if ( !empty ($l)) {
				$lname = substr( $l , strrpos($l, '/') + 1);
				$socials['linkedin'] = '<a rel="me" class="u-linkedin x-linkedin url u-url" href="'.$l.'" title="'.$aname.' @ LinkedIn">'.$lname.'</a>';
			}

			$gh = get_the_author_meta ( 'github' , $aid );
			if ( !empty ($gh)) {
				$socials['googleplus'] = '<a rel="me" class="u-github x-github url u-url" href="https://github.com/'.$gh.'" title="'.$aname.' @ Github">'.$gh.'</a>';
			}

			if ( !empty($socials)) {
				$out .= '<span class="spacer">Find me:</span>' . join ( "\n", $socials);
			}
		}
		$out .= '</span>';
		return $out;
	}

	/**
	 * get webmention/retweet/reply data and display origin link
	 */
	public function repost_data() {
		global $post;

		/* Twitter retweet */
		$repost_id = get_post_meta($post->ID, 'twitter_rt_id', true );
		$repost_uid = get_post_meta($post->ID, 'twitter_rt_user_id', true );
		if ( !empty($repost_id) && !empty($repost_uid) ) {
			$origin = 'https://twitter.com/'. $repost_uid .'/status/'. $repost_id; ?>
				<p class="urel"><?php _e("Retweeted from: ") ?><a class="u-repost-of" href="<?php echo $origin; ?>" ><?php echo $origin; ?></a></p>
			<?php
		}
		unset ( $repost_id, $repost_uid );

		/* Twitter reply */
		$reply_id = get_post_meta($post->ID, 'twitter_reply_id', true );
		$reply_uid = get_post_meta($post->ID, 'twitter_reply_user_id', true );
		if ( !empty($reply_id) && !empty($reply_uid) ) {
			$origin = 'https://twitter.com/'. $reply_uid .'/status/'. $reply_id; ?>
				<p class="urel"><?php _e("In reply to: ") ?><a rel="in-reply-to" class="u-in-reply-to" href="<?php echo $origin; ?>" ><?php echo $origin; ?></a></p>
			<?php
		}
		unset ( $reply_id, $reply_uid );

		/* General reply */
		$reply_url = get_post_meta($post->ID, 'u-in-reply-to', true );
		if ( !empty($reply_url) ) { ?>
				<p class="urel"><?php _e("In reply to: ") ?><a rel="in-reply-to" class="u-in-reply-to" href="<?php echo $reply_url; ?>" ><?php echo $reply_url; ?></a></p>
			<?php
		}
		unset ( $reply_url );

		/* General repost */
		$repost_url = get_post_meta($post->ID, 'u-repost-of', true );
		if ( !empty($repost_url) ) { ?>
				<p class="urel"><?php _e("Repost of: ") ?><a class="u-repost-of" href="<?php echo $repost_url; ?>" ><?php echo $repost_url; ?></a></p>
			<?php
		}
		unset ( $repost_url );

		/* General url *
		$url = get_post_meta($post->ID, 'u-source', true );
		if ( !empty($url) ) { ?>
				<p class="urel"><?php _e("Source: ") ?><a class="u-source" href="<?php echo $url; ?>" ><?php echo $repost_url; ?></a></p>
			<?php
		}
		unset ( $url );
		*/

		/* link meta */
		$url = get_post_meta($post->ID, '_format_link_url', true );
		$title = get_the_title ($post->ID );
		$webmention = get_post_meta($post->ID, '_format_link_webmention', true );
		if ( !empty($url ) && !empty($webmention) && $webmention != 'none' ) {
			switch ($webmention) {
				case 'rsvp-yes':
				case 'rsvp-no':
				case 'reply':
					?> <p><?php _e('This is a reply to: ', self::theme_constant )?><a class="u-in-reply-to icon-link-ext-alt" href="<?php echo $url ?>"><?php echo $title ?></a></p><?php
					break;
				case 'repost':
					?> <p><?php _e('Reposted from: ', self::theme_constant )?><a class="u-repost-of icon-link-ext-alt" href="<?php echo $url ?>"><?php echo $title ?></a></p><?php
					break;
				case 'like':
					?> <p><a class="u-like u-like-of icon-thumbs-up" href="<?php echo $url ?>"><?php echo $title ?></a></p><?php
					break;
			}

			if ( strstr( $webmention, 'rsvp-' ) ) {
				switch ($webmention) {
					case 'rsvp-yes':
						?><data class="p-rsvp" value="yes"><?php _e("I'll attend!", self::theme_constant ); ?></data><?php
						break;
					case 'rsvp-no':
						?><data class="p-rsvp" value="no"><?php _e("I cannot make it.", self::theme_constant ); ?></data><?php
						break;
				}
			}
		}
		unset ($url, $title, $webmention, $data);

	}

	/**
	 *
	 */
	public function rewrites () {
		add_rewrite_rule("indieweb-decentralize-web-centralizing", "indieweb-decentralize-web-centralizing-ourselves", "bottom" );
		add_rewrite_rule("/wordpress/(.*)", "/open-source/$matches[1]", "bottom" );
		add_rewrite_rule("/b/(.*)", "/blips/$matches[1]", "bottom" );
		add_rewrite_rule("/open-source/wordpress/(.*)", "/open-source/$matches[1]", "bottom" );
		add_rewrite_rule("/blog/(.*)", "/journal/$matches[1]", "bottom" );
	}

	public function shorturl () {
		global $post;

		if ( self::shorturl_enabled ) {
			return self::shortdomain . $post->ID;
		}
		else {
			$url = rtrim( get_bloginfo('url'), '/' ) . '/';
			return $url.'?p='.$post->ID;
		}
	}

	public function get_shortlink ( $shortlink, $id, $context, $allow_slugs ) {
		return $this->shorturl();
	}

	public function shortlink () {
		echo '<link rel="shortlink" href="'. $this->shorturl() . '" />'."\n";
	}

	/**
	 *
	 */
	public function add_post_types () {

		//register_post_type( 'notes',
			//array(
				//'labels' => array(
					//'name' => __( 'Note', self::theme_constant ),
					//'singular_name' => __( 'Notes', self::theme_constant ),
					//'menu_name' => __( 'Notes', self::theme_constant ),
				//),
				//'public' => true,
				//'has_archive' => true,
				//'menu_position' => 5,
				//'menu_icon' => 'dashicons-tagcloud',
				//'supports' => array (
					//'editor',
					//'author',
					//'custom-fields',
				//),
			//)
		//);

		//register_taxonomy( 'relation', 'notes', array (
			//'labels' => array(
				//'name'                       => _x( 'Relations', 'Taxonomy General Name', self::theme_constant ),
				//'singular_name'              => _x( 'Relation', 'Taxonomy Singular Name', self::theme_constant ),
				//'menu_name'                  => __( 'Relations', self::theme_constant ),
				//'all_items'                  => __( 'All relations', self::theme_constant ),
				//'parent_item'                => __( 'Parent item', self::theme_constant ),
				//'parent_item_colon'          => __( 'Parent Item:', self::theme_constant ),
				//'new_item_name'              => __( 'New Relation', self::theme_constant ),
				//'add_new_item'               => __( 'Add new relation', self::theme_constant ),
				//'edit_item'                  => __( 'Edit relation', self::theme_constant ),
				//'update_item'                => __( 'Update Item', self::theme_constant ),
				//'separate_items_with_commas' => __( 'Separate relations with commas', self::theme_constant ),
				//'search_items'               => __( 'Search relations', self::theme_constant ),
				//'add_or_remove_items'        => __( 'Add or remove relations', self::theme_constant ),
				//'choose_from_most_used'      => __( 'Choose from the most used relations', self::theme_constant ),
				//'not_found'                  => __( 'Not Found', self::theme_constant ),
			//),
			//'public' => false,
			//'show_ui' => true,
			//'hierarchical' => true,
			//'show_admin_column' => true,
			//'show_in_nav_menus' => false,
			//'show_tagcloud' => false,
		//) );
	}

	public function add_post_format_data ( $src ) {
		global $post;
		$format = get_post_format ( $post->ID );

		/* quote format */
		if ( $format == 'quote' && !strstr ( $src, '<blockquote>' ) )
			$src = '<blockquote>'. $src .'</blockquote>';

		/* quote meta */
		$source_name = get_post_meta($post->ID, '_format_quote_source_name', true );
		$source_url = get_post_meta($post->ID, '_format_quote_source_url', true );
		if ( !empty( $source_name ) && !empty ( $source_url) ) {
			$src .= '<p class="alignright"><a class="u-quote-source u-like-of icon-link-ext-alt" href="'. $source_url .'">'. $source_name .'</a></p>';
		}
		elseif ( !empty($source_name )) {
			$src .= '<p class="u-quote-source alignright">'. $source_name .'</p>';
		}
		unset ($source_name, $source_url);

		/* image meta */
		$img = get_post_thumbnail_id( $post->ID );
		if ( !empty($format) && $format != 'standard ' && $format != 'gallery' ) {
			$asrc = $this->replace_images_with_adaptive ( $src );
			if ( strlen($src) == strlen($asrc) && !empty($img) )
				$src .= do_shortcode( '[adaptimg aid=' . $img .' size=hd share=0 standalone=1]');
			else
				$src = $asrc;
			unset ( $asrc );
		}
		unset ( $img );

		/* audio meta */
		$audio = get_post_meta($post->ID, '_format_audio_embed', true );
		if ( !empty($audio)) {
				$src .= $audio;
		}
		unset ( $audio );

		/* video meta */
		$video = get_post_meta($post->ID, '_format_video_embed', true );
		if ( !empty($video)) {
			if ( strstr( $video, 'ted'))
				$src .= '['. $video .']';
			elseif (strstr( $video, 'youtube'))
				$src .= '[embed]'. $video .'[/embed]';
		}
		unset ( $video );

		/* link meta */
		$url = get_post_meta($post->ID, '_format_link_url', true );
		$title = get_the_title ($post->ID );
		$webmention = get_post_meta($post->ID, '_format_link_webmention', true );
		if ( !empty($url ) && ( empty($webmention) || $webmention == 'none' ) ) {
				$src .= '<p><a class="icon-link-ext-alt" href="'.$url.'">'. $title .'</a></p>';
		}
		unset ($url, $title, $webmention);

		return $src;
	}

	public function add_user_meta_fields ($profile_fields) {

		$profile_fields['github'] = 'Github username';
		$profile_fields['mobile'] = 'Mobile phone number';
		$profile_fields['linkedin'] = 'LinkedIn profile URL';

		return $profile_fields;
	}

}

/**** END OF FUNCTIONS *****/

if ( !isset( $petermolnareu_theme ) || empty ( $petermolnareu_theme ) ) {
	$petermolnareu_theme = new petermolnareu();
}

?>
