# Proposed changes to the pillar structure and the CPTs

What exists works; these are the additions the content system needs. All are ACF field groups or taxonomy terms — no new CPT is required except possibly §4.

## 1. ACF group "Answerable opening" (all five CPTs + posts) — needed now
`short_answer` (textarea) · `glance_1_label`…`glance_5_value` (text ×10) · `visited` (text) · `visit_basis` (text) · `updated_on` (date) · `disclosure` (select) · `advisor_roundup` (true/false). One Kadence Element renders it under the title on every template. (`eeat-rollout.md`.)

## 2. ACF group "Content system" (all five CPTs + posts) — needed now
So the library lives in WordPress, not only in the desk:
`content_category` (select: Commercial / Decision-support / Informational / Specialist intel / Authority) · `content_goal` (text) · `primary_keyword` (text) · `secondary_keywords` (textarea) · `ai_questions` (textarea, one per line) · `health` (select: ok / decaying / stale / thin / duplicate) · `last_fact_check` (date) · `social_kit_made` (true/false) · `hub` (post object → the destination hub page).
Show in REST. The worker writes these; Rank Math reads `primary_keyword` as the focus keyword via a small filter.

## 3. Taxonomy "format" normalised across CPTs
Today each CPT carries its own format taxonomy (`dispatches-formats`, `the-guide-format`, `brief_format`) with different term shapes. Keep them, but add the missing terms so every format has one canonical name:
- The Guide: **The Primer**, **The Rolodex**, **The Roundup** (exists)
- The Brief: **The Route**, **The Dossier** (exists)
- The Dispatch: **Postcard From**, **Field Notes From**, **The Take** (exist) + **The Answer** (new — the one-question piece; same template as Field Notes with the question as the two-line title and a FAQPage schema flag)
- The Property: **The Verdict**, **The Comparison** (exists as "The Tradeoff" — rename the label to The Comparison, keep the slug)
Kicker labels in templates read from the term name, so every piece prints the same label everywhere.

## 4. Destination hubs — a page type, not a CPT
`/destinations/[place]/` as Pages (parent: Destinations) with one Kadence Advanced Page template and Query Loop blocks per format filtered on the `destination` taxonomy (`destination-hub-spec.md`). Prerequisite (flagged in the respin skill): register the `destination` taxonomy for `post` too and backfill, so old posts appear in hubs and the current `/destinations/` tabs stop depending on old WP categories.

## 5. The Comparison — give it real fields
The Tradeoff template (Element 29436) should carry: `the_short_answer` · `property_a_name` · `property_a` · `property_b_name` · `property_b` · `the_tradeoff` (beach / rooms / food / service / price as `<h4>` blocks) · `who_books_which` · `last_thoughts` · `stayed_both` (true/false). It's the format AI engines quote most; it deserves structure.

## 6. "The Planning Room" fields on The Route
`trip_file_maps_url` (Google Maps list) · `trip_file_sheet_url` (the booking-order sheet) · `trip_file_pdf` (file) — the paid deliverables the gate protects. Shown to members only, below the day-by-day.

## 7. Booking box (Kadence Element, all hotel contexts)
Not ACF — a reusable Element with a condition on CPT = property, plus a shortcode for Rolodex/Shortlist hotel mentions: `[ctt_book hotel="Caruso, A Belmond Hotel" perks="upgrade, breakfast, $100 credit"]`. Posts to cocotrantravel.com/contact with hotel + source URL as hidden fields.

## 8. Social kit (optional, later)
`social_hook` · `carousel_slides` (repeater) · `caption` · `hashtags` · `first_comment` · `dm_keyword` — only if Coco wants the kit stored on the post rather than in the desk. Until then the desk's `social` collection holds it.

## Order
1 and 2 this week (two field groups, ~30 min in ACF). 3 when the next Answer piece is written. 7 with the conversion plan. 4 with the first hub. 5 and 6 when the next Comparison/Route is built. 8 later.
