# Skill: screenshot-loop

## Purpose
Iterative visual design review. You share a screenshot of your current WordPress page,
Claude compares it to your inspiration reference, identifies the top gaps, and gives
you exact CSS fixes. Repeat until the design matches your vision.

## When to Use
- After building a page section in Kadence Blocks and wanting visual feedback
- When your site doesn't look like your inspiration yet and you can't pinpoint why
- For final QA before a page goes live

## How to Invoke

**Basic loop — share current screenshot + inspiration:**
> Paste your current site screenshot + inspiration URL/screenshot, then say:
> "Screenshot loop — compare these and give me the top 3 gaps + exact CSS fixes"

**Loop with context:**
> "Screenshot loop iteration 2 — I applied your last fixes. Here's the new screenshot.
>  What's still off compared to [inspiration URL]?"

**Mobile review:**
> "Screenshot loop — mobile view. Here's a screenshot at 375px width. What needs
>  fixing for mobile?"

**Automated loop (Local WordPress + Node.js):**
> Run `node screenshot-loop/capture.js --url=http://localhost --watch`
> Then say: "New screenshot captured at child-theme/screenshot-refs/latest.png — analyze it"

---

## Loop Workflow

```
ITERATION START
│
├─ You paste: current screenshot + inspiration reference
│
├─ Claude outputs:
│   ├─ VISUAL COMPARISON (what matches, what doesn't)
│   ├─ TOP 3 GAPS (prioritized by visual impact)
│   │   ├─ Gap 1: [description] → [exact CSS fix]
│   │   ├─ Gap 2: [description] → [exact CSS fix]
│   │   └─ Gap 3: [description] → [exact CSS fix]
│   └─ ITERATION NOTES (saved to iteration-log.md)
│
├─ You apply the CSS fixes
│
├─ You take a new screenshot
│
└─ REPEAT until approved
```

## Output Format

Claude will always structure feedback as:

```
SCREENSHOT LOOP — ITERATION [N]
Date: [date]

WHAT'S MATCHING WELL:
  ✓ [element] — [what's right]

TOP 3 GAPS TO CLOSE:

GAP 1 (HIGH IMPACT): [description]
  Problem: [what looks wrong]
  Root cause: [CSS property causing it]
  Fix → paste into 04-components.css:
    .selector {
      property: value;
    }

GAP 2 (MEDIUM IMPACT): [description]
  ...

GAP 3 (LOW IMPACT): [description]
  ...

NEXT STEP: Apply these 3 fixes, then share a new screenshot.
```

---

## Automated Screenshot Capture

If you have a local WordPress dev environment (e.g. LocalWP) and Node.js installed,
the `screenshot-loop/capture.js` script can auto-capture screenshots.

### Setup
```bash
# From project root
cd screenshot-loop
npm install puppeteer
```

### Usage
```bash
# One-time screenshot
node capture.js --url=http://localhost:8888 --out=../child-theme/screenshot-refs/

# Watch mode — captures new screenshot every time you save a CSS file
node capture.js --url=http://localhost:8888 --watch --out=../child-theme/screenshot-refs/

# Mobile screenshot (iPhone 12 viewport)
node capture.js --url=http://localhost:8888 --mobile --out=../child-theme/screenshot-refs/

# Full page screenshot
node capture.js --url=http://localhost:8888 --full-page --out=../child-theme/screenshot-refs/
```

Screenshots are saved as `latest.png` and `[timestamp].png` in `screenshot-refs/`.

---

## Iteration Log

Every loop session is logged in `child-theme/screenshot-refs/iteration-log.md`.

```markdown
## Session [date]
**Page:** [page name/URL]
**Inspiration:** [URL]

### Iteration 1
- Gap 1: [description] → Fixed: yes/no
- Gap 2: [description] → Fixed: yes/no
- Gap 3: [description] → Fixed: yes/no

### Iteration 2
...

**Approved:** [date] ✓
```

---

## Skill Notes
- Always reference the original inspiration URL when comparing — never compare from memory
- Prioritize gaps by visual impact (layout > typography > color > micro-details)
- Give only 3 fixes per iteration — too many changes at once make it hard to track what helped
- Always provide copy-paste-ready CSS, never vague suggestions
- When the gap is a Kadence Block setting (not CSS), specify the exact setting path:
  e.g. "Block → Style tab → Border → set to 1px solid var(--color-accent)"
