<?php
/**
 * Jannie Motion Layer: scroll reveal and header scroll state (motion-ui).
 *
 * Pairs with `assets/css/motion.css`. Pure progressive enhancement: content is only hidden once an
 * IntersectionObserver is ready to reveal it, and only when it starts below the first viewport, so
 * the hero / page intro (LCP) and anything already on screen are never touched.
 *
 * @package Jannie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Print the motion script:
 * - Header gets `.is-scrolled` once a top-of-page sentinel leaves the viewport (no scroll handler).
 * - `html.jn-smooth-scroll` (smooth in-page anchors) is set on first input, so a cross-page #anchor lands at once.
 * - Below-the-fold section headers, grid items and FAQ items fade up once as they scroll in; items of
 *   one grid are staggered. Skipped entirely under `prefers-reduced-motion`.
 * - Low-end devices (<= 2 GB RAM, or <= 4 cores where RAM is not exposed) get `.jn-motion-lite`.
 *
 * @return void
 */
function jannie_print_motion_script() {
	?>
	<script id="jn-motion">
	(function () {
		if (!('IntersectionObserver' in window)) {
			return;
		}
		var root = document.documentElement;

		// Smooth in-page anchors from the first input on: the browser's own scroll to a cross-page #anchor
		// (which can run after load) stays instant, while every anchor the visitor clicks still glides.
		var enableSmoothScroll = function () {
			root.classList.add('jn-smooth-scroll');
		};
		['pointerdown', 'keydown', 'touchstart'].forEach(function (type) {
			window.addEventListener(type, enableSmoothScroll, { once: true, passive: true, capture: true });
		});

		var header = document.getElementById('header');
		if (header) {
			var sentinel = document.createElement('div');
			sentinel.className = 'jn-scroll-sentinel';
			sentinel.setAttribute('aria-hidden', 'true');
			document.body.appendChild(sentinel);
			new IntersectionObserver(function (entries) {
				header.classList.toggle('is-scrolled', !entries[entries.length - 1].isIntersecting);
			}).observe(sentinel);
		}

		if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
			return;
		}

		var memory = navigator.deviceMemory;
		if ((memory !== undefined && memory <= 2) || (memory === undefined && navigator.hardwareConcurrency <= 4)) {
			root.classList.add('jn-motion-lite');
		}

		var REVEAL_TARGETS = [
			'.jn-section-header-split',
			'.jn-section-header-centered',
			'.jn-expert-grid > .wp-block-column',
			'.jn-pillars-grid > .wp-block-column',
			'.jn-tiers-grid > .wp-block-column',
			'.jn-testimonials-grid > li',
			'.jn-faq > *',
			'.jn-booking-shell',
			'.jn-fav-intro__text',
			'.jn-fav-collection__title',
			'.jn-fav-products .wc-block-product',
			'.jn-fav-manifesto__content'
		];
		var MAX_STAGGER_STEPS = 4;
		var fold = window.innerHeight;

		// Read every position first, then write classes, so layout is measured once. Targets are tested by
		// their parent's top so a grid is either fully hidden or left alone, never revealed half by half.
		var belowFold = [];
		var tops = [];
		Array.prototype.forEach.call(document.querySelectorAll(REVEAL_TARGETS.join(',')), function (el) {
			if (el.parentNode.getBoundingClientRect().top > fold) {
				belowFold.push(el);
				tops.push(Math.round(el.getBoundingClientRect().top));
			}
		});

		var observer = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (!entry.isIntersecting) {
					return;
				}
				var el = entry.target;
				observer.unobserve(el);
				// Hand the element back to its own styles afterwards (card hover transforms, etc.).
				el.addEventListener('transitionend', function done(e) {
					if (e.target !== el || e.propertyName !== 'opacity') {
						return;
					}
					el.removeEventListener('transitionend', done);
					el.classList.remove('jn-reveal', 'is-revealed');
					el.style.removeProperty('--jn-reveal-order');
				});
				el.classList.add('is-revealed');
			});
		}, { rootMargin: '0px 0px -10% 0px' });

		// Stagger only siblings that share a row; stacked items (mobile, FAQ) each enter on their own.
		belowFold.forEach(function (el, i) {
			var order = 0;
			for (var j = i - 1; j >= 0 && order < MAX_STAGGER_STEPS && belowFold[j].parentNode === el.parentNode && tops[j] === tops[i]; j--) {
				order++;
			}
			el.style.setProperty('--jn-reveal-order', String(order));
			el.classList.add('jn-reveal');
			observer.observe(el);
		});
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'jannie_print_motion_script', 30 );
