/**
 * jn/product-stage: a soft spotlight follows the mouse across the hero and the stage drifts a few pixels
 * against it (ADR-025). Mouse only, never under reduced motion or on low-end devices (`.jn-motion-lite`).
 * Both effects move by `transform` alone, once per animation frame.
 */
( function () {
	'use strict';

	var stage = document.querySelector( '.jn-product-stage' );
	if ( ! stage || ! window.matchMedia ) {
		return;
	}
	if ( ! window.matchMedia( '(pointer: fine)' ).matches || window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
		return;
	}

	var area = stage.closest( 'section' ) || stage.parentNode;
	var spot = stage.querySelector( '.jn-product-stage__spotlight' );
	var PARALLAX_X = -13;
	var PARALLAX_Y = -11;
	var pointer = null;
	var frame = 0;

	function isStill() {
		var root = document.documentElement;
		return root.classList.contains( 'jn-motion-lite' ) || root.classList.contains( 'jn-fav-motion-paused' );
	}

	function paint() {
		frame = 0;
		if ( ! pointer || isStill() ) {
			return;
		}
		var bounds = area.getBoundingClientRect();
		var x = Math.min( 1, Math.max( 0, ( pointer.x - bounds.left ) / bounds.width ) );
		var y = Math.min( 1, Math.max( 0, ( pointer.y - bounds.top ) / bounds.height ) );
		stage.style.setProperty( '--jn-stage-x', ( ( x - 0.5 ) * PARALLAX_X ).toFixed( 1 ) + 'px' );
		stage.style.setProperty( '--jn-stage-y', ( ( y - 0.5 ) * PARALLAX_Y ).toFixed( 1 ) + 'px' );
		if ( spot ) {
			var own = stage.getBoundingClientRect();
			spot.classList.add( 'is-tracking' );
			spot.style.transform = 'translate3d(' + Math.round( pointer.x - own.left ) + 'px,' + Math.round( pointer.y - own.top ) + 'px,0)';
		}
	}

	area.addEventListener( 'pointermove', function ( event ) {
		pointer = { x: event.clientX, y: event.clientY };
		if ( ! frame ) {
			frame = window.requestAnimationFrame( paint );
		}
	}, { passive: true } );

	area.addEventListener( 'pointerleave', function () {
		pointer = null;
		stage.style.removeProperty( '--jn-stage-x' );
		stage.style.removeProperty( '--jn-stage-y' );
		if ( spot ) {
			spot.classList.remove( 'is-tracking' );
			spot.style.removeProperty( 'transform' );
		}
	} );
}() );
