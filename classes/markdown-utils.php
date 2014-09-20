<?php

class pmlnr_md {

	public function __construct () {
	}

	/**
	 * replace HTML img insert with Markdown Extra syntax
	 */
	public static function rebuild_media_string( $str ) {
		if ( !strstr ( $str, '<img' ) )
			return $str;


		$src = preg_value ( $str, '/src="([^"]+)"/' );
		$title = preg_value ( $str, '/title="([^"]+)"/' );
		$alt = preg_value ( $str, '/alt="([^"]+)"/' );
		if ( empty ( $alt ) && !empty ( $title ) ) $alt = $title;
		$wpid = preg_value ( $str, '/wp-image-(\d*)/' );
		$src = preg_value ( $str, '/src="([^"]+)"/' );
		$cl = preg_value ( $str, '/class="([^"]+)?(align(left|right|center))([^"]+)?"/', 2 );

		$img = '!['.$alt.']('. $src .' '. $title .'){#img-'. $wpid .' .'.$cl.'}';
		return $img;
	}

}
