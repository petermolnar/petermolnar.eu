<?php

include_once ( dirname(__FILE__) . '/adaptgal-ng.php' );
include_once ( dirname(__FILE__) . '/utils.php' );

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
	public static function vcard ( $uid = false, $show_name = true, $show_avatar = true, $linebreak = false ) {
		$aid = ( $uid==false ) ? 1 : $uid;
		$aemail = get_the_author_meta ( 'user_email' , $aid );
		$aname = get_the_author_meta ( 'display_name' , $aid );
		$aurl = get_the_author_meta ( 'user_url' , $aid );
		$gravatar = md5( strtolower( trim(  $aemail )));
		$show_name = $show_name ? '' : ' hide';
		$show_avatar = $show_avatar ? '' : ' hide';
		$class = 'h-card vcard';

		$r = sprintf ('
		<span class="h-card vcard">
			<a class="fn p-name url u-url%s" href="%s">%s</a>
			<img class="photo avatar u-photo u-avatar%s" src="https://s.gravatar.com/avatar/%s?s=64" style="width:12px; height:12px;" alt="Photo of %s" />
			<a rel="me" class="u-email email icon-mail" href="mailto:%s" title="%s email address">%s</a>
		', $show_name, $aurl, $aname, $show_avatar, $gravatar, $aname, $aemail, $aname, $aemail );

		/* social */
		$socials = array (
			'github' => array (
				'name' => 'Github',
				'url' => 'https://github.com/%s',
			),
			'linkedin' => array (
				'name' => 'LinkedIn',
				'url' => 'https://www.linkedin.com/in/%s',
			),
			'twitter' => array (
				'name' => 'Twitter',
				'url' => 'https://twitter.com/%s',
			),
			'flickr' => array (
				'name' => 'Flickr',
				'url' => 'https://www.flickr.com/people/%s',
			),
			'500px' => array (
				'name' => '500px',
				'url' => 'https://500px.com/%s',
			),
		);
		$c = 2;
		foreach ( $socials as $nw => $social ) {
			$socialmeta = get_the_author_meta ( $nw , $aid );
			if ( !empty ($socialmeta) ) {
				$url = sprintf ( $social['url'], $socialmeta );
				$break = ($linebreak && $c % $linebreak == 0 ) ? '<br />' : '';
				$s[ $nw ] = sprintf ( '<a rel="me" class="u-%s x-%s url u-url icon-%s" href="%s" title="%s @ %s">%s</a>%s', $nw, $nw, $nw, $url, $aname, $social['name'], $socialmeta, $break );
			}
			$c += 1;
		}

		if ( !empty($socials)) {
			//$r .= sprintf ( '<span class="spacer">%s</span>', __('Find me:') );
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
		if ( $title )
			$r = sprintf ('<h5>%s</h5>', __('Tagged as:') );


		$tags = get_the_tags();
		if ( $tags ) {
			$r .= '<div class="p-category">';

			foreach( $tags as $tag )
				$t[] = sprintf ( '<a href="%s" class="icon-tag">%s</a>', get_tag_link( $tag->term_id ), $tag->name );

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

		?><nav class="siblings">
			<ul>
				<li><?php previous_post_link( '%link' , '%title' , true ); ?></li><br />
				<li><?php next_post_link( '%link' , '%title' , true ); ?></li>
			</ul>
		</nav>
		<?php $r = ob_get_clean();
		return $r;
	}

	/**
	 *
	 * @return string structured string for sibling articles
	 */
	public static function related( $title = true, $max = 4 ) {
		global $post;
		$tags = wp_get_post_tags($post->ID);
		$list = array();
		$r = '';
		$exclude = array($post->ID);
		if ($tags) {
			$tag_ids = array();
			foreach($tags as $tag) {
				$tag_ids[] = $tag->term_id;
				//$tags_names[] = $tag->name;
			}

			$numolder = ceil($max / 2);
			$numnewer = $max - $numolder;

			$baseargs=array(
				'tag__in'             => $tag_ids,
				'post__not_in'        => $exclude,
				'ignore_sticky_posts' => 1,
				'caller_get_posts'    => 1,
			);

			# older
			$args = $baseargs;
			$args['posts_per_page'] = $numolder;
			$args['date_query'] =array(array( 'before' => $post->post_date ));
			$_query = new wp_query( $args );

			while( $_query->have_posts() ) {
				$_query->the_post();
				array_push($exclude, $post->ID);
				$img = '';
				if (has_post_thumbnail($post->ID)) {
					$thid = get_post_thumbnail_id( $post->ID );
					$src = wp_get_attachment_image_src($thid, 'thumbnail');
					$img = sprintf ('<img src="%s" class="related-post" />', $src[0]);
				}

				$list[] = sprintf('<li class="related"><a href="%s" title="%s">%s%s</a></li>', get_permalink(), $post->post_title, $img,$post->post_title);
			}

			# newer
			$args = $baseargs;
			$args['posts_per_page'] = $numnewer;
			$args['date_query'] = array(array( 'after' => $post->post_date ));
			$args['post__not_in'] = $exclude;
			$_query = new wp_query( $args );

			while( $_query->have_posts() ) {
				$_query->the_post();
				$img = '';
				if (has_post_thumbnail($post->ID)) {
					$thid = get_post_thumbnail_id( $post->ID );
					$src = wp_get_attachment_image_src($thid, 'thumbnail');
					$img = sprintf ('<img src="%s" class="related-post" />', $src[0]);
				}

				$list[] = sprintf('<li class="related"><a href="%s" title="%s">%s%s</a></li>', get_permalink(), $post->post_title, $img,$post->post_title);
			}

			wp_reset_postdata();
		}

		if (!empty( $list )) {
			if ( $title ) $r .= sprintf ('<h5>%s</h5>', __('Read more:') );
			$r .= sprintf( '<nav class="siblings"><ul>%s</ul></nav>', join ("<br />\n", $list) );
		}

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
		$r = sprintf ( '<span class="right spacer icon-clock">%d %s</span>', round( str_word_count( strip_tags($content), 0 ) / 300 ), __('mins to read') );
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
		return sprintf ( '<a class="u-url right icon-comment spacer" href="%s#comments">%s</a>', get_the_permalink(), get_comments_number( '', '1', '%' )  );
	}

	/**
	 * add sad no comments here message with the possible syndication links
	 *
	 * @return string formatted message, including syndication list
	 *
	 */
	public static function syndicates ( ) {
		global $post;

		$syndicated = array();

		/* SNAP data *
		$_syndicates = self::getRelSyndicationFromSNAP( false, true );
		if ( !empty($_syndicates) )
			foreach ( $_syndicates as $silo => $url )
				$syndicated[ $url ] = 1;

		/* Syndication URLs data *
		$_syndicates = get_post_meta ( get_the_ID(), 'syndication_urls', true );
		if ( $_syndicates ) {
			$_syndicates = explode( "\n", $_syndicates );
			//$syndicated = array_merge ( $syndicated, $_syndicates );
		}

		/* manually imported twitter *
		$tweet_id = get_post_meta( get_the_ID(), 'twitter_tweet_id', true );
		if ( !empty($tweet_id) ) {
			$syndicated[ "https://twitter.com/petermolnar/status/" . $tweet_id ] = 1;
		}

		/* 500px *
		$fivehpx_id = get_post_meta( get_the_ID(), '500px_photo_id', true );
		if ( !empty($fivehpx_id) ) {
			$syndicated[ 'https://500px.com/photo/' . $fivehpx_id ] = 1;
		}

		$syndicated = array_keys($syndicated);
		*/

		$_syndicates = get_post_meta ( get_the_ID(), 'syndication_urls', true );
		if ( $_syndicates ) {
			$_syndicates = explode( "\n", $_syndicates );
			$syndicated = array_merge ( $syndicated, $_syndicates );
		}


		return $syndicated;
	}

	/**
	 * reply at syndicated / linked networks
	 *
	 * @return string formatted message, including syndication list
	 *
	 */
	public static function reply ( ) {
		global $post;

		/* match = '/http[s]?:\/\/(www\.)?([0-9A-Za-z]+)\.([0-9A-Za-z]+)\//' */
		$syndicates = self::syndicates();
		$reply = array();

		/* twitter */
		// "PHP Strict Standards:  Only variables should be passed by reference"
		$arr = preg_grep ( '/twitter\.com/', $syndicates );
		$twitter = array_pop( $arr );
		if ( !empty($twitter)) {
			// "PHP Strict Standards:  Only variables should be passed by reference"
			$arr = explode("/", $twitter);
			$arr = end ( $arr );
			$reply[] = sprintf ( '<li><a class="link-twitter icon-twitter" href="https://twitter.com/intent/tweet?in_reply_to=%s" target="_blank">Twitter</a></li>', $arr );
		}

		$normals = array ( 'facebook', 'flickr', '500px' );
		foreach ( $normals as $silo ):
			$arr = preg_grep ( "/{$silo}/", $syndicates );
			// "PHP Strict Standards:  Only variables should be passed by reference"
			$url = array_pop( $arr );

			if ( !empty($url))
				$reply[] = sprintf ( '<li><a class="link-%s icon-%s" href="%s" target="_blank">%s</a></li>', $silo, $silo, $url, ucfirst($silo) );
		endforeach;

		/* short url */
		$reply[] = sprintf ( '<li><a class="openwebicon-webmention" href="%s" target="_blank">%s</a></li>', wp_get_shortlink(), __('Webmentions') );


		//$r = '';
		//if ( function_exists('getRelSyndicationFromSNAP'))
			//$syndicates = getRelSyndicationFromSNAP( true );

		///* Twitter */
		//if ( !empty ($syndicates['TW'])) {
			//preg_match('/href="(.*?)"/', $syndicates['TW'], $twurls );
			//if (!empty($twurls[1]))
				//$tweet_id = substr(strrchr($twurls[1], "/"), 1);
		//}

		//if ( empty ( $tweet_id ))
			//$tweet_id = get_post_meta( get_the_ID(), 'twitter_tweet_id', true );

		//if ( empty ( $tweet_id ))
			//$tweet_id = get_post_meta( get_the_ID(), 'twitter_id', true );

		//if ( !empty($tweet_id) )
			//$syndicates['TW'] = sprintf ( '<li><a class="link-twitter icon-twitter" href="https://twitter.com/intent/tweet?in_reply_to=%s" target="_blank">Twitter</a></li>', $tweet_id );

		///* 500px */
		//$fivehpx_id = get_post_meta( get_the_ID(), '500px_photo_id', true );

		//if ( !empty($fivehpx_id) )
			//$syndicates['500px'] = sprintf ( '<li><a class="link-500px icon-500px" href="https://500px.com/photo/%s" target="_blank">500px</a></li>', $fivehpx_id );

		///* short url */
		//$url = wp_get_shortlink();
		//$txt = $url;
		//$syndicates[] = sprintf ( '<li><a class="openwebicon-webmention" href="%s" target="_blank">%s</a></li>', $url, __('Webmentions') );

		if (!empty($reply)) {
			$r = sprintf('<indie-action do="reply" with="%s" class="share"><h5>%s</h5><ul>%s</ul></indie-action>', get_permalink(), __('Reply'), implode ( "\n", $reply ));
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
			$shlist[] = '<a class="icon-'. $service .'" href="' . $url . '">'. $txt .'</a>';
		}
		else {
			$url = 'http://www.facebook.com/share.php?u=' . $link . '&t=' . $title;
			$txt = __( 'share' );
			$shlist[] = '<a class="icon-'. $service .'" href="' . $url . '">'. $txt .'</a>';
		}

		/* Twitter */
		$service = 'twitter';
		$repost_id = get_post_meta($post->ID, 'twitter_rt_id', true );
		$repost_uid = get_post_meta($post->ID, 'twitter_rt_user_id', true );
		$tw = get_post_meta( $post->ID, 'twitter_tweet_id', true );
		if ( empty($tw))
			$tw = get_post_meta( get_the_ID(), 'twitter_id', true );

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
		$url = wp_get_shortlink();
		$shlist[] = sprintf ( '<li><a class="openwebicon-webmention" href="%s" target="_blank">%s</a></li>', $url, $url );

		/*
		if ( !empty($rshlist))
			$r .= sprintf ('<indie-action do="repost" with="%s" class="share"><h5>%s</h5><ul><li>%s</li></ul></indie-action>', $plink, __('Reshare' ), implode( '</li><li>', $rshlist ) );
		*/

		$r .= sprintf ('<indie-action do="post" with="%s" class="share"><h5>%s</h5><ul><li>%s</li></ul></indie-action>', $plink, __('Share' ), implode( '</li><li>', $shlist ) );

		//$r .= '<p class="">I also accept <a class="spacer" href="http://indiewebcamp.com/webmentions" rel="nofollow"><i class="openwebicon-webmention"></i>webmentions</a>.</p>';

		return $r;
	}

	/**
	 *
	 */
	public static function meta ( ) {
		global $post;
		$r = array();

		$reply = get_post_meta( $post->ID, 'u-in-reply-to', true );
		if ( !empty($reply)) {
			$reply = explode ("\n", $reply);
			foreach ( $reply as $url ) {
				$url = trim($url);
				$l = sprintf ( "%s: [%s](%s){.u-in-reply-to}\n", __("This is a reply to"), $url, $url );
				$r[] = $l;
			}
		}

		$repost = get_post_meta( $post->ID, 'u-repost-of', true );
		if ( !empty($repost)) {
			$l = sprintf ( "%s: [%s](%s){.u-repost-of}\n", __("This is a repost of"), $repost, $repost );
			$r[] = $l;
		}

		/*
		$pinged = empty($post->pinged) ? array() : explode ("\n", $post->pinged);
		if ( !empty($pinged) && is_array($pinged)) {
			foreach ( $pinged as $url ) {
				if ( !empty($url) && !strstr( $url, 'indiewebcamp.com/webmentions' ) ) {
					$l = sprintf ( "%s: [%s](%s){.u-in-reply-to}\n", __("This is a reply to"), $url, $url );
					$r[] = $l;
				}
			}

		}*/

		return join("\n",$r);
	}


	public static function featured_image ( $src ) {
		global $post;
		$thid = get_post_thumbnail_id( $post->ID );
		if ( ! $thid )
			return $src;

		if ( $kind = wp_get_post_terms( $post->ID, 'kind', array( 'fields' => 'all' ) )) {
			if(is_array($kind)) $kind = array_pop( $kind );
			if (is_object($kind)) $kind = $kind->slug;
		}

		$format = get_post_format ( $post->ID );

		if (!empty($format) && $format != 'standard' ) {
			$img = pmlnr_utils::imagewithmeta( $thid );
			$a = sprintf ( '![%s](%s "%s"){.adaptimg #%s}' , $img['alt'], $img['url'], $img['title'], $thid );
			$src = $src . "\n" . $a;

			if ( $kind == 'photo' or $format == 'image')
				$src = $src . self::photo_exif( $post, $thid );
		}

		return adaptive_images::adaptive_embedded( $src );
	}


	public static function photo_exif ( &$post, &$thid ) {
		$thmeta = wp_get_attachment_metadata( $thid );
		if ( isset( $thmeta['image_meta'] ) && !empty($thmeta['image_meta']) &&
			 isset($thmeta['image_meta']['camera']) && !empty($thmeta['image_meta']['camera']) ):
			$thmeta = $thmeta['image_meta'];

			//shutter speed
			if ( (1 / $thmeta['shutter_speed'] ) > 1) {
				$shutter_speed = "1/";
				if ((number_format((1 / $thmeta['shutter_speed']), 1)) == 1.3 or
					 number_format((1 / $thmeta['shutter_speed']), 1) == 1.5 or
					 number_format((1 / $thmeta['shutter_speed']), 1) == 1.6 or
					 number_format((1 / $thmeta['shutter_speed']), 1) == 2.5)
						$shutter_speed .= number_format((1 / $thmeta['shutter_speed']), 1, '.', '');

				else
					$shutter_speed .= number_format((1 / $thmeta['shutter_speed']), 0, '.', '');
			}
			else {
				$shutter_speed = $thmeta['shutter_speed'];
			}

			$displaymeta = array (
				//'created_timestamp' => sprintf ( __('Taken at: %s'), str_replace('T', ' ', date("c", $thmeta['created_timestamp']))),
				'camera' => '<i class="icon-camera spacer"></i>'. $thmeta['camera'],
				'iso' => sprintf (__('<i class="icon-sensitivity spacer"></i>ISO %s'), $thmeta['iso'] ),
				'focal_length' => sprintf (__('<i class="icon-focallength spacer"></i>%smm'), $thmeta['focal_length'] ),
				'aperture' => sprintf ( __('<i class="icon-aperture spacer"></i>f/%s'), $thmeta['aperture']),
				'shutter_speed' => sprintf( __('<i class="icon-clock spacer"></i>%s sec'), $shutter_speed),
			);

			$cc = get_post_meta ( $post->ID, 'cc', true );
			if ( empty ( $cc ) ) $cc = 'by';

			$ccicons = explode('-', $cc);
			$cci[] = '<i class="icon-cc"></i>';
			foreach ( $ccicons as $ccicon ) {
				$cci[] = '<i class="icon-cc-'. strtolower($ccicon) . '"></i>';
			}

			$cc = '<a href="http://creativecommons.org/licenses/'. $cc .'/4.0/">'. join( $cci,'' ) .'</a>';

			return '<div class="inlinelist">' . $cc . join( ', ', $displaymeta ) .'</div>';
		endif;
	}

	public static function getRelSyndicationFromSNAP( $return_array_only = false, $return_raw = false ) {
		global $nxs_snapAvNts;
		global $post;

		$see_on_social = "";
		$broadcasts = null;

		$snap_options = get_option('NS_SNAutoPoster');
		$urlmap = array (
			'AP' => array(),
			'BG' => array(),
			// 'DA' => array(), /* DeviantArt will use postURL */
			'DI' => array(),
			'DL' => array(),
			'FB' => array( 'url' => '%BASE%/posts/%pgID%' ),
			//'FF' => array(), /* FriendFeed should be using postURL */
			'FL' => array(),
			'FP' => array(),
			'GP' => array(),
			'IP' => array(),
			'LI' => array( 'url' => '%pgID%' ),
			'LJ' => array(),
			'PK' => array(),
			'PN' => array(),
			'SC' => array(),
			'ST' => array(),
			'SU' => array(),
			'TR' => array( 'url'=>'%BASE%/post/%pgID%' ), /* even if Tumblr has postURL set as well, it's buggy and missing a */
			'TW' => array( 'url'=>'%BASE%/status/%pgID%' ),
			'VB' => array(),
			'VK' => array(),
			'WP' => array(),
			'YT' => array(),
		);

		/* all SNAP entries are in separate meta entries for the post based on the service name's "code" */
		if ( !empty($nxs_snapAvNts)):
		foreach ( $nxs_snapAvNts as $key => $serv ) {
			$mkey = 'snap'. $serv['code'];
			$urlkey = $serv['lcode'].'URL';
			$okey = $serv['lcode'];
			$metas = maybe_unserialize(get_post_meta(get_the_ID(), $mkey, true ));


			if ( !empty( $metas ) && is_array ( $metas ) ) {
				foreach ( $metas as $cntr => $m ) {
					$url = false;

					if ( isset ( $m['isPosted'] ) && $m['isPosted'] == 1 ) {
						/* postURL entry will only be used if there's no urlmap set for the service above
						 * this is due to either missing postURL values or buggy entries */
						if ( isset( $m['postURL'] ) && !empty( $m['postURL'] ) && empty( $urlmap[ $serv['code'] ] ) ) {
							$url = $m['postURL'];
						}
						else {
							$base = (isset( $urlmap[ $serv['code'] ]['url'])) ? $urlmap[ $serv['code'] ]['url'] : false;

							if ( $base != false ) {
								/* Facebook exception, why not */
								if ( $serv['code'] == 'FB' ) {
									$pos = strpos( $m['pgID'],'_' );
									$pgID = ( $pos == false ) ? $m['pgID'] : substr( $m['pgID'], $pos + 1 );
								}
								else {
									$pgID = $m['pgID'];
								}

								$o = $snap_options[ $okey ][$cntr];
								$search = array('%BASE%', '%pgID%' );
								$replace = array ( $o[ $urlkey ], $pgID );
								$url = str_replace ( $search, $replace, $base );
							}
						}

						if ( $url != false ) {
							/* trim all the double slashes, some sites cannot coope with them */
							$url = preg_replace('~(^|[^:])//+~', '\\1/', $url);
							$classname = sanitize_title ( $serv['name'], $serv['lcode'] );
							$broadcasts[ $serv['code'] ] = '<li><a class="u-syndication link-'. $classname .' icon-'. $classname .'" rel="syndication" href="'. $url .'" target="_blank">'. $serv['name'] .'</a></li>';

							$raw[ $classname ] = $url;
						}

					}
				}
			}
		}

		endif;

		if (count($broadcasts) != 0 ) {
			$see_on_social = '<ul>'.implode("\n", $broadcasts).'</ul>';
		}

		if ( $return_raw )
			return $raw;
		elseif ( $return_array_only )
			return $broadcasts;
		else
			return $see_on_social;
	}
}
