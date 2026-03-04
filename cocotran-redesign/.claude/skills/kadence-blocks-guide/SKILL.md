# Skill: /kadence-blocks-guide

Get a step-by-step Kadence Blocks build guide for any page section.
Claude tells you exactly which blocks to add and every setting to configure.

## How to invoke

```
/kadence-blocks-guide hero
  "Full viewport hero, dark image overlay, heading slides up, two CTA buttons"

/kadence-blocks-guide services
  "3-column icon + heading + text cards, hover lift effect, stagger entrance"

/kadence-blocks-guide testimonials
  "Carousel-style testimonials with quote marks, avatar, name"

/kadence-blocks-guide [paste screenshot or URL]
  "Recreate this section layout in Kadence Blocks"
```

## Output format

Claude gives you a numbered list, block by block:

```
STEP 1 — Add a Kadence Row Block
  Settings:
    Layout tab:
      Columns: 1
      Inner column gap: 0
    Style tab:
      Background type: Image
      (Upload your hero image)
      Overlay color: #000000  Opacity: 50%
    Advanced tab:
      Min height: 100vh
      Vertical alignment: Center
      Additional CSS class(es): (leave blank)

STEP 2 — Inside the Row, add a Kadence Column Block
  Settings:
    Style → Padding: 80px top, 80px bottom, 40px left, 40px right

STEP 3 — Inside the Column, add a Kadence Advanced Heading Block
  Settings:
    HTML tag: H1
    Typography → Font size: clamp(2.5rem, 6vw, 5rem)
    Typography → Font weight: 700
    Color: #FFFFFF
  Advanced tab:
    Additional CSS class(es): fx-slide-up

STEP 4 — Add another Kadence Advanced Heading (subtitle)
  Settings:
    HTML tag: P  (or H2 — use P for better SEO if H1 is above)
    Font size: 1.25rem
    Color: rgba(255,255,255,0.85)
  Advanced:
    Additional CSS class(es): fx-fade fx-delay-2

STEP 5 — Add Kadence Advanced Button Block
  Settings:
    Button text: Get Started
    Link: /contact/
    Style: Filled
    Background: var(--c-accent)  [or your accent hex]
    Border radius: 9999px  (pill shape)
    Padding: 14px top/bottom, 32px left/right
  Advanced:
    Additional CSS class(es): fx-fade fx-delay-3 fx-hover-lift

PREVIEW:
  [ASCII mockup of section]
  ┌────────────────────────────────────────┐
  │                                        │
  │  HERO HEADING SLIDES UP                │
  │  Subtitle fades in after               │
  │  [  Get Started  ]                     │
  │                                        │
  └────────────────────────────────────────┘
```

## Common sections available

- `hero` — full viewport hero with background image/video/gradient
- `services` — icon + heading + text card grid
- `about` — two-column text + image (alternating layout)
- `stats` — counter numbers with labels
- `testimonials` — quote cards with avatar
- `cta` — high-contrast full-width call to action
- `gallery` — image grid with hover zoom
- `pricing` — pricing cards with feature lists
- `faq` — accordion expand/collapse
- `team` — team member cards
- `blog-grid` — recent posts grid
- `footer` — multi-column footer with logo, links, socials

## Skill notes
- Block names shown are for Kadence Blocks (Gutenberg) — not Elementor
- Kadence Row → `.kt-row-layout-wrap` in the DOM
- Kadence Info Box → `.kt-blocks-info-box-link-wrap` in the DOM
- For Kadence-specific CSS overrides, always use these selectors
- Every guide includes which `fx-*` classes to add and where
