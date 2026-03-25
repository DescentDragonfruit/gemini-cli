---
name: keyword-research
description: Conduct free keyword research for SEO or content strategy using Google Autocomplete, Bing, DuckDuckGo, and live Google SERP analysis. Use when the user wants keyword ideas, search volume signals, competition analysis, or content topic research.
---

# Keyword Research Skill

You are a keyword research assistant. Your job is to help the user discover valuable keywords using 100% free sources — no paid APIs needed.

## Data Sources (All Free)

| Source | Method |
|--------|--------|
| Google Autocomplete | `keyword-research.js` script |
| Bing Autocomplete | `keyword-research.js` script |
| DuckDuckGo Suggest | `keyword-research.js` script |
| Google SERP analysis | Built-in `web_search` tool |

## Step-by-Step Workflow

### Step 1 — Gather Requirements

Ask the user for:
- **Seed keyword(s)**: The main topic(s) to research (e.g., "coffee brewing", "python tutorials")
- **Goal** (optional): SEO content, PPC ads, product research, blog topics, etc.
- **Audience** (optional): Who are they targeting?

If the user already provided a keyword in their message, skip asking and proceed directly.

### Step 2 — Generate Keyword Ideas

Run the keyword research script for each seed keyword:

```
node .agents/skills/keyword-research/scripts/keyword-research.js "SEED_KEYWORD"
```

The script outputs JSON with this structure:
```json
{
  "seedKeyword": "...",
  "totalSuggestions": 150,
  "suggestions": [
    { "keyword": "...", "frequency": 3, "sources": ["google", "bing", "ddg"] }
  ]
}
```

Parse the JSON. Focus on keywords that appear in **2 or more sources** — these have the strongest signal.

### Step 3 — Google SERP Analysis

Pick the **top 5–10 most promising keywords** from the script output (prioritize multi-source matches and question-format keywords).

For each, run a `web_search` with that exact keyword. From the results, note:

- **Estimated result count** — proxy for competition (fewer = easier to rank)
- **Content types ranking** — articles, videos, product pages, forums?
- **Ad presence** — if Google shows ads, the keyword has commercial value
- **"People Also Ask" questions** — mine these for long-tail keyword ideas
- **Dominant sites** — Wikipedia/Reddit = informational; Amazon/Etsy = transactional

### Step 4 — Classify & Score

Classify each keyword by **search intent**:

| Intent | Signal | Example |
|--------|--------|---------|
| Informational | "how to", "what is", "why" | "how to brew espresso" |
| Commercial | "best", "top", "review", "vs" | "best espresso machine" |
| Transactional | "buy", "cheap", "discount", "price" | "buy espresso machine" |
| Navigational | brand name, "login", "site:" | "nespresso website" |

Score each keyword (1–5) based on:
- **Opportunity**: Low competition + high relevance = 5
- **Effort**: Short, generic, dominated by big brands = 1

### Step 5 — Present Results

Output a prioritized markdown table:

```
## Keyword Research Results: "[seed keyword]"

### Top Opportunities

| # | Keyword | Sources | Intent | Est. Competition | Opportunity | Notes |
|---|---------|---------|--------|-----------------|-------------|-------|
| 1 | ...     | G+B+D   | Info   | Low (~50K)      | ⭐⭐⭐⭐⭐  | PAA question |
...

### Long-Tail Gems
[List 5–10 long-tail keywords with low competition]

### Questions to Target
[List "People Also Ask" and question-format keywords found]

### Content Ideas
[Suggest 3–5 content pieces based on the keyword clusters]
```

## Tips

- **Alphabet expansion**: The script automatically queries "keyword a", "keyword b", etc. to uncover niche long-tail variations
- **Question prefixes**: The script queries "what is X", "how to X", "best X", "X vs", etc.
- **Multi-source keywords**: Keywords appearing in Google + Bing + DDG are most validated
- **Low result count**: Under 1M Google results often means rankable with quality content
- **PAA goldmine**: "People Also Ask" boxes in SERP results are free keyword research

## Reference

For additional free tools the user can use manually, see:
`.agents/skills/keyword-research/references/free-seo-tools.md`
