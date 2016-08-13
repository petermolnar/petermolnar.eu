<?php

namespace PETERMOLNAREU\CLEANUP;

\add_action( 'init', 'PETERMOLNAREU\CLEANUP\init' );
\add_action( 'wp_enqueue_scripts', 'PETERMOLNAREU\CLEANUP\remove_enqueues', 10 );

// cleanup
\remove_action('wp_head', 'rsd_link');
\remove_action('wp_head', 'wlwmanifest_link');
\remove_action('wp_head', 'index_rel_link'); // Index link
\remove_action('wp_head', 'parent_post_rel_link', 10, 0); // Prev link
\remove_action('wp_head', 'start_post_rel_link', 10, 0);
\remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
\remove_action('wp_head', 'adjacent_posts_rel_link', 10, 0);
\remove_action('wp_head', 'wp_generator');
\remove_action('wp_head', 'rest_output_link_wp_head', 10 );
\remove_action('template_redirect', 'rest_output_link_header', 11, 0 );
//remove_action('wp_head', 'rel_canonical');
//remove_action('admin_print_styles', 'print_emoji_styles' );
//remove_action('wp_head', 'print_emoji_detection_script', 7 );
//remove_action('admin_print_scripts', 'print_emoji_detection_script' );
//remove_action('wp_print_styles', 'print_emoji_styles' );

// RSS will be added by hand
\remove_action( 'wp_head', 'feed_links', 2 );
\remove_action( 'wp_head','feed_links_extra', 3);

// replace shortlink
\remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );


/* init function, should be used in the theme init loop */
function init (  ) {
	// cleanup
	//$destroys = [ 'wpautop', 'convert_chars', 'wptexturize', 'make_clickable',
	//'wp_make_content_images_responsive', 'convert_smilies', 'prepend_attachment' ];
	//$froms = [ 'the_content', 'the_excerpt', 'the_title', 'comment_text' ];
	//foreach ( $destroys as $destroy ) {
		//foreach ( $froms as $from ) {
			//\remove_filter( $from, $destroy );
		//}
	//}
	\remove_filter( 'the_content', 'wpautop' );
	\remove_filter( 'the_content', 'convert_chars' );
	\remove_filter( 'the_content', 'wptexturize' );
	\remove_filter( 'the_content', 'make_clickable' );
	\remove_filter( 'the_content', 'convert_smilies' );
	\remove_filter( 'the_content', 'wp_make_content_images_responsive' );

	\remove_filter( 'the_excerpt', 'wpautop' );
	\remove_filter( 'the_excerpt', 'convert_chars' );
	\remove_filter( 'the_excerpt', 'wptexturize' );
	\remove_filter( 'the_excerpt', 'convert_smilies' );

	\remove_filter( 'the_title', 'convert_chars' );
	\remove_filter( 'the_title', 'wptexturize' );

	\remove_filter( 'comment_text', 'make_clickable', 9);

	//remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	//remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	//remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	//add_filter( 'tiny_mce_plugins', array(&$this, 'disable_emojicons_tinymce') );
	\remove_filter( 'the_content', array( $GLOBALS['wp_embed'], 'autoembed' ), 8 );

	// remove too special chars
	\add_filter( 'content_save_pre' , 'PETERMOLNAREU\CLEANUP\_sanitize_content', 10, 1);

	// press this magic
	//\add_filter( 'press_this_data', 'PETERMOLNAREU\CLEANUP\cleanup_press_this_data', 9, 1 );
	\add_filter( 'press_this_suggested_html', 'PETERMOLNAREU\CLEANUP\cleanup_press_this_suggested', 2, 2 );
	\add_filter ('enable_press_this_media_discovery', '__return_false' );

	//\add_filter( 'the_content' , 'PETERMOLNAREU\CLEANUP\_sanitize_content' );
	//\add_filter( 'the_excerpt' , 'PETERMOLNAREU\CLEANUP\_sanitize_content' );
	//\add_filter( 'the_title' , 'PETERMOLNAREU\CLEANUP\_sanitize_content' );

	\remove_filter('sanitize_title', 'sanitize_title_with_dashes');
	\add_filter('sanitize_title', 'PETERMOLNAREU\CLEANUP\sanitize_title_with_dashes', 1, 3);
}

/**
 * replacement function which allowes dots
 *
 */
function sanitize_title_with_dashes( $title, $raw_title = '', $context = 'display' ) {
	$title = strip_tags($title);

	// Preserve escaped octets.
	$title = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $title);
	// Remove percent signs that are not part of an octet.
	$title = str_replace('%', '', $title);
	// Restore octets.
	$title = preg_replace('|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $title);


	$title = _sanitize_content( $title );

	if (\seems_utf8($title)) {
		if (function_exists('mb_strtolower')) {
			$title = mb_strtolower($title, 'UTF-8');
		}
		$title = utf8_uri_encode($title, 200);
	}

	$title = strtolower($title);
	$title = preg_replace('/[^\.%a-z0-9 _-]/', '', $title);
	$title = preg_replace('/\s+/', '-', $title);
	$title = preg_replace('|-+|', '-', $title);
	$title = trim($title, '-');

	return $title;
}

///**
 //*
 //*/
//function cleanup_press_this_data ( $data ) {
	//if ( isset( $data['s'] ) && ! empty( $data['s'] ))
		//$data['s'] = \pmlnr_markdown::html2markdown( $data['s'] );

	//return $data;
//}

/**
 *
 */
function cleanup_press_this_suggested ( $default_html, $data ) {

	$ref = array();
	$relation = '';
	parse_str ( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_QUERY ), $ref );

	if ( is_array( $ref ) && isset ( $ref['u'] ) && ! empty( $ref['u'] ) ) {
		$url = $ref['u'];
		$t = '';

		if ( isset( $ref['type'] ) )
			$t = $ref['type'];

		switch ( $t ) {
			case 'repost':
				$type = 'repost';
				break;
			case 'reply':
				$type = 'reply';
				break;
			default:
				$type = 'fav';
				break;
		}

		$relation = "*** {$type}: {$url}";

	}

	$default_html = array (
		'quote' => '> %1$s',
		//'link' => "\n" . '\\- %2$s',
		'link' => '',
		'embed' => $relation,
	);

	return $default_html;
}

	/*
	 *
	 *
	public static function cleanup_press_this_content ( $content ) {
		$content = preg_replace("/^Source: /m", '\- ', $content);
		return $content;
	}
	*/

/**
 *
 */
function remove_enqueues () {
	// cleanup
	\wp_dequeue_style ('wp-mediaelement');
	\wp_dequeue_style ('open-sans-css');
	\wp_deregister_style ('wp-mediaelement');
	\wp_deregister_style ('open-sans-css');

	\wp_dequeue_script( 'mediaelement' );
	\wp_dequeue_script( 'wp-mediaelement' );
	\wp_dequeue_script ('wp-embed');
	\wp_dequeue_script ('devicepx');

	\wp_deregister_script( 'mediaelement' );
	\wp_deregister_script( 'wp-mediaelement' );
	\wp_deregister_script ('wp-embed');
	\wp_deregister_script ('devicepx');
}

	//public function disable_emojicons_tinymce( $plugins ) {
		//if ( is_array( $plugins ) )
			//return array_diff( $plugins, array( 'wpemoji' ) );
		//else
			//return array();
	//}

/**
 * remove hidious quote chars and other exotic things
 */
function sanitize_content( $content ) {
	$search = array( '”', '“', '’', '–' );
	$replace = array ( '"', '"', "'", '-' );

	$content = str_replace( $search, $replace, $content );
	return $content;
}


/**
 * remove hidious quote chars and other exotic things
 */
function _sanitize_content( $content ) {

	$mimic = unmimic();

	foreach ( $mimic as $char => $evils ) {
		foreach ( $evils as $evil ) {
			$content = str_replace( $evil, $char, $content );
		}
	}

	return $content;
}

/**
 *
 */
function unmimic ( ) {

/*
	$extended = array (
		'0' => array ( '⁰', '₀' ),
		'1' => array ( '¹', '₁' ),
		'2' => array ( '²', '₂' ),
		'3' => array ( '³', '₃' ),
		'4' => array ( '⁴', '₄' ),
		'5' => array ( '⁵', '₅' ),
		'6' => array ( '⁶', '₆' ),
		'7' => array ( '⁷', '₇' ),
		'8' => array ( '⁸', '₈' ),
		'9' => array ( '⁹', '₉' ),
	);
*/

	$base = array (
		' ' => array ( ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', '　', '%c2%a0', '&nbsp;', '&#160;' ),
		'!' => array ( '！', 'ǃ', 'ⵑ', '︕', '﹗', 'ᆝ' ),
		'"' => array ( '”', '“', '＂', '̎', '៉' ),
		"'" => array ( '＇', 'ʹ', 'ʹ', '̍', '’' ),
		'#' => array ( '＃', '﹟' ),
		'$' => array ( '＄', '﹩' ),
		'%' => array ( '％', '٪', '⁒', '﹪' ),
		'&' => array ( '＆', '﹠' ),
		'(' => array ( '（', '﹙', '⁽', '₍', '⟮' ),
		')' => array ( '）', '﹚', '⁾', '₎', '⟯' ),
		'*' => array ( '＊', '⋆', '﹡' ),
		'+' => array ( '＋', '᛭', '﹢', '⁺', '₊' ),
		',' => array ( '，', 'ˏ', 'ᛧ', '‚', '︐', '﹐', '̗', '̦' ),
		'-' => array ( '－', '˗', '−', '⎼', '╴', '﹣', '⁻', '₋', '̵', ' ', 'ᝍ', '᠆', 'ᱼ', '⎯', 'ⲻ', 'ー', 'ㄧ', '%e2%80%93', '&ndash;', '&#8211;', '%e2%80%94', '&mdash;', '&#8212;' ),
		'.' => array ( '．', '․', '﹒', '̣' ),
		'/' => array ( '／', '᜵', '⁄', '∕', '⧸', '̸', 'Ⳇ', '〳' ),
		'{' => array ( '｛', '﹛' ),
		'}' => array ( '｝', '﹜' ),
		'|' => array ( '｜', 'ǀ', 'ᛁ', '⎜', '⎟', '⎢', '⎥', '⎪', '⎮', '￨', '︳', 'ࡆ', 'ᅵ', '⃒', '⼁', '〡', '丨', '︱' ),
		':' => array ( '：', 'ː', '˸', '։', '፡', '᛬', '⁚', '∶', '⠆', '︓', '﹕', '׃', 'ះ', 'ៈ', '᠄', 'ᱺ', '︰' ),
		';' => array ( '；', ';', '︔', '﹔' ),
		'<' => array ( '＜', '˂', '‹', '≺', '❮', 'ⵦ', '﹤', '〱', 'ㄑ' ),
		'=' => array ( '＝', '═', '⚌', '﹦', '⁼', '₌', '゠' ),
		'>' => array ( '＞', '˃', '›', '≻', '❯', '﹥' ),
		'?' => array ( '？', '︖', '﹖' ),
		'@' => array ( '＠', '﹫' ),
		'~' => array ( '～', '˜', '⁓', '∼', '〜' ),
		'[' => array ( '［' ),
		']' => array ( '］' ),
		"\\" => array ( '＼', '∖', '⧵', '⧹', '﹨', '〵' ),
		'^' => array ( '＾', '˄', 'ˆ', 'ᶺ', '⌃', '̂' ),
		'_' => array ( '＿', 'ˍ', '⚊', '̱', '̠', '﹘' ),
		'`' => array ( '｀', 'ˋ', '`', '‵', '̀' ),
		'0' => array ( '⓪', '０', '᱐' ),
		'1' => array ( '①', '１' ),
		'2' => array ( 'ᒿ', '②', '２' ),
		'3' => array ( 'Ʒ', 'ℨ', '③', '３', 'ᢃ', 'Ⳅ', 'Ⳍ', 'ⳍ' ),
		'4' => array ( 'Ꮞ', '④', '４' ),
		'5' => array ( '⑤', '５' ),
		'6' => array ( 'Ꮾ', '⑥', '６' ),
		'7' => array ( '⑦', '７' ),
		'8' => array ( '⑧', '８' ),
		'9' => array ( 'Ꮽ', '⑨', '９' ),
		'A' => array ( 'Α', 'А', 'Ꭺ', 'ᴬ', 'Ⓐ', 'Ａ' ),
		'B' => array ( 'Β', 'В', 'Ᏼ', 'ᗷ', 'Ⲃ', 'ᴮ', 'ℬ', 'Ⓑ', 'Ｂ' ),
		'C' => array ( 'Ϲ', 'С', 'Ꮯ', 'Ⅽ', 'Ⲥ', 'ℂ', 'ℭ', 'Ⓒ', 'Ｃ' ),
		'D' => array ( 'Ꭰ', 'ᗪ', 'Ⅾ', 'ᴰ', 'ⅅ', 'Ⓓ', 'Ｄ' ),
		'E' => array ( 'Ε', 'Е', 'Ꭼ', 'ᴱ', 'ℰ', 'Ⓔ', 'Ｅ' ),
		'F' => array ( 'ᖴ', 'ℱ', 'Ⓕ', 'Ｆ' ),
		'G' => array ( 'Ԍ', 'Ꮐ', 'ᴳ', 'Ⓖ', 'Ｇ' ),
		'H' => array ( 'Η', 'Н', 'ዘ', 'Ꮋ', 'ᕼ', 'Ⲏ', 'ᴴ', 'ℋ', 'ℌ', 'ℍ', 'Ⓗ', 'Ｈ' ),
		'I' => array ( 'Ι', 'І', 'Ⅰ', 'ᴵ', 'ℐ', 'ℑ', 'Ⓘ', 'Ｉ' ),
		'J' => array ( 'Ј', 'Ꭻ', 'ᒍ', 'ᴶ', 'Ⓙ', 'Ｊ' ),
		'K' => array ( 'Κ', 'Ꮶ', 'ᛕ', 'K', 'Ⲕ', 'ᴷ', 'Ⓚ', 'Ｋ' ),
		'L' => array ( 'Ꮮ', 'ᒪ', 'Ⅼ', 'ᴸ', 'ℒ', 'Ⓛ', 'Ｌ', 'Ⳑ', '㇄' ),
		'M' => array ( 'Μ', 'Ϻ', 'М', 'Ꮇ', 'Ⅿ', 'ᴹ', 'ℳ', 'Ⓜ', 'Ｍ' ),
		'N' => array ( 'Ν', 'Ⲛ', 'ᴺ', 'ℕ', 'Ⓝ', 'Ｎ' ),
		'O' => array ( 'Ο', 'О', 'Ⲟ', 'ᴼ', 'Ⓞ', 'Ｏ', 'ᱛ' ),
		'P' => array ( 'Ρ', 'Р', 'Ꮲ', 'Ⲣ', 'ᴾ', 'ℙ', 'Ⓟ', 'Ｐ' ),
		'Q' => array ( 'Ԛ', 'ⵕ', 'ℚ', 'Ⓠ', 'Ｑ', 'Ⴓ' ),
		'R' => array ( 'Ꭱ', 'Ꮢ', 'ᖇ', 'ᴿ', 'ℛ', 'ℜ', 'ℝ', 'Ⓡ', 'Ｒ' ),
		'S' => array ( 'Ѕ', 'Ꮪ', 'Ⓢ', 'Ｓ', 'Ⴝ' ),
		'T' => array ( 'Τ', 'Т', 'Ꭲ', 'ᵀ', 'Ⓣ', 'Ｔ' ),
		'U' => array ( 'ᵁ', 'Ⓤ', 'Ｕ' ),
		'V' => array ( 'Ꮩ', 'Ⅴ', 'Ⓥ', 'Ｖ' ),
		'W' => array ( 'Ꮃ', 'Ꮤ', 'ᵂ', 'Ⓦ', 'Ｗ' ),
		'X' => array ( 'Χ', 'Х', 'Ⅹ', 'Ⲭ', 'Ⓧ', 'Ｘ' ),
		'Y' => array ( 'Υ', 'Ⲩ', 'ϒ', 'Ⓨ', 'Ｙ' ),
		'Z' => array ( 'Ζ', 'Ꮓ', 'ℤ', 'Ⓩ', 'Ｚ' ),
		'a' => array ( 'ɑ', 'а', 'ª', 'ᵃ', 'ᵅ', 'ₐ', 'ⓐ', 'ａ' ),
		'b' => array ( 'ᵇ', 'ⓑ', 'ｂ' ),
		'c' => array ( 'ϲ', 'с', 'ⅽ', 'ᶜ', 'ⓒ', 'ｃ' ),
		'd' => array ( 'ԁ', 'ⅾ', 'ᵈ', 'ⅆ', 'ⓓ', 'ｄ' ),
		'e' => array ( 'е', 'ᥱ', 'ᵉ', 'ₑ', 'ℯ', 'ⅇ', 'ⓔ', 'ｅ', 'ᧉ' ),
		'f' => array ( 'ᶠ', 'ⓕ', 'ｆ' ),
		'g' => array ( 'ɡ', 'ᵍ', 'ᶢ', 'ℊ', 'ⓖ', 'ｇ' ),
		'h' => array ( 'һ', 'ʰ', 'ℎ', 'ⓗ', 'ｈ' ),
		'i' => array ( 'і', 'ⅰ', 'ᵢ', 'ⁱ', 'ℹ', 'ⅈ', 'ⓘ', 'ｉ' ),
		'j' => array ( 'ϳ', 'ј', 'ʲ', 'ⅉ', 'ⓙ', 'ⱼ', 'ｊ' ),
		'k' => array ( 'ᵏ', 'ⓚ', 'ｋ' ),
		'l' => array ( 'ⅼ', 'ˡ', 'ℓ', 'ⓛ', 'ｌ' ),
		'm' => array ( 'ⅿ', 'ᵐ', 'ⓜ', 'ｍ' ),
		'n' => array ( 'ᥒ', 'ⁿ', 'ⓝ', 'ｎ' ),
		'o' => array ( 'ο', 'о', 'ഠ', 'ⲟ', 'º', 'ᵒ', 'ₒ', 'ℴ', 'ⓞ', 'ｏ', '೦', '൦', 'ᦞ', '᧐' ),
		'p' => array ( 'р', 'ⲣ', 'ᵖ', 'ⓟ', 'ｐ' ),
		'q' => array ( 'ⓠ', 'ｑ' ),
		'r' => array ( 'ʳ', 'ᵣ', 'ⓡ', 'ｒ' ),
		's' => array ( 'ѕ', 'ˢ', 'ⓢ', 'ｓ' ),
		't' => array ( 'ᵗ', 'ⓣ', 'ｔ' ),
		'u' => array ( 'ᥙ', '∪', 'ᵘ', 'ᵤ', 'ⓤ', 'ｕ' ),
		'v' => array ( 'ᴠ', 'ⅴ', '∨', '⋁', 'ᵛ', 'ᵥ', 'ⓥ', 'ⱽ', 'ｖ' ),
		'w' => array ( 'ᴡ', 'ʷ', 'ⓦ', 'ｗ' ),
		'x' => array ( 'х', 'ⅹ', 'ⲭ', 'ˣ', 'ₓ', 'ⓧ', 'ｘ' ),
		'y' => array ( 'у', 'ỿ', 'ʸ', 'ⓨ', 'ｙ' ),
		'z' => array ( 'ᴢ', 'ᶻ', 'ⓩ', 'ｚ', 'ᤁ' ),
	);

	//$base = apply_filters('mimic', $base);

	return $base;
}
