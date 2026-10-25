# Install — WordPress assets for cocotran.com (≈15 minutes)

Everything in this folder is import-ready. Do it in this order; each step depends on the one before. Nothing here edits an existing template or post.

| # | Minutes | Step | File(s) |
|---|---|---|---|
| 1 | 3 | **ACF field groups.** Easiest: Code Snippets (WPCode) → PHP → paste `eeat-opening-setup.php` → Run once (imports all three, idempotent; 25 Oct: none of the three were on the site yet). Or WP admin → **ACF → Tools → Import Field Groups** → choose file → Import. Three times. The groups appear on Properties, Guides, Briefs, Dispatches, Shortlists and Posts ("Answerable opening" above the editor, "Content system" below it, "Booking box" in the side panel on Properties). All are `show_in_rest`, so the worker and the Editorial Desk can write them over `/wp/v2/{cpt}/{id}` with `{acf:{…}}`. | `acf-answerable-opening.json`, `acf-content-system.json`, `acf-booking-access.json` |
| 2 | 2 | **mu-plugins.** Copy both PHP files to `wp-content/mu-plugins/` (create the folder if it is not there). Must-use plugins load automatically; there is nothing to activate and nothing appears under Plugins → Installed except a "Must-Use" tab. Check the site still loads. | `booking-box-shortcode.php`, `paywall-schema-snippet.php` |
| 3 | 4 | **Kadence Element: Answerable opening.** Created over REST on 25 Oct as **draft post 30407** (hook `Single Content: Before`, priority 5, Show on the five CPTs + Posts) — open it under Elements → Drafts and Publish once step 1 is done. Manual path: Add New → Code editor → paste → same settings → Publish. Full clicks and the "under the H1" hook note in its README. | `kadence-element-answerable-opening.html` + README |
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

## The Comparison on the Brief CPT (added 24 Oct) — ≈25 minutes

Place-vs-place comparisons (Bora Bora vs Maldives) are Briefs with the new `brief_format` term **The Comparison**. The wiring kit is three files plus a guide, all in this folder; nothing in it edits an existing template or post:

- `brief-comparison-SETUP.md` — the steps, two paths (Cowork in Coco's Chrome, or REST + `wp eval-file`), the test build (`test-bora-bora-vs-maldives`), a 6-line checklist.
- `brief-comparison-setup.php` — one idempotent script: creates the term, imports `acf-comparison-brief.json` (embedded copy inside, so Code Snippets works too), creates the Bora Bora / Maldives destination terms, prints the IDs. Re-runnable; no deletions.
- `kadence-element-brief-comparison.html` — the Kadence Element (Code editor paste; hook `Single Content: Before`, priority 10, Show on Singular: Briefs + brief_format = The Comparison). Needs `booking-box-shortcode.php` (step 2) for its `[ctt_book]` block and sits under the Answerable opening (step 3) without duplicating it.

Order: step 1–3 above first, then the kit. Comparisons are public: set `ct_gated = 0` on each one or `paywall-schema-snippet.php` marks the Brief as paywalled.

## Autolink + authority (added 24 Oct) — ≈10 minutes

Four more snippets inside the **Coco Tran Site Snippets** plugin (`12-install/coco-tran-site-snippets.zip`, now 1.2.5 — the loader requires `autolink.php`, `pair.php`, `authority.php`, `rest-expose.php`, `template-hooks.php`, `acf-groups.php` + the `acf/` folder beside the three earlier files). `rest-expose.php` makes `property / guide / brief / dispatch / shortlist…`, their taxonomies (`destinations`, `brief_format`, `collection`…) and the ACF groups that target them available under `/wp-json/wp/v2/<type>` — confirmed missing on 25 Oct 2026 (`wp/v2/types` listed none of them), and without it the worker can only build plain posts. Standalone copy for Code Snippets: `rest-expose.php` (PHP, run everywhere). On the multisite: **Network Admin → Plugins → Add New → Upload** → choose the zip → WordPress shows "This plugin is already installed" → click **Replace current with uploaded** (the step that was skipped on 24 Oct: the site still reports 1.0.0 / Photo CMS 1.1.0) or copy the three files to `wp-content/mu-plugins/` with the others.

1. **Settings → Reading → "Coco Tran snippets"** (also writable over `/wp-json/wp/v2/settings`): `ct_author_bio` (one sentence, shown under every piece and used as the Person `description`), `ct_social_instagram`, `ct_social_youtube` (profile URLs; shown as links and sent as `sameAs` — leave empty and neither appears), `ct_autolink_max` (links per piece, default 6).
2. **The link map.** Nothing links until the worker POSTs it: `POST /wp-json/coco-tran/v1/link-map` with the Application Password (`edit_others_posts`), per `link-map-builder.md`. `GET` the same route to read it back; `GET /wp-json/coco-tran/v1/autolink-report?post=ID` shows what a piece would get.
3. **Check one Verdict and one Guide**: the first mention of another piece's keyword in a paragraph is a link with `data-ct-autolink="1"`, never in a heading, caption, quote, button, an existing link, the opening block or the first 150 characters; at most one link per target and never to itself. Under the content: **Pair it with** (`nav.ct-pair`, up to 4: same destination, other formats first, then the `/destinations/[place]/` page or `ct_hub_url`), then the author block (`aside.ct-author`: "Coco Tran · Photos & words", the role, the bio, "{n} properties stayed, {m} guides", About · How we review · Instagram · YouTube), then the booking box. View source: Rank Math's Article/Review carries `"author": {"@id": "https://cocotran.com/about/#person"}` and the graph has the Person node (name, url, jobTitle, description, sameAs, knowsAbout = the piece's destinations). Rich Results Test on one.
4. **Per-piece switches** (post meta): `ct_autolink_off`, `ct_pair_off`, `ct_author_off` = 1; `ct_hub_url` overrides the hub link. `[ct_author]` places the block by hand (posts, pages); the automatic block skips a piece that already has the shortcode.
5. Cache: Delete all Cache + Cloudflare purge after the first map POST. Pair blocks are cached 12 h per piece and cleared by every map POST and by any publish/trash of a piece.

Notes: the engine rewrites the content through DOMDocument, so `<img …/>` becomes `<img …>` and attribute order can change — no visual effect. The hub page is found by `ct_hub_url`, then the ACF `hub` post object, then a published Page whose slug equals the destination slug.
