/**
 * Cocotran Scroll FX — Parallax + Scroll-Linked Effects
 *
 * Handles:
 *   1. Depth parallax  (fx-parallax-slow, -mid, -fast)
 *   2. Zoom on scroll  (fx-zoom-scroll)
 *   3. Rotate on scroll (fx-rotate-scroll)
 *
 * All effects use requestAnimationFrame for smooth 60fps.
 * Disabled on mobile and prefers-reduced-motion.
 */
(function () {
  'use strict';

  var motionOK = window.matchMedia('(prefers-reduced-motion: no-preference)').matches;
  var isMobile = window.innerWidth < 768;

  if (!motionOK || isMobile) return;

  /* ── Collect elements ────────────────────────────────────── */

  var parallaxMap = [
    { sel: '.fx-parallax-slow', speed:  0.20 },
    { sel: '.fx-parallax-mid',  speed:  0.40 },
    { sel: '.fx-parallax-fast', speed:  0.65 },
  ];

  var parallaxEls = [];
  parallaxMap.forEach(function (p) {
    document.querySelectorAll(p.sel).forEach(function (el) {
      parallaxEls.push({ el: el, speed: p.speed });
    });
  });

  var zoomScrollEls   = document.querySelectorAll('.fx-zoom-scroll');
  var rotateScrollEls = document.querySelectorAll('.fx-rotate-scroll');

  var hasWork = parallaxEls.length || zoomScrollEls.length || rotateScrollEls.length;
  if (!hasWork) return;

  /* ── Scroll loop ─────────────────────────────────────────── */

  var ticking = false;

  function update() {
    var scrollY = window.scrollY;
    var vh      = window.innerHeight;

    /* Depth parallax */
    parallaxEls.forEach(function (item) {
      var rect   = item.el.getBoundingClientRect();
      /* Center-relative offset: 0 when element center is at viewport center */
      var center = rect.top + rect.height / 2;
      var delta  = (vh / 2 - center) * item.speed;
      item.el.style.transform = 'translateY(' + delta.toFixed(1) + 'px)';
    });

    /* Zoom on scroll: scale from 0.9 (top of viewport) to 1.05 (bottom) */
    zoomScrollEls.forEach(function (el) {
      var rect     = el.getBoundingClientRect();
      var progress = 1 - (rect.top / vh);                 /* 0 → 1 as element scrolls up */
      var clamped  = Math.max(0, Math.min(1, progress));
      var scale    = (0.90 + clamped * 0.15).toFixed(3);  /* 0.90 → 1.05 */
      el.style.transform = 'scale(' + scale + ')';
    });

    /* Rotate on scroll: rotate up to 15deg based on scroll position */
    rotateScrollEls.forEach(function (el) {
      var rect     = el.getBoundingClientRect();
      var progress = 1 - (rect.top / vh);
      var clamped  = Math.max(0, Math.min(1, progress));
      var deg      = ((clamped - 0.5) * 30).toFixed(2);   /* -15deg → +15deg */
      el.style.transform = 'rotate(' + deg + 'deg)';
    });

    ticking = false;
  }

  window.addEventListener('scroll', function () {
    if (!ticking) {
      requestAnimationFrame(update);
      ticking = true;
    }
  }, { passive: true });

  /* Recalculate on resize */
  window.addEventListener('resize', function () {
    isMobile = window.innerWidth < 768;
    if (isMobile) {
      /* Reset all transforms */
      parallaxEls.forEach(function (i) { i.el.style.transform = ''; });
      zoomScrollEls.forEach(function (el) { el.style.transform = ''; });
      rotateScrollEls.forEach(function (el) { el.style.transform = ''; });
    }
  });

  /* Initial render */
  update();

})();
