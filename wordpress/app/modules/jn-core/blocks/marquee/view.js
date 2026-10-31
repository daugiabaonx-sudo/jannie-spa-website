/**
 * jn/marquee: the pause button stops every decorative loop on the page (marquee, orbits, floating bottles)
 * by toggling `html.jn-fav-motion-paused` (WCAG 2.2.2). Its name stays the same; `aria-pressed` carries the state.
 */
( function () {
	'use strict';

	var buttons = document.querySelectorAll( '.jn-marquee__toggle' );
	if ( ! buttons.length ) {
		return;
	}
	var root = document.documentElement;

	function sync( paused ) {
		root.classList.toggle( 'jn-fav-motion-paused', paused );
		Array.prototype.forEach.call( buttons, function ( button ) {
			button.setAttribute( 'aria-pressed', paused ? 'true' : 'false' );
		} );
	}

	Array.prototype.forEach.call( buttons, function ( button ) {
		button.addEventListener( 'click', function () {
			sync( ! root.classList.contains( 'jn-fav-motion-paused' ) );
		} );
	} );
}() );
