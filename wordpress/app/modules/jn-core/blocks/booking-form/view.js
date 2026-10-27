/**
 * Progressive Enhancement for `jn/booking-form` (<= 5 KB compressed, deferred).
 *
 * 1. Tier button click interception (`a[href*="#booking-form"]` with `?goi=`):
 *    preselects the matching package in `#package-select` and scrolls smoothly
 *    to `#booking-form` without a page reload.
 * 2. Page-cache safe token refresh: on first user interaction (`focusin`, `pointerdown`,
 *    `touchstart`), fetches a short-lived signed token from `data-token-url` (`rest_url()`)
 *    if the embedded token is missing, invalid, or older than 10 minutes.
 * 3. Submits booking requests via `fetch()` to `data-submit-url` (`rest_url()`) and
 *    displays the verbatim wireframe success text.
 */
( function () {
	'use strict';

	var PACKAGE_MAP = {
		'30-phut': 'Gói Tư Vấn Cá Nhân 1:1 (30 Phút)',
		'60-phut': 'Gói Coaching Chuyên Nghiệp 1:1 (60 Phút)',
		'mentorship-3-thang': 'Gói Master Mentorship (3 Tháng)'
	};

	var form = document.getElementById( 'coaching-registration-form' );
	if ( ! form ) {
		return;
	}

	var selectEl = document.getElementById( 'package-select' );
	var waitlistEl = document.getElementById( 'jn-booking-waitlist-note' );
	var tokenInput = document.getElementById( 'jn-form-token' );
	var landingInput = document.getElementById( 'jn-landing-url' );
	var referrerInput = document.getElementById( 'jn-referrer' );
	var submitBtn = document.getElementById( 'submit-btn' );
	var feedback = document.getElementById( 'form-feedback' );
	var defaultBtnHtml = submitBtn ? submitBtn.innerHTML : '';

	if ( landingInput ) {
		landingInput.value = window.location.href;
	}
	if ( referrerInput && document.referrer ) {
		referrerInput.value = document.referrer;
	}

	function syncWaitlistBanner() {
		if ( ! selectEl || ! waitlistEl ) {
			return;
		}
		var opt = selectEl.options[ selectEl.selectedIndex ];
		var atCap = opt && opt.getAttribute( 'data-at-capacity' ) === '1';
		if ( atCap ) {
			waitlistEl.hidden = false;
			waitlistEl.classList.add( 'is-visible' );
		} else {
			waitlistEl.hidden = true;
			waitlistEl.classList.remove( 'is-visible' );
		}
	}

	function selectPackageByKey( keyOrValue ) {
		if ( ! selectEl || ! keyOrValue ) {
			return false;
		}

		var targetVal = PACKAGE_MAP[ keyOrValue ] || keyOrValue;
		for ( var i = 0; i < selectEl.options.length; i++ ) {
			var opt = selectEl.options[ i ];
			if (
				opt.value === targetVal ||
				opt.getAttribute( 'data-package-key' ) === keyOrValue ||
				opt.text.indexOf( targetVal.slice( 0, 14 ) ) !== -1
			) {
				selectEl.selectedIndex = i;
				syncWaitlistBanner();
				return true;
			}
		}
		return false;
	}

	function parseGoiFromUrl( href ) {
		try {
			var url = new URL( href, window.location.href );
			return url.searchParams.get( 'goi' );
		} catch ( err ) {
			return null;
		}
	}

	if ( selectEl ) {
		selectEl.addEventListener( 'change', syncWaitlistBanner );
		syncWaitlistBanner();
	}

	var initialGoi = parseGoiFromUrl( window.location.href );
	if ( initialGoi ) {
		selectPackageByKey( initialGoi );
	}

	// Page-cache safe token handshake on first interaction.
	var tokenPromise = null;
	var tokenRefreshed = false;

	function getTokenTimestamp( rawToken ) {
		if ( ! rawToken ) {
			return 0;
		}
		var parts = String( rawToken ).split( '.' );
		var ts = parseInt( parts[ 0 ], 10 );
		return isNaN( ts ) ? 0 : ts;
	}

	function ensureFreshToken( forceRefresh ) {
		var tokenUrl = form.getAttribute( 'data-token-url' );
		if ( ! tokenUrl || ! window.fetch ) {
			return Promise.resolve();
		}

		var currentVal = tokenInput ? tokenInput.value : '';
		var issuedTs = getTokenTimestamp( currentVal );
		var nowSec = Math.floor( Date.now() / 1000 );
		var isStaleOrInvalid = ! issuedTs || currentVal.split( '.' ).length !== 3 || ( nowSec - issuedTs ) > 600 || ( nowSec - issuedTs ) < 0;

		if ( ! forceRefresh && ! isStaleOrInvalid && tokenRefreshed ) {
			return Promise.resolve();
		}

		if ( ! forceRefresh && ! isStaleOrInvalid && issuedTs > 0 ) {
			// Still fetch once in background on first interaction so cached HTML is always verified.
			if ( tokenRefreshed ) {
				return Promise.resolve();
			}
		}

		if ( tokenPromise ) {
			return tokenPromise;
		}

		tokenRefreshed = true;
		tokenPromise = window.fetch( tokenUrl, {
			method: 'GET',
			credentials: 'same-origin',
			cache: 'no-store',
			headers: { Accept: 'application/json' }
		} )
			.then( function ( res ) {
				return res.ok ? res.json() : null;
			} )
			.then( function ( data ) {
				if ( data && data.token && tokenInput ) {
					// Replace token if original token was stale/cached or invalid.
					if ( forceRefresh || isStaleOrInvalid ) {
						tokenInput.value = data.token;
						tokenInput.setAttribute( 'data-issued-at', String( data.issued_at || Math.floor( Date.now() / 1000 ) ) );
					}
				}
				tokenPromise = null;
			} )
			.catch( function () {
				tokenPromise = null;
			} );

		return tokenPromise;
	}

	function onFirstInteraction() {
		ensureFreshToken( false );
	}

	form.addEventListener( 'focusin', onFirstInteraction, { once: true } );
	form.addEventListener( 'pointerdown', onFirstInteraction, { once: true } );

	// Intercept clicks on tier links pointing to `?goi=<key>#booking-form`.
	document.addEventListener( 'click', function ( e ) {
		var link = e.target.closest ? e.target.closest( 'a[href*="#booking-form"]' ) : null;
		if ( ! link ) {
			return;
		}

		var href = link.getAttribute( 'href' ) || '';
		var goiKey = parseGoiFromUrl( href );
		var targetSection = document.getElementById( 'booking-form' );

		if ( goiKey && targetSection ) {
			e.preventDefault();
			ensureFreshToken( false );
			selectPackageByKey( goiKey );

			if ( window.history && window.history.replaceState ) {
				var nextUrl = new URL( window.location.href );
				nextUrl.searchParams.set( 'goi', goiKey );
				nextUrl.hash = 'booking-form';
				window.history.replaceState( null, '', nextUrl.toString() );
				if ( landingInput ) {
					landingInput.value = nextUrl.toString();
				}
			}

			targetSection.scrollIntoView( { behavior: 'smooth', block: 'start' } );

			if ( selectEl ) {
				setTimeout( function () {
					selectEl.focus( { preventScroll: true } );
				}, 300 );
			}
		}
	} );

	function showError( msg ) {
		if ( feedback ) {
			feedback.className = 'jn-booking-form__feedback is-visible is-error';
			feedback.textContent = msg;
		}
		if ( submitBtn ) {
			submitBtn.disabled = false;
			submitBtn.removeAttribute( 'aria-busy' );
			submitBtn.innerHTML = defaultBtnHtml;
		}
	}

	function showSuccess( waitlistMsg ) {
		if ( submitBtn ) {
			submitBtn.classList.add( 'is-hidden' );
			submitBtn.removeAttribute( 'aria-busy' );
		}
		if ( feedback ) {
			feedback.className = 'jn-booking-form__feedback is-visible';
			var html =
				'<p class="jn-booking-form__feedback-copy"><span class="jn-booking-form__feedback-strong">Cảm ơn bạn!</span> Yêu cầu đã được ghi nhận. Đội ngũ trợ lý của Jannie Nguyễn sẽ liên hệ trực tiếp qua số điện thoại của bạn trong vòng 24 giờ.</p>';
			if ( waitlistMsg ) {
				html += '<p class="jn-booking-form__feedback-waitlist">' + waitlistMsg + '</p>';
			}
			feedback.innerHTML = html;
		}
	}

	form.addEventListener( 'submit', function ( e ) {
		var submitUrl = form.getAttribute( 'data-submit-url' );
		if ( ! submitUrl || ! window.fetch ) {
			return;
		}

		e.preventDefault();

		var hp = document.getElementById( 'jn-website-hp' );
		if ( hp && hp.value !== '' ) {
			return;
		}

		var nameInput = document.getElementById( 'full-name' );
		var phoneInput = document.getElementById( 'phone-number' );
		var emailInput = document.getElementById( 'email-address' );
		var consentInput = document.getElementById( 'privacy-consent' );

		var missing = [];
		if ( ! nameInput || nameInput.value.trim().length < 2 ) {
			missing.push( 'Họ và Tên' );
		}
		if ( ! phoneInput || ! phoneInput.value.trim() ) {
			missing.push( 'Số Điện Thoại / Zalo' );
		}
		if ( ! emailInput || ! emailInput.value.trim() || emailInput.value.indexOf( '@' ) === -1 ) {
			missing.push( 'Địa Chỉ Email hợp lệ' );
		}
		if ( ! consentInput || ! consentInput.checked ) {
			missing.push( 'Đồng ý Chính sách bảo mật' );
		}

		if ( missing.length > 0 ) {
			showError( 'Vui lòng điền đầy đủ thông tin bắt buộc: ' + missing.join( ', ' ) + '.' );
			return;
		}

		if ( submitBtn ) {
			submitBtn.disabled = true;
			submitBtn.setAttribute( 'aria-busy', 'true' );
			submitBtn.innerHTML = '<span>Đang Xử Lý Hồ Sơ...</span>';
		}

		ensureFreshToken( false ).then( function () {
			var currentToken = tokenInput ? tokenInput.value : '';
			var issuedTs = getTokenTimestamp( currentToken );
			var minFill = parseInt( form.getAttribute( 'data-min-fill' ) || '2', 10 );
			var nowSec = Date.now() / 1000;
			var waitMs = 0;

			if ( issuedTs > 0 && ( nowSec - issuedTs ) < ( minFill + 0.25 ) ) {
				waitMs = Math.ceil( ( ( minFill + 0.25 ) - ( nowSec - issuedTs ) ) * 1000 );
			}

			setTimeout( function () {
				var formData = new FormData( form );
				var payload = {};
				formData.forEach( function ( val, key ) {
					payload[ key ] = val;
				} );

				window.fetch( submitUrl, {
					method: 'POST',
					credentials: 'same-origin',
					headers: {
						'Content-Type': 'application/json',
						Accept: 'application/json'
					},
					body: JSON.stringify( payload )
				} )
					.then( function ( res ) {
						return res.json().then( function ( body ) {
							return { status: res.status, body: body };
						} );
					} )
					.then( function ( result ) {
						if ( result.status === 400 && result.body && result.body.code === 'expired_token' ) {
							// Automatically recover once if the page was served from an old page cache.
							return ensureFreshToken( true ).then( function () {
								var retryWait = ( parseInt( form.getAttribute( 'data-min-fill' ) || '2', 10 ) + 0.3 ) * 1000;
								return new Promise( function ( resolve ) {
									setTimeout( resolve, retryWait );
								} ).then( function () {
									payload._jn_form_token = tokenInput ? tokenInput.value : '';
									return window.fetch( submitUrl, {
										method: 'POST',
										credentials: 'same-origin',
										headers: {
											'Content-Type': 'application/json',
											Accept: 'application/json'
										},
										body: JSON.stringify( payload )
									} ).then( function ( r2 ) {
										return r2.json().then( function ( b2 ) {
											return { status: r2.status, body: b2 };
										} );
									} );
								} );
							} );
						}
						return result;
					} )
					.then( function ( finalResult ) {
						if ( finalResult.body && finalResult.body.success ) {
							showSuccess( finalResult.body.waitlist_msg || '' );
						} else {
							var errMsg = ( finalResult.body && finalResult.body.message )
								? finalResult.body.message
								: 'Không thể gửi yêu cầu lúc này. Vui lòng thử lại.';
							showError( errMsg );
						}
					} )
					.catch( function () {
						showError( 'Lỗi kết nối mạng. Vui lòng kiểm tra kết nối và thử lại.' );
					} );
			}, waitMs );
		} );
	} );
} )();
