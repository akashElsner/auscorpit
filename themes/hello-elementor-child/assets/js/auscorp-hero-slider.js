(function () {
	'use strict';

	function initHeroVideos(hero) {
		var root = hero || document;
		root.querySelectorAll('.auscorp-hero__video').forEach(function (video) {
			if (video.dataset.auscorpVideoReady === 'true') {
				return;
			}

			video.muted = true;
			video.defaultMuted = true;
			video.setAttribute('muted', '');
			video.setAttribute('playsinline', '');
			video.setAttribute('webkit-playsinline', '');

			var playPromise = video.play();
			if (playPromise && typeof playPromise.then === 'function') {
				playPromise.catch(function () {
					var resume = function () {
						video.play().catch(function () {});
						document.removeEventListener('touchstart', resume);
						document.removeEventListener('click', resume);
					};
					document.addEventListener('touchstart', resume, { once: true, passive: true });
					document.addEventListener('click', resume, { once: true });
				});
			}

			video.dataset.auscorpVideoReady = 'true';
		});
	}

	function initHeroSlider(slider) {
		var slides = slider.querySelectorAll('.auscorp-hero__slide');
		var dots = slider.querySelectorAll('.auscorp-hero__dot');
		var prevBtn = slider.querySelector('.auscorp-hero__arrow--prev');
		var nextBtn = slider.querySelector('.auscorp-hero__arrow--next');

		if (!slides.length) {
			return;
		}

		var current = 0;
		var total = slides.length;
		var autoplay = slider.dataset.autoplay === 'true';
		var autoplaySpeed = parseInt(slider.dataset.autoplaySpeed, 10) || 6000;
		var pauseOnHover = slider.dataset.pauseHover === 'true';
		var infinite = slider.dataset.infinite === 'true';
		var timer = null;

		function goTo(index) {
			if (index < 0) {
				index = infinite ? total - 1 : 0;
			} else if (index >= total) {
				index = infinite ? 0 : total - 1;
			}

			slides[current].classList.remove('is-active');
			if (dots[current]) {
				dots[current].classList.remove('is-active');
				dots[current].setAttribute('aria-selected', 'false');
			}

			current = index;

			slides[current].classList.add('is-active');
			if (dots[current]) {
				dots[current].classList.add('is-active');
				dots[current].setAttribute('aria-selected', 'true');
			}
		}

		function next() {
			goTo(current + 1);
		}

		function prev() {
			goTo(current - 1);
		}

		function startAutoplay() {
			if (!autoplay || total <= 1) {
				return;
			}

			stopAutoplay();
			timer = window.setInterval(next, autoplaySpeed);
		}

		function stopAutoplay() {
			if (timer) {
				window.clearInterval(timer);
				timer = null;
			}
		}

		if (prevBtn) {
			prevBtn.addEventListener('click', function () {
				prev();
				startAutoplay();
			});
		}

		if (nextBtn) {
			nextBtn.addEventListener('click', function () {
				next();
				startAutoplay();
			});
		}

		dots.forEach(function (dot) {
			dot.addEventListener('click', function () {
				var index = parseInt(dot.dataset.slide, 10);
				if (!isNaN(index)) {
					goTo(index);
					startAutoplay();
				}
			});
		});

		if (pauseOnHover) {
			slider.addEventListener('mouseenter', stopAutoplay);
			slider.addEventListener('mouseleave', startAutoplay);
			slider.addEventListener('focusin', stopAutoplay);
			slider.addEventListener('focusout', startAutoplay);
		}

		startAutoplay();
	}

	function initHeroWidget(scope) {
		var root = scope || document;
		root.querySelectorAll('.auscorp-hero').forEach(function (hero) {
			initHeroVideos(hero);
		});
		root.querySelectorAll('.auscorp-hero__slider').forEach(initHeroSlider);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			initHeroWidget();
		});
	} else {
		initHeroWidget();
	}

	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		window.elementorFrontend.hooks.addAction(
			'frontend/element_ready/auscorp_hero_slider.default',
			function ($scope) {
				initHeroWidget($scope[0]);
			}
		);
	}
})();
