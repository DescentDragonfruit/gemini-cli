# Skill: kadence-child-theme

## Purpose
Generate Kadence-specific component code — CSS overrides and PHP snippets — that
work correctly with Kadence Theme's structure, Kadence Blocks, and your child theme.
Also provides step-by-step Kadence Blocks build guides for common page sections.

## When to Use
- You want to build a specific page section (hero, cards, nav, CTA, footer)
- You need to override a Kadence default style that's hard to change in the UI
- You want PHP hooks for header/footer customization or conditional logic
- You want a complete block-by-block build guide for a page

## How to Invoke

**Build a specific section:**
> "Build me a full-viewport hero section for Kadence Blocks — dark overlay,
>  animated heading, two CTA buttons"

**Generate CSS override:**
> "Generate a CSS override for Kadence's default navigation so it shrinks
>  on scroll and adds a background blur effect"

**Block-by-block guide:**
> "Give me a step-by-step Kadence Blocks guide for building a 3-column services
>  section with icons, heading, text, and hover lift effect on each card"

**PHP hook:**
> "Add a custom body class for cocotrantravel.com so I can style it differently
>  from cocotran.com with a single child theme"

---

## Component Library

### Hero Section
Full-viewport hero with overlay, animated headline, and CTA buttons.

**Kadence Block structure:**
```
Kadence Row Block (full-width, no padding)
  └─ Kadence Column Block (100% width)
       ├─ Kadence Heading Block (H1 — "hero-headline reveal")
       ├─ Kadence Heading Block (H2/subtitle — "reveal reveal-slow")
       └─ Kadence Advanced Button Block ("reveal reveal-slow hover-lift")
```

**Key settings:**
- Row → Background: Image or video, overlay color + opacity
- Row → Min height: 100vh
- Row → Content alignment: Center or left
- Row → Padding: 0 (remove defaults)
- Heading → HTML tag: H1
- Heading → Typography: `var(--font-heading)`, size: `clamp(2.5rem, 6vw, 5rem)`

**CSS override** (paste into `04-components.css`):
```css
/* Hero section */
.hero-section .kt-row-layout-inner {
  position: relative;
  min-height: 100vh;
  display: flex;
  align-items: center;
}
.hero-section .kt-inside-inner-col {
  padding: var(--space-xl) var(--space-md);
}
```

---

### Card Grid (Services / Features)
3-column card grid with icon, heading, text, hover lift.

**Kadence Block structure:**
```
Kadence Row Block (contained width)
  ├─ Kadence Column Block × 3
  │    └─ Kadence Info Box Block
  │         ├─ Icon (Kadence icon library)
  │         ├─ Heading
  │         └─ Text
  └─ (Add class "stagger-children" to the Row Block)
```

**Key settings:**
- Row → Columns: 3 (desktop), 2 (tablet), 1 (mobile)
- Row → Column gap: 32px
- Info Box → Border: 1px solid rgba(0,0,0,0.08)
- Info Box → Border radius: 12px
- Info Box → Padding: 40px
- Add class `hover-lift` to each Info Box

**CSS override:**
```css
/* Card grid */
.services-grid .kt-blocks-info-box-link-wrap {
  background: var(--color-surface);
  border-radius: 12px;
  transition: transform var(--duration-base) var(--ease-smooth),
              box-shadow var(--duration-base) var(--ease-smooth);
}
```

---

### Sticky Navigation
Nav that shrinks and adds backdrop blur on scroll.

**CSS override** (paste into `04-components.css`):
```css
/* Sticky nav — default state */
#masthead,
.site-header {
  position: sticky;
  top: 0;
  z-index: 1000;
  transition: padding var(--duration-fast) var(--ease-smooth),
              background var(--duration-fast) var(--ease-smooth),
              backdrop-filter var(--duration-fast) var(--ease-smooth);
}

/* Scrolled state — added by scroll-observer.js */
.header-scrolled #masthead,
.header-scrolled .site-header {
  padding-top: 8px;
  padding-bottom: 8px;
  background: rgba(255,255,255,0.85);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  box-shadow: 0 2px 20px rgba(0,0,0,0.08);
}
```

**JS snippet** (add to `scroll-observer.js`):
```js
/* Sticky nav scroll behavior */
(function() {
  const header = document.querySelector('#masthead, .site-header');
  if (!header) return;
  window.addEventListener('scroll', function() {
    document.body.classList.toggle('header-scrolled', window.scrollY > 60);
  }, { passive: true });
})();
```

---

### CTA Section
High-contrast call-to-action with animated background gradient.

**Kadence Block structure:**
```
Kadence Row Block (full-width)
  └─ Kadence Column Block (60% max-width, centered)
       ├─ Kadence Heading Block (H2)
       ├─ Kadence Advanced Text Block (subtitle)
       └─ Kadence Advanced Button Block
```

**Key settings:**
- Row → Background: gradient from `var(--color-primary)` to `var(--color-secondary)`
- Row → Padding: 100px vertical
- Row → Content alignment: center
- Add class `reveal` to heading and button

---

### Footer
Multi-column footer with brand colors and links.

**Kadence Block structure (in footer.php or via Kadence Footer Builder):**
```
Footer Row (full-width, 4 columns)
  ├─ Column 1: Logo + tagline + social icons
  ├─ Column 2: Services links
  ├─ Column 3: Quick links
  └─ Column 4: Contact info
```

---

## PHP Snippets for functions.php

### Add per-site body class
Lets you style cocotran.com vs. cocotrantravel.com differently with one child theme:
```php
function cocotran_body_class( $classes ) {
    if ( strpos( home_url(), 'cocotrantravel' ) !== false ) {
        $classes[] = 'site-travel';
    } else {
        $classes[] = 'site-translation';
    }
    return $classes;
}
add_filter( 'body_class', 'cocotran_body_class' );
```

### Enqueue Google Fonts
```php
function cocotran_enqueue_fonts() {
    wp_enqueue_style(
        'cocotran-google-fonts',
        'https://fonts.googleapis.com/css2?family=FONT_NAME:wght@300;400;600;700&display=swap',
        array(),
        null
    );
}
add_action( 'wp_enqueue_scripts', 'cocotran_enqueue_fonts' );
```

### Kadence filter — custom header class
```php
add_filter( 'kadence_header_class', function( $classes ) {
    $classes[] = 'cocotran-header';
    return $classes;
});
```

---

## Skill Notes
- Always use Kadence's `.kb-` and `.kt-` class prefixes when overriding block styles
- Use CSS specificity carefully — child theme styles must be at least as specific as Kadence's defaults
- Never use `!important` unless absolutely necessary (Kadence uses it in some places — match their specificity instead)
- Mobile breakpoints: 767px (tablet), 480px (mobile)
- Kadence Row Block renders as `.kt-row-layout-wrap` → `.kt-row-layout-inner` → `.kt-inside-inner-col`
- Kadence Info Box renders as `.kt-blocks-info-box-link-wrap`
