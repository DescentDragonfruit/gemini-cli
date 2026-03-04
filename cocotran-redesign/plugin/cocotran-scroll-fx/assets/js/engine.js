/**
 * Cocotran Scroll FX — Animation Engine
 *
 * Handles:
 *   1. Entrance animations  (fx-fade, fx-slide-*, fx-zoom, fx-bounce, etc.)
 *   2. Stagger children     (fx-stagger)
 *   3. Text effects         (fx-letters, fx-words)
 *   4. Counter animation    (fx-count)
 *   5. Sticky nav           (.csfx-nav-scrolled on body)
 *   6. Scroll progress bar  (.fx-progress-bar)
 *   7. 3D mouse tilt        (fx-hover-tilt)
 *
 * No libraries. Pure vanilla JS. ~5KB minified.
 */
(function () {
  'use strict';

  /* ── Motion preference ─────────────────────────────────── */
  var motionOK = window.matchMedia('(prefers-reduced-motion: no-preference)').matches;
  var isMobile = window.innerWidth < 768;

  /* ── 1. Entrance animations ──────────────────────────────
     Selectors that get the IntersectionObserver treatment.   */

  var ENTRANCE_SEL = [
    '.fx-fade',
    '.fx-slide-up', '.fx-slide-down', '.fx-slide-left', '.fx-slide-right',
    '.fx-zoom',     '.fx-zoom-out',
    '.fx-bounce',
    '.fx-glide',
    '.fx-flip-x',   '.fx-flip-y',
    '.fx-spin',
    '.fx-unfold',
    '.fx-blur',
    '.fx-clip-left','.fx-clip-right','.fx-clip-up',
  ].join(',');

  function initEntrance() {
    var els = document.querySelectorAll(ENTRANCE_SEL);
    if (!els.length) return;

    /* On mobile without .fx-mobile: elements are already shown via CSS.
       Still observe them so .is-visible is set (for CSS hooks), but
       fire immediately.                                                */
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target); /* default: animate once */

        /* .fx-repeat: re-hide and re-observe after it leaves */
        if (entry.target.classList.contains('fx-repeat')) {
          reObserve(observer, entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    els.forEach(function (el) { observer.observe(el); });
  }

  function reObserve(observer, el) {
    var leaveObs = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) return;
        el.classList.remove('is-visible');
        leaveObs.unobserve(el);
        /* small delay so CSS reset takes before re-observing */
        setTimeout(function () { observer.observe(el); }, 50);
      });
    }, { threshold: 0 });
    leaveObs.observe(el);
  }

  /* ── 2. Stagger children ─────────────────────────────────  */

  function initStagger() {
    var parents = document.querySelectorAll('.fx-stagger');
    if (!parents.length) return;

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        var children = Array.from(entry.target.children);
        children.forEach(function (child, i) {
          child.style.transitionDelay = (i * 110) + 'ms';
        });
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.08 });

    parents.forEach(function (el) { observer.observe(el); });
  }

  /* ── 3. Text effects ─────────────────────────────────────
     fx-letters: each character animates in separately.
     fx-words:   each word animates in separately.            */

  function splitText(el, unit) {
    /* unit: 'char' | 'word' */
    var text   = el.textContent;
    var parts  = unit === 'char' ? text.split('') : text.split(' ');
    el.textContent = '';
    el.setAttribute('aria-label', text); /* preserve screen-reader text */

    parts.forEach(function (part, i) {
      var span = document.createElement('span');
      span.className = unit === 'char' ? 'letter' : 'word';
      span.textContent = part;
      span.style.transitionDelay = (i * 35) + 'ms'; /* 35ms per char */
      if (unit === 'word' && i < parts.length - 1) {
        span.textContent += '\u00A0'; /* non-breaking space between words */
      }
      el.appendChild(span);
    });
  }

  function initText() {
    var letterEls = document.querySelectorAll('.fx-letters');
    var wordEls   = document.querySelectorAll('.fx-words');

    letterEls.forEach(function (el) { splitText(el, 'char'); });
    wordEls.forEach(function (el)   { splitText(el, 'word'); });

    var all = document.querySelectorAll('.fx-letters, .fx-words');
    if (!all.length) return;

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-visible');
        /* Add visible class to each child span */
        Array.from(entry.target.children).forEach(function (span) {
          span.style.opacity   = '1';
          span.style.transform = 'none';
        });
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.5 });

    all.forEach(function (el) { observer.observe(el); });
  }

  /* ── 4. Counter animation ────────────────────────────────
     Usage: <span class="fx-count" data-target="2500">0</span>
     Counts up from 0 to data-target when in view.            */

  function animateCount(el) {
    var target   = parseFloat(el.dataset.target || el.textContent.replace(/[^0-9.]/g, ''));
    var decimals = (target % 1 !== 0) ? String(target).split('.')[1].length : 0;
    var prefix   = el.dataset.prefix || '';
    var suffix   = el.dataset.suffix || '';
    var duration = parseInt(el.dataset.duration || '2000', 10);
    var start    = performance.now();

    function step(now) {
      var progress = Math.min((now - start) / duration, 1);
      /* ease-out quad */
      var eased    = 1 - (1 - progress) * (1 - progress);
      var current  = (eased * target).toFixed(decimals);
      el.textContent = prefix + current.toLocaleString() + suffix;
      if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }

  function initCounters() {
    var counters = document.querySelectorAll('.fx-count');
    if (!counters.length) return;

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        animateCount(entry.target);
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.5 });

    counters.forEach(function (el) { observer.observe(el); });
  }

  /* ── 5. Sticky nav ───────────────────────────────────────  */

  function initNav() {
    window.addEventListener('scroll', function () {
      document.body.classList.toggle('csfx-nav-scrolled', window.scrollY > 60);
    }, { passive: true });
  }

  /* ── 6. Scroll progress bar ──────────────────────────────
     Inserts a thin bar at top of page that fills 0→100%.    */

  function initProgressBar() {
    var bar = document.querySelector('.fx-progress-bar');
    if (!bar) {
      /* Auto-insert if user added fx-progress-bar class to any element */
      var placeholder = document.querySelector('[class*="fx-progress-bar"]');
      if (!placeholder) return;
      bar = document.createElement('div');
      bar.className = 'fx-progress-bar';
      document.body.prepend(bar);
    }

    var ticking = false;
    window.addEventListener('scroll', function () {
      if (!ticking) {
        requestAnimationFrame(function () {
          var scrollTop  = window.scrollY;
          var docHeight  = document.documentElement.scrollHeight - window.innerHeight;
          var pct        = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
          bar.style.width = pct.toFixed(2) + '%';
          ticking = false;
        });
        ticking = true;
      }
    }, { passive: true });
  }

  /* ── 7. 3D Tilt on hover ─────────────────────────────────
     Mouse position → subtle perspective rotation.
     Only runs on non-touch devices.                          */

  function initTilt() {
    if (isMobile || !window.matchMedia('(hover: hover)').matches) return;

    var tilts = document.querySelectorAll('.fx-hover-tilt');
    if (!tilts.length) return;

    tilts.forEach(function (el) {
      el.addEventListener('mousemove', function (e) {
        var rect    = el.getBoundingClientRect();
        var cx      = rect.left + rect.width  / 2;
        var cy      = rect.top  + rect.height / 2;
        var dx      = (e.clientX - cx) / (rect.width  / 2);  /* -1 to 1 */
        var dy      = (e.clientY - cy) / (rect.height / 2);  /* -1 to 1 */
        var rotX    = (-dy * 8).toFixed(2);  /* max 8deg */
        var rotY    = ( dx * 8).toFixed(2);
        el.style.transform = 'perspective(800px) rotateX(' + rotX + 'deg) rotateY(' + rotY + 'deg)';
      });

      el.addEventListener('mouseleave', function () {
        el.style.transition = 'transform 400ms ' + getComputedStyle(document.documentElement).getPropertyValue('--ease-out');
        el.style.transform  = 'none';
      });

      el.addEventListener('mouseenter', function () {
        el.style.transition = 'transform 100ms linear';
      });
    });
  }

  /* ── Init ────────────────────────────────────────────────  */

  function init() {
    if (motionOK) {
      initEntrance();
      initStagger();
      if (!isMobile) {
        initText();   /* text effects disabled on mobile for performance */
      }
    } else {
      /* Reduced motion: instantly show everything */
      document.querySelectorAll(ENTRANCE_SEL + ', .fx-stagger > *').forEach(function (el) {
        el.classList.add('is-visible');
        el.style.opacity = '1';
        el.style.transform = 'none';
      });
    }

    initCounters();   /* counters always run (no motion, just numbers) */
    initNav();
    initProgressBar();
    if (motionOK) initTilt();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  /* ── Resize handler ──────────────────────────────────────  */
  window.addEventListener('resize', function () {
    isMobile = window.innerWidth < 768;
  });

})();
