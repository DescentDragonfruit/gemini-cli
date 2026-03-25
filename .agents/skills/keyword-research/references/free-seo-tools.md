# Free Keyword Research Tools Reference

A curated list of free tools you can use alongside this skill for deeper keyword research.

---

## Fully Free (No Account Required)

### Autocomplete APIs (used by this skill's script)
| Tool | URL | Notes |
|------|-----|-------|
| Google Suggest | `suggestqueries.google.com/complete/search?client=firefox&q=KEYWORD` | 10 results per query |
| Bing Suggest | `api.bing.com/osjson.aspx?query=KEYWORD` | 8 results per query |
| DuckDuckGo Suggest | `duckduckgo.com/ac/?q=KEYWORD&type=list` | 10 results per query |

### Research Tools
| Tool | URL | What it does |
|------|-----|-------------|
| Google Trends | trends.google.com | Relative search volume over time, seasonal patterns, regional interest |
| Answer The Public | answerthepublic.com | Question/preposition/comparison keyword visualizer (3 free searches/day) |
| Google Search | google.com | SERP analysis, People Also Ask, Related Searches section |
| Bing Webmaster Tools | bing.com/webmasters | Free keyword research for sites you own |
| WordStream Free Keyword Tool | wordstream.com/keywords | 10 free lookups/day, shows CPC + volume |

---

## Free Tier (Account Required)

| Tool | Free Tier Limits | Best For |
|------|-----------------|----------|
| Google Keyword Planner | Unlimited (needs Google Ads account, no spending required) | Search volume ranges, CPC estimates |
| Ubersuggest | 3 free searches/day | Keyword ideas + basic difficulty scores |
| Semrush | 10 keyword reports/day | Competitive analysis, SERP features |
| Moz Keyword Explorer | 10 queries/month | Difficulty scores, organic CTR |
| Ahrefs Free Keyword Generator | Unlimited basic results | Ideas from Google, Bing, YouTube, Amazon |
| Keywordseverywhere.com | 100,000 free credits | Browser extension showing volume inline |
| SpyFu | 10 free searches/day | Competitor keyword analysis |

---

## Google Search Console (Free, Owns-Your-Site Only)

If you own the website you're optimizing:
- Go to: search.google.com/search-console
- **Performance report** → shows exact queries, impressions, clicks, CTR, position
- Best source of truth for your existing keyword rankings
- Filter by page, country, device, date range

---

## Google Keyword Planner (Free)

1. Create a Google Ads account (no spending required): ads.google.com
2. Tools → Planning → Keyword Planner
3. "Discover new keywords" → enter seed keywords
4. Shows: monthly search volume ranges, competition (Low/Med/High), CPC bids
5. Export to CSV for bulk analysis

**Note**: Without active ad spend, Google shows volume ranges (e.g., "1K–10K") not exact numbers.

---

## Google Trends Tips

- Compare up to 5 keywords side by side
- See seasonal trends (important for content calendars)
- Explore "Rising" queries in a topic for emerging keywords
- URL pattern for programmatic access: `trends.google.com/trends/explore?q=KEYWORD&geo=US`
- Python library (pytrends) for bulk data: `pip install pytrends`

---

## Programmatic Free Options

### pytrends (Python, Free)
```bash
pip install pytrends
```
```python
from pytrends.request import TrendReq

pytrends = TrendReq(hl='en-US', tz=360)
pytrends.build_payload(['coffee brewing'], timeframe='today 12-m')
df = pytrends.interest_over_time()
related = pytrends.related_queries()
```

### serpapi.com (100 free searches/month)
- Returns structured Google SERP data (PAA, related searches, ads, organic results)
- Register at serpapi.com for free API key

### DataForSEO Sandbox (Free, unlimited in sandbox mode)
- Developer sandbox: app.dataforseo.com
- Returns keyword volume, CPC, competition, SERP features
- Sandbox data is synthetic but useful for testing pipelines

---

## Quick Wins Checklist

- [ ] Run this skill's script to get 100–300 seed keyword ideas (free, instant)
- [ ] Use `web_search` in Gemini CLI to check Google SERP for top candidates
- [ ] Check Google Trends for seasonality of your top keywords
- [ ] Add your site to Google Search Console to see actual query data
- [ ] Use Google Keyword Planner for volume ranges and CPC signals
- [ ] Mine "People Also Ask" and "Related Searches" from Google results
- [ ] Check Answer The Public for question-format long-tail keywords
