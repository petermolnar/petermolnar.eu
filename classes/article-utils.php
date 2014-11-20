<?php

class pmlnr_article {

	public function __construct () {
	}

	/**
	 *  author vcard
	 */
	public static function author ( $hide = false ) {
		global $post;
		$aid =  get_the_author_meta( 'ID' );
		$aemail = get_the_author_meta ( 'user_email' , $aid );
		$aname = get_the_author_meta ( 'display_name' , $aid );
		$aurl = get_the_author_meta ( 'user_url' , $aid );
		$gravatar = md5( strtolower( trim(  $aemail )));
		$h = ( $hide ) ? ' hide' : '';

		$r = sprintf ('
		<span class="p-author h-card vcard%s">
			<a class="fn p-name url u-url" href="%s">%s</a>
			<img class="photo avatar u-photo u-avatar" src="https://s.gravatar.com/avatar/%s?s=64" alt="Photo of %s"/>
		</span>', $h, $aurl, $aname, $gravatar, $aname );

		return $r;
	}

	/**
	 *  vcard
	 */
	public static function vcard ( $uid = false ) {
		$aid = ( $uid==false ) ? 1 : $uid;
		$aemail = get_the_author_meta ( 'user_email' , $aid );
		$aname = get_the_author_meta ( 'display_name' , $aid );
		$aurl = get_the_author_meta ( 'user_url' , $aid );
		$gravatar = md5( strtolower( trim(  $aemail )));
		$class = 'h-card vcard';

		$r = sprintf ('
		<span class="h-card vcard">
			<a class="fn p-name url u-url" href="%s">%s</a>
			<img class="photo avatar u-photo u-avatar" src="https://s.gravatar.com/avatar/%s?s=64" style="width:12px; height:12px;" alt="Photo of %s"/>
			<a rel="me" class="u-email email" href="mailto:%s" title="%s email address">%s</a>
		', $aurl, $aname, $gravatar, $aname, $aemail, $aname, $aemail );

		/* social */
		$socials = array (
			'twitter' => array (
				'name' => 'Twitter',
				'url' => 'https://twitter.com/%s',
			),
			'github' => array (
				'name' => 'Github',
				'url' => 'https://github.com/%s',
			),
			'linkedin' => array (
				'name' => 'LinkedIn',
				'url' => 'https://www.linkedin.com/in/%s',
			),
			'flickr' => array (
				'name' => 'Flickr',
				'url' => 'https://www.flickr.com/people/%s',
			),
		);

		foreach ( $socials as $nw => $social ) {
			$socialmeta = get_the_author_meta ( $nw , $aid );
			if ( !empty ($socialmeta) ) {
				$url = sprintf ( $social['url'], $socialmeta );
				$s[ $nw ] = sprintf ( '<a rel="me" class="u-%s x-%s url u-url" href="%s" title="%s @ %s">%s</a>', $nw, $nw, $url, $aname, $social['name'], $socialmeta );
			}
		}

		if ( !empty($socials)) {
			$r .= sprintf ( '<span class="spacer">%s</span>', __('Find me:') );
			$r .= join ( " ", $s);
		}

		$r .= '</span>';
		return $r;
	}

	/**
	 * Get URL for an author
	 *
	 * @return string Auther website
	 */
	public static function author_url ( ) {
		global $post;
		return get_the_author_meta ( 'user_url' , get_the_author_meta( 'ID' ) );
	}


	/**
	 * @return string structured img with medium size attachment image
	 */
	public static function photo ( $hide = false ) {
		global $post;
		if ( !has_post_thumbnail () )
			return false;

		$img = pmlnr_utils::imagewithmeta( get_post_thumbnail_id( $post->ID ) );
		$h = ( $hide ) ? ' hide' : '';
		$r = sprintf ( '<img class="u-photo%s" src="%s" />' , $h, $img['mediumurl'] );
		//$r = sprintf ( '![%s](%s "%s"){.u-photo%s}' , $img['alt'], $img['mediumurl'], $img['title'], $h );

		return $r;
	}

	/**
	 * return pubdate of current post h-enty formatted
	 *
	 * @return string: 2 <time> elements of publish / last modification time
	 */
	public static function pubdate () {
		global $post;
		$r = sprintf ( '<time class="dt-published" datetime="%s">%s %s</time>', get_the_time( 'c', $post->ID ), get_the_time( get_option('date_format'), $post->ID ), get_the_time( get_option('time_format'), $post->ID ) );
		$r .= sprintf ( '<time class="dt-updated hide" datetime="%s">%s %s</time>', get_the_modified_time( 'c', $post->ID ), get_the_modified_time( get_option('date_format'), $post->ID ), get_the_modified_time( get_option('time_format'), $post->ID ) );

		return $r;
	}

	/**
	 * return title of current post h-enty formatted
	 *
	 * @return string: <h1> elements of publish / last modification time
	 */
	public static function title ( $type = '' ) {
		global $post;

		$shortlink = wp_get_shortlink();
		$permalink = get_permalink();
		$title = get_the_title();

		switch ( $type ) {
			case 'link':
				$s ='
				<a class="u-url" href="%s" rel="bookmark" title="%s">
					<span class="p-name">%s</span>
				</a>';
				break;
			case 'hide':
				$s = '
				<a class="u-url hide" href="%s" rel="bookmark" title="%s">
					<span class="p-name">%s</span>
				</a>';
				break;
			case 'listmore':
				$s = '
				<h2>
					<a class="u-url" href="%s" rel="bookmark" title="%s">
						<span class="p-name more">%s</span>
					</a>
				</h2>';
				break;
			case 'listelement':
				$s = '
				<h2>
					<a class="u-url" href="%s" rel="bookmark" title="%s">
						<span class="p-name">%s</span>
					</a>
				</h2>';
				break;
			default:
				$s = '
				<h1>
					<a class="u-url" href="%s" rel="bookmark" title="%s">
						<span class="p-name">%s</span>
					</a>
				</h1>';
			break;
		}

		$r = sprintf ( $s, $permalink, $title, $title ) . sprintf ('<span class="u-uid hide">%s</span>', $shortlink);

		return $r;
	}

	/**
	 * @return string structured tags list
	 */
	 public static function tags ( $title = true ) {
		$r = '';
		if ( $title ) $r = sprintf ('<h5>%s</h5>', __('Tagged as:') );

		$tags = get_the_tags();
		if ( $tags ) {
			$r .= '<div class="p-category">';

			foreach( $tags as $tag )
				$t[] = sprintf ( '<a href="%s">%s</a>', get_tag_link( $tag->term_id ), $tag->name );

			$r .= join( ', ', $t ) . '</div>';
		}

		return $r;
	}

	/**
	 *
	 * @return string structured string for sibling articles
	 */
	public static function siblings( $title = true ) {
		/* thank you WordPress for not having get_(previous|next)_post_link */
		ob_start();
		if ( $title ) printf ('<h5>%s</h5>', __('Read more:') );

		?><nav class="siblings"><?php
			previous_post_link( '%link' , '%title' , true );
			next_post_link( '%link' , '%title' , true );
		?></nav>
		<?php $r = ob_get_clean();
		return $r;
	}

	/**
	 * determines minutes to read content
	 *
	 * @param &$content string by reference: content to examine
	 * @return int rounded number of minutes to read text
	 *
	 */
	public static function minstoread ( &$content ) {
		$r = sprintf ( '<span class="right minstoread">%d %s</span>', round( str_word_count( strip_tags($content), 0 ) / 300 ), __('mins to read') );
		return $r;
	}

	/**
	 * formatted comment counter URL to post
	 *
	 * @return <a> with formatting linking to post
	 *
	 */
	public static function commentcntr ( ) {
		global $post;
		return sprintf ( '<a class="u-url right icon-comment commentcounter" href="%s#comments">%s</a>', get_the_permalink(), get_comments_number( '', '1', '%' )  );
	}

	/**
	 * add sad no comments here message with the possible syndication links
	 *
	 * @return string formatted message, including syndication list
	 *
	 */
	public static function syndicates ( ) {
		global $post;

		$syndicates = array();
		if ( function_exists('getRelSyndicationFromSNAP'))
			$syndicates = getRelSyndicationFromSNAP( true );

		/* imported tweets */
		$tw = get_post_meta( get_the_ID(), 'twitter_tweet_id', true );
		if ( !empty($tw) )
			$syndicates['TW'] = sprintf ( '<li><a class="u-syndication link-twitter icon-twitter" rel="syndication" href="https://twitter.com/petermolnar/status/%s" target="_blank">Twitter</a></li>', $tw );

		$r = sprintf('
		<h5>%s</h5>
		<p>%s', __('There is no comment form here, but you can still discuss.'), __('Send a pingback, a trackback') );
		if (!empty($syndicates)) {
			$r .=  __( ", a webmention; or reply on:" );
			$r .= sprintf ( '<div class="usyndication"><ul>%s</ul></div>', implode ( "\n", $syndicates ));
		}
		else {
			$r .= __(' or a webmention.');
		}
		$r .= '</p>';

		return $r;
	}

	/**
	 * reply at syndicated / linked networks
	 *
	 * @return string formatted message, including syndication list
	 *
	 */
	public static function reply ( ) {
		global $post;

		$syndicates = array();
		$r = '';
		if ( function_exists('getRelSyndicationFromSNAP'))
			$syndicates = getRelSyndicationFromSNAP( true );

		/* Twitter */
		if ( !empty ($syndicates['TW'])) {
			preg_match('/href="(.*?)"/', $syndicates['TW'], $twurls );
			if (!empty($twurls[1]))
				$tweet_id = substr(strrchr($twurls[1], "/"), 1);
		}

		if ( empty ( $tweet_id ))
			$tweet_id = get_post_meta( get_the_ID(), 'twitter_tweet_id', true );

		if ( !empty($tweet_id) )
			$syndicates['TW'] = sprintf ( '<li><a class="link-twitter icon-twitter" href="https://twitter.com/intent/tweet?in_reply_to=%s" target="_blank">Twitter</a></li>', $tweet_id );

		if (!empty($syndicates)) {
			$r = sprintf('<indie-action do="reply" with="%s" class="share"><h5>%s</h5><ul>%s</ul></indie-action>', get_permalink(), __('Reply'), implode ( "\n", $syndicates ));
		}

		return $r;
	}

	/**
	 * updated share function: retweet/reshare/reshit if SNAP entry or something else is
	 * available
	 */
	public static function share ( ) {
		global $post;

		$r = '';
		$plink = get_permalink();
		$link = urlencode( $plink );
		$title = urlencode( get_the_title() );
		$desciption = urlencode( get_the_excerpt() );

		$media = wp_get_attachment_image_src(get_post_thumbnail_id( $post->ID ),'large', true);
		$media_url = ( ! $media ) ? false : $media[0];

		global $nxs_snapAvNts;
		$snap_options = get_option('NS_SNAutoPoster');

		/* all SNAP entries are in separate meta entries for the post based on the service name's "code" */
		if ( !empty ( $nxs_snapAvNts ) && is_array ( $nxs_snapAvNts ) ) {
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
						if ( isset( $snap_options[$okey][$cntr][$urlkey] ) && !empty( $snap_options[$okey][$cntr][$urlkey] ) )
							$surl[ $s ] = $snap_options[$okey][$cntr][$urlkey];
					}
				}
			}
		}

		/* Twitter */
		$service = 'twitter';
		$repost_id = get_post_meta($post->ID, 'twitter_rt_id', true );
		$repost_uid = get_post_meta($post->ID, 'twitter_rt_user_id', true );
		$tw = get_post_meta( $post->ID, 'twitter_tweet_id', true );
		$url = false;
		if ( !empty( $pgIDs[ $service ] ) ) {
			$url = 'https://twitter.com/intent/retweet?tweet_id=' . $pgIDs[ $service ];
			$txt = __( 'reweet' );
		}
		elseif ( !empty($repost_id) && !empty($repost_uid) ) {
			$url = 'https://twitter.com/intent/retweet?tweet_id=' . $repost_id;
			$txt = __( 'reweet' );
		}
		elseif ( !empty($tw) ) {
			$url = 'https://twitter.com/intent/retweet?tweet_id=' . $tw;
			$txt = __( 'reweet' );
		}

		if ( empty($url) ) {
			$url = 'https://twitter.com/share?url='. $link .'&text='. $title;
			$txt = __( 'tweet' );
			$shlist[] = '<a class="icon-'. $service .'" href="' . $url . '">'. $txt .'</a>';
		}
		else {
			$rshlist[] = '<a class="icon-'. $service .'" href="' . $url . '">'. $txt .'</a>';
		}


		/* Facebook */
		$service = 'facebook';
		$url = false;
		if ( isset($pgIDs) && !empty( $pgIDs[ $service ] ) ) $pgIDs[$service] = explode ( '_', $pgIDs[$service] );
		if ( isset($pgIDs) &&  is_array ( $pgIDs[$service] ) && !empty($pgIDs[$service][1]) ) {
			//https://www.facebook.com/sharer.php?s=100&p[url]=http://www.example.com/&p[images][0]=/images/image.jpg&p[title]=Title&p[summary]=Summary
			//$url = 'https://www.facebook.com/sharer/sharer.php?' . urlencode ('s=99&p[0]='. $pgIDs[$service][0] .'&p[1]='. $pgIDs[$service][1] );
			// '&p[images][0]='.  $media_url . '&p[title]=' . $title . '&p[summary]=' ) . $desciption;
			$base = '%BASE%/posts/%pgID%';
			$search = array('%BASE%', '%pgID%' );
			$replace = array ( $surl[ $service ], $pgIDs[$service][1] );
			$url =  'http://www.facebook.com/share.php?u=' . str_replace ( $search, $replace, $base );
			$txt = __( 'reshare' );
			$rshlist[] = '<a class="icon-'. $service .'" href="' . $url . '">'. $txt .'</a>';
		}
		else {
			$url = 'http://www.facebook.com/share.php?u=' . $link . '&t=' . $title;
			$txt = __( 'share' );
			$shlist[] = '<a class="icon-'. $service .'" href="' . $url . '">'. $txt .'</a>';
		}

		/* Google Plus */
		$service = 'googleplus';
		$url = 'https://plus.google.com/share?url=' . $link;
		$txt = __( '+1' );
		$shlist[] = '<a class="icon-'. $service .'" href="' . $url . '">'. $txt .'</a>';

		/* Tumblr */
		$service = 'tumblr';
		$url = 'http://www.tumblr.com/share/link?url='.$link.'&name='.$title.'&description='. $desciption;
		$txt = __( 'share' );
		$shlist[] = '<a class="icon-'. $service .'" href="' . $url . '">'. $txt .'</a>';

		/* Pinterest */
		if ( $media_url ) {
			$service = 'pinterest';
			$url = 'https://pinterest.com/pin/create/bookmarklet/?media='. $media_url .'&url='. $link .'&is_video=false&description='. $title;
			$txt = __( 'pin' );
			$shlist[] = '<a class="icon-'. $service .'" href="' . $url . '">'. $txt .'</a>';
		}

		/* short url */
		$service = 'url';
		$url = wp_get_shortlink();
		$txt = $url;
		$shlist[] = '<a class="icon-globe" href="' . $url . '">'. $txt .'</a>';


		if ( !empty($rshlist))
			$r .= sprintf ('<indie-action do="repost" with="%s" class="share"><h5>%s</h5><ul><li>%s</li></ul></indie-action>', $plink, __('Reshare' ), implode( '</li><li>', $rshlist ) );

		$r .= sprintf ('<indie-action do="post" with="%s" class="share"><h5>%s</h5><ul><li>%s</li></ul></indie-action>', $plink, __('Share' ), implode( '</li><li>', $shlist ) );

		return $r;
	}

	/**
	 *
	 */
	public static function meta ( ) {
		global $post;
		$reply = get_post_meta( $post->ID, 'u-in-reply-to', true );
		if ( !empty($reply)) {
			$l = sprintf ( "%s: [%s](%s){.u-in-reply-to}\n", __("This is a reply to"), $reply, $reply );
			$r = $l;
		}

		$repost = get_post_meta( $post->ID, 'u-repost-of', true );
		if ( !empty($repost)) {
			$l = sprintf ( "%s: [%s](%s){.u-repost-of}\n", __("This is a repost of"), $reply, $reply );
			$r = $l;
		}

		return $r;
	}

}
