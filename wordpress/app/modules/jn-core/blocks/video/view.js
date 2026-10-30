/**
 * jn/video: swap the YouTube facade link for a youtube-nocookie player on a plain click.
 * Modified clicks (new tab/window) keep the link's default behaviour.
 */
( function () {
	'use strict';

	var ID = /^[A-Za-z0-9_-]{11}$/;

	document.addEventListener( 'click', function ( event ) {
		var link = event.target.closest && event.target.closest( 'a[data-jn-video-id]' );
		if ( ! link || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey ) {
			return;
		}
		var id = link.getAttribute( 'data-jn-video-id' );
		if ( ! ID.test( id ) ) {
			return;
		}
		event.preventDefault();

		var frame = document.createElement( 'iframe' );
		frame.src = 'https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1&rel=0';
		frame.title = link.textContent.trim() || 'YouTube';
		frame.allow = 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture';
		frame.allowFullscreen = true;
		frame.className = 'jn-video__frame';
		frame.tabIndex = 0;
		link.replaceWith( frame );
		frame.focus();
	} );
}() );
