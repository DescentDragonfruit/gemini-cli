# Cocotran WordPress Redesign — Project Reference

> **Two sites. One design system. Wix-quality feel, zero plugin bloat.**
>
> This file is your master reference for redesigning **cocotran.com** and
> **cocotrantravel.com** using your existing Kadence child theme + Kadence Blocks.
> Open this any time to remember the stack, the workflow, and how to use each skill.

---

## 1. Project Overview

| Site | Purpose | Status |
|------|---------|--------|
| **cocotran.com** | Translation / language services business | Redesign in progress |
| **cocotrantravel.com** | Travel experiences business | Redesign in progress |

Both sites share a **single child theme** as the code base. Per-site differences
are handled via CSS custom properties and page-specific body classes.

### Design Goals
- **Design excellence** — polished look that reflects each brand's personality
- **User experience** — clear navigation, responsive layouts, enjoyable to use
- **Creativity & originality** — unique elements that stand out in their niches
- **Functionality** — eCommerce-ready (WooCommerce), mobile-first, fast
- **Modern interactions** — Wix-quality animations using only custom CSS/JS

---

## 2. Tech Stack

| Layer | Tool |
|-------|------|
| CMS | WordPress |
| Theme | Kadence Theme (parent) |
| Child Theme | Kadence child theme (your existing one) |
| Page Builder | Kadence Blocks (Gutenberg) |
| Animation | Custom CSS keyframes + vanilla JS IntersectionObserver |
| eCommerce | WooCommerce (planned) |
| Fonts | Google Fonts (via `functions.php`) |
| Icons | SVG inline or Kadence Icon Block |

**No GSAP. No Elementor. No page-builder plugins.** Everything lives in the child theme.

---

## 3. Brand Assets

> Fill in this section once your brand is finalized. These values feed directly into the `/brand-apply` skill.

### cocotran.com
```
Primary Color:     #____________
Secondary Color:   #____________
Accent Color:      #____________
Background:        #____________
Text Color:        #____________
Heading Font:      ____________  (Google Fonts name)
Body Font:         ____________  (Google Fonts name)
Logo Path:         /assets/img/cocotran-logo.svg
Favicon:           /assets/img/favicon-cocotran.png
```

### cocotrantravel.com
```
Primary Color:     #____________
Secondary Color:   #____________
Accent Color:      #____________
Background:        #____________
Text Color:        #____________
Heading Font:      ____________
Body Font:         ____________
Logo Path:         /assets/img/cocotrantravel-logo.svg
Favicon:           /assets/img/favicon-cocotrantravel.png
```

---

## 4. Animation Class System

Add these CSS classes to any Kadence Block via **Block → Advanced → Additional CSS class(es)**.

| Class | Effect | JS Required |
|-------|--------|-------------|
| `reveal` | Fade + slide up on scroll | Yes (scroll-observer.js) |
| `reveal-left` | Fade + slide in from left | Yes |
| `reveal-right` | Fade + slide in from right | Yes |
| `reveal-scale` | Fade + scale up from 95% | Yes |
| `stagger-children` | Children animate in sequence | Yes |
| `parallax-slow` | Parallax at 20% scroll speed | Yes (parallax.js) |
| `parallax-medium` | Parallax at 40% scroll speed | Yes |
| `parallax-fast` | Parallax at 60% scroll speed | Yes |
| `hover-lift` | Translate Y -6px + shadow on hover | No (CSS only) |
| `hover-glow` | Accent color glow on hover | No |
| `hover-scale` | Scale 1.03 on hover | No |
| `hover-underline` | Animated underline on hover | No |
| `page-fade` | Applied to page wrapper for transitions | Yes (page-transitions.js) |

### Mobile Behavior
All animations are **disabled on screens < 768px** by default for performance.
Add class `animate-mobile` alongside any reveal class to force animations on mobile too.

### Accessibility
All animations respect `prefers-reduced-motion`. Users who have this set in their OS
will see no animation — the content is still visible, just without motion.

---

## 5. Child Theme File Structure

```
wp-content/themes/your-kadence-child/
├── style.css                    ← Theme header + imports all CSS files
├── functions.php                ← Enqueues CSS/JS, Google Fonts, Kadence hooks
├── assets/
│   ├── css/
│   │   ├── 01-custom-properties.css  ← Brand tokens (colors, fonts, spacing)
│   │   ├── 02-base.css               ← Global resets, body, links, headings
│   │   ├── 03-animations.css         ← Keyframes + .reveal / .parallax classes
│   │   ├── 04-components.css         ← Hero, cards, nav, CTA, footer
│   │   └── 05-mobile.css             ← Mobile overrides + responsive breakpoints
│   ├── js/
│   │   ├── scroll-observer.js        ← IntersectionObserver scroll animations
│   │   ├── parallax.js               ← Depth-layer parallax scroll effect
│   │   └── page-transitions.js       ← Smooth page-to-page fade/slide
│   └── img/
│       ├── cocotran-logo.svg
│       └── cocotrantravel-logo.svg
└── screenshot-refs/             ← Save inspiration screenshots here
    ├── iteration-log.md         ← Notes from each screenshot loop session
    └── (screenshots here)
```

**Rule:** Never modify Kadence parent theme files. All changes go in the child theme.

---

## 6. How Claude Will Help You Design

### The Short Answer
Claude cannot click inside your WordPress admin or browser. But Claude will:
1. **Tell you exactly which blocks to add** and every setting to configure in them
2. **Generate all the CSS and JS** — you just copy-paste into the child theme files
3. **Analyze your inspiration URLs** — extract the color, type, layout, and animation style
4. **Run the screenshot loop** — you paste a screenshot, Claude identifies gaps and gives exact fixes
5. **Walk you through step-by-step** — from blank page to finished design, one block at a time

### Starting From a Blank Page — The Workflow

```
1. Share inspiration URL
       ↓
2. Run /design-analyzer → get design tokens + block structure plan
       ↓
3. Run /brand-apply → generate 01-custom-properties.css with your brand
       ↓
4. Claude gives you a block-by-block build guide:
   "Step 1: Add a Kadence Row Block. Set columns to 1.
    Set min-height to 100vh. Set background to var(--color-primary)..."
       ↓
5. You build it in WordPress following the guide
       ↓
6. Paste a screenshot → /screenshot-loop analyzes gaps
       ↓
7. Claude outputs exact CSS fixes + block setting changes
       ↓
8. You apply them → paste new screenshot → repeat until approved
```

### Sharing Inspiration
- **URL:** Paste any website URL — Claude will fetch and analyze it
- **Screenshot:** Paste an image directly into the chat
- **Video/GIF:** Describe the animation, or share a URL to the site — Claude identifies the technique

---

## 7. Screenshot Loop Workflow

The screenshot loop is your iterative design review tool.

### Option A — Manual (works anywhere)
1. Open your WordPress site in a browser
2. Take a screenshot (Mac: `Cmd+Shift+4`, Windows: `Win+Shift+S`)
3. Paste the screenshot into the chat with Claude
4. Claude compares it vs. your inspiration and outputs top 3 gaps + exact CSS fixes
5. Apply fixes, take new screenshot, repeat

### Option B — Automated (requires Local WordPress + Node.js)
```bash
# From the project root, run:
node screenshot-loop/capture.js --url=http://localhost:8888 --out=child-theme/screenshot-refs/

# This auto-captures a screenshot every time you save a CSS file
# Claude then reads the saved PNG and analyzes it automatically
```
See `screenshot-loop/capture.js` for setup instructions.

### Iteration Log
Every session's notes are saved in `child-theme/screenshot-refs/iteration-log.md`.
This gives you a history of every design decision made.

---

## 8. Skills Reference

Run these as prompts in your Claude session. Share context (URLs, screenshots, hex codes) with each.

| Skill | What It Does | Example Usage |
|-------|-------------|---------------|
| `/design-analyzer` | Analyze URL or screenshot → extract design tokens + animation techniques | "Analyze this URL: [url]" |
| `/brand-apply` | Turn brand colors + fonts → complete CSS custom properties file | "Apply these brand colors: primary #1a2b3c, secondary #..." |
| `/animation-builder` | Generate CSS + JS for any animation effect | "Build a scroll reveal for .hero-title with stagger" |
| `/kadence-child-theme` | Generate Kadence-specific component code | "Build me a full-viewport hero section for Kadence Blocks" |
| `/screenshot-loop` | Iterative visual review: screenshot → gap analysis → CSS fix → repeat | "Here's my current screenshot vs. inspiration [paste both]" |
| `/woo-shop-styles` | WooCommerce product page animations | "Add hover animations to my WooCommerce shop grid" |
| `/a11y-check` | Audit animations for accessibility compliance | "Check my animations.css for accessibility" |
| `/perf-audit` | Performance audit for CSS/JS animations | "Audit my scroll animations for performance issues" |

---

## 9. How to Add Files to Your WordPress Child Theme

### Step 1 — Locate your child theme
In your WordPress host's file manager or via FTP, navigate to:
```
/wp-content/themes/your-kadence-child-theme/
```

### Step 2 — Create the assets folders (if they don't exist)
```
assets/
assets/css/
assets/js/
assets/img/
```

### Step 3 — Upload generated CSS files
After running `/brand-apply` and `/animation-builder`, upload:
- `01-custom-properties.css` → `assets/css/`
- `02-base.css` → `assets/css/`
- `03-animations.css` → `assets/css/`
- `04-components.css` → `assets/css/`
- `05-mobile.css` → `assets/css/`

### Step 4 — Upload generated JS files
- `scroll-observer.js` → `assets/js/`
- `parallax.js` → `assets/js/`
- `page-transitions.js` → `assets/js/`

### Step 5 — Update functions.php
Open your child theme's `functions.php` and add the enqueue block from
`child-theme/functions.php` in this project. This tells WordPress to load your files.

**Important:** After uploading updated CSS/JS files, bump the version number in
`functions.php` (e.g. `1.0.0` → `1.0.1`) to force browsers to reload the new version.

### Step 6 — Apply animation classes in WordPress
1. Edit any page in the WordPress block editor
2. Click on a block (Heading, Image, Group, etc.)
3. In the right sidebar → **Advanced** tab → **Additional CSS class(es)**
4. Type the class name (e.g. `reveal`, `hover-lift`, `parallax-slow`)
5. Save the page — animation is live

### Step 7 — Test on staging first
Always test on a staging/dev environment before applying to your live site.
Most hosts offer a 1-click staging option (e.g. WP Engine, Kinsta, SiteGround).

### Step 8 — Mobile check
- Open Chrome DevTools → toggle device toolbar → test on iPhone and Android viewport sizes
- Run `/a11y-check` to confirm motion accessibility
- Run `/perf-audit` to confirm no scroll jank

---

## 10. Recommended Next Steps

- [ ] Fill in **Brand Assets** section (Section 3 above)
- [ ] Share your first inspiration URL → run `/design-analyzer`
- [ ] Run `/brand-apply` to generate your CSS token file
- [ ] Build your first page section using the Kadence block guide from `/kadence-child-theme`
- [ ] Set up a staging site for safe testing
- [ ] Run the screenshot loop after each major section is built
- [ ] Run `/a11y-check` and `/perf-audit` before going live

---

## 11. Important Rules

1. **Never edit Kadence parent theme files** — only work in the child theme
2. **Bump the version number** in `functions.php` every time you upload new CSS/JS
3. **Test on staging** before applying to the live site
4. **Always check mobile** after any CSS change — use Chrome DevTools
5. **Run `/a11y-check`** before any animation goes live — accessibility is non-negotiable
6. **Use CSS custom properties** (e.g. `var(--color-primary)`) everywhere — never hardcode hex values

---

*Last updated: 2026-03-04 | Project: Cocotran WordPress Redesign*
