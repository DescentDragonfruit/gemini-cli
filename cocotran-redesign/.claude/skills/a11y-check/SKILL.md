# Skill: /a11y-check

Audit animation CSS + JS for accessibility. Outputs a PASS/FAIL checklist with exact fixes.

## How to invoke

```
/a11y-check [paste scroll-fx.css]
/a11y-check [paste engine.js]
/a11y-check full  ← audit all plugin files
```

## Checklist

- [ ] `prefers-reduced-motion: reduce` block covers ALL animations
- [ ] All elements hidden for animation start visible immediately in reduced-motion
- [ ] No infinite animations without pause mechanism
- [ ] No rapid flashing (> 3/sec) — seizure risk
- [ ] Parallax disabled in reduced-motion
- [ ] All hover effects also trigger on `:focus-visible`
- [ ] Focus ring visible and meets 3:1 contrast ratio
- [ ] Color contrast: body text ≥ 4.5:1, large text ≥ 3:1
- [ ] Heading hierarchy is correct (H1 → H2 → H3, no skips)
- [ ] Images have descriptive `alt` text
- [ ] Buttons have accessible labels
- [ ] No `outline: none` without a replacement

## Output

```
A11Y AUDIT
CRITICAL (fix before launch): FAIL ✗ [item] — Fix: [exact code]
WARNINGS:                     WARN ⚠ [item] — Fix: [exact code]
PASSING:                      PASS ✓ [item]
SCORE: X/Y
```
