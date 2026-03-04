/**
 * Parallax — Cocotran Child Theme
 *
 * Creates a depth-layer parallax effect on elements with:
 *   .parallax-slow   — 20% scroll speed (subtle)
 *   .parallax-medium — 40% scroll speed (clear)
 *   .parallax-fast   — 60% scroll speed (dramatic)
 *
 * Uses requestAnimationFrame for smooth 60fps.
 * Disabled on mobile (< 768px) and prefers-reduced-motion.
 */
(function () {
  'use strict';

  // ── Guards ─────────────────────────────────────────────────────────────
  var motionOK = window.matchMedia('(prefers-reduced-motion: no-preference)').matches;
  var isMobile = window.innerWidth < 768;

  if (!motionOK || isMobile) return;

  // ── Config ─────────────────────────────────────────────────────────────
  var SPEEDS = {
    'parallax-slow':   0.20,
    'parallax-medium': 0.40,
    'parallax-fast':   0.60,
  };

  // ── Collect all parallax elements ──────────────────────────────────────
  var elements = [];

  Object.keys(SPEEDS).forEach(function (cls) {
    document.querySelectorAll('.' + cls).forEach(function (el) {
      elements.push({ el: el, speed: SPEEDS[cls] });
    });
  });

  if (!elements.length) return;

  // ── Animation loop ─────────────────────────────────────────────────────
  var lastScrollY = window.scrollY;
  var ticking = false;

  function updateParallax() {
    elements.forEach(function (item) {
      var rect   = item.el.getBoundingClientRect();
      var center = rect.top + rect.height / 2;
      var offset = (window.innerHeight / 2 - center) * item.speed;

      item.el.style.transform = 'translateY(' + offset.toFixed(2) + 'px)';
    });
    ticking = false;
  }

  window.addEventListener(
    'scroll',
    function () {
      lastScrollY = window.scrollY;
      if (!ticking) {
        requestAnimationFrame(updateParallax);
        ticking = true;
      }
    },
    { passive: true }
  );

  // ── Recalculate on resize + orientation change ─────────────────────────
  window.addEventListener('resize', function () {
    isMobile = window.innerWidth < 768;
    if (isMobile) {
      // Reset transforms on resize to mobile
      elements.forEach(function (item) {
        item.el.style.transform = '';
      });
    }
  });

  // Initial calculation on load
  updateParallax();

})();
