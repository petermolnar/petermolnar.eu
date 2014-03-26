jQuery(document).ready(function($) {
	var hash = window.location.hash.substring(1);
	var $adaptgal = $('.adaptgal');
	var $thumbs = $('.adaptgal-thumbs ul li a');
	var $previews = $('.adaptgal-previews figure');
	var internalclick = false;
	var $active = false;
	var h = 0.9;

	//$(window).bind('resize', function() {
	//	$adaptgal.height( $(document).height() * h );
	//});

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
		$active = $( $(this).attr('href') );
		$thumbs.removeClass('adaptgal-active');
		$(this).addClass('adaptgal-active');
		location.href = $(this).attr('href');
		//internalclick = false;
	});

	// swipe reactions, only one finger!
	$previews.swipe( {
		swipeLeft:function(event, direction, distance, duration, fingerCount) {
			next( $(this) );
		},
		swipeRight:function(event, direction, distance, duration, fingerCount) {
			prev( $(this) );
		},
		threshold:0,
		fingers:1
	});

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
