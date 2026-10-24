# The content system — library, categories, goals, update cadence, title lock

One record per live URL, kept in the Studio Desk's **Library** (store collection `library`), seeded from the 24 Oct crawl (180 records: 75 pillar pieces + 105 old posts) and maintained by the worker. The same fields should exist in WordPress too (`pillar-structure-proposals.md` §2) so the library is never the only copy.

## Record schema

| Field | Values | Who sets it |
|---|---|---|
| `url`, `title`, `cpt`, `pillar`, `format` | from WP | worker (crawl) |
| `category` | **Commercial** (hotel reviews, comparisons — reader is close to booking) · **Decision-support** (rolodex, roundup, shortlist — choosing between options) · **Informational** (primer, route, answer — planning/learning) · **Specialist intel** (dossier, logistics, "what to know before") · **Authority** (postcards, field notes, essays — brand and trust, social reuse) | rule by format; Coco can override |
| `goal` | one sentence: the query it should own + the conversion it should drive (booking box / Planning Room / inquiry / citation / saves) | seeded per format; refine per piece |
| `keyword`, `secondary_keywords[]`, `ai_questions[]` | the primary query, secondaries, and the AI-phrased questions it answers | worker research job |
| `last_updated`, `visited`, `disclosure` | dates/basis | WP + Coco |
| `rank` | `{clicks28, impressions28, position28, ctr28, clicks_prev, impressions_prev, position_prev, top_queries[], ga_sessions28, ga_engaged28, pulled_at}` | worker, weekly, from Search Console + GA4 |
| `health` | `ok` · `decaying` (impressions −30% or position +3 vs previous 28 days) · `stale` (last_updated > 12 months, or a changeable fact older than its season) · `thin` (< 400 visible words and gated) · `duplicate` (another record owns the same keyword) | worker rule |
| `suggestions` | `{seo:[], structure:[], photos:[], title_check:{...}, generated_at}` | desk ("Suggest improvements") or worker |
| `social` | ids of social kits made from it | desk |
| `notes`, `status` (`live`, `live-old`, `draft`, `retired`, `merged-into:<id>`) | free | Coco / worker |

Searchable and filterable in the Library tab by text (title, url, keyword), pillar, category, health, and last-updated range.

## Update cadence (the worker's rules)

- **Weekly** (Monday): pull Search Console (28d vs previous 28d, per URL, with top queries) and GA4 (sessions, engaged sessions, conversions by landing page); write `rank`; recompute `health`.
- `decaying` → the worker creates a job `mode: update`, `reason: "decay"`, with the top queries it's losing and a proposed fix (title/opening/FAQ), status `queued`. Coco sees it in the Queue.
- `stale` → same, `reason: "stale"`, listing the changeable facts to re-verify.
- `duplicate` → `needs-coco` with the pair and a merge recommendation.
- Any piece whose top query shifted to a question shape ("is … worth it", "A or B") → suggestion to add/retitle, and an Answer piece candidate in the question bank.
- After any update ships: `last_updated` = today, `health` = ok, and the rank baseline resets.

## The title lock (checked the same way, every time)

A title passes when **all** are true (the desk's title check runs these deterministically, then asks Claude for the judgement calls):

1. Pattern for the pillar:
   - Verdict: `[Hotel], [Place]: An Honest Review` or `[Hotel] Review: [the angle]`
   - Comparison: `[A] vs [B]: Which Should You Choose?` (or `…: Which Is Right for You?`)
   - Primer: `[Place] Travel Guide: What to Know Before You Book`
   - Rolodex: `[Place] Travel Guide: Where to Stay, Eat and Explore`
   - Roundup / Shortlist: `Best [thing] in [Place]: An Advisor's Shortlist` / `Where to Eat in [Place]: …`
   - Route: `[N] Days in [Place]: A [adjective] First-Time Itinerary` / `Two Weeks in Japan: A Route That Works`
   - Dossier: `[Place] Travel Guide: The Dossier` (practical)
   - Answer: the question, verbatim, ≤60 chars
   - Dispatch: free, but subject + place present
2. ≤ 60 characters (SEO title); H1 may be longer and is the piece's name.
3. Subject and place both present, in plain words.
4. None of: `(20xx)`, ULTIMATE, BEST/TOP in caps, INCREDIBLE, "look no further", "must-see", trailing `|` or `•`, double spaces, lowercase proper nouns.
5. One primary query, and no other live record owns it (duplicate check against the library).
6. The kicker (The Verdict / The Primer …) is **not** in the SEO title; it's in the H1 kicker line.

## The editorial process, locked

```
Context (notes · Granola · Proposal/Agency Studio · live link for updates)
  → Desk: format decision → draft in voice + pillar fields + opening block + SEO set
  → Desk: gates + voice pass on itself → job "drafted"
  → Worker: research (what ranks, AI questions) → fact pass (primary sources) → final SEO → "researched"
  → Coco: approve in the Queue
  → Worker: WordPress draft (or payload for Cowork) → "built" + preview
  → Coco: publish
  → Worker: library record created/updated · redirects if respin · question bank · benchmark set
  → Desk: social kit from the piece (carousel · caption · hashtags · first comment · DM keyword · Reels script)
  → Weekly: rankings → health → refresh jobs
```

No step is skipped; no piece goes live without a gate report on its job. The report, the research notes and the diff live on the job record, so "why did we write this" is always answerable.

## Where a Comparison lives (decided 24 Oct 2026)

- **Hotel vs hotel** → Property CPT, format *The Trade-Off* (kicker reads "The Comparison"). Both sides are bookable; the relationship fields point at the two Verdicts; the booking box sells the stay.
- **Place vs place** (Bora Bora vs Maldives, Amalfi vs Puglia) → Brief CPT, `brief_format` term **The Comparison** (new), ACF group `acf-comparison-brief.json`. Same eight sections (set-up, location, stays, food, days, price difference, who books which, verdict), no relationship fields, Article schema, URL `/brief/…`. The booking box still appears through the `[ctt_book]` shortcode naming the hotel each side would book.
- Rule for the worker: if either side is a destination, it is a Brief. Title lock is the same for both: `[A] vs [B]: Which Should You Choose?`
