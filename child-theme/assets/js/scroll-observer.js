/**
 * Scroll Observer — Cocotran Child Theme
 *
 * Handles:
 *   1. Scroll-triggered reveal animations (.reveal, .reveal-left, etc.)
 *   2. Stagger children animations (.stagger-children)
 *   3. Sticky nav scroll class (.header-scrolled on body)
 *
 * No libraries. Pure vanilla JS with IntersectionObserver.
 * Respects prefers-reduced-motion automatically.
 */
(function () {
  'use strict';

  // ── Respect prefers-reduced-motion ─────────────────────────────────────
  var motionOK = window.matchMedia('(prefers-reduced-motion: no-preference)').matches;

  // ── 1. Scroll Reveal ───────────────────────────────────────────────────

  var REVEAL_CLASSES = [
    '.reveal',
    '.reveal-left',
    '.reveal-right',
    '.reveal-scale',
    '.reveal-fast',
  ];

  function initReveal() {
    if (!motionOK) return; // skip if user prefers reduced motion

    var targets = document.querySelectorAll(REVEAL_CLASSES.join(', '));
    if (!targets.length) return;

    var observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target); // fire once only
          }
        });
      },
      {
        threshold: 0.12,       // trigger when 12% of element is visible
        rootMargin: '0px 0px -40px 0px', // trigger slightly before element enters viewport
      }
    );

    targets.forEach(function (el) {
      observer.observe(el);
    });
  }

  // ── 2. Stagger Children ────────────────────────────────────────────────

  function initStagger() {
    if (!motionOK) return;

    var parents = document.querySelectorAll('.stagger-children');
    if (!parents.length) return;

    var observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            var children = Array.from(entry.target.children);
            children.forEach(function (child, index) {
              child.style.transitionDelay = index * 120 + 'ms'; // 120ms stagger
            });
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.10 }
    );

    parents.forEach(function (el) {
      observer.observe(el);
    });
  }

  // ── 3. Sticky Nav — header-scrolled body class ────────────────────────

  function initStickyNav() {
    var SCROLL_THRESHOLD = 60; // px from top before nav changes

    window.addEventListener(
      'scroll',
      function () {
        document.body.classList.toggle('header-scrolled', window.scrollY > SCROLL_THRESHOLD);
      },
      { passive: true }
    );
  }

  // ── Init on DOM ready ──────────────────────────────────────────────────

  function init() {
    initReveal();
    initStagger();
    initStickyNav();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
