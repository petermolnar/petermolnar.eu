<?php

class pmlnr_utils {

	public function __construct () {
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
	public static function tweetify( $content, $hashtags = false ) {

		/* twitter */
		preg_match_all('/@([0-9a-zA-Z_]+)/', $content, $twusers);

		if ( !empty ( $twusers[0] ) && !empty ( $twusers[1] )) {
			foreach ( $twusers[1] as $cntr=>$twname ) {
				$repl = $twusers[0][$cntr];
				$content = str_replace ( $repl, '<a href="https://twitter.com/'.$twname.'" rel="nofollow">@'.$twname.'</a>', $content );
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

}
