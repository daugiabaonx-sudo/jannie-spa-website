/**
 * Progressive Enhancement for `jn/booking-form` (<= 5 KB compressed, deferred).
 *
 * 1. Tier button click interception (`a[href*="goi="][href*="#booking-form"]`):
 *    preselects the matching package in `#package-select` and scrolls smoothly
 *    to `#booking-form` without a page reload.
 * 2. Local validation + Step 3 preview notice ("chức năng đang hoàn thiện")
 *    rendered inside the `aria-live="polite"` feedback region.
 */
( function () {
	'use strict';

	var PACKAGE_MAP = {
		'30-phut': 'Gói Tư Vấn Cá Nhân 1:1 (30 Phút)',
		'60-phut': 'Gói Coaching Chuyên Nghiệp 1:1 (60 Phút)',
		'mentorship-3-thang': 'Gói Master Mentorship (3 Tháng)'
	};

	function selectPackageByKey( keyOrValue ) {
		var selectEl = document.getElementById( 'package-select' );
		if ( ! selectEl || ! keyOrValue ) {
			return false;
		}

		var targetVal = PACKAGE_MAP[ keyOrValue ] || keyOrValue;
		for ( var i = 0; i < selectEl.options.length; i++ ) {
			var opt = selectEl.options[ i ];
			if (
				opt.value === targetVal ||
				opt.getAttribute( 'data-package-key' ) === keyOrValue ||
				opt.text.indexOf( targetVal.slice( 0, 12 ) ) !== -1
			) {
				selectEl.selectedIndex = i;
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

	// 1. Apply `?goi=` from current URL if present.
	var initialGoi = parseGoiFromUrl( window.location.href );
	if ( initialGoi ) {
		selectPackageByKey( initialGoi );
	}

	// 2. Intercept clicks on tier links pointing to `?goi=<key>#booking-form`.
	document.addEventListener( 'click', function ( e ) {
		var link = e.target.closest( 'a[href*="#booking-form"]' );
		if ( ! link ) {
			return;
		}

		var href = link.getAttribute( 'href' ) || '';
		var goiKey = parseGoiFromUrl( href );
		var targetSection = document.getElementById( 'booking-form' );

		if ( goiKey && targetSection ) {
			e.preventDefault();
			selectPackageByKey( goiKey );

			if ( window.history && window.history.replaceState ) {
				var nextUrl = new URL( window.location.href );
				nextUrl.searchParams.set( 'goi', goiKey );
				nextUrl.hash = 'booking-form';
				window.history.replaceState( null, '', nextUrl.toString() );
			}

			targetSection.scrollIntoView( { behavior: 'smooth', block: 'start' } );

			var selectEl = document.getElementById( 'package-select' );
			if ( selectEl ) {
				setTimeout( function () {
					selectEl.focus( { preventScroll: true } );
				}, 350 );
			}
		}
	} );

	// 3. Handle form submission locally until Step 5 backend endpoint is wired.
	var form = document.getElementById( 'coaching-registration-form' );
	if ( ! form ) {
		return;
	}

	form.addEventListener( 'submit', function ( e ) {
		e.preventDefault();

		var hp = document.getElementById( 'jn-website-hp' );
		if ( hp && hp.value !== '' ) {
			return;
		}

		var feedback = document.getElementById( 'form-feedback' );
		var submitBtn = document.getElementById( 'submit-btn' );
		var nameInput = document.getElementById( 'full-name' );
		var phoneInput = document.getElementById( 'phone-number' );
		var emailInput = document.getElementById( 'email-address' );
		var consentInput = document.getElementById( 'privacy-consent' );

		var missing = [];
		if ( ! nameInput || ! nameInput.value.trim() ) {
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
			if ( feedback ) {
				feedback.className = 'jn-booking-form__feedback is-visible is-error';
				feedback.textContent = 'Vui lòng điền đầy đủ thông tin bắt buộc: ' + missing.join( ', ' ) + '.';
			}
			return;
		}

		if ( submitBtn ) {
			submitBtn.disabled = true;
			submitBtn.setAttribute( 'aria-busy', 'true' );
			submitBtn.innerHTML = '<span>Đang Xử Lý Hồ Sơ...</span>';
		}

		setTimeout( function () {
			if ( submitBtn ) {
				submitBtn.classList.add( 'is-hidden' );
			}
			if ( feedback ) {
				feedback.className = 'jn-booking-form__feedback is-visible';
				feedback.innerHTML =
					'<p class="jn-booking-form__feedback-dev">[Thông báo: Chức năng gửi hồ sơ trực tuyến đang hoàn thiện (Step 5) — dữ liệu hiện chỉ hiển thị cục bộ trên trình duyệt.]</p>' +
					'<p class="jn-booking-form__feedback-copy"><span class="jn-booking-form__feedback-strong">Cảm ơn bạn!</span> Yêu cầu đã được ghi nhận. Đội ngũ trợ lý của Jannie Nguyễn sẽ liên hệ trực tiếp qua số điện thoại của bạn trong vòng 24 giờ.</p>';
			}
		}, 400 );
	} );
} )();
