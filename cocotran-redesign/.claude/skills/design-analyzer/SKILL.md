# Skill: /design-analyzer

Analyze a design inspiration URL or screenshot.
Extracts colors, fonts, layout, and animation techniques.
Identifies what each animation is and whether it can be built with CSS/JS only.

## How to use

Share a URL:
> /design-analyzer https://example.com

Share a screenshot (paste the image):
> /design-analyzer — [paste screenshot]

Ask about a specific effect:
> /design-analyzer https://example.com — what is that scroll animation on the hero? Can we build it?

Compare to your current site:
> /design-analyzer https://inspiration.com vs cocotran.com — what are the top 5 gaps?

## What you get

**Design tokens** — ready to hand to /brand-apply:
- Exact hex colors (primary, secondary, accent, background, text)
- Font names, sizes, weights, letter-spacing, line-height
- Layout: max-width, section padding, column gap, border-radius, shadow style
- Button shape, image style, border style

**Animation breakdown** — for each effect spotted:
- What it's called (e.g. "clip-path wipe reveal", "character stagger", "scroll-linked scale")
- Technique: CSS only / IntersectionObserver / scroll-linked rAF
- Feasibility: Easy / Medium / Complex
- Which fx-* class covers it (or whether /animation-builder needs to generate custom code)
- Mobile behavior

**Kadence block plan** — section by section:
- Which blocks to use
- Which settings to configure
- Which fx-* classes to add

**Gap analysis** (when comparing two sites):
- Top 5 differences, prioritized by visual impact
- Specific fix for each
