# Audit findings — cocotran.com

Date: 24 Oct 2026. Method: full crawl of every sitemap URL (status codes), on-page extraction of ~200 live URLs (title, description, H1, schema, word count, paywall, images/alt), bot-UA tests (Googlebot, GPTBot, PerplexityBot, ClaudeBot), mobile Playwright performance runs, REST API inventory of the five custom post types, manual read of key pages. Google PageSpeed API was over quota, so speed numbers are from a local mobile emulation; no Search Console access.

Severity: **P0** = losing traffic/trust today · **P1** = material SEO/AI-search gain · **P2** = quality/polish.

---

## A. Publishing errors visible to readers (P0)

### A1. Leaked editorial note in the membership FAQ — `/subscribe/`
Live text, verbatim:

> **Do the Insider hotel amenities apply if I book independently?**
> Almost certainly no — these read like perks tied to booking through Coco Tran Travel specifically, not something a member gets on a DIY booking. Worth stating outright so no one's surprised.
>
> **Can I switch Archive from monthly to annual billing?**
> Almost certainly no — these read like perks tied to booking through Coco Tran Travel specifically, not something a member gets on a DIY booking. Worth stating outright so no one's surprised.

The first answer is a drafting note ("these read like…", "worth stating outright") that was published as the answer. The second question has the first answer pasted under it, so the billing question is answered with text about hotel perks.

Other inconsistencies on the same page:
- "You will receive your client discount code **to for** your one year access" (typo).
- "**Quarterly** Private Intel arrives **three times a year**" (quarterly = four).
- "How is a membership different…" answer starts lowercase ("membership is the reporting…").
- The Reader tier card says "Receive access to all: The essays, the picks, the video intel" — it reads as if the free tier gets everything; the FAQ then says the Archive is what unlocks the full library. Needs one consistent description of what free gets.
- `/pricing-4/` is live and indexable with the description "The description of the tier list will go here, it should be concise and impactful." — a template placeholder.

Fix: `03-fixes/subscribe-faq-corrected.md`. Noindex or delete `/pricing-4/`.

### A2. Placeholder text live and indexable
- `/editorial/` — H1 **"lorei ipsum"**, meta description **"Nullam quis risus eget urna mollis ornare vel eu leo."**
- Homepage meta description is **"1 / 5"** (the slider counter was picked up as the description).
- `/destinations/` description: "All, Africa, Asia, Europe, North America, South America, Antarctica, Oceania 1 2 3Tab 4Tab 5Tab…"
- `/about/` title "About •", description "Our Philosophy". `/shop/` description "Products Archive •".
- `/login/`, `/register/`, `/password-reset/`, `/account/` all share the same description (copied from the Property pillar blurb) and are in the sitemap.

### A3. Keyword-stuffed old-post intros and titles
Example — `/kimpton-sawyer-hotel-sacramento/`, first 120 words:

> One of The Best Sacramento Hotel Downtown Sacramento california … Kimpton Sawyer Hotel Sacramento is one of the top luxury hotels in Sacramento's Downtown. Book your Stay at The Kimpton Saywer Hotel Now! #1 TOP SACRAMENTO…

Title: "Staying At The Kimpton Sawyer Hotel Sacramento (2026) The Kimpton Sawyer Hotel Review" (87 chars).

This pattern runs through most of the 105 still-live old posts: `(2026)` prefix (sometimes broken as `()` or `( 2026)`), BEST/ULTIMATE/INCREDIBLE in caps, trailing `|`, hotel/place name repeated 3–5 times in the first paragraph, "lol" asides. It is the opposite register from the new Verdict/Rolodex pieces and it is what Google and AI engines will read when they land on the old URLs.

Fix: `03-fixes/kimpton-sawyer-intro-rewrite.md` (model) and `03-fixes/meta-title-rewrites.csv` (all titles).

### A4. Typos / inconsistent naming on new pieces
- `/property/belmond-cap-jaluca-review/` — slug misspells Juluca; title "The Property:  belmond cap juluca, anguilla" (lowercase, double space).
- "Trip **Lenght**" label on every Brief (template field).
- Brief "Da Nang —" title is truncated.
- "Menton, FRANCE"; "POSTCARDS FROM: CAP JULUCA ANGUILLA" (caps).
- Format labels vary: "The Verdict" vs "The Property"; "Postcard From" / "Postcards From:" / "Field Notes From:"; "The Rolodex" vs "The Roundup" vs "The Primer" within Guides. Pick one label per content type and use it in the H1 kicker, the title tag and the card.

---

## B. Indexing & crawlability (P0)

### B1. Sitemap excludes all new content and includes dead weight
`sitemap_index.xml` (Rank Math) lists post, page, product, category, product_cat, author. It does **not** list property, guide, brief, dispatch or shortlist — the 75 pieces that are now the publication. `/property-sitemap.xml` etc. 301 to the homepage.

Meanwhile `post-sitemap.xml` has 228 URLs: 105 live, **118 are 301 redirects** to the new pieces, **5 return 500** on first request. The page sitemap includes `/login/`, `/register/`, `/password-reset/`, `/account/`, `/cart/`, `/checkout/`, `/pricing-4/`.

Impact: Google is discovering the new pieces only via internal links, and wasting crawl budget on redirects. This is the single most likely reason the new content under-performs.

### B2. 118 old posts redirected into pages that show Google ~300 words
Redirect targets: guide 75, brief 23, dispatch 10, shortlist 8, property 2. Several of the redirected posts were long (3,000–6,000 words) and the kind that rank. Their replacements expose ~257 words (guides) / ~348 words (briefs) before the wall, to every bot tested. Google will re-evaluate those URLs as thin and rankings will drop. See D for the paywall strategy.

### B3. `/feed/` returns HTTP 500 ("No feed available")
Kills Feedly/RSS readers, Google Discover/News eligibility signals, Pinterest/Zapier/newsletter automations.

### B4. Unknown URLs 301 to the homepage (soft 404)
`/this-page-does-not-exist/` → 301 → `/`. Google flags these as soft 404s; also masks real broken links. Return a proper 404 (`03-fixes/404-page.md`).

### B5. Server returns 500/429 under light concurrent load
16 parallel requests produced five 500s and one 429 on first attempt (all recovered on retry). Googlebot crawls in parallel; intermittent 5xx slows crawl rate. Worth a ticket to the host / check LiteSpeed crawler and Cloudflare rate-limit rules.

### B6. robots.txt blocks AhrefsBot only
Fine. No AI crawlers blocked (good for AI-search visibility). No `Sitemap:` line — add one.

---

## C. Structured data & entity (P1)

### C1. New content types carry BreadcrumbList only
Old posts have `BlogPosting` + `Person` + dates. The 75 new pieces have **no** Article/Review schema, **no** author, **no** datePublished/dateModified. Rank Math's per-post-type schema default was never set for the CPTs. Fix in `03-fixes/rank-math-settings.md`.

### C2. Site identity schema is stale and wrong-typed
```
@type: [Organization, Person]
name: "Coco Tran — Coco Tran — Aesthetic Travel Blog &amp; Lifestyle Blog"
WebSite.name: "Travel Blog with Curated Travel Guides and Itineraries | Lifestyle & Style Journal by Coco Tran"
Person (author): name "Coco", image = Gravatar
```
No `sameAs` (Instagram ×2, YouTube, cocotrantravel.com, cocotranstudio.com), no `jobTitle`, no `knowsAbout`. AI engines resolve "who is this and why trust them" from exactly this. Replacement in `03-fixes/schema-person-organization.json`.

### C3. No paywall markup
Gated pages serve the members wall to Googlebot with no `isAccessibleForFree:false` / `hasPart` declaration. Without it Google can't index the gated text; with it (and the full text in the HTML), it can, and the lead-in gets flagged as paywalled rather than thin. `03-fixes/paywall-structured-data.md`.

### C4. No `llms.txt`
`/llms.txt` redirects to the homepage. Low effort, low-but-nonzero value. `03-fixes/llms.txt`.

---

## D. Membership / paywall strategy (P0–P1)

Current state (what a non-member and every bot sees):

| Type | Pieces | Paywalled | Avg visible words |
|---|---|---|---|
| Guide (Primer/Rolodex/Roundup) | 34 | 34 | 257 |
| Brief (Route/Dossier) | 15 | 15 | 348 |
| Property (Verdict) | 6 | 1 | 1,584 |
| Shortlist | 5 | 0 | 942 |
| Dispatch | 15 | 0 | 572 |

Problems:
1. The **Primers** ("read this before you book anything") are the natural top-of-funnel, question-answering pages — the ones AI engines would quote — and they're the most tightly gated.
2. The wall arrives mid-sentence with no summary, no table of contents, no sense of what's behind it. A reader can't tell whether the gated part is 800 words or 8,000.
3. The FAQ promises "nothing metered, nothing paywalled" but the public site doesn't show a single complete example of the paid reporting.
4. Google's guidance: either show the full text to Googlebot with paywall markup (indexed, labelled), or accept that gated pages rank on the visible portion only. Right now it's the worst of both.

Recommendation (detail in `02-action-plan.md` phase 2):
- **Primers → fully free.** They are the acquisition layer and the AI-citation layer.
- **Rolodex / Roundup → long free lead** (800–1,200 words: intro + first neighbourhood/section + the "how to use this" scaffolding), gate the detailed rolodex entries.
- **Properties (Verdict) → free summary box** (the verdict, who it's for, price context, room to book) + gate the room-by-room detail. Five of six are already fully free; be consistent.
- **Briefs (Routes/Dossiers) → gated**, but with a free "The ask / The shape of the trip" block plus a day-by-day table of contents.
- Add paywall markup everywhere a wall exists.
- Publish 2–3 **sample paid pieces** in full, labelled as samples, and link them from `/subscribe/`.

---

## E. Performance & mobile (P1)

Mobile emulation (390×844, Playwright, cold cache):

| Page | LCP | CLS | Requests | Transfer |
|---|---|---|---|---|
| Homepage | 5.6 s | 0.00 | 137 | **24.1 MB** |
| /property/caruso-ravello/ | 2.3 s | **0.64** | 66 | 1.3 MB |
| /guide/kyoto-guide/ | 2.4 s | 0.11 | 66 | 1.3 MB |

Causes:
- Homepage hero slider loads **ten 1.2–2.1 MB full-size JPEGs** (e.g. `Amalfi-Coast-…-08.jpg` at 2.12 MB). The theme's `srcset`/WebP isn't being applied to slider images.
- New-piece images are original JPGs (0 of 20 WebP on Caruso) while old posts serve WebP — the image optimiser isn't processing uploads from July 2025 onward, or the Kadence gallery block bypasses it.
- 300–660 KB of **inline CSS per page** (Kadence block styles + Rank Math + membership plugin). 75–144 `<script>` tags per page.
- CLS 0.64 on Property pages: hero image and the "The Verdict —" headline stack have no reserved height; the membership bar injects late.
- Mobile homepage: cookie banner + sticky subscribe bar together cover the hero; on the Caruso page the white logo sits on a white header (invisible) and the H1 fills the whole first viewport with no image.

---

## F. On-page SEO inventory (P1)

From `data/meta_inventory.csv` (201 URLs):
- 49 titles > 60 chars (will truncate). 15 descriptions > 160 chars.
- ~95 old-post titles use the `(2026)` / BEST / Ultimate / trailing `|` pattern (full list + rewrites in `03-fixes/meta-title-rewrites.csv`).
- 3 missing descriptions (`/checkout/`, `/cart/`, `/brief/paris-the-dossier/`), 1 missing title (`/checkout/`).
- Duplicate descriptions: the four account pages; privacy + terms ("Effective Date: 01/01/2026").
- **No H1** on all 15 Briefs, all 5 Shortlists, and `/about/`, `/subscribe/`, `/destinations/`, `/contact/`. The Brief/Shortlist templates render the title as a styled `<p>`/`<h2>`.
- Property and Dispatch templates emit **two H1s** ("The Verdict —" + the name). Make the kicker a `<p>` or `<span>`; one H1 = the piece's name.
- 4–5 images per page with empty `alt` (template/decorative images — acceptable if truly decorative, but the hero images on new pieces also lack alt).
- Internal links: new pieces link to the Primer/Rolodex sibling in prose ("is in the Primer") but there's no consistent "Also in [destination]" block, and old posts don't link forward to their new-format siblings.

### Duplicate / competing coverage
Topics with more than one live page competing for the same query:
- Mexico City hotels: `/where-to-stay-in-mexico-city/` (old, 4,451 words, live) vs `/guide/mexico-city-hotels/` (new).
- Lake Como: `/guide/lake-como-guide/` (Roundup) + `/guide/lake-como-planning-guide/` (Rolodex) + `/brief/lake-como-the-dossier/`.
- Hanoi: `/guide/things-to-do-in-hanoi/` (Roundup) + `/guide/hanoi-city-guide/` (Rolodex) + `/brief/hanoi-the-dossier/` + two Shortlists.
- Santorini: old `/travel-to-santorini-greece-santorini-travel-guide/` + `/guide/santorini-primer/` + `/guide/santorini-guide/` + `/brief/santorini-dossier/`.
- Tokyo/Fuji day trips: `/tokyo-to-mount-fuji-day-trip-guide/` + `/fuji-day-trip-from-tokyo/` (both old, both live).
- Sequoia: 12 old posts on one park.

This isn't necessarily bad — a hub with Primer + Rolodex + Route is the intended system — but each piece needs a distinct primary query, a distinct title, and cross-links, or Google picks one at random. See `03-fixes/destination-hub-spec.md`.

---

## G. Authority / E-E-A-T / AI-search (P1)

- About page is well written but states **no credentials**: no advisor affiliation (host agency / consortium — only what's true), no years, no count of properties stayed, no "how we review" method, no disclosure of comped vs paid stays. AI engines and Google's reviews system weigh exactly this. `03-fixes/about-page-trust-block.md`.
- **cocotranstudio.com and the YouTube channel are not linked** from the site (YouTube appears only as embeds; Instagram links to `cocotrantravelco` only). The entity graph across your properties is disconnected.
- No visible **"Stayed: [month year] · Updated: [date]"** on Property pieces; no dateModified anywhere on new pieces.
- No **verdict summary** at the top of reviews; no **FAQ** on Primers; one comparison piece (FS Anguilla vs Cap Juluca) — the format AI engines quote most.
- Author byline is "Photos & words by Coco" linking to `/author/hellococotran-com/` — the author page exists but has no bio, credentials or sameAs.

---

## H. Search strategy (from the second review, adopted)

Four content groups, in priority order, each mapped to the existing formats:

1. **Independent hotel reviews** → The Verdict. High commercial intent. Query shapes: "[hotel] review", "is [hotel] worth it", "[hotel A] vs [hotel B]".
2. **Destination planning guides** → The Primer + The Route. Evergreen. "Kyoto first time guide", "4 days in Tokyo itinerary", "where to stay in Santorini".
3. **Curated shortlists and comparisons** → The Shortlist + Rolodex. Decision support. "best boutique hotels [place] couples", "where to stay Amalfi without a car". Explain the differences, don't just list.
4. **Specialist travel intelligence** → The Dispatch + Dossier. Differentiation. Logistics, seasons, "what to know before booking X". This is where advisor + photographer is unique.

Rule for every piece: one primary search intent; title, H1, first paragraph, description and internal anchors all carry it. Write for the discerning traveller first, make the subject unambiguous for Google second.

Proposed title examples (final wording to be checked against Search Console queries):

| Piece | Proposed title |
|---|---|
| Kimpton Sawyer review | Kimpton Sawyer Hotel Review: A Stay in Downtown Sacramento |
| FS Anguilla vs Cap Juluca | Four Seasons Anguilla vs. Cap Juluca: Which Should You Choose? |
| Four Days in Tokyo | 4 Days in Tokyo: A Thoughtful First-Time Itinerary |
| Kyoto Primer | Kyoto Travel Guide: Where to Stay, Eat, and Explore |
| Santorini Dossier | Santorini Travel Guide: Where to Stay and What to Know |

Full list in `03-fixes/meta-title-rewrites.csv`.

---

## What's already good (keep)

- First-hand specificity ("room 312", "half past six at Fushimi Inari") — exactly what quality raters and AI engines reward.
- Titles/descriptions on the new pieces are strong where they've been written by hand.
- Old → new redirects are clean; no 404s found among sitemap URLs.
- HTTPS, canonicals, `max-image-preview:large`, no AI crawlers blocked, Cloudflare + LiteSpeed caching (TTFB ~150 ms).
- The five-format editorial system is the right shape for a destination-hub architecture — it just needs the hubs and the links.
