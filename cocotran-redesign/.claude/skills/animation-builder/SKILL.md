# Skill: /animation-builder

Generate production-ready CSS + JS for any animation effect.
Covers effects not in the built-in fx-* class library,
or custom variations of existing effects.

## How to use

Describe what you want:
> /animation-builder — hero heading: each word slides up with a slight rotation, staggered

Share a URL with the effect you like:
> /animation-builder — I like the text reveal on https://example.com — build it for my hero

Request a specific advanced effect:
> /animation-builder — horizontal scroll section: content scrolls sideways while page scrolls down
> /animation-builder — SVG path draws itself as you scroll to it
> /animation-builder — numbers count up with a slot-machine rolling effect
> /animation-builder — Ken Burns: hero image slowly zooms + pans

## What you get

**CSS** — paste into `assets/css/animations.css`
- Initial (hidden) state
- Animated (visible) state
- @keyframes if needed
- prefers-reduced-motion block
- Mobile behavior

**JS** — paste into `assets/js/engine.js`
- Function wrapped in IIFE
- IntersectionObserver or rAF as appropriate
- Passive scroll listeners
- Call added inside the main init() function

**Step-by-step instructions** for applying it to a Kadence block.

## Rules Claude follows
- Only `transform` and `opacity` in animations — never width/height/top/left
- Always `{ passive: true }` on scroll listeners
- Always a prefers-reduced-motion fallback
- Mobile: disabled by default unless fx-mobile is added
