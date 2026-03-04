# Skill: design-analyzer

## Purpose
Analyze a design inspiration URL or screenshot to extract design tokens, identify
animation techniques, and produce a structured plan for implementing the look in
your Kadence child theme.

## When to Use
- You found a website or UI you love and want to replicate the style
- You want Claude to identify what a specific animation technique is called and
  whether it can be implemented with CSS/JS
- You need a gap analysis between your current site and an inspiration reference
- You're starting a new page from scratch and need a block-by-block plan

## How to Invoke

**Share a URL:**
> "Analyze this design for inspiration: https://example.com"

**Share a screenshot:**
> Paste the screenshot image directly into the chat, then say:
> "Analyze this screenshot and extract the design tokens"

**Evaluate a specific animation:**
> "I like the animation on this page: https://example.com — what technique is it
>  and can we do it with CSS/JS only? Evaluate it before we build anything."

**Gap analysis vs. current site:**
> "Compare this inspiration [URL/screenshot] to cocotran.com and list the top 5
>  design gaps to close"

---

## What Claude Will Output

### 1. Design Token Extraction
```
COLOR PALETTE
  Primary:      #______  (usage: CTAs, headings, brand elements)
  Secondary:    #______  (usage: accents, borders, highlights)
  Background:   #______
  Surface:      #______  (cards, panels)
  Text:         #______
  Text muted:   #______

TYPOGRAPHY
  Heading font:   ______  (Google Fonts / system)
  Body font:      ______
  Heading sizes:  H1 ___px / H2 ___px / H3 ___px
  Body size:      ___px / line-height ___
  Weight scale:   _____ (light=300, regular=400, bold=700)
  Letter spacing: H1 ___em / body ___em

LAYOUT & SPACING
  Max content width:  ___px
  Section padding:    ___px vertical
  Column gutter:      ___px
  Card padding:       ___px
  Border radius:      ___px (cards) / ___px (buttons)

VISUAL STYLE
  Shadow style:  ______  (none / soft / hard / colored)
  Border style:  ______  (none / thin / thick / gradient)
  Image style:   ______  (full-bleed / rounded / masked)
  Button style:  ______  (filled / outlined / pill / square)
```

### 2. Animation Technique Identification
For each animation observed:
```
ANIMATION: [name]
  What it is:      [plain English description]
  Technique:       [CSS only / IntersectionObserver / scroll event listener / Canvas]
  Feasibility:     [Easy / Medium / Complex]
  Performance:     [Excellent / Good / Heavy — use sparingly]
  Implementation:  [build with /animation-builder? yes/no]
  Notes:           [any caveats or mobile considerations]
```

### 3. Kadence Block Build Plan
A section-by-section guide for recreating the layout:
```
SECTION 1 — Hero
  Block: Kadence Row Block (full-width)
  Settings:
    - Min height: 100vh
    - Background: [type + value]
    - Padding: 80px top/bottom
  Inner blocks:
    - Kadence Heading Block: H1, font-size clamp(2.5rem, 6vw, 5rem)
    - Kadence Advanced Button: CTA button, pill shape
  Animation class: reveal stagger-children

SECTION 2 — [name]
  ...
```

### 4. Gap Analysis (when comparing to current site)
```
GAP 1: [what's different] → [how to fix it]
GAP 2: ...
GAP 3: ...
```

---

## Skill Notes
- Always evaluate animation feasibility BEFORE generating implementation code
- Flag any animation that requires a library (GSAP, Three.js) — user prefers CSS/JS only
- If an effect cannot be replicated without a library, explain why and suggest the nearest CSS-only alternative
- Include mobile impact for every animation noted
