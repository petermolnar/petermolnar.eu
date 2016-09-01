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

		$profile_fields['key'] = __( 'URL to PGP key for the email address above',
			'petermolnareu');
		$profile_fields['github'] = __('Github username',
			 'petermolnareu');
		$profile_fields['flickr'] = __('Flickr username',
			 'petermolnareu');
		$profile_fields['twitter'] = __('twitter username',
			 'petermolnareu');

		//$profile_fields['tubmlr'] = __('Tumblr blog URL',
			 //'petermolnareu');
		//$profile_fields['500px'] = __('500px username',
			 //'petermolnareu');
		//$profile_fields['instagram'] = __('instagram username',
			 //'petermolnareu');
		//$profile_fields['skype'] = __('skype username',
			 //'petermolnareu');
		//$profile_fields['wechat'] = __('wechat username',
			 //'petermolnareu');
		//$profile_fields['icq'] = __('ICQ number',
			 //'petermolnareu');
		//$profile_fields['qq'] = __('QQ number',
			 //'petermolnareu');
		//$profile_fields['telegram'] = __('Telegram handle',
			 //'petermolnareu');
		//$profile_fields['mobile'] = __('Mobile phone number',
			 //'petermolnareu');
		//$profile_fields['linkedin'] = __('LinkedIn username',
			 //'petermolnareu');


		return $profile_fields;
	}

	/**
	 * new utils - no formatting, no html, just data
	 */
	public static function author_social ( $author_id = 1 ) {

		if ( $cached = wp_cache_get ( $author_id, __CLASS__ . __FUNCTION__ ) )
			return $cached;

		$list = [];

		$socials = array (
			'github'   => 'https://github.com/%s',
			'flickr'   => 'https://www.flickr.com/people/%s',
			'telegram' => 'https://telegram.me/%s',
			'key' => '%s',

			//'linkedin'   => 'https://www.linkedin.com/in/%s',
			//'skype'   => 'callto://%s+type=skype',
			//'wechat'   => 'callto://%s+type=wechat',
			//'qq'   => 'callto://%s+type=qq',
			//'twitter'  => 'https://twitter.com/%s',

		);

		foreach ( $socials as $silo => $pattern ) {
			$socialmeta = get_the_author_meta ( $silo , $author_id );

			if ( !empty($socialmeta) )
				$list[ $silo ] = sprintf ( $pattern, $socialmeta );

		}

		wp_cache_set ( $author_id, $list, __CLASS__ . __FUNCTION__,
			static::expire );
		return $list;
	}

	/**
	 * template variables for twig
	 */
	public static function template_vars ( $author_id = 1 ) {

		$r = array();

		//if ( $cached = wp_cache_get ( $author_id, __CLASS__ . __FUNCTION__ ) )
			//return $cached;

		$email = get_the_author_meta ( 'user_email' , $author_id );

		// invalid user
		if ( empty ( $email ) )
			return $r;

		$thid = get_user_option ( 'metronet_image_id', $author_id );
		if ( $thid ) {
			$image = wp_get_attachment_image_src ($thid, 'thumbnail');
			$avatar = site_url( $image[0] );
		}
		else {
			$avatar = sprintf(
				'https://s.gravatar.com/avatar/%s?=64',
				md5( strtolower( trim( $email ) ) )
			);
		}

		$r = array (
			//'id' => $author_id,
			'name' =>  get_the_author_meta ( 'display_name' , $author_id ),
			'email' =>  $email,
			'avatar' => $avatar,
			'url' => get_the_author_meta ( 'user_url' , $author_id ),
			'socials' => static::author_social ( $author_id ),
			//'pgp' => get_the_author_meta ( 'pgp' , $author_id ),
			'handle' => get_the_author_meta( 'nickname', $author_id ),
		);

		//wp_cache_set ( $author_id, $r, __CLASS__ . __FUNCTION__,
			//static::expire );

		return $r;

	}
}
