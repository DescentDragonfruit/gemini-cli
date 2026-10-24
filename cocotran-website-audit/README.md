# cocotran.com — Website Audit & Action Plan

**Audited:** 24 October 2026 · **Site:** https://cocotran.com (WordPress · Kadence · Rank Math · LiteSpeed · Cloudflare)
**Scope:** SEO, AI-search readiness (Google AI Overviews, ChatGPT, Perplexity, Claude), technical health, editorial quality, membership/paywall strategy.

## What's in this folder

| File | What it is |
|---|---|
| `01-audit-findings.md` | Every finding, with evidence, severity and the fix. Merges the live-crawl audit with the five priority findings and SEO strategy from the second review. |
| `02-action-plan.md` | The plan: phased, with checkboxes, owner, effort and status. **This is the file to work from.** |
| `03-fixes/` | Ready-to-paste fixes. Each file maps to a checkbox in the plan. |
| `04-scripts/` | The crawl/measurement scripts used for the audit, plus `recheck.sh` to re-run them after changes. |
| `data/` | Raw crawl output: title/description inventory of all 201 live URLs, sitemap status, paywall/word-count audit of the 75 new pieces. |

## Ready-to-paste fixes (`03-fixes/`)

| File | Fixes plan item |
|---|---|
| `subscribe-faq-corrected.md` | 1.1 — corrected membership FAQ copy (removes the leaked editorial note, fixes the duplicated answer, aligns tier descriptions) |
| `kimpton-sawyer-intro-rewrite.md` | 1.2 — natural rewrite of the keyword-stuffed Kimpton Sawyer intro, usable as the model for all old-post intros |
| `meta-title-rewrites.csv` | 2.3 — proposed SEO title + meta description for every page whose current one is stuffed, truncated, placeholder or missing (~100 URLs) |
| `rank-math-settings.md` | 1.3–1.6 — click-by-click: sitemap coverage, noindex utility pages, Article/Review schema per content type, 404 behaviour, RSS feed |
| `schema-person-organization.json` | 2.1 — Person + Organization JSON-LD for the site identity (replaces the "Aesthetic Travel Blog & Lifestyle Blog" entity) |
| `paywall-structured-data.md` | 2.2 — `isAccessibleForFree` / `hasPart` markup so Google indexes gated text without a cloaking penalty |
| `llms.txt` | 2.4 — drop at site root |
| `about-page-trust-block.md` | 2.5 — "Why trust this" block + About title/description |
| `404-page.md` | 1.5 — copy for a real 404 page |
| `article-templates.md` | 3.x — AI-quotable structure for each content type (Verdict box, fact block, FAQ, dates) |
| `destination-hub-spec.md` | 3.x — destination hub architecture, Kyoto as the worked example, hub map for all current destinations |

## Added 24 Oct — second pass

| Folder / file | What it is |
|---|---|
| `05-editorial-system/publishing-checklist.md` | The ten pre-publication gates, the structured-data checklist per page type, the prompt Claude runs every draft through, the voice tests |
| `05-editorial-system/methodology-page.md` | Copy for `/how-we-review/` (link from every Property) |
| `05-editorial-system/about-page-v2.md` | About page rewrite with the who / did-she-visit / when / why-trust signals, plus the rollout rule for article pages |
| `05-editorial-system/eeat-rollout.md` | The answerable opening (Short answer · At a glance · byline · disclosure) designed for the site's typography, the ACF fields, one Kadence Element for all CPTs, the bulk-backfill procedure, and the go-forward workflow for Claude-assisted pieces |
| `05-editorial-system/ai-faq-question-bank.md` | The 20 seed questions, which piece owns each, the gap, and "The Answer" quick-article format |
| `05-editorial-system/citation-opportunities.md` | Verified (24 Oct 2026) status/cost of journalist-request services, travel-media channels, profile builders, podcast platforms, photo licensing; a weekly 60-minute routine; a pitch template; what not to do |
| `05-editorial-system/90-day-plan.md` | Days 1–14 / 15–45 / 46–90 and the four outcome metrics with a baseline table |
| `05-editorial-system/paywall-decision.md` | Free / Archive / Insider framework, recommended split, the qualifying questions — decision recorded once Coco answers |
| `06-wp-plugin/coco-photo-cms/` | **WordPress plugin**: resize + WebP/AVIF on upload, descriptive filenames from Property/Place/Detail fields, required alt + credit, rename-with-301, replace everywhere or only-here, usages finder, ImageObject schema, REST intake for the artifact, bulk tools. `php -l` clean; not yet run on a live site — install on staging first. README inside. |
| `07-ai-benchmark/` | `questions.md` (the fixed 20 + method) and dated run reports. Baseline 24 Oct: **0 of 20** cited. Re-runs on the 1st and 15th via the scheduled task. |
| `08-skills/coco-editorial-standards/SKILL.md` | The standards as a skill — add to the Coco Tran Collective project so every drafting session follows them |
| **Editorial Desk artifact** | https://claude.ai/artifact/NVykejaCwzyJDdN2G1NMQt — pre-publication desk, voice check, opening-block generator, answer finder, citation tracker, benchmark log |

## How to apply

The site is not Jetpack-connected to the WordPress.com account this session could see, so changes couldn't be pushed from here. Two routes:

1. **Apply by hand** in WP admin using the files above (each is written for copy-paste).
2. **Connect the site** (Jetpack → connect to the WordPress.com account that owns `hello@cocotran.com`, or an Application Password on the REST API) and the next session can apply the content and settings changes directly.

After each phase, run `04-scripts/recheck.sh` to confirm the technical items.

## Headline numbers from the crawl

- 201 live URLs; 228 URLs in the post sitemap of which **118 redirect and 5 error** — and **none of the 75 new Property/Guide/Brief/Dispatch/Shortlist pieces are in any sitemap**.
- **All 34 Guides and all 15 Briefs** show ~250–350 words to Googlebot/GPTBot/PerplexityBot/ClaudeBot before the paywall.
- Homepage: **24 MB** on mobile, LCP ~5.6 s. Property pages: CLS 0.64 (fail threshold 0.25).
- 75 new pieces carry **BreadcrumbList only** — no Article/Review schema, no author, no dates.
- `/feed/` returns 500. Unknown URLs 301 to the homepage (soft 404).
- Placeholder text live on `/editorial/` ("lorei ipsum", "Nullam quis risus…"), `/pricing-4/`, homepage description ("1 / 5").
- A leaked editorial note is live in the `/subscribe/` FAQ, including a duplicated answer on the wrong question.
- 49 titles over 60 characters; ~95 old-post titles use `(2026)`, `BEST`, `Ultimate`, trailing `|`.
