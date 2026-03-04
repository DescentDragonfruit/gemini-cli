# Skill: /brand-apply

Turn your brand colors and fonts into a complete tokens.css file.
One source of truth — every component uses these variables.

## How to use

> /brand-apply
> Primary: #1A3C5E
> Secondary: #F4A261
> Accent: #2EC4B6
> Background: #FAFAFA
> Text: #1A1A2E
> Heading font: Playfair Display
> Body font: Inter

For cocotrantravel.com (override section only):
> /brand-apply — travel site overrides
> Primary: #2D6A4F
> Accent: #FFB703
> (keep fonts the same)

Can also extract directly from an inspiration URL:
> /brand-apply — extract from https://example.com

## What you get

A complete `tokens.css` file with:
- All color tokens + auto-derived dark/light variants and RGB values
- Full typography scale (sizes, weights, line-heights, letter-spacing)
- Spacing scale, border-radius scale, shadow system, z-index scale
- Animation easing curves and duration tokens
- `body.site-travel { }` block for cocotrantravel.com overrides
- Google Fonts `@import` line ready to paste into functions.php

Upload the file to `assets/css/tokens.css` in your child theme.
Never hardcode hex values anywhere else — always use `var(--token-name)`.
