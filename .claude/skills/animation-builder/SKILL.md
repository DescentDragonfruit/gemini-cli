# Skill: animation-builder

## Purpose
Generate production-ready CSS and vanilla JavaScript for any animation effect
compatible with your Kadence child theme. No libraries. No plugins. Just clean,
performant code you paste directly into your child theme files.

## When to Use
- You've approved an animation technique from `/design-analyzer` and want the code
- You want to add a specific effect to a Kadence block
- You need to create a new animation not yet in your `03-animations.css`

## How to Invoke

**Scroll reveal:**
> "Build a scroll-triggered fade-up reveal for .hero-title with 0.8s duration"

**Stagger children:**
> "Build a stagger animation for all .kb-block-info-box cards — each one delays by 100ms"

**Parallax:**
> "Build a parallax effect for .hero-background at 30% scroll speed"

**Page transition:**
> "Build a smooth fade page transition — fade out current page, fade in next page"

**Hover effect:**
> "Build a hover lift + glow effect for .wp-block-kadence-advancedbtn"

**Custom:**
> "I saw a text clip-path wipe animation on [URL] — build it for my hero heading"

---

## Output Format

Claude will always output code in this exact structure:

### CSS (paste into `assets/css/03-animations.css`)
```css
/* ============================================
   ANIMATION: [name]
   Effect: [plain English description]
   Usage: Add class "[class-name]" to any Kadence block
   Mobile: [disabled by default / enabled / mobile-specific variant]
   ============================================ */

/* Initial state */
.[class-name] { }

/* Animated state */
.[class-name].is-visible { }

/* Keyframes (if needed) */
@keyframes [animation-name] { }

/* Accessibility — reduced motion */
@media (prefers-reduced-motion: reduce) {
  .[class-name],
  .[class-name].is-visible { }
}

/* Mobile — disabled by default, add .animate-mobile to enable */
@media (max-width: 767px) {
  .[class-name]:not(.animate-mobile) { }
}
```

### JavaScript (paste into `assets/js/scroll-observer.js` or specified file)
```js
/* ============================================
   ANIMATION: [name]
   ============================================ */
(function() {
  // code here
})();
```

### How to Apply (step-by-step)
```
1. Paste the CSS block into assets/css/03-animations.css
2. Paste the JS block into assets/js/scroll-observer.js
3. In WordPress block editor: click your block → Advanced → Additional CSS class(es)
4. Add: [class-name]
5. Save and preview
```

---

## Animation Library

### Scroll-Triggered Reveals
| Class | Effect |
|-------|--------|
| `reveal` | Fade up (default — 40px Y offset) |
| `reveal-left` | Fade from left (60px X offset) |
| `reveal-right` | Fade from right (60px X offset) |
| `reveal-scale` | Fade + scale from 95% |
| `reveal-fast` | Reveal with 0.4s duration |
| `reveal-slow` | Reveal with 1.2s duration |
| `stagger-children` | Children animate in sequence (150ms stagger) |

### Parallax
| Class | Speed |
|-------|-------|
| `parallax-slow` | 20% scroll rate — subtle depth |
| `parallax-medium` | 40% scroll rate — clear depth |
| `parallax-fast` | 60% scroll rate — dramatic depth |

### Hover Micro-interactions (CSS only, no JS)
| Class | Effect |
|-------|--------|
| `hover-lift` | translateY(-6px) + shadow on hover |
| `hover-glow` | Colored glow via box-shadow |
| `hover-scale` | scale(1.03) on hover |
| `hover-underline` | Animated underline slides in |
| `hover-bg-slide` | Background color slides in from left |
| `hover-icon-nudge` | Nudges an icon child element right |

### Page Transitions
| Class | Effect |
|-------|--------|
| `page-fade` | Applied to body — cross-fade on navigation |
| `page-slide-up` | New page slides up from bottom |

---

## Skill Notes
- Always use `transform` and `opacity` for animations — never `width`, `height`, `top`, `left`
- Always use `will-change: transform, opacity` on elements that animate
- Always wrap JS in an IIFE to avoid polluting global scope
- Always add `passive: true` to any scroll event listeners
- Always `disconnect()` IntersectionObserver after all targets have fired (if animating once)
- Always include the `@media (prefers-reduced-motion: reduce)` block
- Test every animation on mobile viewport before finalizing
