jQuery(document).ready(function($) {
	var hash = window.location.hash.substring(1);
	var $thumbs = $('.adaptgal-thumbs ul li a');
	var $previews = $('.adaptgal-previews figure');
	var $slideshow_control = $('#adaptgal-slideshow-control');
	var slideshow_running = false;
	var slideshow_timeout = false;
	var internalclick = false;
	var slideshow_on = 'adaptgal-slideshow-on';
	var $active = false;
	var $loading = $( '.adaptgal-loading' );

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

	$slideshow_control.click( function (e) {
		state = !slideshow_running;
		slideshow_startstop( state );
		return false;
	});

	function slideshow_startstop  ( state ) {
		if ( !state ) {
			$slideshow_control.removeClass ( slideshow_on );
			clearTimeout( slideshow_timeout );
			$loading.stop(true, false).animate({width:'0%'}, 100);
		}
		else {
			$slideshow_control.addClass ( slideshow_on );
			slideshow( true );
		}
		slideshow_running = state;
	}

	function slideshow( first ) {
		internalclick = true;
		slideshow_timeout = setTimeout(slideshow, 3000);
		$loading.animate({width:'100%'}, 3000).animate({width:'0%'}, 1);
		if ( !first ) {
			next( $active );
		}
		internalclick = false;
	}

	$thumbs.click( function (event) {
		// if the click is real click, quit slideshow
		if ( internalclick == false ) {
			slideshow_startstop  ( false );
		}
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
