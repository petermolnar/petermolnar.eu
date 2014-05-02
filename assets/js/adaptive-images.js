jQuery(document).ready(function($) {
	var hash = window.location.hash.substring(1);
	var $adaptgal = $('.adaptgal');
	var $thumbs = $('.adaptgal-thumbs ul li a');
	var $previews = $('.adaptgal-previews figure');
	var internalclick = false;
	var $active = false;
	var $ae = false;
	var h = 0.9;

	//$(".adaptgal").each(function (i) {
	//	var h = $(this).height()
	//	if ( h > $(window).height() ) {
	//		$(this).height ( $(window).height() )
	//	}
	//});

/*	var keyCode = {
		DOWN: 40,
		END: 35,
		HOME: 36,
		LEFT: 37,
		PAGE_DOWN: 34,
		PAGE_UP: 33,
		RIGHT: 39,
		UP: 38
	}
*/

	$(document).keydown(function(event){
		var key = event.keyCode || event.which;

		// right
		if ( key === 39 ) {
			next ( $active );
			return false;
		}
		// left
		else if ( key === 37 ) {
			prev ( $active );
			return false;
		}

		return true;
	});

	function next ( e ) {
		$test = $('a[href="#' + $(e).attr('id') +'"]').parent().next().children();
		if ( $test.length > 0 ) {
			$next = $test.first();
		}
		else {
			$next = $thumbs.first();
		}
		$next.trigger('click');
	}

	function prev ( e ) {
		$test = $('a[href="#' + $(e).attr('id') +'"]').parent().prev().children();
		if ( $test.length > 0 ) {
			$prev = $test.first();
		}
		else {
			$prev = $thumbs.last();
		}
		$prev.trigger('click');
	}

	$thumbs.click( function (event) {
		$ae = $(this);
		$active = $( $(this).attr('href') );
		$thumbs.removeClass('adaptgal-active');
		$(this).addClass('adaptgal-active');

		var pos = $(window).scrollTop();
		location.hash = $(this).attr('href');
		$(window).scrollTop(pos);
	});

	//// swipe reactions, only one finger!
	//$previews.swipe( {
	//	swipeLeft:function(event, direction, distance, duration, fingerCount) {
	//		next( $(this) );
	//	},
	//	swipeRight:function(event, direction, distance, duration, fingerCount) {
	//		prev( $(this) );
	//	},
	//	threshold:0,
	//	fingers:1
	//});

	// init the first element or activate the one set by anchor hash
	if ( $active == false ) {
		if ( ! hash ) {
			$first = $thumbs.first();
		}
		else {
			$first = $('a[href="#' + hash +'"]');
		}
		$first.trigger('click');
	}
});
