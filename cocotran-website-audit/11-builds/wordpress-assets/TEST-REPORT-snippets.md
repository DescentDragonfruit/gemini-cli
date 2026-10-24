# Test report — Coco Tran Site Snippets 1.1.0 (autolink · Pair it with · author + Person schema)

Date: 2026-10-24. Throwaway WordPress 7.1.3 (SQLite, PHP 8.3.6, Twenty Twenty-Five block theme, no Rank Math, no ACF), `php -S 127.0.0.1:8089 -t wordpress router.php`, wp-cli, REST with Application Passwords (admin + a subscriber), anonymous curl for the front end. A rig-only mu-plugin registers `property`, `guide`, `brief`, `dispatch`, `shortlist`, the hierarchical `destination` taxonomy (REST base `destinations`) and `the-guide-format`. `debug.log` empty after every step. `php -l` clean on all seven plugin files; the rebuilt zip diffs identical to the plugin tested on the rig.

## Setup
Six pieces + one hub Page: property *Caruso, A Belmond Hotel, Ravello* (39), guide *Amalfi Coast Travel Guide* (40, format The Primer), brief *5 Days on the Amalfi Coast* (41), dispatch *Postcard From Positano* (42) — all Amalfi Coast; property *Aman Kyoto* (43), shortlist *Best Hotels in Kyoto* (44) — Kyoto; Page `amalfi-coast` (45). Options: `ct_author_bio`, `ct_social_instagram`, `ct_social_youtube` set; `ct_autolink_max` default 6. The guide's content holds a `.ct-ao` group, a heading, a hand-made link to the Positano dispatch, a blockquote, a button, a figure caption and three paragraphs, each carrying keywords.

## Results

| # | Check | Result |
|---|---|---|
| 1 | `POST /coco-tran/v1/link-map` unauthenticated / subscriber | 401 / 403 |
| 2 | POST 9 entries (5 good, 1 off-site URL, 1 three-letter keyword, 1 duplicate URL, 1 keyword owned by another URL) | `stored: 5`, four `rejected` rows with reasons, `version` = timestamp; a 3-letter alias was dropped silently |
| 3 | `GET /coco-tran/v1/link-map` | same 5 entries, aliases cleaned, `post_id` resolved from the URL where omitted (guide → 40) |
| 4 | Guide 40 rendered | 3 autolinks: `Caruso` in paragraph 1 (after the 150-char mark), `five days`, `Aman Kyoto` in the last paragraph; `<h2>Where Caruso fits…</h2>`, the existing `<a>`, the blockquote, the button, the figcaption and the `.ct-ao` paragraph untouched; `CARUSO again` not linked a second time; `Positano` not linked because the author already linked that URL by hand (one per target); the guide's own keyword skipped |
| 5 | `GET /autolink-report?post=40` | `count: 3`, same three keyword/URL pairs as the page, with `context` snippets |
| 6 | Property 39 | own name `Caruso` never linked; `Positano` at char ~95 skipped, its second mention (~200) linked; `five days` (only inside the first 150 chars) not linked; `Aman Kyoto` linked |
| 7 | `ct_autolink_max = 2` | guide shows exactly the two priority-3 links (Caruso, Aman Kyoto); report `max: 2, count: 2` |
| 8 | `ct_autolink_off = 1` on 40 | 0 autolinks on the page; report `off: true` with the would-be list and a note |
| 9 | Pair it with on 40 (guide, Amalfi) | 4 items: dispatch, brief, property (other formats, newest first) + hub Page `amalfi-coast` last, kickers The Dispatch / The Brief / The Verdict / Destination guide, 12-word excerpts from `short_answer` |
| 10 | Pair it with on 43 (Aman Kyoto) | shortlist 44 first (same destination, other format), then properties by pillar fallback; no hub (none exists) |
| 11 | `ct_pair_off = 1` | block gone |
| 12 | Author block on 40 | "Coco Tran · Photos & words", role line, bio, "3 properties stayed, 1 guide", "Filed under Amalfi Coast", About · How we review · Instagram · YouTube |
| 13 | Person JSON-LD (no Rank Math → footer graph) | one `application/ld+json`, valid JSON: Person `@id …/about/#person` with name, url, jobTitle, description, sameAs ×2, knowsAbout ["Amalfi Coast"] + a minimal Article whose `author` is `{"@id": …#person}` |
| 14 | `ct_author_off = 1` | no block and no Person script (nothing invisible emitted) |
| 15 | Rank Math path, simulated with `apply_filters('rank_math/json_ld', …)` in a singular property context | `Review.author` replaced by `{@type Person, @id, name}`, `ct_person` node added, footer fallback suppressed |
| 16 | Transients | 3 `_transient_ct_pair_*` rows after rendering 3 pages → 0 after a second map POST (`version` bumped) → 1 after the next render |
| 17 | `[ct_author]` in a plain post + destination term | block once, Person once, 2 autolinks (post type `post` is autolinked), Pair block present, no automatic second author block |
| 18 | Property with `[ctt_book]` in its content | order in the page: `ct-pair` → `ct-author` → `<aside class="ctt-book">` |
| 19 | `/autolink-report?post=999999` · POST with no valid entry | 404 `ct_not_found` · 400 `ct_empty_map` with the rejected list, previous map kept |
| 20 | `/wp/v2/settings` | the four options readable (and writable) over REST |

## Bugs fixed while testing
- Pair candidate loop: the stop condition compared the picked list against itself and never stopped at the slot budget — rewritten with an explicit counter before the first render.
- `?p=ID` checks returned empty bodies until `-L` was added (301 to the pretty permalink; test harness, not plugin).

## Not covered
- Rank Math itself is not on the rig: the `rank_math/json_ld` path was driven through the filter, not a real Rank Math page; check one live piece in the Rich Results Test after install.
- ACF is not on the rig: `short_answer` and `hub` were raw post meta (same keys ACF writes).
- No Kadence theme: the booking-box Kadence Element (hook Single Content: After) was not rendered; the in-content `[ctt_book]` case was.
- The format taxonomies for `property` and `shortlist` are unknown (not in the repo); the kicker falls back to the pillar label (The Verdict / The Shortlist) until `ct_format_taxonomies` is filtered or a taxonomy with "format" in its name exists on those types.
- Content containing inline `<svg>` or `<script>` is passed through DOMDocument's HTML serialiser; it was not exercised.
