/**
 * Cocotran Scroll FX — Page Transitions
 *
 * Smooth fade-and-slide transition between WordPress pages.
 * Disabled on mobile and prefers-reduced-motion.
 */
(function () {
  'use strict';

  var motionOK = window.matchMedia('(prefers-reduced-motion: no-preference)').matches;
  var isMobile = window.innerWidth < 768;

  if (!motionOK || isMobile) return;

  var DUR = 320; /* ms — match --dur-fast in tokens.css */

  /* Page enter */
  document.body.classList.add('csfx-entering');
  window.addEventListener('load', function () {
    setTimeout(function () {
      document.body.classList.remove('csfx-entering');
    }, DUR);
  });

  /* Page leave on link click */
  document.addEventListener('click', function (e) {
    var link = e.target.closest('a');
    if (!link) return;

    var href = link.getAttribute('href');
    if (!href || href.charAt(0) === '#') return;
    if (link.hostname !== window.location.hostname) return;
    if (/\/(wp-admin|wp-login)/.test(href)) return;
    if (link.target === '_blank') return;
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

    e.preventDefault();
    document.body.classList.add('csfx-leaving');
    setTimeout(function () { window.location.href = href; }, DUR);
  });

  window.addEventListener('popstate', function () {
    document.body.classList.add('csfx-entering');
  });

})();
