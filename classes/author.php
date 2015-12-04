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

		//if ( $cached = wp_cache_get ( $author_id, __CLASS__ . __FUNCTION__ ) )
			//return $cached;

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

		//wp_cache_set ( $author_id, $list, __CLASS__ . __FUNCTION__, self::expire );
		return $list;
	}

	/**
	 * template variables for twig
	 */
	public static function template_vars ( $author_id = 1, $prefix = '' ) {

		if ( $cached = wp_cache_get ( $author_id, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$email = get_the_author_meta ( 'user_email' , $author_id );
		$name = get_the_author_meta ( 'display_name' , $author_id );

		$thid = get_user_option ( 'metronet_image_id', $author_id );
		if ( $thid ) {
			$image = wp_get_attachment_image_src ($thid, 'thumbnail');
			$avatar = static::fix_url($image[0]);
		}
		else {
			$avatar = sprintf('https://s.gravatar.com/avatar/%s?=64', md5( strtolower( trim( $email ) ) ) );
		}

		$r = array (
			'id' => $author_id,
			'name' =>  $name,
			'email' =>  $email,
			'avatar' => $avatar,
			'url' => get_the_author_meta ( 'user_url' , $author_id ),
			'socials' => static::author_social ( $author_id ),
			'pgp' => get_the_author_meta ( 'pgp' , $author_id ),
		);

		if (!empty($prefix)) {
			foreach ($r as $key => $value ) {
				$r[ $prefix . $key ] = $value;
				unset($r[$key]);
			}
		}

		wp_cache_set ( $author_id, $r, __CLASS__ . __FUNCTION__, self::expire );

		return $r;

	}
}
