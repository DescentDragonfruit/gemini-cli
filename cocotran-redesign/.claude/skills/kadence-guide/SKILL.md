# Skill: /kadence-guide

Step-by-step Kadence Blocks build instructions for any page section.
Claude tells you exactly which blocks to add and every setting to configure.

## How to use

> /kadence-guide — hero: full viewport, dark overlay, heading slides up, two CTA buttons
> /kadence-guide — 3-column service cards with icon, heading, text, hover lift
> /kadence-guide — testimonials with avatar, quote, name, company
> /kadence-guide — stats row: 4 numbers that count up on scroll
> /kadence-guide — [paste screenshot or URL] recreate this section

## What you get

Numbered steps, one block at a time, with every setting specified:

```
STEP 1 — Add a Kadence Row Block
  Layout tab:   Columns: 1
  Style tab:    Background: Image → upload your image
                Overlay color: #000  Opacity: 50%
  Advanced tab: Min height: 100vh, Vertical align: Center

STEP 2 — Inside the Row: Kadence Column Block
  Style tab: Padding 80px top/bottom, 40px left/right

STEP 3 — Inside the Column: Kadence Advanced Heading
  Tag: H1, size: clamp(2.5rem, 6vw, 5rem), weight: 700, color: #FFF
  Advanced → Additional CSS class(es): fx-slide-up

STEP 4 — Kadence Advanced Heading (subtitle)
  Tag: P, size: 1.25rem, color: rgba(255,255,255,0.85)
  Additional CSS class(es): fx-fade fx-delay-2

STEP 5 — Kadence Advanced Button
  Text: Get Started, link: /contact/
  Style: Filled, border-radius: 9999px
  Additional CSS class(es): fx-fade fx-delay-3 fx-hover-lift
```

Includes a simple ASCII preview of the finished layout.
