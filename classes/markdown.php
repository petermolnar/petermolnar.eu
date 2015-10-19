<?php

class pmlnr_markdown extends pmlnr_base {

	/**
	 *
	 */
	public function __construct ( ) {
		add_action( 'init', array( &$this, 'init'));
	}

	/* init function, should be used in the theme init loop */
	public function init (  ) {

		// replace img inserts with Markdown
		add_filter( 'image_send_to_editor', array( &$this, 'media_string_html2md'), 10 );

		// markdown
		add_filter( 'the_content', array( &$this, 'parsedown'), 8, 1 );
		add_filter( 'the_excerpt', array( &$this, 'parsedown'), 8, 1 );

	}

	/**
	 * replace HTML img insert with Markdown Extra syntax
	 */
	public static function media_string_html2md( $str ) {
		if ( !strstr ( $str, '<img' ) )
			return $str;


		$src = static::preg_value ( $str, '/src="([^"]+)"/' );
		$title = static::preg_value ( $str, '/title="([^"]+)"/' );
		$alt = static::preg_value ( $str, '/alt="([^"]+)"/' );
		if ( empty ( $alt ) && !empty ( $title ) ) $alt = $title;
		$wpid = static::preg_value ( $str, '/wp-image-(\d*)/' );
		$src = static::preg_value ( $str, '/src="([^"]+)"/' );
		$cl = static::preg_value ( $str, '/class="([^"]+)?(align(left|right|center))([^"]+)?"/', 2 );
		if (!empty($cl)) $cl = ' .' . $cl;

		if (!empty($title)) $title = ' ' . $title;
		if (!empty($wpid)) $imgid = '#img-' . $wpid;


		$img = sprintf ('![%s](%s%s){%s%s}', $alt, $src, $title, $imgid, $cl);
		return $img;
	}

	/**
	 * parsedown
	 */
	public static function parsedown ( $md ) {

		if ( empty ( $md ) )
			return false;

		$hash = sha1($md);
		if ( $cached = wp_cache_get ( $hash, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$parsedown = new ParsedownExtra();
		$parsedown->setBreaksEnabled(true);
		$parsedown->setUrlsLinked(true);
		$r = $parsedown->text ( $md );

		wp_cache_set ( $hash, $r, __CLASS__ . __FUNCTION__, self::expire );

		return $r;
	}
}
