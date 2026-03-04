/**
 * Page Transitions — Cocotran Child Theme
 *
 * Adds a smooth fade transition between WordPress pages.
 *
 * How it works:
 *   1. On page load, body gets .page-entering class → fades in
 *   2. When a link is clicked, body gets .page-leaving class → fades out
 *   3. After fade-out completes, browser navigates to new page
 *   4. New page fades in via step 1
 *
 * CSS handles the actual fade (03-animations.css .page-leaving / .page-entering).
 * Disabled on mobile and prefers-reduced-motion.
 */
(function () {
  'use strict';

  // ── Guards ─────────────────────────────────────────────────────────────
  var motionOK = window.matchMedia('(prefers-reduced-motion: no-preference)').matches;
  var isMobile = window.innerWidth < 768;

  if (!motionOK || isMobile) return;

  // ── Transition duration (must match CSS --duration-base) ───────────────
  var DURATION = 350; // ms — match var(--duration-base)

  // ── Page enter animation ───────────────────────────────────────────────
  document.body.classList.add('page-entering');

  window.addEventListener('load', function () {
    // Remove the entering class after animation completes
    setTimeout(function () {
      document.body.classList.remove('page-entering');
    }, DURATION);
  });

  // ── Page leave animation on link click ────────────────────────────────
  document.addEventListener('click', function (e) {
    var link = e.target.closest('a');

    if (!link) return;

    // Skip: external links, same-page anchors, admin links, non-http links
    var href = link.getAttribute('href');
    if (!href) return;
    if (href.startsWith('#')) return;
    if (href.startsWith('mailto:') || href.startsWith('tel:')) return;
    if (link.hostname !== window.location.hostname) return;
    if (href.includes('/wp-admin/') || href.includes('/wp-login.php')) return;
    if (link.target === '_blank') return;
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return; // modifier keys = open in new tab

    // Prevent default navigation
    e.preventDefault();

    // Add leaving class to trigger fade-out CSS
    document.body.classList.add('page-leaving');

    // Navigate after fade-out completes
    setTimeout(function () {
      window.location.href = href;
    }, DURATION);
  });

  // ── Back/forward button ────────────────────────────────────────────────
  window.addEventListener('popstate', function () {
    document.body.classList.add('page-entering');
  });

})();
