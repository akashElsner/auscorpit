(function () {
	'use strict';

	function initTestimonialsSlider(root) {
		if (!root || root.dataset.auscorpTestimonialsReady === 'true') {
			return;
		}

		var viewport = root.querySelector('.auscorp-testimonials__viewport');
		var track = root.querySelector('.auscorp-testimonials__track');
		var prevBtn = root.querySelector('.auscorp-testimonials__arrow--prev');
		var nextBtn = root.querySelector('.auscorp-testimonials__arrow--next');
		var dotsWrap = root.querySelector('.auscorp-testimonials__dots');

		if (!viewport || !track) {
			return;
		}

		var originalSlides = Array.from(track.querySelectorAll('.auscorp-testimonials__slide:not(.auscorp-testimonials__slide--clone)'));

		if (!originalSlides.length) {
			return;
		}

		var activeSlideIndex = parseInt(root.dataset.activeIndex, 10) || 0;
		var gap = parseInt(root.dataset.gap, 10) || 24;
		var autoplay = root.dataset.autoplay === 'true';
		var autoplaySpeed = parseInt(root.dataset.autoplaySpeed, 10) || 5000;
		var pauseOnHover = root.dataset.pauseHover === 'true';
		var infinite = root.dataset.infinite === 'true';
		var centerHighlight = root.dataset.centerHighlight !== 'false';
		var timer = null;
		var currentIndex = 0;
		var logicalActiveIndex = 0;
		var allSlides = originalSlides.slice();
		var cloneCount = 0;
		var isAnimating = false;

		function getSlidesPerView() {
			var width = window.innerWidth;

			if (width <= 767) {
				return parseInt(root.dataset.slidesMobile, 10) || 1;
			}

			if (width <= 1024) {
				return parseInt(root.dataset.slidesTablet, 10) || 1;
			}

			return parseInt(root.dataset.slidesDesktop, 10) || 3;
		}

		function getCenterOffset(slidesPerView) {
			return centerHighlight ? Math.floor((slidesPerView - 1) / 2) : 0;
		}

		function getRealSlideCount() {
			return originalSlides.length;
		}

		function canScroll(slidesPerView) {
			return getRealSlideCount() > slidesPerView;
		}

		function getMaxScrollIndex(slidesPerView) {
			return Math.max(0, getRealSlideCount() - slidesPerView);
		}

		function normalizeLogicalIndex(index) {
			var total = getRealSlideCount();

			if (!total) {
				return 0;
			}

			if (infinite) {
				return ((index % total) + total) % total;
			}

			return Math.max(0, Math.min(index, total - 1));
		}

		function getSlideRealIndex(slide) {
			if (!slide) {
				return 0;
			}

			if (slide.dataset.realIndex !== undefined) {
				return parseInt(slide.dataset.realIndex, 10) || 0;
			}

			return parseInt(slide.dataset.index, 10) || 0;
		}

		function removeClones() {
			track.querySelectorAll('.auscorp-testimonials__slide--clone').forEach(function (clone) {
				clone.remove();
			});
			allSlides = originalSlides.slice();
			cloneCount = 0;
		}

		function setupClones(slidesPerView) {
			removeClones();

			if (!infinite || getRealSlideCount() < 2 || !canScroll(slidesPerView)) {
				return;
			}

			var buffer = Math.min(getRealSlideCount(), slidesPerView);
			var firstOriginal = originalSlides[0];

			for (var i = getRealSlideCount() - buffer; i < getRealSlideCount(); i++) {
				var prependClone = originalSlides[i].cloneNode(true);
				prependClone.classList.add('auscorp-testimonials__slide--clone');
				prependClone.classList.remove('is-active');
				prependClone.dataset.realIndex = String(i);
				track.insertBefore(prependClone, firstOriginal);
			}

			for (var j = 0; j < buffer; j++) {
				var appendClone = originalSlides[j].cloneNode(true);
				appendClone.classList.add('auscorp-testimonials__slide--clone');
				appendClone.classList.remove('is-active');
				appendClone.dataset.realIndex = String(j);
				track.appendChild(appendClone);
			}

			cloneCount = buffer;
			allSlides = Array.from(track.querySelectorAll('.auscorp-testimonials__slide'));
		}

		function getSlideStep() {
			var slide = allSlides[0];
			if (!slide) {
				return 0;
			}

			return slide.getBoundingClientRect().width + gap;
		}

		function setTrackTransition(enabled) {
			track.style.transition = enabled ? 'transform 0.45s ease' : 'none';
		}

		function applyTransform() {
			var step = getSlideStep();
			track.style.transform = 'translate3d(-' + (currentIndex * step) + 'px, 0, 0)';
		}

		function updateActiveState(slidesPerView) {
			var centerOffset = getCenterOffset(slidesPerView);
			var centerSlide = allSlides[currentIndex + centerOffset];
			logicalActiveIndex = normalizeLogicalIndex(getSlideRealIndex(centerSlide));

			allSlides.forEach(function (slide) {
				var realIndex = getSlideRealIndex(slide);
				var isActive = centerHighlight && realIndex === logicalActiveIndex;
				slide.classList.toggle('is-active', isActive);
			});

			if (dotsWrap) {
				dotsWrap.querySelectorAll('.auscorp-testimonials__dot').forEach(function (dot, index) {
					var isActive = index === logicalActiveIndex;
					dot.classList.toggle('is-active', isActive);
					dot.setAttribute('aria-selected', isActive ? 'true' : 'false');
				});
			}
		}

		function updateNavState(slidesPerView) {
			var scrollable = canScroll(slidesPerView);
			var disableNav = !infinite && getRealSlideCount() <= 1;

			if (prevBtn) {
				prevBtn.disabled = disableNav || (!infinite && scrollable && currentIndex <= cloneCount);
			}

			if (nextBtn) {
				nextBtn.disabled = disableNav || (!infinite && scrollable && currentIndex >= cloneCount + getMaxScrollIndex(slidesPerView));
			}
		}

		function getScrollIndexForLogical(logicalIndex, slidesPerView) {
			var centerOffset = getCenterOffset(slidesPerView);
			var scrollIndex = logicalIndex - centerOffset;

			if (!infinite) {
				return Math.max(0, Math.min(scrollIndex, getMaxScrollIndex(slidesPerView)));
			}

			if (!canScroll(slidesPerView)) {
				return cloneCount;
			}

			return cloneCount + Math.max(0, Math.min(scrollIndex, getMaxScrollIndex(slidesPerView)));
		}

		function updateSlider(resetPosition) {
			var slidesPerView = getSlidesPerView();
			root.style.setProperty('--slides-per-view', slidesPerView);
			root.style.setProperty('--slide-gap', gap + 'px');

			setupClones(slidesPerView);

			if (resetPosition || root.dataset.auscorpTestimonialsReady !== 'true') {
				logicalActiveIndex = normalizeLogicalIndex(activeSlideIndex);
				currentIndex = getScrollIndexForLogical(logicalActiveIndex, slidesPerView);
			}

			setTrackTransition(false);
			applyTransform();
			updateActiveState(slidesPerView);
			updateNavState(slidesPerView);

			window.requestAnimationFrame(function () {
				setTrackTransition(true);
			});
		}

		function onTransitionEnd(event) {
			if (event.target !== track || !infinite || !canScroll(getSlidesPerView())) {
				return;
			}

			var slidesPerView = getSlidesPerView();
			var realCount = getRealSlideCount();
			var needsReset = false;

			if (currentIndex >= cloneCount + realCount) {
				currentIndex -= realCount;
				needsReset = true;
			} else if (currentIndex < cloneCount) {
				currentIndex += realCount;
				needsReset = true;
			}

			if (needsReset) {
				setTrackTransition(false);
				applyTransform();
				updateActiveState(slidesPerView);
				window.requestAnimationFrame(function () {
					setTrackTransition(true);
				});
			}

			isAnimating = false;
		}

		function goToLogical(index, animate) {
			var slidesPerView = getSlidesPerView();
			var nextLogical = normalizeLogicalIndex(index);
			var nextScroll = getScrollIndexForLogical(nextLogical, slidesPerView);
			var shouldAnimate = animate !== false && canScroll(slidesPerView);

			logicalActiveIndex = nextLogical;

			if (!canScroll(slidesPerView)) {
				currentIndex = cloneCount;
				setTrackTransition(false);
				applyTransform();
				updateActiveState(slidesPerView);
				updateNavState(slidesPerView);
				window.requestAnimationFrame(function () {
					setTrackTransition(true);
				});
				return;
			}

			if (!shouldAnimate) {
				currentIndex = nextScroll;
				setTrackTransition(false);
				applyTransform();
				updateActiveState(slidesPerView);
				updateNavState(slidesPerView);
				window.requestAnimationFrame(function () {
					setTrackTransition(true);
				});
				return;
			}

			isAnimating = true;
			currentIndex = nextScroll;
			setTrackTransition(true);
			applyTransform();
			updateActiveState(slidesPerView);
			updateNavState(slidesPerView);
		}

		function next() {
			if (isAnimating) {
				return;
			}

			var slidesPerView = getSlidesPerView();

			if (!canScroll(slidesPerView)) {
				goToLogical(logicalActiveIndex + 1, false);
				return;
			}

			if (infinite) {
				isAnimating = true;
				currentIndex += 1;
				setTrackTransition(true);
				applyTransform();
				updateActiveState(slidesPerView);
				updateNavState(slidesPerView);
				return;
			}

			goToLogical(logicalActiveIndex + 1, true);
		}

		function prev() {
			if (isAnimating) {
				return;
			}

			var slidesPerView = getSlidesPerView();

			if (!canScroll(slidesPerView)) {
				goToLogical(logicalActiveIndex - 1, false);
				return;
			}

			if (infinite) {
				isAnimating = true;
				currentIndex -= 1;
				setTrackTransition(true);
				applyTransform();
				updateActiveState(slidesPerView);
				updateNavState(slidesPerView);
				return;
			}

			goToLogical(logicalActiveIndex - 1, true);
		}

		function startAutoplay() {
			if (!autoplay || getRealSlideCount() <= 1) {
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

		track.addEventListener('transitionend', onTransitionEnd);

		track.addEventListener('click', function (event) {
			var slide = event.target.closest('.auscorp-testimonials__slide');
			if (!slide || !track.contains(slide)) {
				return;
			}

			goToLogical(getSlideRealIndex(slide), true);
			startAutoplay();
		});

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

		if (dotsWrap) {
			dotsWrap.querySelectorAll('.auscorp-testimonials__dot').forEach(function (dot) {
				dot.addEventListener('click', function () {
					var index = parseInt(dot.dataset.slide, 10);
					if (!isNaN(index)) {
						goToLogical(index, true);
						startAutoplay();
					}
				});
			});
		}

		if (pauseOnHover) {
			root.addEventListener('mouseenter', stopAutoplay);
			root.addEventListener('mouseleave', startAutoplay);
			root.addEventListener('focusin', stopAutoplay);
			root.addEventListener('focusout', startAutoplay);
		}

		var resizeTimer;
		window.addEventListener('resize', function () {
			window.clearTimeout(resizeTimer);
			resizeTimer = window.setTimeout(function () {
				updateSlider(true);
			}, 150);
		});

		updateSlider(true);
		startAutoplay();

		root.dataset.auscorpTestimonialsReady = 'true';
	}

	function initTestimonialsWidgets(scope) {
		var container = scope && scope.querySelectorAll ? scope : document;
		container.querySelectorAll('.auscorp-testimonials').forEach(function (slider) {
			if (slider.dataset.auscorpTestimonialsReady === 'true') {
				return;
			}
			initTestimonialsSlider(slider);
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			initTestimonialsWidgets();
		});
	} else {
		initTestimonialsWidgets();
	}

	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		window.elementorFrontend.hooks.addAction(
			'frontend/element_ready/auscorp_testimonials_slider.default',
			function ($scope) {
				initTestimonialsWidgets($scope[0]);
			}
		);
	}
})();
