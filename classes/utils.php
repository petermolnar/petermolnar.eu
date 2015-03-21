<?php

class pmlnr_utils {

	public function __construct () {
	}

	/**
	 *
	 */
	public static function imagewithmeta( $aid ) {
		if ( empty ( $aid ) )
			return false;

		$__post = get_post( $aid );
		$img = array ();

		$img['title'] = esc_attr($__post->post_title);
		$img['alt'] = strip_tags ( get_post_meta($__post->id, '_wp_attachment_image_alt', true) );
		if ( empty ($img['alt'])) $img['alt'] = $img['title'];

		$img['caption'] = esc_attr($__post->post_excerpt);
		$img['description'] = esc_attr($__post->post_content);
		$img['slug'] =  sanitize_title ( $__post->post_title , $aid );
			if ( is_numeric( substr( $img['slug'], 0, 1) ) )
				$img['slug'] = 'img-' . $img['slug'];

		$aimg = wp_get_attachment_image_src( $aid, 'full' );
		$img['url'] = self::absolute_url($aimg[0]);

		$aimg = wp_get_attachment_image_src( $aid, 'medium' );
		$img['medium'] = self::absolute_url($aimg[0]);

		$aimg = wp_get_attachment_image_src( $aid, 'thumbnail' );
		$img['thumbnail'] = self::absolute_url($aimg[0]);

		$aimg = wp_get_attachment_image_src( $aid, 'large' );
		$img['large'] = self::absolute_url($aimg[0]);

		return $img;
	}

	/**
	 * auto-link all plain text links, exclude anything in html tags
	 */
	public static function linkify ( $content ) {
		$content = preg_replace('$(https?://[a-z0-9_./?=&#-]+)(?![^<>]*>)$i', ' <a href="$1" target="_blank">$1</a> ', $content." ");
		$content = preg_replace('$(www\.[a-z0-9_./?=&#-]+)(?![^<>]*>)$i', '<a target="_blank" href="http://$1"  target="_blank">$1</a> ', $content." ");
		return $content;
	}

	/**
	 * Twitter link all @ starting string
	 */
	public static function tweetify( $content ) {

		/* twitter */
		preg_match_all('/@([0-9a-zA-Z_]+) /', $content, $users);

		if ( !empty ( $users[0] ) && !empty ( $users[1] )) {
			foreach ( $users[1] as $cntr=>$uname ) {
				if ( $uname == 'import' ) continue;
				$repl = $users[0][$cntr];
				$content = str_replace ( $repl, '[@'. $uname .'](https://twitter.com/'. $uname .') ', $content );
			}
		}

		/*
		if ( $hashtags ) {
			preg_match_all('/#([0-9a-zA-Z_-]+)/', $content, $hashtags);
			if ( !empty ( $hashtags[0] ) && !empty ( $hashtags[1] )) {
				foreach ( $hashtags[1] as $cntr=>$tagname ) {
					$repl = $hashtags[0][$cntr];
					$content = str_replace ( $repl, '<a href="https://twitter.com/hashtag/'. $tagname.'?src=hash" rel="nofollow">#'.$tagname.'</a>', $content );
				}
			}
		}
		*/

		return $content;
	}

	/**
	 * Facebook link all ^ starting string
	 */
	public static function facebookify( $content, $hashtags = false ) {

		preg_match_all('/\^([a-zA-Z\._-]+)/', $content, $users);

		if ( !empty ( $users[0] ) && !empty ( $users[1] )) {
			foreach ( $users[1] as $cntr=>$uname ) {
				$repl = $users[0][$cntr];
				$content = str_replace ( $repl, '['. $uname .'](https://facebook.com/'. $uname .')', $content );
			}
		}

		return $content;
	}

	/**
	 * absolute_url - make relative urls absolute the safe way
	 *
	 * @param &$url string: url to append
	 * @return string: appended url
	 */

	public static function absolute_url ( $url ) {
		$surl = rtrim( get_bloginfo('url'), '/' );
		$url = $surl . str_replace ( $surl, '', $url );
		return self::replace_if_ssl( $url );
	}

	/**
	 * replace_if_ssl - returns https:// in case HTTPS is going on
	 *
	 * @param &$url string: url to check
	 * @return string: url checked
	 */
	public static function replace_if_ssl ( $url ) {
		if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' )
			$_SERVER['HTTPS'] = 'on';

		if ( isset($_SERVER['HTTPS']) && (( strtolower($_SERVER['HTTPS']) == 'on' )  || ( $_SERVER['HTTPS'] == '1' ) ))
			$url = str_replace ( 'http://' , 'https://' , $url );

		return $url;
	}

	/**
	 * the built-in WordPress EXIF shutter speed is not human-readable, this parses it
	 *
	public static function shutter_speed ( $num ) {
		if ( (1 / $num) > 1) {
			$r = "1/";
			if ((number_format((1 / $num), 1)) == 1.3 or number_format((1 / $num), 1) == 1.5 or number_format((1 / $num), 1) == 1.6 or number_format((1 / $num), 1) == 2.5) {
				$r .= number_format((1 / $num), 1, '.', '');
			}
			else {
				$r .= number_format((1 / $num), 0, '.', '');
			}
		}
		else {
			$r = $num;
		}
		return $r;
	}
	*/
	/*
	public static function clean_urls($urls) {
		$array = array_map('pmlnr_utils::clean_url', $urls);
		return array_filter(array_unique($array));
	}
	*/
	public static function clean_url($string) {
		$url = trim($string);

		if ( !filter_var($url, FILTER_VALIDATE_URL) )
			return false;

		$url = esc_url_raw($url);
			return $url;
	}
}
