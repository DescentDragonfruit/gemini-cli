# Cocotran Website Redesign

Two WordPress sites. Kadence theme + Kadence child theme + Kadence Blocks.
Goal: modern, dynamic, interactive — Wix-quality animations using custom CSS/JS only.

---

## Sites

| Site | Purpose |
|------|---------|
| cocotran.com | Translation / language services |
| cocotrantravel.com | Travel experiences |

Both use the same Kadence child theme. Per-site color differences are handled
with CSS custom properties scoped to `body.site-travel`.

---

## Brand Assets

Fill these in before running `/brand-apply`.

### cocotran.com
```
Primary:      #______
Secondary:    #______
Accent:       #______
Background:   #______
Text:         #______
Heading font: ____________
Body font:    ____________
```

### cocotrantravel.com
```
Primary:      #______
Secondary:    #______
Accent:       #______
Background:   #______
Text:         #______
Heading font: ____________
Body font:    ____________
```

---

## Tech Stack

- **CMS:** WordPress
- **Theme:** Kadence (parent) + Kadence child theme (your existing one)
- **Page builder:** Kadence Blocks (Gutenberg)
- **Animations:** Custom CSS keyframes + vanilla JS (IntersectionObserver, requestAnimationFrame)
- **No GSAP. No Elementor. No page-builder plugins.**

---

## Animation Class System

Apply these to any Kadence block via **Advanced → Additional CSS class(es)**.

### Entrance animations (scroll-triggered)
| Class | Effect |
|-------|--------|
| `fx-fade` | Fade in |
| `fx-slide-up` | Slide from below |
| `fx-slide-left` | Slide from right |
| `fx-slide-right` | Slide from left |
| `fx-zoom` | Scale up from 90% |
| `fx-bounce` | Spring overshoot entry |
| `fx-flip` | 3D perspective flip |
| `fx-blur` | Blur-to-sharp reveal |
| `fx-clip` | Clip-path wipe |
| `fx-letters` | Characters stagger in one by one |
| `fx-words` | Words stagger in |
| `fx-stagger` | Direct children animate in sequence |
| `fx-count` | Number counts up from zero |

### Scroll-linked effects (tied to scroll position)
| Class | Effect |
|-------|--------|
| `fx-parallax-slow` | Moves at 20% scroll speed (subtle depth) |
| `fx-parallax-mid` | Moves at 40% scroll speed |
| `fx-parallax-fast` | Moves at 65% scroll speed (dramatic) |
| `fx-zoom-scroll` | Element scales as you scroll past |
| `fx-rotate-scroll` | Element rotates with scroll |

### Hover micro-interactions (CSS only, no JS needed)
| Class | Effect |
|-------|--------|
| `fx-hover-lift` | Rise + shadow on hover |
| `fx-hover-glow` | Accent-colored glow |
| `fx-hover-scale` | Subtle zoom |
| `fx-hover-underline` | Animated underline slides in |
| `fx-hover-tilt` | 3D perspective tilt follows mouse |
| `fx-hover-zoom` | Image zooms inside its container |

### Modifiers (combine with any fx- class)
| Class | Effect |
|-------|--------|
| `fx-delay-1` … `fx-delay-5` | 100ms / 200ms / 300ms / 500ms / 700ms delay |
| `fx-fast` | 250ms duration |
| `fx-slow` | 900ms duration |
| `fx-repeat` | Re-animates every time element enters view |
| `fx-mobile` | Force-enable on mobile (animations off by default on mobile) |

**Example combinations:**
```
fx-slide-up fx-delay-2          → slides up, 200ms delay
fx-zoom fx-slow fx-repeat       → slow zoom, replays each time
fx-stagger fx-delay-1           → children stagger in, first one delayed 100ms
fx-letters fx-fast              → fast character stagger
```

---

## Child Theme File Map

Claude generates these files. You upload them to your existing Kadence child theme.

```
wp-content/themes/your-kadence-child/
├── style.css          ← already exists — do not touch
├── functions.php      ← add the enqueue block below
└── assets/
    ├── css/
    │   ├── tokens.css          ← brand colors, fonts, spacing (from /brand-apply)
    │   ├── animations.css      ← all fx-* classes + keyframes (from /animation-builder)
    │   └── components.css      ← hero, nav, card, CTA overrides (from /kadence-guide)
    └── js/
        ├── engine.js           ← IntersectionObserver scroll reveals
        ├── parallax.js         ← scroll-linked parallax + zoom effects
        └── transitions.js      ← page-to-page fade/slide transitions
```

**Rule:** Never modify Kadence parent theme files. Only work inside the child theme.

---

## How to Add Files to WordPress

1. Go to your host's **File Manager** (or use FTP)
2. Navigate to `/wp-content/themes/your-kadence-child/`
3. Create `assets/css/` and `assets/js/` if they don't exist
4. Upload the generated files into those folders
5. Open `functions.php` and add this block:

```php
function cocotran_enqueue() {
    $v = '1.0.0'; // bump after every upload to force browser cache refresh
    $u = get_stylesheet_directory_uri() . '/assets/';

    wp_enqueue_style(  'coc-tokens',  $u . 'css/tokens.css',     [],              $v );
    wp_enqueue_style(  'coc-anim',    $u . 'css/animations.css', ['coc-tokens'],  $v );
    wp_enqueue_style(  'coc-comp',    $u . 'css/components.css', ['coc-anim'],    $v );
    wp_enqueue_script( 'coc-engine',  $u . 'js/engine.js',       [], $v, true );
    wp_enqueue_script( 'coc-para',    $u . 'js/parallax.js',     [], $v, true );
    wp_enqueue_script( 'coc-trans',   $u . 'js/transitions.js',  [], $v, true );
}
add_action( 'wp_enqueue_scripts', 'cocotran_enqueue' );
```

6. **Every time you upload new CSS/JS files:** change `'1.0.0'` → `'1.0.1'` (or any new number). This forces all browsers to reload the new files instead of using a cached version.

---

## How Claude Helps You Design

Claude **cannot** click in your WordPress admin or browser. But Claude will:

- **Analyze any URL or screenshot** — extract exact colors, fonts, layout, animation techniques
- **Generate all CSS and JS** — you copy-paste into your child theme files
- **Give block-by-block build instructions** — exactly which Kadence blocks to add, every setting
- **Run the screenshot loop** — compare your site to inspiration and output exact CSS fixes
- **Evaluate animations before building** — share a URL, Claude identifies the technique and whether it's possible with CSS/JS only

---

## Workflow: From Blank Page to Finished Design

```
1. Share inspiration URL or screenshot
         ↓
2. /design-analyzer
   → extracts colors, fonts, layout, identifies animation techniques
         ↓
3. /brand-apply  (share your hex codes + font names)
   → generates tokens.css → you upload it
         ↓
4. /kadence-guide  (describe the section you want to build)
   → step-by-step block instructions:
     "Step 1: Add Kadence Row Block. Set min-height to 100vh..."
         ↓
5. You build it in WordPress following the guide
         ↓
6. Take a screenshot → paste into chat → /screenshot-loop
   → top 3 gaps + exact CSS fixes
         ↓
7. Apply fixes → new screenshot → repeat until approved ✓
```

## Workflow: Recreating a Specific Animation

```
1. Share the URL with the animation you like
         ↓
2. /design-analyzer  "What is that scroll animation on the hero?"
   → identifies technique + evaluates if CSS/JS can do it
         ↓
3. /animation-builder  "Build it for my hero heading"
   → generates CSS + JS code
         ↓
4. Paste into animations.css + engine.js → upload → test
```

---

## Skills Reference

| Skill | Invoke with |
|-------|-------------|
| Analyze inspiration URL or screenshot | `/design-analyzer` |
| Apply brand colors + fonts → tokens.css | `/brand-apply` |
| Build any animation effect | `/animation-builder` |
| Step-by-step Kadence block guide | `/kadence-guide` |
| Screenshot comparison + CSS fixes | `/screenshot-loop` |
| WooCommerce shop animations | `/woo-styles` |
| Accessibility audit | `/a11y-check` |
| Performance audit | `/perf-audit` |

---

## Rules

1. Never edit Kadence parent theme files — child theme only
2. Bump the version number in `functions.php` after every file upload
3. Test on staging before pushing to your live site
4. Check mobile after every CSS change (Chrome DevTools → device toolbar)
5. Run `/a11y-check` before any animation goes live
6. Always use `var(--token-name)` for colors — never hardcode hex values in component CSS

---

*cocotran.com + cocotrantravel.com — Redesign Project*
*Last updated: 2026-03-04*
