# Skill: brand-apply

## Purpose
Turn your brand assets (colors, fonts, logo) into a complete, production-ready
CSS custom properties file for your Kadence child theme. One source of truth for
your entire design system — every component uses these tokens.

## When to Use
- You have your brand colors finalized and want to apply them site-wide
- You want to switch fonts across the whole site in one place
- You want to set up the design token system before building any pages
- You're starting on the second site (cocotrantravel.com) and need a different color palette

## How to Invoke

**Full brand setup:**
> "Apply my brand:
>  Primary: #1A3C5E
>  Secondary: #F4A261
>  Accent: #2EC4B6
>  Background: #FAFAFA
>  Text: #1A1A2E
>  Heading font: Playfair Display
>  Body font: Inter"

**Update colors only:**
> "Update my brand colors for cocotrantravel.com:
>  Primary: #2D6A4F (forest green)
>  Secondary: #95D5B2
>  Keep the same fonts"

**Generate from inspiration:**
> "Extract the brand colors from this URL and apply them: https://example.com"
> (Run /design-analyzer first, then pass the color output to /brand-apply)

---

## Output

Claude generates the complete `01-custom-properties.css` file:

```css
/* ============================================
   DESIGN TOKENS — cocotran.com
   Generated: [date]
   Update these values to retheme the entire site
   ============================================ */

:root {

  /* ── Brand Colors ─────────────────────────── */
  --color-primary:        #______;   /* main brand color — CTAs, headings */
  --color-primary-dark:   #______;   /* 15% darker — hover states */
  --color-primary-light:  #______;   /* 15% lighter — backgrounds */
  --color-secondary:      #______;   /* accent color — highlights, icons */
  --color-accent:         #______;   /* third color — special elements */
  --color-accent-dark:    #______;

  /* ── Neutrals ─────────────────────────────── */
  --color-bg:             #______;   /* page background */
  --color-surface:        #______;   /* cards, panels (slightly off-white or dark) */
  --color-surface-raised: #______;   /* modals, dropdowns */
  --color-border:         #______;   /* dividers, input borders */
  --color-text:           #______;   /* primary text */
  --color-text-muted:     #______;   /* secondary text, captions */
  --color-text-inverse:   #______;   /* text on dark/primary backgrounds */

  /* ── Typography ───────────────────────────── */
  --font-heading:  '______', sans-serif;
  --font-body:     '______', sans-serif;
  --font-mono:     'JetBrains Mono', 'Courier New', monospace;

  --font-size-xs:    0.75rem;    /*  12px */
  --font-size-sm:    0.875rem;   /*  14px */
  --font-size-base:  1rem;       /*  16px */
  --font-size-md:    1.125rem;   /*  18px */
  --font-size-lg:    1.25rem;    /*  20px */
  --font-size-xl:    1.5rem;     /*  24px */
  --font-size-2xl:   2rem;       /*  32px */
  --font-size-3xl:   2.5rem;     /*  40px */
  --font-size-hero:  clamp(2.5rem, 6vw, 5rem);  /* responsive hero heading */

  --font-weight-light:    300;
  --font-weight-regular:  400;
  --font-weight-medium:   500;
  --font-weight-semibold: 600;
  --font-weight-bold:     700;

  --line-height-tight:  1.2;
  --line-height-snug:   1.4;
  --line-height-base:   1.6;
  --line-height-loose:  1.8;

  --letter-spacing-tight:  -0.02em;
  --letter-spacing-normal:  0em;
  --letter-spacing-wide:    0.05em;
  --letter-spacing-wider:   0.1em;

  /* ── Spacing Scale ────────────────────────── */
  --space-2xs:  0.25rem;   /*   4px */
  --space-xs:   0.5rem;    /*   8px */
  --space-sm:   0.75rem;   /*  12px */
  --space-md:   1rem;      /*  16px */
  --space-lg:   1.5rem;    /*  24px */
  --space-xl:   2rem;      /*  32px */
  --space-2xl:  3rem;      /*  48px */
  --space-3xl:  4rem;      /*  64px */
  --space-4xl:  6rem;      /*  96px */
  --space-5xl:  8rem;      /* 128px */

  /* ── Layout ───────────────────────────────── */
  --max-width-content:  1200px;
  --max-width-text:      720px;
  --max-width-narrow:    560px;

  /* ── Borders & Radius ─────────────────────── */
  --radius-sm:    4px;
  --radius-md:    8px;
  --radius-lg:   12px;
  --radius-xl:   20px;
  --radius-pill: 9999px;

  --border-thin:    1px solid var(--color-border);
  --border-medium:  2px solid var(--color-border);

  /* ── Shadows ──────────────────────────────── */
  --shadow-sm:    0 1px  3px rgba(0,0,0,0.08);
  --shadow-md:    0 4px 12px rgba(0,0,0,0.10);
  --shadow-lg:    0 8px 30px rgba(0,0,0,0.12);
  --shadow-xl:    0 16px 60px rgba(0,0,0,0.15);
  --shadow-color: 0 4px 20px rgba(VAR_PRIMARY_RGB, 0.25); /* brand-tinted shadow */

  /* ── Animation ────────────────────────────── */
  --ease-smooth:   cubic-bezier(0.4, 0, 0.2, 1);
  --ease-spring:   cubic-bezier(0.34, 1.56, 0.64, 1);
  --ease-in:       cubic-bezier(0.4, 0, 1, 1);
  --ease-out:      cubic-bezier(0, 0, 0.2, 1);

  --duration-instant:  100ms;
  --duration-fast:     200ms;
  --duration-base:     350ms;
  --duration-slow:     600ms;
  --duration-slower:   900ms;

  /* ── Z-Index Scale ────────────────────────── */
  --z-below:   -1;
  --z-base:     0;
  --z-raised:   10;
  --z-dropdown: 100;
  --z-sticky:   200;
  --z-modal:    300;
  --z-toast:    400;
}
```

Claude also generates:
1. **Google Fonts `@import`** line for `functions.php`
2. **Dark mode variant** (optional) using `@media (prefers-color-scheme: dark) { :root { } }`
3. **Per-site variant** for `body.site-travel { }` (cocotrantravel.com overrides)
4. **Kadence Global Settings JSON** — paste into Kadence → Design → Global Palette to sync colors in the block editor

---

## Skill Notes
- Never hardcode hex values in component CSS — always use `var(--color-*)` tokens
- Primary-dark is derived by darkening primary by ~15% (Claude calculates this)
- Shadow-color requires the primary color in RGB format for the `rgba()` function
- The `--font-size-hero` uses `clamp()` so it scales with viewport on all screen sizes
- Always generate the per-site body class variant so both sites can share one stylesheet
