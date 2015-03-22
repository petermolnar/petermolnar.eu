<?php

class pmlnr_utils {

	public function __construct () {
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
	 * fully sanitize a url
	 */
	public static function clean_url($string) {
		$url = trim($string);

		if ( !filter_var($url, FILTER_VALIDATE_URL) )
			return false;

		$url = esc_url_raw($url);
			return $url;
	}

	/**
	 *
	 */
	public static function log( $message) {
		if (is_object($message) || is_array($message))
			$message = json_encode($message);

		if ( defined('WP_DEBUG') && WP_DEBUG == true )
			error_log ( __FILE__ . ': ' . $message);
	}

	/**
	 *
	 */
	public static function islocalhost() {
		$r = false;

		if ( $_SERVER['SERVER_ADDR'] == $_SERVER['REMOTE_ADDR'] || $_SERVER['REMOTE_ADDR'] == '127.0.0.1' )
			$r = true;

		return $r;
	}

	/**
	 * Twitter link all @ starting string
	 *
	public static function tweetify( $content ) {

		preg_match_all('/@([0-9a-zA-Z_]+) /', $content, $users);

		if ( !empty ( $users[0] ) && !empty ( $users[1] )) {
			foreach ( $users[1] as $cntr=>$uname ) {
				if ( $uname == 'import' ) continue;
				$repl = $users[0][$cntr];
				$content = str_replace ( $repl, '[@'. $uname .'](https://twitter.com/'. $uname .') ', $content );
			}
		}

		if ( $hashtags ) {
			preg_match_all('/#([0-9a-zA-Z_-]+)/', $content, $hashtags);
			if ( !empty ( $hashtags[0] ) && !empty ( $hashtags[1] )) {
				foreach ( $hashtags[1] as $cntr=>$tagname ) {
					$repl = $hashtags[0][$cntr];
					$content = str_replace ( $repl, '<a href="https://twitter.com/hashtag/'. $tagname.'?src=hash" rel="nofollow">#'.$tagname.'</a>', $content );
				}
			}
		}

		return $content;
	}
	*/
}
