# Coco Tran — Publishing checklist, structured-data checklist and the pre-publication prompt

Every piece — new, respun, or Claude-drafted — goes through this before "Publish". The Editorial Desk artifact runs it interactively; this file is the source of truth.

## Part 1 · The editorial pre-publication checklist (10 gates)

| # | Gate | Pass means |
|---|---|---|
| 1 | **Headline says what the story covers** | A stranger could tell the subject, place and format from the title alone. SEO title ≤ 60 chars; H1 = the piece's name (kicker is not the H1). No "(2026)", "ultimate", BEST in caps, "look no further". |
| 2 | **Opening establishes destination, subject and value** | First paragraph names the place and the thing in plain words. A "Short answer" block (2–3 sentences) sits above or just below the lede. |
| 3 | **Proofread — grammar, repetition, SEO phrasing** | No doubled words, no keyword repeated more than once per paragraph, no stray drafting notes ("these read like…", "worth stating…"), no placeholder text, no "Lenght". |
| 4 | **Firsthand vs sourced is distinguishable** | What Coco saw/ate/paid is in first person. Anything from a hotel, press kit or another outlet is attributed ("the hotel says", "according to…"). Nothing invented. |
| 5 | **Changeable facts verified** | Dates, prices, opening hours, closures, rebrands, visa rules checked against a primary source in the last 30 days — or left out. Rate context stated as a range with the month. |
| 6 | **Original photographs, accurate captions and alt** | Her own photos (or "courtesy of the hotel", credited). Filenames descriptive. Alt text describes the actual image — no keyword lists. Credit present. |
| 7 | **Related stories and the destination hub are linked** | "Also in [destination]" block present; Primer ↔ Rolodex ↔ Route ↔ Properties cross-linked; hub linked (once hubs exist). Anchor text descriptive. |
| 8 | **Author, published date, genuine updated date correct** | Byline "Coco Tran". Visited/Stayed month-year. "Updated" only when something changed. Schema dates match the visible ones. |
| 9 | **Disclosures clear** | Paid / hosted / discounted stated on every Property. Affiliate and Coco Tran Travel booking relationship stated where relevant. Methodology page linked from every Property. |
| 10 | **Free portion delivers value before the gate** | A non-member leaves with the short answer, the at-a-glance facts and a reason to subscribe. The gate never lands mid-sentence. Gated pages carry paywall markup. |

**Voice gates (apply to 1–10):** first person, speaking to "you"; positive, warm, excited — never clipped or cold; concrete over clever; no sentence that explains the sentence before it ("That's the point."); banned words absent (breathtaking, seamless, meticulously curated, world-class, stunning, wanderlust, elevated-as-adjective, 6-star); no italics in body HTML; no mid-article sales pitch (one Inquire/CTA near the end only, where the format allows it); private/escorted is the default recommendation, top-tier hotels only, $800+/night floor on Properties.

## Part 2 · Structured-data checklist by page type

Verify on a representative page of each type after any template change (Rich Results Test or `04-scripts/onpage.py`). Only emit what is true and visible on the page; never add ratings, stars or review markup to chase rich results.

| Page type | Required markup | Must agree with visible content |
|---|---|---|
| **Property (The Verdict / The Comparison)** | `Article` (or `Review` with `itemReviewed: Hotel {name, address, url}` — **no `reviewRating`** unless Coco decides to show stars site-wide), `BreadcrumbList`, `Person` author ref, `isAccessibleForFree`+`hasPart` if gated, `ImageObject` for hero | headline = H1; author = byline; datePublished = visible published date; dateModified = visible "Updated"; image = the hero actually on the page; hotel name/address = what the piece says |
| **Guide (Primer / Rolodex / Roundup)** | `Article`, `BreadcrumbList`, `Person`, `FAQPage` **only** when a visible FAQ block exists, paywall markup if gated | same; FAQ questions/answers verbatim from the page |
| **Brief (Route / Dossier)** | `Article`, `BreadcrumbList`, `Person`, paywall markup | same |
| **Shortlist** | `Article` + optional `ItemList` of the picks (names + URLs), `BreadcrumbList`, `Person` | list items = the picks on the page, in order |
| **Dispatch** | `Article`, `BreadcrumbList`, `Person` | same |
| **Old blog posts** | `Article` (change from `BlogPosting`), `Person` | same |
| **Author page `/author/…/`** | `Person` with `jobTitle`, `sameAs` (both Instagrams, YouTube, cocotrantravel.com, cocotranstudio.com), `image`, `knowsAbout` | bio text on the page says the same things |
| **Homepage** | `WebSite` (name "Coco Tran", SearchAction), `Organization` (publisher) + `Person` (founder) from `03-fixes/schema-person-organization.json` | site title, logo, links in footer |
| **Destination hubs** | `CollectionPage`, `BreadcrumbList` | hub lists what the page lists |
| **Methodology / About / Disclosures** | `WebPage` (`AboutPage` for About), link `publishingPrinciples` from Organization to the methodology page | — |
| **Images (all)** | `ImageObject` for hero + first 6 (plugin does this): `creator` Coco Tran, `creditText`, `copyrightNotice`, `license` URL, `contentLocation` | alt/caption on the page describe the same image |

Rank Math handles Article/Review/Breadcrumb/Person once set per post type (`03-fixes/rank-math-settings.md`); paywall markup from `03-fixes/paywall-structured-data.md`; images from the Coco Photo CMS plugin.

**Don't:** add `AggregateRating`, star `reviewRating`, `Hotel` as the page's main entity, `Event`, or `Product` markup; mark pages `isAccessibleForFree:false` that are fully free; put `FAQPage` on pages without a visible FAQ.

## Part 3 · The pre-publication prompt (paste into Claude with the draft)

```
You are the Coco Tran editorial desk. Run this draft through the ten publishing gates and the voice gates in publishing-checklist.md. For each gate answer PASS / FIX / NEEDS COCO with one line of evidence (quote the offending phrase for FIX).

Then:
1. VOICE: list every sentence that (a) explains the sentence before it, (b) uses a banned word, (c) reads cold or clipped, (d) is third-person or listicle, (e) contains a drafting note or placeholder. Give the rewrite for each in Coco's voice: first person, warm, excited, concrete, told like she's telling a friend after the fact.
2. ANSWERABILITY: write the "Short answer" (2–3 sentences: what it is, where, what's distinctive, who it suits) and the "At a glance" facts (location/setting · best suited to · standout · consider before booking · visited month-year) using only facts present in the draft. Mark any fact you had to leave blank.
3. FACTS: list every changeable claim (price, date, hours, closure, rebrand, visa) with a VERIFY flag.
4. SEO: propose the SEO title (≤60), meta description (≤155), H1, slug, and primary query. Propose 3–5 H2s that contain the subject + place.
5. LINKS: list the Primer/Rolodex/Route/Property/Shortlist/hub pieces this should link to.
6. SCHEMA: state which markup this page type needs (from Part 2) and which fields must match the visible content.
7. DISCLOSURE: state the paid/hosted/affiliate line to show.
Output in that order. Do not rewrite the body unless asked; keep every fact and every verdict.
```

## Part 4 · Voice verification — the test sentences

Run any paragraph against these; one failure = rewrite that paragraph.

- Does it sound like Coco telling you about it the day after? (Not a camera panning, not a brochure.)
- Is there a sentence that comments on the previous sentence instead of giving the next concrete thing? Cut it.
- Would she say "honestly", "I have to shout out…", "which I didn't realise until…"? Those are fine. "That's the point." is not.
- Is the warmth there? If a verdict reads clipped ("Three nights. One shore."), let it breathe.
- Is anything negative sour? Reframe as charm or handle lightly, keep the fact.
- Is the recommendation private/escorted/top-tier by default, with the DIY option as a genuine secondary?
- Is there one named person, dish, designer or room? Specifics beat adjectives.
