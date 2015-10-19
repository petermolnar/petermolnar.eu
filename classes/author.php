<?php

class pmlnr_author extends pmlnr_base {

	public function __construct ( ) {
		add_action( 'init', array( &$this, 'init'));
	}

	/* init function, should be used in the theme init loop */
	public function init (  ) {
		// additional user meta fields
		add_filter('user_contactmethods', array( &$this, 'add_user_meta_fields'));

	}

	/**
	 * additional user fields
	 */
	public function add_user_meta_fields ($profile_fields) {

		$profile_fields['pgp'] = __('URL to PGP key for the email address above', 'petermolnareu');
		$profile_fields['github'] = __('Github username', 'petermolnareu');
		$profile_fields['mobile'] = __('Mobile phone number', 'petermolnareu');
		$profile_fields['linkedin'] = __('LinkedIn username', 'petermolnareu');
		$profile_fields['flickr'] = __('Flickr username', 'petermolnareu');
		$profile_fields['tubmlr'] = __('Tumblr blog URL', 'petermolnareu');
		$profile_fields['500px'] = __('500px username', 'petermolnareu');
		$profile_fields['instagram'] = __('instagram username', 'petermolnareu');
		$profile_fields['skype'] = __('skype username', 'petermolnareu');
		$profile_fields['twitter'] = __('twitter username', 'petermolnareu');

		return $profile_fields;
	}

	/**
	 * new utils - no formatting, no html, just data
	 */

	public static function author_social ( $author_id = 1 ) {
		$list = [];

		$socials = array (
			'github'   => 'https://github.com/%s',
			//'linkedin' => 'https://www.linkedin.com/in/%s',
			'twitter'  => 'https://twitter.com/%s',
			'flickr'   => 'https://www.flickr.com/people/%s',
			//'500px'	=> 'https://500px.com/%s',
			//'instagram'=> 'https://instagram.com/%s',
			//'skype'=> 'callto:%s',
		);

		foreach ( $socials as $silo => $pattern ) {
			$socialmeta = get_the_author_meta ( $silo , $author_id );

			if ( !empty($socialmeta) )
				$list[ $silo ] = sprintf ( $pattern, $socialmeta );

		}

		return $list;
	}


	public static function template_vars (&$post = null) {
		$post = static::fix_post($post);

		if ($post === false)
			return false;

		if ( $cached = wp_cache_get ( $post->ID, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$r = array (
			'id' => $post->post_author,
			'name' =>  get_the_author_meta ( 'display_name' , $post->post_author ),
			'email' =>  get_the_author_meta ( 'user_email' , $post->post_author ),
			'gravatar' => sprintf('https://s.gravatar.com/avatar/%s?=64', md5( strtolower( trim( get_the_author_meta ( 'user_email' , $post->post_author ) ) ) )),
			'url' => get_the_author_meta ( 'user_url' , $post->post_author ),
			'socials' => static::author_social ( $post->post_author ),
			'pgp' => get_the_author_meta ( 'pgp' , $post->post_author ),
		);
		wp_cache_set ( $post->ID, $r, __CLASS__ . __FUNCTION__, self::expire );

		return $r;


	}
}
