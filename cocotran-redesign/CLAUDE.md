# Cocotran WordPress Redesign — Project Reference

> **Two sites. One plugin. Wix-quality animations, zero page builder bloat.**
>
> This file is your master reference for redesigning **cocotran.com** and
> **cocotrantravel.com** using the `cocotran-scroll-fx` WordPress plugin.
> Open this any time to remember the stack, the workflow, and how to use each skill.

---

## 1. Project Overview

| Site | Purpose | Status |
|------|---------|--------|
| **cocotran.com** | Translation / language services | Redesign in progress |
| **cocotrantravel.com** | Travel experiences | Redesign in progress |

Both sites share **one plugin** for all scroll effects and animations.
Per-site design differences (colors, fonts) live in your Kadence child theme via CSS custom properties.

---

## 2. Why a Plugin (Not a Child Theme)?

| Child Theme | Plugin (cocotran-scroll-fx) |
|---|---|
| Tied to the active theme | Works with any theme — survives theme switches |
| Must be re-applied if theme changes | Activate once, works forever |
| CSS/JS mixed with design | Pure animation + interaction layer only |
| Harder to share or reuse | One ZIP file → install on both sites |

The plugin handles **all scroll effects and animations**.
Your Kadence child theme handles **brand colors, fonts, and Kadence Block overrides**.

---

## 3. Plugin Location

```
cocotran-redesign/
├── CLAUDE.md                          ← This file (project reference)
├── .claude/skills/                    ← Claude Code skills (slash commands)
│   ├── design-analyzer/SKILL.md
│   ├── animation-builder/SKILL.md
│   ├── screenshot-loop/SKILL.md
│   ├── brand-apply/SKILL.md
│   ├── kadence-blocks-guide/SKILL.md
│   ├── woo-shop-styles/SKILL.md
│   ├── a11y-check/SKILL.md
│   └── perf-audit/SKILL.md
├── plugin/
│   └── cocotran-scroll-fx/           ← Upload this folder to WordPress
│       ├── cocotran-scroll-fx.php    ← Plugin entry file
│       ├── assets/
│       │   ├── css/
│       │   │   ├── tokens.css        ← Brand design tokens
│       │   │   ├── scroll-fx.css     ← All scroll animation classes
│       │   │   └── micro.css         ← Hover micro-interactions
│       │   └── js/
│       │       ├── engine.js         ← Main animation engine (IntersectionObserver + RAF)
│       │       ├── parallax.js       ← Depth parallax + scroll-linked effects
│       │       └── transitions.js    ← Page-to-page transitions
└── screenshot-loop/
    ├── capture.js                    ← Auto-screenshot script (needs Node.js)
    └── package.json
```

---

## 4. Brand Assets

> Fill these in, then run `/brand-apply` to generate tokens.css

### cocotran.com
```
Primary Color:     #____________
Secondary Color:   #____________
Accent Color:      #____________
Background:        #____________
Text Color:        #____________
Heading Font:      ____________  (Google Fonts name)
Body Font:         ____________  (Google Fonts name)
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
```

---

## 5. Animation Class System (Wix-Style)

Add these classes to any Kadence Block via **Block → Advanced → Additional CSS class(es)**.

### Entrance Animations (scroll-triggered)
| Class | Effect | Wix Equivalent |
|-------|--------|---------------|
| `fx-fade` | Fade in | Fade |
| `fx-slide-up` | Slide from below | Slide |
| `fx-slide-down` | Slide from above | Slide |
| `fx-slide-left` | Slide from right | Slide |
| `fx-slide-right` | Slide from left | Slide |
| `fx-zoom` | Scale up from 90% | Zoom |
| `fx-zoom-out` | Scale down from 110% | Zoom Out |
| `fx-bounce` | Spring entry with overshoot | Bounce |
| `fx-glide` | Smooth diagonal glide | Glide |
| `fx-flip-x` | Flip on horizontal axis | Flip |
| `fx-flip-y` | Flip on vertical axis | Flip |
| `fx-spin` | Rotate 360° into position | Spin |
| `fx-unfold` | Perspective unfold reveal | Fold |
| `fx-blur` | Blur to sharp on entry | — |
| `fx-clip-left` | Clip-path wipe from left | — |
| `fx-clip-right` | Clip-path wipe from right | — |
| `fx-letters` | Letters animate in sequence | Text Reveal |
| `fx-words` | Words animate in sequence | — |
| `fx-count` | Number counts up on entry | — |

### Scroll-Linked Effects (tied to scroll position)
| Class | Effect |
|-------|--------|
| `fx-parallax-slow` | Background moves at 20% speed |
| `fx-parallax-mid` | Background moves at 40% speed |
| `fx-parallax-fast` | Background moves at 60% speed |
| `fx-zoom-scroll` | Element scales as you scroll past |
| `fx-rotate-scroll` | Element rotates as you scroll |
| `fx-progress` | Width expands as you scroll down page |

### Hover Micro-interactions (CSS only, no JS)
| Class | Effect |
|-------|--------|
| `fx-hover-lift` | Rise + shadow |
| `fx-hover-glow` | Accent glow |
| `fx-hover-scale` | Subtle zoom |
| `fx-hover-underline` | Slide-in underline |
| `fx-hover-bg` | Background fills from left |
| `fx-hover-tilt` | 3D perspective tilt |

### Modifiers (combine with any fx- class)
| Modifier | Effect |
|----------|--------|
| `fx-delay-1` through `fx-delay-5` | Adds 100ms, 200ms, 300ms, 400ms, 500ms delay |
| `fx-duration-fast` | 300ms animation |
| `fx-duration-slow` | 900ms animation |
| `fx-once` | Animate only first time (default) |
| `fx-repeat` | Re-animate every time element enters viewport |
| `fx-mobile` | Enable animation on mobile too (disabled by default) |

---

## 6. How to Install the Plugin

1. Download / zip the `plugin/cocotran-scroll-fx/` folder
2. In WordPress admin → **Plugins → Add New → Upload Plugin**
3. Upload the ZIP → Install → Activate
4. The plugin is now active — add `fx-*` classes to any block

Or via FTP:
- Upload `plugin/cocotran-scroll-fx/` folder to `/wp-content/plugins/`
- Activate in **Plugins → Installed Plugins**

---

## 7. How Claude Will Help You Design

### What Claude CAN do
- **Generate CSS + JS code** for any effect — you copy-paste into the plugin files
- **Tell you exactly which Kadence Blocks to add** and every setting to configure
- **Analyze any inspiration URL or screenshot** — extract colors, layout, animation techniques
- **Run the screenshot loop** — compare your site to inspiration, output exact fixes
- **Evaluate animation examples** before building — identify technique + feasibility
- **Generate your full token file** from brand colors + fonts

### What Claude CANNOT do
- Log into your WordPress admin or click buttons for you
- View your live site without a screenshot or URL

### The Design Workflow (from blank page)

```
1. Share inspiration URL or screenshot
       ↓
2. /design-analyzer → tokens + Kadence Blocks build plan
       ↓
3. /brand-apply → generate tokens.css with your brand
       ↓
4. Claude gives block-by-block instructions:
   "Add a Kadence Row Block. Set min-height to 100vh.
    Set background to image. Add Kadence Heading H1.
    In Advanced → CSS Classes, type: fx-slide-up fx-delay-1"
       ↓
5. You build it in WordPress following the guide
       ↓
6. Paste a screenshot → /screenshot-loop
       ↓
7. Claude outputs: top 3 gaps + exact CSS fixes
       ↓
8. Apply → new screenshot → repeat until approved
```

---

## 8. Skills Reference

| Skill | What It Does |
|-------|-------------|
| `/design-analyzer` | Analyze URL/screenshot → design tokens + Kadence block plan |
| `/brand-apply` | Brand colors + fonts → complete tokens.css |
| `/animation-builder` | Generate CSS/JS for any custom animation effect |
| `/kadence-blocks-guide` | Step-by-step Kadence block guide for any section |
| `/screenshot-loop` | Screenshot → gap analysis → CSS fix → repeat |
| `/woo-shop-styles` | WooCommerce shop page animations |
| `/a11y-check` | Accessibility audit (prefers-reduced-motion, contrast, focus) |
| `/perf-audit` | Performance audit (jank, layout triggers, passive listeners) |

---

## 9. Important Rules

1. **Plugin handles animations** — don't add animation CSS to the child theme
2. **Child theme handles brand** — colors, fonts, Kadence block overrides only
3. **Bump the version** in `cocotran-scroll-fx.php` after every plugin file update
4. **Test on staging** before applying to the live site
5. **Always check mobile** — run `node screenshot-loop/capture.js --mobile`
6. **Run `/a11y-check`** before any animation goes live
7. **Use `var(--token-*)` tokens** everywhere — never hardcode hex values

---

## 10. Recommended Next Steps

- [ ] Fill in Brand Assets (Section 4)
- [ ] Install the plugin on your staging site
- [ ] Share an inspiration URL → run `/design-analyzer`
- [ ] Run `/brand-apply` → upload generated `tokens.css`
- [ ] Share animation examples you like → Claude evaluates + builds them
- [ ] Build first page section using `/kadence-blocks-guide`
- [ ] Run screenshot loop, iterate until approved
- [ ] Run `/a11y-check` and `/perf-audit` before going live

---

*Last updated: 2026-03-04 | cocotran-redesign project*
