# Skill: /screenshot-loop

Iterative visual review. Paste a screenshot → compare vs. inspiration →
get top 3 gaps + exact fixes → apply → repeat until it matches.

## How to use

Start a loop:
> /screenshot-loop — [paste your screenshot] vs https://inspiration.com

Next iteration (after applying fixes):
> /screenshot-loop iteration 2 — [paste new screenshot] — what's still off?

Mobile review:
> /screenshot-loop mobile — [paste mobile screenshot at 375px width]

## What you get per iteration

```
ITERATION N

LOOKING GOOD ✓
  [what already matches the inspiration]

TOP 3 GAPS:

GAP 1 (HIGH IMPACT) — [description]
  Problem:    [what looks wrong and why]
  Fix — CSS (paste into components.css):
    .selector { property: value; }
  — OR — Kadence setting:
    Block → Style tab → [setting] → [value]

GAP 2 (MEDIUM) — ...
GAP 3 (LOW) — ...

NEXT: Apply these 3 fixes → take a new screenshot → share for Iteration N+1
```

## Rules Claude follows
- Always 3 fixes per iteration — not more (keeps changes trackable)
- Always copy-paste ready — never vague suggestions
- Always references the original inspiration URL — never from memory
- Specifies exact Kadence setting path when the fix is a block setting
