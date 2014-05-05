<?php

include_once ('classes/theme-cleanup.php');
include_once ('classes/adaptive-images.php');

class petermolnareu {
	const theme_constant = 'petermolnareu';
	const menu_header = 'header';

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
	private $adaptive_images = null;

	public function __construct () {
		$this->base_url = $this->replace_if_ssl( get_bloginfo("url") );
		$this->theme_url = $this->replace_if_ssl( get_bloginfo("stylesheet_directory") );
		$this->js_dir = $this->theme_url . '/assets/js/';
		$this->css_dir = $this->theme_url . '/assets/css/';
		$this->font_dir = $this->theme_url . '/assets/font/';
		$this->image_dir = $this->theme_url . '/assets/image/';
		$this->info = wp_get_theme( );

		/* cleanup class */
		$this->cleanup = new theme_cleaup();


		/* theme init */
		add_action( 'init', array( &$this, 'init'));
		add_action( 'init', array( &$this->cleanup, 'filters'));

		/* adaptive galleries class */
		$this->adaptive_images = new adaptive_images( $this );
		add_action( 'init', array( &$this->adaptive_images, 'init'));

		/* set up CSS, JS and fonts */
		add_action( 'wp_enqueue_scripts', array(&$this,'register_css_js'));

		/* excerpt letter counter */
		add_action( 'admin_head-post.php',  array(&$this, 'excerpt_count_js'));
		add_action( 'admin_head-post-new.php',  array(&$this, 'excerpt_count_js' ));
	}

	public function init () {
		/* set theme supports */
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'menus' );
		add_theme_support( 'automatic-feed-links' );
		/*
		 * Enable support for Post Formats.
		 * See http://codex.wordpress.org/Post_Formats
		 */
		add_theme_support( 'post-formats', array(
			// aside
			'image', 'video', 'audio', 'quote', 'link', 'gallery', 'status'
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

		/* enable SVG uploads */
		add_filter('upload_mimes', array( &$this, 'custom_upload_mimes' ) );

		/* add syntax highlighting */
		add_shortcode('code', array ( &$this, 'syntax_highlight' ) );
		add_shortcode('cc', array ( &$this, 'syntax_highlight' ) );

		/* legacy shortcode handler */
		add_filter( 'the_content', array( &$this, 'legacy' ), 1);
		//remove_filter( 'the_content', 'wpautop' );
		//add_filter( 'the_content', 'wpautop' , 99 );
		add_filter( 'the_content', 'shortcode_unautop', 100 );

		//add_filter('the_content', array( &$this, 'twtreplace'));
		//add_filter('comment_text', array( &$this, 'twtreplace'));

		/* overwrite gallery shortcode */
		remove_shortcode('gallery');
		add_shortcode('gallery', array ( &$this->adaptive_images, 'adaptgal' ) );
	}

	public function register_css_js () {
		/* enqueue CSS */

		wp_register_style( 'reset', $this->css_dir . 'reset.css', false, null );
		wp_enqueue_style( 'reset' );

		//if ( is_user_logged_in() )
		//	wp_register_style( 'style', $this->theme_url . '/style-new.css' , array('reset'), $this->info->version );
		//else
			wp_register_style( 'style', $this->theme_url . '/style.css' , array('reset'), $this->info->version );
		wp_enqueue_style( 'style' );

		/* syntax highlight */
		wp_register_style( 'prism', $this->css_dir . 'prism.css', false, null );
		wp_register_script( 'prism' , $this->js_dir . 'prism.js', false, null, true );

		/* CDN scripts */
		wp_deregister_script( 'jquery' );
		wp_register_script( 'jquery', $this->replace_if_ssl( 'http://code.jquery.com/jquery-1.11.0.min.js' ), false, null, true );
		wp_enqueue_script( 'jquery' );

		wp_register_script( 'jquery.touchSwipe', $this->js_dir . 'jquery.touchSwipe.min.js', array('jquery'), null, true );
		wp_register_script( 'jquery.adaptive-images', $this->js_dir . 'adaptive-images.js', array('jquery','jquery.touchSwipe'), null, true );
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
	 *
	 *
	 */
	public function share ( $link , $title, $comment=false, $parent=false ) {
		global $post;
		$link = urlencode($link);
		$title = urlencode($title);
		$desciption = urlencode(get_the_excerpt());
		$type = get_post_type ( $post );

		$share = array (

			'twitter'=>array (

				'url'=>'https://twitter.com/share?url='. $link .'&text='. $title,
				'title'=>__('Tweet', self::theme_constant),
			),

			'facebook'=>array (
				'url'=>'http://www.facebook.com/share.php?u=' . $link . '&t=' . $title,
				'title'=>__('Share', self::theme_constant),
			),

			'googleplus'=>array (
				'url'=>'https://plus.google.com/share?url=' . $link,
				'title'=>__('+1', self::theme_constant),
			),

			'tumblr'=>array (
				'url'=>'http://www.tumblr.com/share/link?url='.$link.'&name='.$title.'&description='. $desciption,
				'title'=>__('share', self::theme_constant),
			),

		);

		if ( $parent != false ) {
			$share['pinterest']  = array (
				'url'=>'https://pinterest.com/pin/create/bookmarklet/?media='. $link .'&url='. urlencode($parent) .'&is_video=false&description='. $title,
				'title'=>__('pin', self::theme_constant),
			);
		}


		if ($comment) {
			$share['comment'] = array (
				'url'=>get_permalink( $post->ID ) . "#comments",
				'title'=>__('comment', self::theme_constant),
			);
		}

		$out = '';
		foreach ($share as $site=>$details) {
				$st = 'icon-' . $site;

				$out .= '<li><a class="'. $st .'" href="' . $details['url'] . '" title="' . $details['title'] . '">&nbsp;</a></li>';
		}

		$out = '
			<nav class="share">
				<ul>
				'. $out .'
				</ul>
			</nav>';


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
	 *
	 *
	 */
	public function related_posts ( $_post ) {
		$tags = wp_get_post_tags($_post->ID);
		$list = '';

		if ($tags) {
			$tag_ids = array();
			foreach($tags as $tag) {
				$tag_ids[] = $tag->term_id;
				$tags_names[] = $tag->name;
			}


			$args=array(
				'tag__in' => $tag_ids,
				'post__not_in' => array($_post->ID),
				'posts_per_page'=>12,
				'ignore_sticky_posts'=>1
			);

			$_query = new wp_query( $args );

			while( $_query->have_posts() ) {
				$_query->the_post();

				$post_title = htmlspecialchars( stripslashes( get_the_title() ) );

				$list .= '
						<li>
							<a href="' . get_permalink() . '" title="'. $post_title .'" >
								' . $post_title . '
							</a>
						</li>';
				wp_reset_postdata();
			}
		}

		$out = '
		<section class="sidebar">
			<nav class="sidebar-postlist">
				<h3 class="postlist-title">'. __( "Related posts" ) . '</h3>
				<ul class="postlist">
				'. $list .'
				</ul>
			</nav>
		</section>';

		return $out;
	}

	/**
	 *
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


	public function article_time () {
		global $post;
		/*
		?>
			<time class="article-pubdate" pubdate="<?php the_time( 'r' ); ?>">
				<span class="year"><?php the_time( 'Y' ); ?></span>
				<span class="month"><?php the_time( 'M' ); ?></span>
				<span class="day"><?php the_time( 'd' ); ?></span>
				<span class="hour"><?php the_time( 'H:i' ); ?></span>
			</time>
		<?php
		*/
		?>
			<time class="article-pubdate" pubdate="<?php the_time( 'r' ); ?>">
				<span class="date"><?php the_time( get_option('date_format') ); ?></span>
				<span class="time"><?php the_time( get_option('time_format') ); ?></span>
			</time>
		<?php
	}

	public function replace_images_with_adaptive ( $html ) {
		return $this->adaptive_images->adaptive_embededed( $html );
	}


	public function twtreplace($content) {
		$twtreplace = preg_replace('/([^a-zA-Z0-9-_&])@([0-9a-zA-Z_]+)/',"$1<a href=\"http://twitter.com/$2\" target=\"_blank\" rel=\"nofollow\">@$2</a>",$content);
		return $twtreplace;
	}

	public function linkify ( $content ) {
		$content = preg_replace('$(https?://[a-z0-9_./?=&#-]+)(?![^<>]*>)$i', ' <a href="$1" target="_blank">$1</a> ', $content." ");
		$content = preg_replace('$(www\.[a-z0-9_./?=&#-]+)(?![^<>]*>)$i', '<a target="_blank" href="http://$1"  target="_blank">$1</a> ', $content." ");
		return $content;
	}
}

/**** END OF FUNCTIONS *****/



if ( !isset( $petermolnareu_theme ) || empty ( $petermolnareu_theme ) ) {
	$petermolnareu_theme = new petermolnareu();
}



?>
