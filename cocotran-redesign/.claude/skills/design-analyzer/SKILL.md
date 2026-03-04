# Skill: /design-analyzer

Analyze a design inspiration URL or screenshot. Extract design tokens, identify
animation techniques, and produce a Kadence Blocks build plan.

## How to invoke

```
/design-analyzer https://example.com
/design-analyzer [paste screenshot]
/design-analyzer https://example.com  ← I like the hero scroll animation, what is it?
```

## What Claude outputs

### 1. Design Tokens
```
COLORS
  Primary:     #____  Secondary:  #____  Accent: #____
  Background:  #____  Surface:    #____  Text:   #____

TYPOGRAPHY
  Heading:  [font name]  [sizes]  [weights]  [letter-spacing]
  Body:     [font name]  [size]   [weight]   [line-height]

LAYOUT
  Max width: ____px   Section padding: ____px   Column gap: ____px
  Border radius: ____px   Shadow style: [none/soft/hard/colored]

VISUAL STYLE
  Button shape: [pill/rounded/square]   Border style: [none/thin/gradient]
  Image style:  [full-bleed/masked/rounded]
```

### 2. Animation Technique Identification
For each animation effect spotted:
```
EFFECT: [name]
  What it is:    [plain English — e.g. "clip-path wipe from left"]
  CSS class:     fx-clip-left  (or custom — see /animation-builder)
  Technique:     [CSS only / IntersectionObserver / scroll-linked rAF]
  Feasibility:   [Easy / Medium / Complex]
  Performance:   [Excellent / Good / Heavy]
  Mobile impact: [works / disabled by default / use fx-mobile to enable]
  Notes:         [caveats]
```

### 3. Kadence Blocks Build Plan
Section-by-section guide:
```
SECTION 1 — Hero
  Block: Kadence Row (full-width)
    Settings: min-height 100vh, background image + overlay
  Inner: Kadence Heading H1  → CSS classes: fx-slide-up
         Kadence Heading H3  → CSS classes: fx-fade fx-delay-2
         Kadence Button      → CSS classes: fx-fade fx-delay-3 fx-hover-lift
SECTION 2 — ...
```

### 4. Gap analysis vs. current site (when comparing)
```
GAP 1 (HIGH): [what's different] → [how to close it]
GAP 2 (MED): ...
GAP 3 (LOW): ...
```

## Skill notes
- Always evaluate animation feasibility BEFORE building
- Plugin supports 18+ entrance classes + scroll-linked effects — prefer native fx-* classes
- Flag any effect that requires a library (GSAP, Three.js) — always suggest CSS/JS alternative
