# Skill: perf-audit

## Purpose
Audit your CSS and JavaScript animation files for performance issues. Catches
scroll jank, layout-triggering properties, unoptimized listeners, and oversized
files before they reach your live site.

## When to Use
- Before any page goes live
- When a page feels sluggish or janky during scroll
- After adding new animations or parallax effects
- When Chrome DevTools Performance panel shows long frames

## How to Invoke

**Full audit — paste your files:**
> "Perf audit my animation CSS and JS: [paste 03-animations.css + scroll-observer.js]"

**Specific check:**
> "Is my parallax.js causing scroll jank? [paste file]"

**Quick layout-trigger check:**
> "Check this CSS for layout-triggering properties: [paste CSS]"

---

## Audit Checklist

### CSS Animations — Performance Rules
```
[ ] No animation uses width, height, top, left, right, bottom, margin, padding
    (these trigger layout reflow — very expensive)
[ ] All animations use only transform and/or opacity
    (these trigger composite only — GPU-accelerated, free)
[ ] will-change: transform is set on elements before they animate
    (tells browser to create a GPU layer in advance)
[ ] will-change is removed after animation completes
    (leaving it on static elements wastes GPU memory)
[ ] No transition on all properties (transition: all)
    (this is a performance trap — always specify exact properties)
[ ] Large background images use contain or cover without transform: scale()
[ ] No clip-path animation on very large elements (can be expensive)
[ ] @keyframe animations that are for one-time effects have animation-fill-mode: forwards
    (prevents re-triggering on re-paint)
```

### JavaScript — Performance Rules
```
[ ] Scroll event listeners use { passive: true }
    (without this, Chrome waits to see if you call preventDefault() — causes jank)
[ ] IntersectionObserver is used instead of scroll events for visibility detection
[ ] IntersectionObserver is disconnected after all targets have animated
    (avoids memory leaks on long pages)
[ ] requestAnimationFrame (rAF) is used for scroll-linked animations
    (never directly update styles inside a scroll event handler)
[ ] DOM queries (querySelector, querySelectorAll) are cached in variables
    (not called inside loops or scroll handlers)
[ ] Event listeners are removed when no longer needed
[ ] No layout-read/write interleaving in loops (causes layout thrashing)
[ ] Parallax script uses rAF + throttle pattern for smooth 60fps
```

### File Size
```
[ ] Total CSS file size < 50KB (unminified) — aim for < 15KB minified + gzipped
[ ] Total JS file size < 30KB (unminified) — aim for < 10KB minified + gzipped
[ ] No duplicate CSS rules across files
[ ] No unused animation classes (classes defined but never used in HTML)
```

---

## Output Format

```
PERFORMANCE AUDIT REPORT
Date: [date]
Files checked: [list]

CRITICAL (will cause visible jank):
  FAIL ✗ [item] — [description]
       Before: [problematic code]
       After:  [fixed code]

WARNINGS (potential issues):
  WARN ⚠ [item] — [description]
       Before: [problematic code]
       After:  [fixed code]

PASSING:
  PASS ✓ [item]
  ...

ESTIMATED IMPACT:
  CSS size: [X]KB → minified: [X]KB → gzipped: [X]KB
  JS size:  [X]KB → minified: [X]KB → gzipped: [X]KB
```

---

## Common Fixes

### Fix: Replace layout-triggering properties
```css
/* BAD — triggers layout reflow every frame */
.animated { animation: slideIn 0.5s; }
@keyframes slideIn {
  from { top: -50px; }      /* layout */
  to   { top: 0; }          /* layout */
}

/* GOOD — GPU-composited, zero layout cost */
.animated { animation: slideIn 0.5s; }
@keyframes slideIn {
  from { transform: translateY(-50px); }   /* composite */
  to   { transform: translateY(0); }       /* composite */
}
```

### Fix: Add passive scroll listener
```js
/* BAD */
window.addEventListener('scroll', handler);

/* GOOD */
window.addEventListener('scroll', handler, { passive: true });
```

### Fix: Cache DOM queries
```js
/* BAD — queries DOM on every scroll event */
window.addEventListener('scroll', function() {
  document.querySelectorAll('.parallax-slow').forEach(el => { /* ... */ });
}, { passive: true });

/* GOOD — query once, reuse */
const parallaxEls = document.querySelectorAll('.parallax-slow');
window.addEventListener('scroll', function() {
  parallaxEls.forEach(el => { /* ... */ });
}, { passive: true });
```

### Fix: Use rAF for scroll-linked animation
```js
/* BAD — style reads/writes inside scroll handler */
window.addEventListener('scroll', function() {
  el.style.transform = `translateY(${window.scrollY * 0.3}px)`; // forced reflow
}, { passive: true });

/* GOOD — batch style writes in rAF */
let ticking = false;
let scrollY = 0;

window.addEventListener('scroll', function() {
  scrollY = window.scrollY;
  if (!ticking) {
    requestAnimationFrame(function() {
      el.style.transform = `translateY(${scrollY * 0.3}px)`;
      ticking = false;
    });
    ticking = true;
  }
}, { passive: true });
```

### Fix: Disconnect IntersectionObserver after use
```js
const observer = new IntersectionObserver(function(entries) {
  entries.forEach(function(entry) {
    if (entry.isIntersecting) {
      entry.target.classList.add('is-visible');
      observer.unobserve(entry.target); // stop watching after first fire
    }
  });
}, { threshold: 0.15 });

document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
```

---

## Skill Notes
- Use Chrome DevTools → Performance → Record scroll to identify jank (red frames)
- Use Chrome DevTools → Rendering → Paint flashing to see what's being repainted
- Use `transform: translateZ(0)` as a last resort to force GPU layer creation
- The `will-change` property promotes an element to its own GPU layer — use sparingly
- Target 60fps (16ms per frame) — any animation task taking > 16ms will drop frames
