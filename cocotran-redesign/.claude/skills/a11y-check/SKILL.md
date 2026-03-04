# Skill: /a11y-check

Audit animation CSS and JS for accessibility. PASS/FAIL checklist with exact fixes.

## How to use

> /a11y-check — [paste animations.css]
> /a11y-check — [paste engine.js]
> /a11y-check — full audit of all child theme files

## What gets checked

- prefers-reduced-motion: reduce block covers ALL animations
- Hidden elements are immediately visible in reduced-motion mode
- No infinite animations without a pause/stop mechanism
- No rapid flashing (>3/sec) — seizure risk
- Parallax disabled in reduced-motion mode
- Hover animations also fire on :focus-visible (keyboard users)
- Focus ring visible, meets 3:1 contrast
- Body text contrast ≥ 4.5:1, large text ≥ 3:1
- Heading hierarchy not skipped (H1 → H2 → H3)
- Images have alt text, buttons have labels

Output: CRITICAL / WARNINGS / PASSING with exact code fixes.
