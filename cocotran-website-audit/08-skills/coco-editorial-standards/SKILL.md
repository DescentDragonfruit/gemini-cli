---
name: coco-editorial-standards
description: Publishing standards for cocotran.com — the answerable-opening block, the ten pre-publication gates, structured-data rules, the AI "The Answer" format and the house-voice tests. Use whenever drafting, respinning, reviewing or publishing any Coco Tran piece (Verdict, Primer, Rolodex, Route, Dossier, Shortlist, Dispatch, The Answer), alongside property-review-writer and respin-old-content.
---

# Coco Tran editorial standards

Companion to `property-review-writer` (how to write a Verdict) and `respin-old-content` (how to migrate old posts). This skill is what every piece must satisfy before Coco clicks Publish, and how each piece becomes quotable by Google and AI answer engines without losing the voice.

Source of truth in the repo: `cocotran-website-audit/05-editorial-system/` (publishing-checklist.md, eeat-rollout.md, ai-faq-question-bank.md). Interactive version: the **Coco Tran Editorial Desk** artifact (https://claude.ai/artifact/NVykejaCwzyJDdN2G1NMQt).

## 1. Every piece opens answerably

Between the H1 and the first image, every piece carries, in the site's own typography (no boxes, hairlines only, the brand accent as a 2px left rule on the short answer):

1. **Byline strip** — "Photos & words by Coco Tran · Stayed/Visited [month year] · Updated [date]".
2. **The short answer** — 2–3 sentences, first person, in Coco's voice: what it is, where, what's distinctive, who it suits. Must stand alone when quoted.
3. **At a glance** — five label/value rows, per format (Verdict: Setting · Best for · Standout · Consider · Stayed; Primer: Best months · How long · Base yourself in · Book first · Last visit; Rolodex: Base in · Stay · Book ahead · Don't miss · Last visit; Route: Nights · Who · Season · Stops · Transport; Dossier: Visa · Money · Best months · Getting around · Safety note; Shortlist: Top pick · Second · Wildcard · Price band · Last visit).
4. **Disclosure + method line** — "Paid stay / Hosted stay / Advisor roundup (not a stay)… Hotels here can be booked through Coco Tran Travel. How we review →".

ACF fields: `short_answer`, `glance_1_label`…`glance_5_value`, `visited`, `visit_basis`, `updated_on`, `disclosure`, `advisor_roundup`. Generate them **only from facts in the piece**; write "(blank — Coco to fill)" otherwise. Coco supplies visited/basis/disclosure — never infer them.

The narrative below the block is untouched: MOMENT opening, the fields in order, the photography, the gate.

## 2. The ten gates (all must pass)

1 Headline says what the story covers (SEO title ≤60, one H1, no "(2026)"/ultimate/BEST). 2 Opening names place + subject; short answer present. 3 Proofread — no drafting notes, placeholders, keyword chains, "Lenght". 4 Firsthand vs sourced distinguishable; nothing invented. 5 Changeable facts verified ≤30 days or omitted. 6 Her photos (or credited), descriptive filenames, accurate alt, credit. 7 Also-in block + Primer↔Rolodex↔Route↔Property links + hub. 8 Byline Coco Tran; visited; Updated only when changed; schema dates match. 9 Disclosures: paid/hosted, affiliate, CTT relationship, How-we-review link. 10 Free portion delivers the short answer + glance before the gate; gate never mid-sentence; paywall markup on gated pages.

## 3. Voice tests (fail one → rewrite the paragraph)

- Sounds like Coco telling you the day after, not a camera panning or a brochure.
- No sentence that comments on the previous one ("That's the point." "…doesn't announce itself so much as…"). Give the next concrete thing instead.
- Warm, positive, excited; let verdicts breathe. Never clipped ("Three nights. One shore."). Never cold or matter-of-fact. This holds for everything written in Coco's name: pieces, site pages (About, How we review), emails to readers and GMs, captions, replies. Professional and on brand always; the warmth is the brand.
- **Coco's own voice sheet wins.** Before drafting anything, read `voice/rules` in the Studio Desk store (ArtifactData get, url https://claude.ai/artifact/NVykejaCwzyJDdN2G1NMQt, collection `voice`, doc `rules`). If `text` is non-empty it replaces the house voice block; `voice/allow` lists phrasings never to flag. Mirror any change to `05-editorial-system/voice.md` and commit it.
- First person to "you". Never listicle, never third-person detached.
- Negatives reframed as charm or handled lightly; keep the fact.
- Private/escorted default; top-tier hotels; $800+/night floor on Properties.
- Banned: breathtaking, seamless, meticulously curated, world-class, stunning, wanderlust, elevated (adj.), 6-star, hidden gem, look no further, ultimate, must-see, nestled. No italics in body HTML. No mid-piece CTA.
- One named person/dish/designer/room number per piece.

## 4. Structured data (emit only what's visible; never chase rich results)

Property → Review (itemReviewed Hotel, **no reviewRating**) or Article; Guides/Briefs/Shortlists/Dispatches → Article; FAQPage only with a visible FAQ; ItemList optional on Shortlists; BreadcrumbList + Person everywhere; `isAccessibleForFree:false` + `hasPart{cssSelector:".members-only"}` on gated pages only; ImageObject (creator Coco Tran, creditText, license, contentLocation) for hero + first 6 images. headline/author/datePublished/dateModified/image must equal what's on the page. Never AggregateRating, stars, Hotel as main entity, Event, Product.

## 5. "The Answer" (Dispatch sub-format, free, 350–600 words)

H1 = the question verbatim as people ask AI → Short answer (2–3 sentences, standalone) → Why (150–300 words, one firsthand detail) → If you're choosing (A if / B if) → Go deeper (links) → Visited · Updated · disclosure. Article + FAQPage with that one Q/A. One question per piece; only facts from Coco's notes; everything else `[VERIFY]`. Seed questions and the "how to find more" method are in `ai-faq-question-bank.md`.

## 6. The pre-publication prompt

Run every draft through Part 3 of `publishing-checklist.md` (or the Editorial Desk): PASS/FIX/NEEDS COCO per gate → voice flags with rewrites → short answer + glance → facts to verify → SEO set → links → schema fields → disclosure line. Do not rewrite the body unless asked; keep every fact and every verdict.

## 7. After publish

Redirects (Rank Math → Redirections → Add New, never the updateRedirection endpoint), trash old posts, clear cache, remind Coco to purge Cloudflare, run `04-scripts/recheck.sh` on the URL, add any new question the piece answers to the question bank and benchmark set.
