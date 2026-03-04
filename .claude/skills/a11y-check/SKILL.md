# Skill: a11y-check

## Purpose
Audit your animation CSS and JavaScript for accessibility compliance. Ensures
every animated element is usable by people with motion sensitivity, low vision,
or who rely on keyboard navigation. Outputs a pass/fail checklist with exact fixes.

## When to Use
- Before any page goes live
- After adding a new animation effect
- When a client or user reports that animations are causing issues
- As a final QA step before launch

## How to Invoke

**Full audit — paste your CSS:**
> "Run an a11y check on this CSS file: [paste contents of 03-animations.css]"

**Check a specific animation:**
> "Is this scroll reveal animation accessible? [paste CSS block]"

**Quick motion check:**
> "Does my animations.css respect prefers-reduced-motion for all animations?"

**Full audit including JS:**
> "Audit both my animations CSS and scroll-observer.js for accessibility"

---

## Audit Checklist

Claude checks every item and outputs PASS / FAIL / WARNING for each:

### 1. Motion & Animation
```
[ ] prefers-reduced-motion: reduce block exists
[ ] All @keyframe animations are disabled inside reduced-motion block
[ ] All CSS transitions are disabled inside reduced-motion block
[ ] No animation plays on page load without user trigger (autoplay)
[ ] No animation loops infinitely without a pause/stop mechanism
[ ] No rapid flashing (> 3 flashes per second) — seizure risk
[ ] Parallax effects are disabled in reduced-motion mode
[ ] Page transitions are disabled in reduced-motion mode
```

### 2. Color & Contrast
```
[ ] All text meets WCAG AA: 4.5:1 contrast ratio (normal text)
[ ] All large text meets WCAG AA: 3:1 contrast ratio (18px+ or 14px bold+)
[ ] UI components (buttons, inputs, borders) meet 3:1 against background
[ ] Focus indicators are visible and meet 3:1 contrast ratio
[ ] Color is not the only means of conveying information
[ ] Animated color changes don't rely solely on hue (colorblind users)
```

### 3. Focus & Keyboard
```
[ ] All interactive elements are reachable by Tab key
[ ] Focus order follows visual reading order
[ ] Focus ring is not removed (no `outline: none` without a replacement)
[ ] Hover animations also trigger on :focus (not just :hover)
[ ] No keyboard traps in animated modals or drawers
[ ] Skip navigation link exists for keyboard users
```

### 4. Semantic HTML (Kadence Blocks)
```
[ ] Hero headings use H1 (only one H1 per page)
[ ] Heading hierarchy is not skipped (H1 → H2 → H3, no H1 → H4)
[ ] Images have descriptive alt text
[ ] Decorative images have empty alt=""
[ ] Buttons have accessible labels (not just icons)
[ ] Links have descriptive text (not just "click here")
```

### 5. Performance Impact on Accessibility
```
[ ] Animations use transform/opacity only (no layout-triggering properties)
[ ] IntersectionObserver is used instead of scroll events where possible
[ ] will-change is set only on elements that are actively animating
[ ] No FOUT (flash of unstyled text) from font loading
```

---

## Output Format

```
A11Y AUDIT REPORT
Date: [date]
Files checked: [list]

CRITICAL (fix before launch):
  FAIL ✗ [item] — [description] — Fix: [exact code]

WARNINGS (fix soon):
  WARN ⚠ [item] — [description] — Fix: [exact code]

PASSING:
  PASS ✓ [item]
  PASS ✓ [item]
  ...

SCORE: [X/Y] checks passing
```

---

## Common Fixes

### Fix: Add prefers-reduced-motion block
```css
/* Add at the bottom of 03-animations.css */
@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
    scroll-behavior: auto !important;
  }

  /* Re-show elements that start hidden for animation */
  .reveal,
  .reveal-left,
  .reveal-right,
  .reveal-scale {
    opacity: 1;
    transform: none;
  }

  .parallax-slow,
  .parallax-medium,
  .parallax-fast {
    transform: none !important;
  }
}
```

### Fix: Restore focus ring
```css
/* In 02-base.css */
:focus-visible {
  outline: 3px solid var(--color-accent);
  outline-offset: 3px;
  border-radius: 2px;
}
/* Remove only for mouse users, keep for keyboard */
:focus:not(:focus-visible) {
  outline: none;
}
```

### Fix: Hover animations also fire on focus
```css
/* Bad */
.hover-lift:hover { transform: translateY(-6px); }

/* Good */
.hover-lift:hover,
.hover-lift:focus-within { transform: translateY(-6px); }
```

---

## Skill Notes
- WCAG AA is the minimum standard — aim for it on all pages
- Use the WebAIM Contrast Checker to verify color contrast ratios
- The `prefers-reduced-motion` media query is supported in all modern browsers
- Users with vestibular disorders can experience nausea from parallax and scroll effects
- Test with a screen reader (VoiceOver on Mac, NVDA on Windows) at least once before launch
