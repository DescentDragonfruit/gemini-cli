# Skill: /perf-audit

Audit CSS/JS for scroll jank and performance issues before they go live.

## How to use

> /perf-audit — [paste animations.css]
> /perf-audit — [paste engine.js or parallax.js]
> /perf-audit — full audit

## What gets checked

CSS:
- No animation on width/height/top/left/margin (causes layout reflow)
- All animations use only transform and opacity
- No transition: all
- will-change only on actively animating elements

JS:
- All scroll listeners use { passive: true }
- IntersectionObserver used instead of scroll events for visibility
- rAF used for scroll-linked style updates
- DOM queries cached — not called inside rAF/scroll callbacks
- IntersectionObserver disconnected after one-time animations fire

Output: CRITICAL / WARNINGS / PASSING with before/after code fixes.
