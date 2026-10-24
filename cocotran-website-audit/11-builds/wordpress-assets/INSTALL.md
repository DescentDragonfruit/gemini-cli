# Install — WordPress assets for cocotran.com (≈15 minutes)

Everything in this folder is import-ready. Do it in this order; each step depends on the one before. Nothing here edits an existing template or post.

| # | Minutes | Step | File(s) |
|---|---|---|---|
| 1 | 3 | **ACF field groups.** WP admin → **ACF → Tools → Import Field Groups** → choose file → Import. Three times. The groups appear on Properties, Guides, Briefs, Dispatches, Shortlists and Posts ("Answerable opening" above the editor, "Content system" below it, "Booking box" in the side panel on Properties). All are `show_in_rest`, so the worker and the Editorial Desk can write them over `/wp/v2/{cpt}/{id}` with `{acf:{…}}`. | `acf-answerable-opening.json`, `acf-content-system.json`, `acf-booking-access.json` |
| 2 | 2 | **mu-plugins.** Copy both PHP files to `wp-content/mu-plugins/` (create the folder if it is not there). Must-use plugins load automatically; there is nothing to activate and nothing appears under Plugins → Installed except a "Must-Use" tab. Check the site still loads. | `booking-box-shortcode.php`, `paywall-schema-snippet.php` |
| 3 | 4 | **Kadence Element: Answerable opening.** Appearance → Kadence → Elements → Add New → Code editor → paste → hook `Single Content: Before`, priority 5, Show on the five CPTs + Posts → Publish. Full clicks and the "under the H1" hook note in its README. | `kadence-element-answerable-opening.html` + README |
| 4 | 3 | **Kadence Element: Booking box.** Same route; hook `Single Content: After`, Show on Properties only. Then set *Booking access* on each of the 6 Properties. | `kadence-element-booking-box.html` + README |
| 5 | 3 | **Rank Math titles.** Upload `rank-math-title-import.csv` and `resolve-ids.php` to the server, then `wp eval-file resolve-ids.php rank-math-title-import.csv --apply --dry-run` to see the changes, and again without `--dry-run` to write them (`rank_math_title`, `rank_math_description`, `rank_math_focus_keyword`). No WP-CLI? Put both files beside `wp-load.php` and run `php resolve-ids.php rank-math-title-import.csv --apply`. The script also writes `rank-math-title-import.rankmath.csv` in Rank Math's own CSV-import column layout if you prefer Rank Math Pro → Status & Tools → Import & Export → CSV. Delete `resolve-ids.php` from the server afterwards. | `rank-math-title-import.csv`, `resolve-ids.php` |
| 6 | 1 | **Cache.** Admin bar → **Delete all Cache** (SpeedyCache), then **Cloudflare → Caching → Purge Everything** — Cloudflare is manual, every time. Keep SpeedyCache `logged_in_user` **off**. | — |

## Then check (2 minutes)
- A Property with the fields filled: block under the title, "Stayed … · Updated …" byline, five rows, disclosure line with the How we review link; booking box under the content, button URL contains `hotel=` and `source=`.
- A Property with the fields **empty**: no block at all (the script hides it until backfilled).
- A Brief: view source → the Rank Math `application/ld+json` Article now carries `"isAccessibleForFree": false` and `hasPart.cssSelector: ".members-only"`. A Guide or Dispatch does **not** (they are free; paywall markup on free pages is a policy violation). The one free sample Route: add post meta `ct_gated = 0`.
- Rich Results Test on one Brief and one Property.
- `dateModified` equals `updated_on` where set.

## Notes
- The paywall markup only works if the gated HTML is actually in the page for Googlebot, wrapped in `.members-only` (`03-fixes/paywall-structured-data.md` §1 and §3). The markup without the HTML does nothing; the HTML without the markup is cloaking.
- `rank-math-title-import.csv` has 110 rows (5 source rows with no proposed title were skipped). `focus_keyword` is derived from the proposed title (the text before the first colon or dash) — skim it before `--apply`; it only affects Rank Math's on-page score, not output. `post_type` is a guess from the URL path and is corrected from WordPress by the resolver. Old-post URLs that have already been 301'd and trashed will come back as "Could not resolve" — that is expected; their replacements already carry their own Rank Math titles.
- Pillar-structure §2 also wants Rank Math to read `primary_keyword` as the focus keyword. Not included here; it is a one-line `rank_math/frontend/focus_keyword`-style filter or a sync on `acf/save_post` — add once the Content system group is in use.
- Edit Elements with string split/join, never regex (Kadence block comments break under regex replace).

## Reader questions — "Ask Coco about this place" (Tally form Nqpavl)

Live form: https://tally.so/r/Nqpavl · edit: https://tally.so/forms/Nqpavl/edit. Every submission is emailed to hello@cocotran.com (Tally's default notification; custom subject/body needs Tally Pro).

1. Kadence → Elements → Add New → Content Section. Hook **After content** (single), display on post types **Property** and **Guide** (add Brief if you want it there too).
2. Add a **Custom HTML** block and paste `reader-question-embed.html`. It loads Tally only when the block scrolls into view and sends two hidden fields with each question: `page_url` (the page) and `piece` (the title).
3. Publish the Element, open any Verdict, scroll to the bottom, send yourself a test question. It should arrive by email and appear in the Studio Desk → Reader questions → **Load questions** (the desk calls Tally with your login; allow it the first time).
4. In the desk, **Answer this** prefills a new Answer piece from the question; **Queue for the worker** hands it to the scheduled worker.

### Also on the Planning Room / subscribe page
Add the same `reader-question-embed.html` block to the Planning Room sign-up page (Kadence Element display rule: Page → /subscribe/ and the Planning Room landing page) with the heading changed to "What should I write next?". Questions from paying-intent readers land in the same inbox and the Studio Desk → Reader questions.
