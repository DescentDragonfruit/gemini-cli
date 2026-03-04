# Skill: /animation-builder

Generate production-ready CSS + JS for any custom animation effect not already
in the plugin's built-in `fx-*` class library.

## How to invoke

```
/animation-builder  ← describe what you saw and want
  "I want the hero text to split into characters and each character
   flies in from random directions with a slight rotation"

/animation-builder
  "Build a scroll-linked SVG path drawing effect for the decorative
   line under my section heading"

/animation-builder
  "Build a Ken Burns effect — the hero background image slowly zooms
   and pans while on screen"

/animation-builder
  "I saw a horizontal scroll section on [URL] — build that"
```

## What Claude outputs

Always structured as:

### CSS (paste into `plugin/cocotran-scroll-fx/assets/css/scroll-fx.css`)
```css
/* ANIMATION: [name] — [description] */
.fx-custom-[name] { /* initial state */ }
.fx-custom-[name].is-visible { /* animated state */ }
@keyframes [name] { }
@media (prefers-reduced-motion: reduce) { }
@media (max-width: 767px) { }
```

### JS (paste into `plugin/cocotran-scroll-fx/assets/js/engine.js`)
```js
/* ANIMATION: [name] */
function init[Name]() { }
```

### How to apply
```
1. Paste CSS into scroll-fx.css
2. Paste JS into engine.js (add call inside init() function)
3. Bump version in cocotran-scroll-fx.php
4. In WordPress: block → Advanced → CSS classes → fx-custom-[name]
```

## Advanced effect reference

| Effect | What to ask for |
|--------|----------------|
| SVG stroke draw | "Draw the SVG path on scroll" |
| Ken Burns | "Slow zoom + pan on hero background image" |
| Magnetic cursor | "Button is attracted to mouse cursor" |
| Text scramble | "Text randomizes then settles into final word" |
| Scroll-linked clip | "Reveal image with clip-path tied to scroll position" |
| Horizontal scroll | "Section scrolls horizontally while page scrolls vertically" |
| Morph shapes | "SVG blob shape morphs between two shapes" |
| Number ticker | "Stats counter with rolling number animation" |
| Image sequence | "Images swap in sequence as you scroll (like Apple TV)" |
| Split layout pin | "Left side sticks while right side scrolls through content" |

## Skill notes
- Always prefer CSS-only if the effect can be achieved without JS
- Always wrap JS in an IIFE and use `{ passive: true }` on scroll listeners
- Never use `top/left/width/height` in animations — always `transform/opacity/clip-path`
- Always test mobile behavior — note if the effect should be disabled on mobile
