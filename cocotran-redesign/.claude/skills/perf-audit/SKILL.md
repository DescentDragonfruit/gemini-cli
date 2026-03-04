# Skill: /perf-audit

Audit CSS/JS for scroll jank, layout triggers, and performance anti-patterns.

## How to invoke

```
/perf-audit [paste scroll-fx.css]
/perf-audit [paste engine.js or parallax.js]
/perf-audit full  ← audit all plugin files
```

## Checklist

**CSS:**
- [ ] No animation on `width/height/top/left/margin/padding` (layout reflow)
- [ ] All animations use only `transform` and `opacity`
- [ ] No `transition: all` (slows down everything)
- [ ] `will-change` only on actively-animating elements, removed after
- [ ] File size < 15KB minified + gzipped

**JS:**
- [ ] All scroll listeners have `{ passive: true }`
- [ ] IntersectionObserver used (not scroll events) for visibility
- [ ] rAF used for scroll-linked style updates (parallax, zoom-scroll)
- [ ] DOM queries cached — never inside scroll/rAF callbacks
- [ ] IntersectionObserver disconnected after one-time animations
- [ ] No forced layout reads inside rAF write phase

## Output

```
PERF AUDIT
CRITICAL (will jank): FAIL ✗ [item]
                      Before: [code]   After: [fixed code]
WARNINGS:             WARN ⚠ [item]
PASSING:              PASS ✓ [item]
Est. CSS: XKB → minified XKB → gzipped XKB
```
