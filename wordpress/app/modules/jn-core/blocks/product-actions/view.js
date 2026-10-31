/**
 * jn/product-actions: open the shared consult dialog (native <dialog>: focus trap and Escape built in) with a
 * message about the clicked product, copy that message, and keep the email link's body in step with it.
 * Text only ever goes through `value`, `textContent` and attributes — never parsed as HTML.
 */
( function () {
	'use strict';

	var dialog = document.getElementById( 'jn-consult' );
	if ( ! dialog || typeof dialog.showModal !== 'function' ) {
		return;
	}

	var box = dialog.querySelector( '#jn-consult-text' );
	var status = dialog.querySelector( '.jn-consult__status' );
	var copyButton = dialog.querySelector( '[data-jn-consult-copy]' );
	var mail = dialog.querySelector( '[data-jn-consult-mail]' );
	var opener = null;

	// One pass, so a product name that contains "{price}" is never rewritten.
	function fill( template, values ) {
		return template.replace( /\{(name|price)\}/g, function ( match, key ) {
			return values[ key ];
		} );
	}

	function messageFor( trigger ) {
		var product = trigger.getAttribute( 'data-jn-product' );
		if ( ! product ) {
			return dialog.getAttribute( 'data-jn-msg-general' ) || '';
		}
		return fill( dialog.getAttribute( 'data-jn-msg-product' ) || '', {
			name: product,
			price: trigger.getAttribute( 'data-jn-price' ) || ''
		} );
	}

	document.addEventListener( 'click', function ( event ) {
		var trigger = event.target.closest && event.target.closest( '[data-jn-consult]' );
		if ( ! trigger ) {
			return;
		}
		event.preventDefault();
		opener = trigger;
		box.value = messageFor( trigger );
		status.textContent = '';
		if ( mail ) {
			mail.href = 'mailto:' + mail.getAttribute( 'data-jn-consult-mail' ) +
				'?subject=' + encodeURIComponent( dialog.getAttribute( 'data-jn-mail-subject' ) || '' ) +
				'&body=' + encodeURIComponent( box.value );
		}
		dialog.showModal();
	} );

	// A click on the backdrop lands on the <dialog> itself (its content fills the padding box). The press must
	// start there too: dragging a text selection out of the message box also ends with a click on the dialog.
	var pressedBackdrop = false;
	dialog.addEventListener( 'pointerdown', function ( event ) {
		pressedBackdrop = event.target === dialog;
	} );
	dialog.addEventListener( 'click', function ( event ) {
		var onBackdrop = pressedBackdrop && event.target === dialog;
		pressedBackdrop = false;
		if ( onBackdrop || ( event.target.closest && event.target.closest( '[data-jn-consult-close]' ) ) ) {
			dialog.close();
		}
	} );

	dialog.addEventListener( 'close', function () {
		if ( opener && document.contains( opener ) ) {
			opener.focus();
		}
		opener = null;
	} );

	// Fallback copy needs the text selected; focus then returns to the button so keyboard users keep their place.
	function copyBySelection() {
		var copied = false;
		box.focus();
		box.select();
		try {
			copied = document.execCommand( 'copy' );
		} catch ( error ) {
			copied = false;
		}
		copyButton.focus();
		return copied;
	}

	function report( copied ) {
		status.textContent = dialog.getAttribute( copied ? 'data-jn-copied' : 'data-jn-copy-failed' ) || '';
	}

	copyButton.addEventListener( 'click', function () {
		if ( navigator.clipboard && window.isSecureContext ) {
			navigator.clipboard.writeText( box.value ).then(
				function () {
					report( true );
				},
				function () {
					report( copyBySelection() );
				}
			);
			return;
		}
		report( copyBySelection() );
	} );
}() );
