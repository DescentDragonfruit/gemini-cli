# Skill: /brand-apply

Generate a complete `tokens.css` file from your brand colors and fonts.
This becomes the single source of truth for all colors, typography, spacing, and easing.

## How to invoke

```
/brand-apply
  Primary: #1A3C5E
  Secondary: #F4A261
  Accent: #2EC4B6
  Background: #FAFAFA
  Text: #1A1A2E
  Heading font: Playfair Display
  Body font: Inter

/brand-apply  ← for cocotrantravel.com override section
  Primary: #2D6A4F
  Accent: #FFB703
  (keep other values the same)
```

## What Claude outputs

1. Complete `tokens.css` — all custom properties filled in with your exact brand values
   plus auto-derived variants (dark, light, muted) and RGB versions for transparency

2. Google Fonts `<link>` tag to add to `functions.php` (or Kadence → General → Typography)

3. The `body.site-travel { }` override block for cocotrantravel.com

4. Kadence Global Palette JSON (optional) — paste into Kadence → Design → Global Palette
   so your brand colors appear in the Gutenberg color picker

## Output format

```css
:root {
  --c-primary:        #1A3C5E;
  --c-primary-dk:     #122a43;   /* auto-derived: 15% darker */
  --c-primary-lt:     #2d5f8a;   /* auto-derived: 20% lighter */
  --c-primary-rgb:    26, 60, 94; /* for rgba() usage */
  /* ... all tokens ... */
}
body.site-travel {
  /* cocotrantravel.com overrides */
}
```

## Skill notes
- Always use `var(--c-*)` in all other CSS files — never hardcode hex
- `clamp()` is used for responsive font sizes — no media queries needed for type
- RGB versions of primary/accent are auto-generated for use in `rgba()` transparent colors
