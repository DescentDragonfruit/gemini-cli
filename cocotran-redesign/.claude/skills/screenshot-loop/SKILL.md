# Skill: /screenshot-loop

Iterative visual design review. Screenshot → compare vs. inspiration → top 3 gaps
+ exact fixes → apply → repeat until approved.

## How to invoke

```
/screenshot-loop
  Current: [paste screenshot]
  Inspiration: https://example.com
  "What are the top 3 gaps and how do I fix them?"

/screenshot-loop iteration 3
  [paste new screenshot]
  "Applied your last fixes — what's still off?"

/screenshot-loop mobile
  [paste mobile screenshot at 375px]
  "Check mobile layout"
```

## Output format per iteration

```
SCREENSHOT LOOP — ITERATION [N]

MATCHING WELL ✓
  [what's already looking good]

TOP 3 GAPS:

GAP 1 (HIGH IMPACT) — [description]
  Problem:    [what looks wrong]
  Root cause: [CSS property / Kadence setting]
  Fix:
    Option A — Kadence setting:
      Block → [tab] → [setting] → [value]
    Option B — CSS (paste into plugin scroll-fx.css or your child theme):
      .selector { property: value; }

GAP 2 (MEDIUM IMPACT) — [description]
  ...

GAP 3 (LOW IMPACT) — [description]
  ...

NEXT: Apply these 3 fixes → take a new screenshot → share for Iteration [N+1]
```

Iteration notes are auto-appended to `screenshot-refs/iteration-log.md`.

## Automated capture (optional — requires Node.js)

```bash
cd screenshot-loop
npm install         # first time only
node capture.js --url=http://localhost:8888
node capture.js --url=http://localhost:8888 --mobile
node capture.js --url=http://localhost:8888 --watch    # auto-captures on CSS save
```

Screenshots saved to `screenshot-refs/latest-desktop.png` and `latest-mobile.png`.

## Skill notes
- Always compare to the original inspiration URL — never from memory
- Max 3 fixes per iteration — keeps changes trackable
- Always give copy-paste-ready CSS or exact Kadence setting paths — never vague suggestions
- When gap is in a Kadence setting: "Block → Style tab → Border → 1px solid var(--c-accent)"
- When gap is CSS: give the selector + property that overrides Kadence's default
