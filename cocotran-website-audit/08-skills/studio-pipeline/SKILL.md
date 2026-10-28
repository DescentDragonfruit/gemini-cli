---
name: studio-pipeline
description: The worker half of the Coco Tran Studio Desk. Picks up jobs Coco queued in the Studio Desk artifact, drafts anything not yet drafted, does the search research (title/meta/H1/keyword against what actually ranks), reads Proposal Studio / Agency Studio records, finalises the piece in her voice and pillar structure with the answerable opening, builds the WordPress draft (REST with an Application Password, or Coco's logged-in Chrome in Cowork), and writes status back. Use when asked to "run the studio queue", "process the desk", "build the queued pieces", or on the scheduled run.
---

# Studio pipeline — the worker

Coco fills the Studio Desk (https://claude.ai/artifact/NVykejaCwzyJDdN2G1NMQt); this skill does everything after. Pair it with `coco-editorial-standards` (voice, gates, opening block) and, for a Verdict, `property-review-writer`; for migrations, `respin-old-content` (field maps, Kadence/REST mechanics).

## 0. Read the queue

`ToolSearch select:ArtifactData`, then `ArtifactData query` on url `https://claude.ai/artifact/NVykejaCwzyJDdN2G1NMQt`, collection `jobs`, where `status in ["queued","drafted","approved"]`. **Order: `priority` desc (missing = 0), then `updatedAt` asc.** If the run was fired from the desk, the extra text names a job id to do FIRST and completely — every stage it is ready for in this one run.

**Run telemetry** (always): when you pick a job up, write `run:{started_at: now, step:"draft|research|build|full", steps:[]}`; when you finish it, update `run.finished_at`, `run.minutes`, `run.tokens_estimate` (tokens-left counter at start minus at end, from the `<total_tokens>` reminders) and `run.steps` (what you actually did). The desk shows this and uses it to estimate the next run. Each job carries: `format` (key), `subject`, `dest`, `basis`, `disclosure`, `photos`, `notes`, `granola[]` (title/date/notes), `proposal`, `agency`, and for drafted jobs `draft` (post_title, kicker, fields{}, selects{}, short_answer, glance[], seo{}, links[], verify[], photos_needed[]) + `report`. Every write back pins `if_version`.

Nothing queued → say so in one line and stop.

Jobs may also carry `uploads[]` ({id, url, name, size}) — photos Coco attached in the desk. They live in the artifact's asset store; see 4a.

## 1. Gather context the desk couldn't

- **Proposal Studio** (`https://claude.ai/artifact/AyNiCSZubZrjMar4rpyAY3`) or **Agency Studio** (`https://claude.ai/artifact/7VMcx1iKdmvdtq6KT6cgix`) named in the job: `Artifact read` the artifact (the result saves the HTML to disk — grep it for `collection("` / `doc("` to learn its collection names), then `ArtifactData list/query` those collections and find the record whose name matches `job.proposal` / `job.agency`. Pull the itinerary (days, hotels, logistics, what was booked, what changed). **Client names, emails and prices paid by the client never enter the piece**; "a couple in their forties, first time in Japan" is as specific as it gets.
- Granola notes are already in the job. If a job names a meeting but has no notes, and this session holds the Granola connector, `search_meetings` + `get_meetings` and add them.

## 1b. Update / respin jobs (`mode: "update"`)

The job carries `source_url`, `missing` (what Coco says is wrong or absent) and maybe `current` (pasted text). Pull the live piece yourself — no credentials needed for published posts:
1. CPT from the path: `/property/` → `property`, `/guide/` → `guide`, `/brief/` → `brief`, `/dispatch/` → `dispatch`, `/shortlist/` → `shortlist`, anything else → `posts`. Slug = last path segment.
2. `GET https://cocotran.com/wp-json/wp/v2/{cpt}?slug={slug}&_fields=id,link,title,acf,content,destinations` — `acf` holds every text field and image ID (confirmed 24 Oct: guide and property return full `acf`). For old `posts`, use `content.rendered`.
3. Keep the real `id` (`wp_id`) and the existing image IDs; never touch image fields unless the job says so.
4. Draft with the desk's update prompt: keep every fact, every firsthand moment and her sentences that already pass the rules; fix only `missing`; add the opening block + SEO; apply voice rules where a sentence fails. Store the pulled fields in `original` on the job so the diff is visible.
5. Building an update (step 4 below) writes to the SAME post: `POST /wp/v2/{cpt}/{wp_id}` with only the changed `acf` keys + title/slug if changed. **A published post changes live the moment this runs** — so build only from `status: "approved"`, and say in `worker_notes` exactly which fields changed. Rank Math meta via `updateMeta` as usual. If Coco wants to see it before it goes live, Route C's payload file is the review copy.

## 2. Draft (status `queued` only)

Use the Studio Desk's own prompt structure (format rules + field map + opening block + SEO set — copy from the artifact's `FORMATS` and `draftPrompt`, or from `coco-editorial-standards`), with the full context from step 1. Then run the gates/voice pass (`deskPassPrompt`). Write `draft`, `report`, `status: "drafted"`. `report.gates` is an **array** of `{n, verdict: "PASS"|"FIXED"|"FAIL", note}` (one per gate), `report.needs_coco` and `report.voice_changes` arrays, never a sentence: a string there used to blank the piece in the desk (25 Oct 2026, five jobs). The desk now tolerates it, but write the arrays.

## 3. Research (status `drafted` → `researched`)

Make the SEO set true to what ranks, not just plausible:
1. WebSearch (standard) the `seo.primary_query` and 2–3 `secondary_queries`. Note the top titles, whether they're "X vs Y" / "is X worth it" / "where to stay in X" shapes, any People-Also-Ask-style questions in results, and which competitors hold the page (`07-ai-benchmark/` lists the usual ones).
2. Site-search `site:cocotran.com <subject>` to find sibling pieces to link and any **duplicate** already covering the query (if one exists: stop, write `worker_notes` naming it and set `status: "needs-coco"` — one topic, one piece).
3. Finalise: `seo_title` ≤60 in the winning shape (hotel pieces: "[Hotel] Review: …" / "[A] vs [B]: Which…"; guides: "[Place] Travel Guide: Where to Stay, Eat and Explore"; answers: the question), `meta_description` ≤155, `h1`, `slug`, `focus_keyword`, and the `links` list resolved to real cocotran.com URLs. For a Primer or Answer, make sure the FAQ/short answer covers the questions people actually ask (from step 1).
4. Fact pass: every `[VERIFY]` item. When the pass changes **nothing** on a live piece (an update job or a research job), set the library record's `last_fact_check` = today and, if the site is reachable, the post's ACF `last_fact_check` — never bump `updated_on`/`last_updated` for a check that changed no facts. When it changes a fact, `updated_on` moves too. — verify from a primary source (hotel site, official tourism, government) via WebSearch/WebFetch; replace with the fact and a one-line source note in `worker_notes`, or leave the marker and list it under `needs_coco`. Never remove a marker without a source. Closure/rebrand claims get two sources.
5. Write back: `draft.seo`, `draft.links`, `draft.fields` (only where a VERIFY was resolved), `research: {queries:[], competitors:[], shapes:[], notes:""}`, `status: "researched"`.

Coco approves in the Desk (status → `approved`). If a job is `researched` for more than 7 days, leave it.

## 4. Build in WordPress (status `approved` → `built`)

Route A — **REST with an Application Password**. Probe first: `curl -s -o /dev/null -w '%{http_code}' https://cocotran.com/wp-json/wp/v2/users/me`. 200 means the environment's Network secret is injecting Basic auth at the proxy: send every `cocotran.com/wp-json` request with **no** Authorization header. If the probe fails but `COCOTRAN_WP_USER` / `COCOTRAN_WP_APP_PASSWORD` env vars exist, add `Authorization: Basic base64(user:app_password)` yourself. Neither → Route C. (Drafts and `context=edit` reads need auth either way.)
1. **Comparison routing**: hotel vs hotel → `property` (Trade-Off keys `tradeoff_*`, `best_for_a/b`, `booking_access_a/b`); place vs place → `brief` with `brief_format` term "The Comparison" and keys `comparison_both_share` (one line from the glance row "Both share"; it prints under the two Choose-if lines), `comparison_short_answer`, `side_a_name`, `side_b_name`, `best_for_a/b`, `comparison_the_set_up`, `_the_location`, `_the_stays`, `_the_food`, `_the_days`, `_the_price_difference`, `_who_books_which`, `_the_verdict`, `comparison_booking_note`, `comparison_image_1..5` (group `11-builds/wordpress-assets/acf-comparison-brief.json`). If that group or term is missing on the live site, build Route C and say so.
1b. Confirm the live ACF keys: `GET https://cocotran.com/wp-json/wp/v2/{cpt}?per_page=1&_fields=acf` on a published example of the same format; map provisional keys (Primer, Comparison) to the real ones; drop keys that don't exist rather than guessing.
2. Destination terms: `GET /wp/v2/destinations?search=<city>` for continent + country + city IDs (hierarchical — all three).
3. Create the draft: `POST /wp/v2/{cpt}` with `{status:"draft", title, slug, destinations:[ids], acf:{…fields, …selects, format/dispatch_format/brief_format term where the format lives in a taxonomy — see respin-old-content for the term IDs (Field Notes 762, Postcard 760, Roundup 765, Rolodex 763, Dossier 758, Route 759, **The Comparison (new)** — Brief, slug `the-comparison`, ID assigned when `11-builds/wordpress-assets/brief-comparison-setup.php` creates it: `GET /wp/v2/brief_format?slug=the-comparison`; wiring kit in `11-builds/wordpress-assets/brief-comparison-SETUP.md`)}}`. Send ACF text in 2–3 updates if large.
4. Answerable opening: if the `Answerable opening` ACF group exists (`short_answer`, `glance_*`, `visit_basis`, `disclosure`, `updated_on`) write those fields; if not, prepend the short answer as the first `<p>` of the first text field and put the glance rows as a `<p>` block after it, so nothing is lost.
5. Rank Math: `POST /wp-json/rankmath/v1/updateMeta` with `{objectID, objectType:"post", meta:{rank_math_title, rank_math_description, rank_math_focus_keyword}}` (raw fetch with the nonce when in Chrome; basic auth on REST).
6. Photos: if `job.photos` names WP media (a filename stem), `GET /wp/v2/media?search=<stem>&per_page=50`, assign IDs to the format's image slots in order (field maps in respin-old-content), set the featured image; write alt via the Coco Photo CMS fields if the plugin is installed. If photos are elsewhere, leave slots empty and say so.
7. Verify: `GET /{cpt}/{slug}/?preview=true` renders (or `wp/v2/{cpt}/{id}?context=edit` shows the fields saved), no placeholder text, title fits.
8. Write back: `status: "built"`, `preview_url`, `wp_id`, `worker_notes` (what was mapped, what's missing). The desk shows `built` as **"Publish · needs Coco"** with an "Open in WordPress" button; `approved` shows as "Approved · worker builds it in WP". Approve is the only click she makes before the build: an approved job goes into WordPress as a draft on the next run with no second confirmation.

### 4a. Photos attached in the desk (`job.uploads`)

1. For each upload: `Artifact read` with `url` = the desk and `path` = the upload `id` (32 hex chars) — the file lands in the scratchpad.
2. If the Coco Photo CMS plugin is installed, `POST /wp-json/coco-photo/v1/optimize` is not needed — upload through core `POST /wp/v2/media` (multipart, `Content-Disposition: attachment; filename="<slug>-<n>.jpg"`) with basic auth; the plugin converts to WebP, caps the long edge and writes the credit on upload. Without the plugin, convert to WebP (quality 82, long edge 2000) with Pillow first so the site never carries a 6 MB scan.
3. **Image SEO on every upload (25 Oct 2026):** the desk already converts drops to WebP (≤2400px long edge, ~85 quality) named `<piece-slug>-NN.webp`; if a file is still JPEG/PNG/TIFF, convert it to WebP at the same size before uploading (Pillow or `cwebp`), never upload a scan over 2 MB. Upload with `Content-Disposition: attachment; filename="<primary-keyword-slug>-<place-slug>-NN.webp"`. Then `POST /wp/v2/media/<id>` with `title` (`photos_needed[n].title`, else "<Subject>, <Place> — <what>"), `alt_text` (`photos_needed[n].alt`, ≤125 characters, the place and the subject once, never "image of"), `caption` (`photos_needed[n].caption`, one line in Coco's voice) and `description` (`photos_needed[n].description`, 1–2 sentences, the keyword once). Match uploads to `photos_needed` by `file`, else in order. Assign the IDs to the format's image slots in order, write each slot's `_des`/`_desc` field from the caption, and set the first as the featured image. Record `{media_id, file, alt}` per photo in `worker_notes`.
4. Write the media IDs back to the job as `media_ids[]` and to the library record as `photos_list[]` ({id, url, alt}).

Route B — **Cowork with Coco's logged-in Chrome**: as respin-old-content describes (`wp.apiFetch` from a `/wp-admin/edit.php?post_type=guide` page, small calls, try/catch, never return raw HTML/URLs through the extension). Same steps.

Route C — **no site access**: write the full payload to `cocotran-website-audit/09-studio-output/<slug>.json` (the Desk's "WordPress payload" shape), commit + push on `cocotran-website-audit`, set `status: "needs-site"` with `worker_notes` saying where the file is. Cowork pushes it next time.

Never publish. Coco clicks Publish, then presses **This is live ✓** on the job in the desk (Queue → open the piece → paste the live URL). That button sets the job `published`, upserts `library/<slug>` (text, keyword, SEO set, `last_updated`, photos), pre-fills the social kit and adds a cache/redirect to-do. The worker still does, on its next run, for every job that turned `published` since the last run: redirects if it replaces an old post (Rank Math → Redirections → Add New), cache + Cloudflare purge reminder, `recheck.sh` on the URL, add the piece's question to `ai-faq-question-bank.md`.


### Live REST map (verified 25 Oct 2026, Site Snippets 1.2.1 + ACF PRO active)
Post types and REST bases: `property`, `guide`, `brief`, `dispatch`, `shortlist` (each at `/wp-json/wp/v2/<type>`, `acf` object present). Taxonomies: `destination` → rest_base **`destinations`** (hierarchical; Oceania 632 → French Polynesia 654; Asia 628), `brief_format` (758 The Dossier, 759 The Route; The Comparison is created by the setup script), `dispatches-formats`, `guide`, `the-guide-format`. ACF groups in REST: Guide Fields, Property Fields, The Briefs Fields, The Dispatch, The Shortlist. Diagnostic: `GET /wp-json/coco-tran/v1/types` (admin). **ACF PRO registers the magazine post types**: if `wp/v2/types` suddenly lacks them and magazine URLs 301 to the homepage, ACF PRO has been deactivated — tell Coco first, do not build.

### Answerable opening (live 25 Oct 2026)
Every pillar template prints Kadence Element 30407 in a Shortcode block (`[kadence_element id="30407"]`) directly under the facts strip, before the first body section (wired 25 Oct 2026 evening, restyled to match the facts strip: sans caps labels, hairlines, no fill). It renders the ACF group `group_ct_answerable_opening` (short_answer, glance_1..5 label/value, visited, visit_basis, updated_on, disclosure = Paid stay | Hosted stay | Discounted stay | Advisor roundup (not a stay) | Trip I planned | Trip I took, advisor_roundup) and hides itself when short_answer is empty. Builds MUST fill it: short answer, five glance rows (for a stay: Setting / Best for / Standout / Consider / Stayed; for a comparison: Choose A if / Choose B if / Price difference / Both share / Stayed), visited, visit_basis, updated_on = today, disclosure. Also fill `group_ct_content_system` (content_category, content_goal, primary_keyword, secondary_keywords, ai_questions, last_fact_check) and, on Properties, `booking_access` + `booking_perks_override`.

### Ideas, keyword lock, link plan and trust fields on New-piece jobs (added 25 Oct 2026, desk v43)
New jobs from the desk may carry four more objects. Honour each one; none is optional when present.
- `idea` `{q, src[], imp, clicks, pos, fmt, why, title, place, dest, questions[], secondary[], owner}` — the piece was started from a measured query (Search Console / content gap / People-also-ask / import). **Keyword lock:** `rank_math.focus_keyword` and ACF `primary_keyword` = `idea.q` exactly; `secondary_keywords` from `idea.secondary` plus what the live SERP adds; `ai_questions` = the `idea.questions` the piece answers, verbatim, one per line. If `idea.owner.status` is `live`, this is an update of that piece, never a second piece on the same query.
- `link_plan` `{dest, chain[], hub, pair_with[], related_property_reviews[], related_guide_content[], feature_in_shortlist[], inbound[], old_posts[], missing[]}` — computed from the library by destination chain. Resolve each URL to a post ID (`GET /wp/v2/<cpt>?slug=`) and write `hub` (post object) and the relationship fields that exist on that CPT (guide: `related_property_reviews`, `related_guide_content`, `feature_in_shortlist`; property: `related_post`; dispatch: `related_property` / `related_prop`; brief: `related_guide`). The body must already link the hub and two `pair_with` pieces; if the draft does not, add the links on first natural mention (never in a heading or the opening block). After the piece is **live**, for each `link_plan.inbound` piece append the new post to that piece's related field when it has room (max 4) — never touch its body text. `old_posts` are the old-format posts on the same place: fold facts in only if the job says so; the Respin set redirects them, not this job. `missing` is information for Coco, not work.
- `trust` `{visited, visit_month, stayed_row, visit_basis, disclosure, booking_access, last_fact_check}` — ACF `visited` = `stayed_row` (e.g. "Stayed · Oct 2026", "Planned for clients · not visited"); `visit_basis` = the sentence; `disclosure` = the select value exactly; `booking_access` on property only; `last_fact_check` as given (YYYYMMDD); `updated_on` = the build date (YYYYMMDD). The glance row labelled Stayed / Last visit = `stayed_row`. Empty trust values stay empty and go to `needs_coco`; never infer them from photos or text.
- `summary` (string) and `summary_field` — the one-line summary. Write it to `summary_field` when that is a real ACF name on the CPT (today only property `one_line_about_property`); otherwise keep it on the job and the library record (`summary`). When Coco names the summary field for the other pillars, the desk setting `settings/ideas.summary_fields` carries the names and the payload follows.
- `acf_keys_not_on_site` lists payload keys the CPT does not expose over REST (the desk checks against the live field list). Map them before writing (property's food & beverage field is `the_`), never send an unknown key, never drop a value silently: say in `needs_coco` what could not be placed.

### Update copies are DRAFTS, URLs never change by themselves, redirects are automatic (25 Oct 2026, evening)
- An update job builds a **draft** copy (`status: draft`), not a private post. Preview link = `https://cocotran.com/?post_type=<cpt>&p=<id>&preview=true` (write it to `preview_url`). A draft is invisible to search engines and visitors, so the live page, its rankings and every link to it are untouched until Coco presses **Replace the live article**.
- Replacing keeps the live post's ID, URL and slug. Copy the fields onto the live post, keep `date` as it was, set `modified`/`updated_on` to today, then trash the draft copy. No redirect is involved.
- **A URL changes only when the job says so** (`new_slug` on the job, or a respin where old posts fold into a new piece). Then, the moment the new URL is live, POST each old path to `/wp-json/coco-tran/v1/redirects` `{from:"/old-path/", to:"https://cocotran.com/new/", note:"<job id>"}` — one call per old URL, then `GET` it back and confirm with `curl -sI` that the old path answers 301 to the new one. Record `redirects_done: [{from,to,at}]` on the job and `status: "redirected-to:<slug>"` on each old library record. Never call Rank Math's redirect API. If the route answers 404 the snippet is not installed yet: write `needs_coco: ["Paste snippet-redirects.php into WPCode (runbook §6); the 301 for <from> is waiting"]` and keep `redirects_pending` on the job; retry on the next run.

### Existing photos on queue jobs: convert and write their SEO (25 Oct 2026)
On every update, respin, data: and backfill job — not only new pieces — treat the piece's images as part of the job. For each image the post uses (every ACF image slot, the gallery field, the featured image): if the attachment is not WebP, or its long edge is over 2400px, or the file is over 1 MB, download the original, resize to 2400px long edge, encode WebP (~85), upload it as a new attachment named `<primary-keyword-slug>-<place-slug>-NN.webp` (attach it to the post: `post` = the post id), copy the old attachment's alt/caption when they are real, store `_ct_original_id` = the old attachment id as media meta, and point the slot (and the featured image) at the new id. Never delete the old attachment in the same run (she may want to compare; a later `retire-media` job trashes originals that nothing references). Then, for every image the post uses, write what is missing or generic ("IMG_2031", "DSC", the filename, empty): `title` "<Subject>, <Place> — <what the frame shows>", `alt_text` (≤125 characters, place and subject once, never "image of"), `caption` (one line in Coco's voice, from the paragraph nearest that slot), `description` (1–2 sentences, the keyword once), using only what the piece's own text supports. Write the slot's `_des`/`_desc` caption fields the same way when they are empty. Record `images: [{slot, old_id, new_id, alt}]` in `worker_notes`, and `images_converted`, `images_seo_written` counts on the job. Cloudflare and SpeedyCache purge once at the end.

### Figures never drop, quotes get written, "summary" = the answerable opening (25 Oct 2026, from the Bora Bora check)
- **Figures.** Every price, rate, cost, hour, distance, date and count in the SOURCE (the old post, the job's `original` / `current`, Coco's notes, Granola) must appear in the draft, in a `[VERIFY: …]` marker, or in `verify[]` as "left out as stale: …". Before marking a job drafted or built, diff the source against the draft for these tokens and write `transfer_audit.source_figures_missing` on the job; the desk shows the same list as "Figures in the source that are not in the draft" and Coco puts them back or marks them stale. A built piece is checked once more draft → live ACF (field-name mismatches such as `the_food_&_beverage` → `the_` lose whole sections silently).
- **Pull quotes.** The Verdict and Trade-Off templates print `quote_1_` and `qupte_2`; the Rolodex prints `quote_1_`. Fill them on every build and every update of those formats: one sentence each, lifted verbatim from the piece's own body (≤120 characters, Coco's voice, no quotation marks, no new facts) — `quote_1_` from the body, `qupte_2` from last_thoughts.
- **Summary.** When Coco says "the summary field" she means the answerable opening: `short_answer` (≤420 characters) on every pillar. Property additionally prints `one_line_about_property` at the top; write both there.

### Lessons from the first full New-piece test (Santorini Shortlist, 25 Oct 2026) — all binding
- **One worker per job (claim it).** Before working a job, re-read it and write `{status:"working", claimed_by:<your session id>, claimed_at:now}` with `if_version`. If the write fails on version, or the job already reads `working` with a `claimed_at` under 2 hours old, skip it. Before creating any post, search the CPT for the job's slug (`?slug=&status=draft,private,publish`) and reuse a match instead of creating a second one. When done, set the real status. (Two workers built the Santorini draft twice.)
- **Approved still means research first.** A job that arrives `approved` with an empty `research{}` runs the research step (WebSearch the primary and two secondaries, People-also-ask, what ranks, fact-check the verify items as VERIFIED/UNVERIFIED) before it builds. Write `research{}` and append any new questions to `ai_questions`.
- **WebP applies to new pieces too.** When a new piece uses existing media, convert each non-WebP / over-2400px / over-1 MB attachment exactly as "Existing photos on queue jobs" says, then assign the new IDs.
- **Hub is a Page.** On every CPT the ACF `hub` field accepts Pages only. Resolve it to the destination Page (`GET /wp/v2/pages?slug=<destination-slug>`); if none exists, leave it empty and say so. The link plan's guide hub goes into the body links and `related_guide_content`, not into `hub`.
- **Destination chain.** If a CPT's REST refuses a parent term (Europe on shortlist), save the child terms and record the refusal in `needs_coco`.
- **Fields the template prints but REST does not expose** (shortlist and dispatch `trip_type`): list them under `needs_coco` once, with the value the draft wanted.
- **Price band (Coco's rule).** Every hotel pick and the Price band glance row read "From $700 a night", except a very low-cost country or a very boutique, unique, design-forward property, which read "From $400 a night". A figure stated by Coco or the hotel overrides the rule.
- **Never write a live post that has an open rewrite.** Before writing any field on a live post (backfill, data jobs, audits), check the jobs collection for an update job with that `live_wp_id` and status `built` or `approved`; if there is one, write to its draft copy instead, or skip and note it. (The backfill wrote Tầm Vị's live opening block six minutes after the rewrite copy was built.)
- **test_report.** On any job with `desk_test: true`, write `test_report {worked[], broke[], missing[], suggestions[]}` before finishing.

### Titles, questions and photos — Coco's corrections on the Santorini test (25 Oct 2026, binding)
- **Two titles, always different.** The WordPress post title is the house title for the pillar, exactly: Verdict "The Verdict: [Hotel], [Place]" · Comparison/Trade-Off "The Comparison: [A] vs [B]" · Primer "The Primer: [Place]" · Rolodex "The Rolodex: [Place]" · Roundup "The Roundup: [Theme], [Place]" · Route "The Route: [N Days] in [Place]" · Dossier "[Place] : The Dossier" · Shortlist "[Place] [Theme]: The Shortlist" (Santorini Hotels: The Shortlist, Hanoi Coffee: The Shortlist) · Field Notes "Field Notes From: [Subject]", one restaurant "A Table At: [Restaurant], [City]" · Postcard "Postcard From [Place]" · Take "The Take: [Subject]" · Answer "The Answer: [question]". The template's two-line headline fields (tittle_1 / tittle_2, title_1 / tittle_2, tittle_word_1 / tittle_2_longer_words_underneath) follow the same house style: line 1 the place or subject, line 2 a short line in Coco's voice. The **SEO title** is written to Rank Math (`POST /wp-json/rankmath/v1/updateMeta {objectType:"post", objectID, meta:{rank_math_title, rank_math_description, rank_math_focus_keyword}}`), keyword first, ≤60 characters, in the format's lock pattern, and must not equal the post title. The focus keyword may list the primary plus two secondaries, comma separated.
- **Choose the format from the results page.** Before building, look at what ranks for the primary query: area-by-area lists → Shortlist or Rolodex; one decision with one answer → The Answer (350–600 words); two named options → Comparison. Record the call and the reason in `research.format_call`.
- **People-also-ask is mandatory.** Research always produces at least 6 real questions (WebSearch the primary, "best area to stay in X", "X or Y", "how many nights in X", plus what the results show). Write them verbatim to `ai_questions` and make sure the piece answers each: in `insider_faq` where the format has it, otherwise as an "Asked often" block (`<h3>Asked often</h3>` then `<h4>question</h4><p>answer</p>`) just before the Inquire line, written only from facts already in the piece. Add the best extra phrasings to `secondary_keywords`.
- **Photos: smaller or not at all.** Convert to WebP only when the WebP is smaller than the original (try quality 80, then 76, then 72, method 6); never upscale; keep the original's pixel size when it is under 2400px. Filenames are `<primary-keyword-slug>-<what-it-shows>-NN.webp` with no `-1` suffix: if a name is taken by your own earlier attempt, delete that attempt first. Every image gets alt (≤125 characters), title, caption and description; attach it to the post. When the only copies on the site are small (800px), say so in `needs_coco` and ask for the full-resolution scan.
- **Byline.** Every pillar template prints "Photos & words by Coco Tran" as fixed text (25 Oct 2026); never bind it to a dynamic author field again.

### Topical authority map (added 25 Oct 2026) — the desk queues it by itself; Coco does not decide it
The desk's Authority map runs once a day and writes jobs with `authority {term_id, chain[], pillar, hub, country_hub, siblings[]}` (ids `am-<place>-<pillar>`) and link jobs (`mode: "links"`, ids `links-<place>`).
- **The set.** A place (city, island, region; a village rolls into its parent unless it has 4+ pieces) carries: Rolodex (the hub), Primer, Route, Shortlist · where to stay, Shortlist · where to eat, Postcard or Field Notes, a Verdict, and a Dossier (its own or the country's). A country with two or more covered places carries Rolodex (country hub), Primer, Route, Dossier.
- **Throttle — mandatory.** Per run, take at most **3** jobs whose id starts with `am-`, after every job Coco created herself. Order: `rolodex` first (a place's hub before its spokes), then primer, stay, route, eat, dossier, dispatch, verdict; within a pillar, the place with the most live pieces first. Link jobs (`links-*`) are cheap: up to 5 per run.
- **How a pillar job is built.** `mode: respin` when `source_urls` lists old posts for the place (fold them in under the Respin Pipeline spec, redirects automatic after live); `mode: new` otherwise: write from Coco's own live pieces on that place, her notes in the library text, and research. Same quality bar as any piece: keyword lock (`idea.q` is a starting keyword — replace it with the best measured or researched query for this pillar), People-also-ask ≥6 answered, house WordPress title + different SEO title, photos per the photo rules, trust fields never invented. Build as a DRAFT; Coco publishes.
- **needs-coco jobs** are the only questions: photos (fewer than five of hers for the place) or which hotel she stayed in (a Verdict with no old hotel post). Do not build them until she answers; when an answer arrives on the job, set it `queued`.
- **Stay inside the pillar.** Each piece answers its own job and query (the Rolodex the whole place, the Primer before-you-go, the Shortlists where to stay / eat, the Route the days) and points to the others for the rest, so pieces in the set never compete for one query.
- **Links (topical authority).** Every piece links UP to its place hub and its country hub, ACROSS to two other pillars of the same place; the hub links DOWN to every piece of the place and across to neighbouring places' hubs. A `links-*` job carries `links[{piece, hub, need[]}]`: for each piece add what is missing to the relationship fields its CPT has (guide `related_guide_content` / `related_property_reviews` / `feature_in_shortlist`; property `related_post`; dispatch `related_property`; brief `related_guide`; `hub` is the destination Page only) and one in-body link to the hub on first natural mention; never rewrite Coco's sentences; skip posts with an open update job.
- **After any piece goes live** (new, update or respin): update its place hub's related fields to include it, and queue nothing else — the next daily map run notices the change and queues the link job.

### Travel history, trip plans and photos to edit (added 25 Oct 2026)
- **Source.** The Passport artifact (https://claude.ai/artifact/TecDEdRmvTRrcU4vysqRJ2, collection `trips`: `{name, start, end, stops[{city, iso}], hotels[{name, city, iso, kind: stay|inspected, nights, rating}]}`) is Coco's own record and the most accurate one. It wins over Notion "COCOS TRAVEL HUB" and Agency Studio whenever they disagree. The desk mirrors it into `travel/pp-<passport id>` = `{name, start, end, places[], countries[], hotels[], kind: personal|fam, status: past|live|upcoming, source: "passport"}`, and Agency Studio fam trips (`trips`) and inspections (`inspections`) fill in only where the Passport is silent. The mirror is refreshed when Coco asks ("sync my travel"): read the Passport's `trips`, replace the `travel/pp-*` docs, and add `todo/photos-pp-<id>` for past trips whose places have none of her photos on the site. No scheduled task does this, because scheduled tasks cannot carry connectors in this org. Only trip names, dates, places and hotel names, nights and ratings are ever copied. Never copy passport numbers, dates of birth, addresses, booking references or traveller names from the Passport, Notion or Drive into the desk, a job, a post or the repo.
- **Cuba has no rate floor** (Coco, 26 Oct 2026). Its best boutique hotels are the luxury tier there; she stayed, so she reviews them.
- **Verdicts from the Passport.** A Passport hotel with `kind: stay` is a stay Coco can review: the map's `am-<place>-verdict` job takes that hotel as its subject, with `stay{nights, month}` and `visited` = "Stayed · <Mon YYYY>". `kind: inspected` is a site inspection: use "I toured it" wording, never "I stayed". Before building, check the rate floor ($700 a night, or $400 in a very low-cost country or for a very boutique, design-forward property). If the hotel falls below it, park the job as `needs-coco` with the rate you found.
- **Trust fields from the log.** Coco's own travel log counts as her statement. When a piece's place matches a past trip: `visited` = "Stayed · <Mon YYYY of the latest trip>" for destination pieces; `disclosure` = "Trip I took" for a personal trip; for a fam trip leave `disclosure` and ask in `needs_coco` (hosted, discounted or paid). A hotel's Verdict still needs the hotel itself: match it against fam-trip `hotels[]` or an inspection; otherwise ask. Map jobs carry `visits[]` for this. Use it on backfill jobs too (it answers most "visited" questions).
- **No visit in the log** → write as the advisor who planned it ("I plan this for clients"), never as if she stayed; `visited` empty.
- **Trip plans** (`mode: "plan"`, ids `plan-<trip>`): before the trip starts, write `plan{}` on the job: per place, the authority-map pillars still missing; the shot list each pillar needs (Verdict: arrival, room and bathroom, view, F&B, spa, the one detail; Rolodex: neighbourhoods, a street scene per section; Postcard: one moment, golden hour; Route: transport between bases); the questions to ask each GM (rates, room to ask for, perks, opening dates); 3 reel beats and 1 YouTube outline per place; the target keyword per piece from research. Add one `todo` per travel day. Do not build posts. After the trip ends, the daily map run queues the pieces once Coco's photos are on the site.
- **Photos to edit** are `todo/photos-<trip>` items the desk adds for past trips with places that have none of her photos on the site. When photos for that place appear in the media library, mark the todo done.

### Trips pipeline: trip research, planned pieces, culls (added 25 Oct 2026)
The desk's **Trips** tab runs each trip from plan to video. Agency Studio is the source for fam trips and new trips. The Passport covers prior trips.
- **Agency Studio mirror (every run).** Read Agency Studio (https://claude.ai/artifact/7VMcx1iKdmvdtq6KT6cgix).
  - Its collections are `famtrips`, `reviews` and `guides`. Each document's `data` field is a JSON string.
  - Refresh desk `trips/<id>` with `{name, startDate, endDate, status, destination, purpose, hotels[], places[], days[{date,title,place,description,reflect{did,saw,smelled,ate,felt,free},shots[{text,timing,done}],items[{type,name,notes,rating,photos}]}]}`.
  - Refresh `inspections/<id>` with `{name, location, visitStart, visitEnd, purpose, status, rating, answers_count, answers_text, free, fromTrip}`.
  - Strip booking references, confirmation numbers, balances due and consultant names from every text field.
- **Coco's own itineraries.** In Proposal Studio (https://claude.ai/artifact/AyNiCSZubZrjMar4rpyAY3), `proposals` whose `cover.travelFor` is Coco are her own trips.
  - Match one to its fam trip by dates, then by hotel name.
  - Mirror it as `trips/<id>.itin{pid, title, days[{date,title,location,hotel,tip,body}]}`.
  - Never read or copy the `clients` collection.
- **Trip research** (`type: "research"`, `mode: "trip-research"`, id `research-<trip>`): queued by "Find the pieces".
  - For each place, research keywords for the pieces this trip can fill, one pillar each, in the house title format.
  - Each piece carries: primary keyword; demand (high, medium or low; mark estimates); `publish_by` (6–8 weeks before that place's search peak); `photos` count; `slots[]`; and `cull_tips` (what readers save for this piece).
  - Check what cocotran.com already has first, and propose an update instead of a duplicate.
  - Apply the rate floor to every Verdict. A hotel she only toured goes into a Shortlist or Comparison, never a Verdict.
  - For an upcoming trip, also research shots for each place: `shots[{spot,what,why,when,medium,vertical,avoid}]`, unique and engagement-led, with the cliché named. Add `gm_questions` for each hotel, plus `video{title,hook,outline,shorts}`.
  - Write `research/<trip id>` = `{places[{place,country,shots,pieces,gm_questions}], trip_top[], video, cull_tips{title:[...]}, at}`.
  - Create one pipeline job for each `trip_top` piece (or for each piece on a past trip, up to 12).
  - Append the shots to the Agency Studio famtrip's `days[].shots` on the matching day: text starts with "★", id starts with `r_`. Never edit or remove her own shots, and write with `if_version`.
- **Pipeline jobs** (`pipeline: true`, `trip_id`, `status: "planned"`):
  - **planned** means waiting for Coco. The worker never builds a planned job.
  - Approving a cull in the desk sets it to **queued**; so does "write it". Then build it like any new piece.
  - Facts come only from Coco's first-hand material:
    - `inspections` matching the subject;
    - `trip_notes` with this `trip_id` (her Granola notes, pulled by trip dates);
    - `trips/<id>.days[].reflect` (her own voice) and `items[].notes`.
  - Research is for context only and carries [VERIFY].
  - Never use personal or family details from Granola notes (home, family, partner's job and the like), even when they appear in a trip-day note.
  - Fam trips: leave `disclosure` empty and ask in `needs_coco` (hosted, discounted or paid).
- **Photos.** `culls/<job id>` = `{slots[{slot, pick (file name), d, film, empty}], approved, edited}`.
  - Use the picks in slot order. Coco uploads the edited selects, whose file names match the picks.
  - A slot with `film: true` waits for her scans: build with the digital frame or leave it out, and say so in `needs_coco`.
  - Don't publish photos until `edited` is true.
- **Taste.** `taste/profile` is Coco's eye. `taste_choices` holds what she kept or overrode in culls and in Edit 1 vs Edit 2. Use the profile when choosing a hero or a social cover.
- **Video.** `videos/<job id>` holds the clip list for the piece's YouTube video. Social kits for a pipeline piece reuse its hero and its `cull_tips`.

### Trips, round two (added 26 Oct 2026)
- **Debriefs.** `debriefs/<trip>__<date>` holds `{trip_id, date, reflect{did,saw,smelled,ate,felt}, sources[], status}`. Coco approves a debrief in the desk.
  - On every run, copy each `approved` debrief into the matching Agency Studio famtrip day's `reflect`. Fill empty fields only, never overwrite. Write with `if_version`.
  - Then set the debrief to `status: "synced"` and refresh `trips/<id>.days` in the mirror.
- **Partial culls.** In a whole-trip cull, a piece with fewer than half its slots picked gets `culls/<id>.partial: true` and stays `planned`.
  - Do not build it. It waits for more frames or for film scans.
- **Share checklist.** `share/<job id>.done{carousel, reel, short, youtube, pin, newsletter}` tracks each live piece's channels.
  - When you make a social kit or YouTube plan for a pipeline piece, tick the matching channel.
- **Monday plan.** `weekplan/<YYYY-Www>` records the trip and three pieces Coco chose for the week, with matching calendar entries in `plan`.
  - Within the queued work, give those pieces priority.
- **Results.** `results/<job id>` = `{live_at, d30, d60, d90}`, with each reading a snapshot of the library record's `rank` (clicks28, impressions28, inquiries28, conversions28, position).
  - When the Monday analytics run refreshes the library, also fill any milestone that is now due.
- **Season windows.** A planned piece's search window is open when `publish_by` is 2 months away or less.
  - A window that has passed comes back the same month next year. Never change `publish_by` yourself.

### Trip signals: keyword demand, People also ask, search season (added 26 Oct 2026)
The Content bank ranks past trips by a desk priority score, and "ask the desk" picks the three to do first. Both read `research/<trip id>.signals`, which the ideas agent writes (merge it; never overwrite `pieces`):
`signals = {paa:[{q, place}] (6–12 real Google People-also-ask questions for the trip's places and stays), keywords:[{q, demand: high|medium|low, intent: plan|book|compare|answer, note}] (5–10 head and mid-tail terms), peak_months:["Dec",...] (when searches for the place peak), best_window:"YYYY-MM" (when the first piece should be live: 6–8 weeks before the next peak), competition: low|medium|high, summary:"one sentence on the opportunity", at}`.
- Use WebSearch for real People-also-ask questions, autocomplete and the strength of the ranking results. Mark demand as an estimate. Peak months come from what you can verify (seasonality, events, holidays), never from guesses.
- Refresh a trip's signals when its pieces change or when they are 90 days old.
- The desk's call is saved in `settings/trip_priority {picks[{trip_key, start_with, by, why}], note, at}`. Respect it when choosing what to build next.

### Accommodations and the photo list (added 26 Oct 2026)
- **Accommodations.** `accommodations/<id>` = `{name, aliases[], trips[], notion_id, notion_url, status: linked|missing, agency_review}`.
  - The record links each hotel or experience on a trip to Coco's Notion **Accommodation Master List** (`collection://fffece75-bb28-812b-bb4b-000bd96df58b`), the same list Agency Studio reviews link to through `accomMaster`.
  - Where an Agency Studio review already carries `accomMaster`, that link wins.
  - When a trip gains a hotel, match it by name to the master list. Prefer Coco's own records (ids containing `ece75bb28`).
  - Set `accomMaster` on the matching Agency Studio review when it is empty. Never overwrite one she set, unless it names a different property; if it does, fix it and say so in your notes.
  - A hotel with no master-list record is `status: "missing"`. Do not create Notion pages unless Coco asks.
  - Verdicts and Shortlists cite the master-list record in `notes`, where you'll find the GM, consortia and perks. Never copy phone numbers, emails or booking references from it.
- **Photo list.** `photo_stash/<id>` = `{region, text, trip_key, trip_id, places[], hotels[], experiences[], month, days}` holds places, stays and experiences Coco has photos of that the Passport trips don't show. She keeps adding them.
  - Entries matched to a trip are added to that trip's `ideas_queue` doc as `extras`, and the doc is re-queued with `rerun: "extras"`.
  - The ideas agent then adds only the new ideas the extras open up, for example a Verdict for the Hiram Bingham train or a Lima piece. It never duplicates existing planned pieces.
  - An entry without a trip is its own trip for ideas: use its region, places and month.

### Trips, rounds three and four: sections, the week, the brief, Search Console ideas (added 26 Oct 2026, desk v61)
- **Tabs.** Next 3 · Trips · Photos · Ideas · This week · Writing · Board · Trip · Your eye. The Trips tab offers Cards, List and Timeline (`ls bk-view`).
- **Pace.** `settings/plan_pace` `{articles, social, auto}` starts at 2 articles and 3 social posts a week.
  - The week proposal is `weekplan/<YYYY-Www>` with `v:2`, `items[]` and `accepted_at`.
  - Approving it writes `plan` docs with `kind: "article"|"social"`, `job_id`, `lib_id`, `channel`, `week`.
  - Articles go on Tue/Thu. Social goes on Mon/Wed/Fri.
- **Write it with me.** This button prefills New piece from a pipeline job. Its source is the keyword, PAA, cull picks, inspections, Granola, reflections and the photo list.
  - The saved job gets `from_pipeline: <pipeline job id>`.
  - The pipeline job becomes `status: "drafting"` with `draft_job: <new job id>`.
  - A job with `from_pipeline` takes its photos from `culls/<from_pipeline>`, not `culls/<its own id>`.
  - When the draft job goes live, the pipeline piece counts as live.
- **When a pipeline piece goes live:**
  - Append one line, `Live on cocotran.com: <url>`, to the matching Agency Studio review's `free` field. Never overwrite that field.
  - Add the URL to `live[]` on the matching `accommodations` doc.
- **Brief.** "Run this week's brief" saves `settings/brief_latest`. The per-trip brief saves `research/<trip id>.brief` `{headline, order[], photos[], answer_these[], social[], youtube, skip, at}`.
  - Read-only for the worker. Use it as the order of work when Coco says "do what the brief says".
- **More ideas from Search Console.** Saved in `settings/gsc_ideas` `{ideas[{query, impressions, trip_key, format, title, keyword, why}], at}`.
  - The sources are `insights.content_gaps` and the library's `rank.top_queries`.
  - "Add to <trip>" creates a planned pipeline job `pp-gsc-<slug>` with `idea.src: ["search-console"]`. Treat it like any other planned pipeline piece.

### Authority map actions (added 26 Oct 2026, desk v63)
- Each missing pillar on the Places map opens a panel. The panel offers: brief (saved on the job as `am_brief`), add to queue, edit now (New piece with `from_pipeline`), cull, add photos (job `photos[]`, asset ids), a date (a `plan` doc plus `publish_by`), and "more for this pillar".
- "More for this pillar" creates planned pipeline jobs tagged as extras, with `trip_id` when a trip matches the place. They are ordinary planned pieces. An extra never counts as the missing pillar itself.
- When a job has `am_brief`, follow it as the outline. Use `photos[]` uploads alongside `culls/<id>` picks.

### Trips Overview, clear path, shot plan push, trip import (added 26 Oct 2026, desk v65)
- **Overview tab.** The default Trips view. Every trip is broken down trip › country › city › hotels, with:
  - culled, edited and live counts per piece;
  - pillars per city, from the Authority map;
  - hotels reviewed;
  - a need-to-do score.

  It reads the desk data only.
- **Where to start.** `settings/needle_call` `{headline, start[{trip_key, city, action, why, impact, effort}], quick_wins[], skip, at}`. When Coco says "do what the desk said", work through `start` in order.
- **Clear path per trip.** `research/<trip id>.path` `{headline, steps[{kind: photos|edit|write|share, do, piece_title, why, minutes}], cull_first[], skip, at}`. Photos always come first.
- **Piece brief.** `jobs/<pipeline id>.piece_brief` `{angle, outline[{h2, covers, photo_slot}], answer_these[], first_hand[], photos{needed, have_culled, verdict, why, slots[]}, title_options[], meta, next_steps[], at}`. A job with `from_pipeline` follows its pipeline job's `piece_brief` as the outline, and uses `answer_these` as the questions it must answer.
- **Trip import (`type: trip_import`).** The Overview's "Import now" button queues it, or it runs by itself monthly.
  - **Every run:** if `settings/trip_import.auto_monthly` is not false and `last_run_at` is more than 30 days ago (or missing), run it before the rest of the queue.
  - **Passport side:** read the Passport's `trips` (https://claude.ai/artifact/TecDEdRmvTRrcU4vysqRJ2) and refresh `travel/pp-*`, using the existing Travel history rules and the same fields only.
  - **Agency Studio side:** re-mirror fam trips and inspections (the Agency Studio mirror rules).
  - **When done:** write `settings/trip_import` `{auto_monthly, status: "done", last_run_at, last_result{added, updated, removed, sources[], notes}}`, keep 12 `history[]` entries, and mark the job done.
  - **Security:** never copy passport numbers, dates of birth, addresses, booking references or traveller names.
- **Shot plan push (`type: shot_push`).** Read `trip_shots/<trip id>` `{shots[{id, date, place, spot, what, when, medium, done, src}]}`.
  - Write each shot into the Agency Studio famtrip with the same id: `days[].shots` on the day whose `date` matches, as `{id: "d_<shot id>", text: "★ <spot>: <what> (<when>, <medium>)", timing: <when>, done}`. A shot with no date goes on the first day.
  - Update shots with the same `d_` id in place. Remove `d_` shots that were deleted from the plan. Never touch Coco's own shots or the `r_` research shots.
  - Write with `if_version`.
  - Then set `pushed_at`, `push_status: "done"` and `push_count` on the `trip_shots` doc, and mark the job done.
  - If the trip isn't an Agency Studio famtrip, set `push_status: "failed"` and explain why in `push_note`.

## Hotels and the master list (`type: master_index` · `accom_add`; added 26 Oct 2026)

Queued from Trips. The sources are a trip's hotels card ("add to master list", "link", "Add a hotel from your master list", "add all missing") and the hotel rows in the Trips overview. Coco asked for this, so this job may create master-list pages. The usual rules still apply: never copy phone numbers, emails, booking references, rates, GM names or traveller names into the desk, a job, a post or the repo.

1. **`master_index` (refresh the index).** The job id is `master-index-<date>`.
   - Query the Notion **Accommodation Master List** (`collection://fffece75-bb28-812b-bb4b-000bd96df58b`).
   - For every record, write `master_index/<notion id without dashes>` = `{name, city, country, type, notion_id, notion_url, own}`. Here `own` = the id contains `ece75bb28`.
   - Copy only those fields. Delete `master_index` docs whose record is gone.
   - Set `settings/master_index` = `{status: "done", refreshed_at, count}`.
   - Run it whenever asked, and also at the end of any run that created master-list pages, so search stays current.
2. **`accom_add` (add a trip's hotel to the master list).** The job id is `accom-add-<slug>`. The job carries `accom_id`, `hotel{name, city, country, kind, type}`, `trip_id`, `trip_name` and `month`.
   - First, search the master list by name and aliases. If the property is already there, don't make a duplicate: link it as in step 3 and say so in `worker_notes`.
   - Otherwise create one page in the Accommodation Master List. Fill these properties where the database has them:
     - name
     - location/city
     - country
     - property type: hotel, lodge, camp, villa, resort or train; infer it from the name when `type` is empty
     - a note: "Added from the Studio Desk · <trip_name> · <month> · stayed | site inspection | visited"
     - source "Studio Desk"
   - Nothing else: no contacts, no rates, no booking details.
   - Then link it (step 3). Set the job `status: "done"` and the accommodation's `add_request.status: "done"`.
   - On failure, set `add_request.status: "failed"` with a one-line `note`. The desk shows a retry button.
3. **Linking (also what the desk does itself when Coco picks a record).**
   - The linked record is `accommodations/n-<notion id>` = `{name, aliases[], trips[], stays{<trip id>: "stay" | "inspected" | ""}, city, country, notion_id, notion_url, status: "linked", source: "Accommodation Master List"}`.
   - Merge `trips[]`, `aliases[]` and `stays` from the old `x-<slug>` doc. Set the old doc to `status: "merged", merged_into: "n-<id>"`; never delete it.
   - Also add the new record to `master_index` so desk search finds it straight away.
   - The Agency Studio `accomMaster` rule from **Accommodations and the photo list** still applies on the next run: set it on the matching review when empty.
4. **Hotels Coco adds by hand** are `accommodations/x-<slug>` docs with `status: "missing"`, `source: "Studio Desk"`, `trips[]` and `stays`. If she ticked "also add it to my master list", an `accom_add` job is queued with it.
   - Treat `stays[<trip id>]` like a Passport hotel's `kind`. `"stay"` means a stay she can review: Verdict rules and the rate floor apply. `"inspected"` means "I toured it" wording only.
   - Records with `status: "merged"` are ignored everywhere.

### Ideas queue: keyword ideas for past trips (separate agent, added 25 Oct 2026)
`ideas_queue/<trip id>` holds trips sent from the desk's **Content bank** (Trips → Content bank).
Each doc is `{trip_key, trip_id, src, name, start, end, kind, places[], hotels[{name,city,kind,nights,rating}], on_site[], old_posts[], status, score}`.
**Only the ideas agent works this queue. The scheduled worker never touches it.**

1. **Claim the trip.** Take the highest `score` with `status: "queued"`. Set `status: "running"`, `started_at` and `claimed_by` (your session) with `if_version`. If the write conflicts, skip that trip.
2. **Take stock.** Check what cocotran.com already has for each place and hotel: `/wp-json/wp/v2/search?search=<place>`. Also check the desk's `library` and the authority-map jobs `am-<place>-<pillar>`. Never propose a piece that already exists or is already queued. An old blog post on the same subject becomes `mode: "update"` with its `source_url`.
3. **Keyword research.** For each place and each stay, look at autocomplete, People also ask and the strength of the ranking results. Each idea is one pillar, in the house title format, with:
   - the primary keyword;
   - demand (high, medium or low, as an estimate);
   - `publish_by` (YYYY-MM, 6–8 weeks before that place's search peak; state the peak in `why`);
   - `photos`, `slots[]` and `cull_tips` (what readers save for this piece).
4. **Verdicts.** Only a Passport hotel with `kind: "stay"`, and only when it clears the rate floor: $700 a night, or $400 in a very low-cost country or for a very boutique, design-forward property. A hotel below the floor goes into the place's Shortlist or a Comparison instead.
5. **Rank.** Order by what moves the needle: demand × timing × gap on the authority map. Keep at most 8 ideas per trip; add 10–12 only for a trip with 5 or more places.
6. **Write the results.**
   - `research/<trip id>` = `{pieces[{place,format,title,keyword,demand,why,publish_by,photos,slots,update,source_url}], cull_tips{title:[...]}, places:[], at, by:"ideas-agent"}`.
   - One job per idea: `jobs/pp-<short trip>-<slug>` with `pipeline: true`, `trip_id` set to the queue doc's `trip_id`, and `status: "planned"`. Copy the field shape of `jobs/pp-br-the-verdict-janeiro-hotel-rio-de-janeiro`.
7. **Trust fields.** These come from the Passport trip:
   - A personal trip gets `disclosure: "Trip I took"`.
   - A fam trip leaves `disclosure` empty and asks in `needs_coco`.
   - A stay gets `visited: "Stayed · <Mon YYYY>"`. Anything else gets `"Visited · <Mon YYYY>"`.
8. **Close the trip.** Set the queue doc to `status: "done"` with `n_pieces`, `top` (the 3 best titles) and `done_at`. On an error, set `status: "failed"` and `error`.

**Never** build a post, touch WordPress, or set any job status other than `planned`. Coco moves planned pieces forward from the desk.
**Pace.** Up to 3 trips in parallel (subagents are fine). Commit nothing to the repo.

### Opening-block backfill jobs (`mode: "backfill"`, added 25 Oct 2026)
Element 30407 (The short answer · At a glance · visited · updated) is printed by every pillar template directly under the facts strip and hides itself while `short_answer` is empty. A backfill job carries `targets: [{cpt, wp_id, url, title}]` and, per target: read the live ACF text, write `short_answer` (≤420 characters, first person, standalone: what it is, where, what is distinctive, who it suits — from the piece's own words, no new facts), the five glance rows with the format's labels (stay: Setting / Best for / Standout / Consider / Stayed; comparison: Choose A if / Choose B if / Price difference / Both share / Stayed; guide, brief, shortlist, dispatch: Setting / Best for / Standout / Consider / Last visit) using only values the text states (an unknown row stays empty: the block hides empty rows), `primary_keyword` from the library record's keyword when empty, `secondary_keywords` / `ai_questions` from the record's `rank.queries` (question-shaped ones go to `ai_questions`), `updated_on` = the post's modified date (YYYYMMDD), `content_category` from CATEGORY_BY_FORMAT when empty. **Never** write `visited`, `visit_basis`, `disclosure`, `last_fact_check` or `booking_access` from inference: leave them and list the piece under `needs_coco` with the one question. Write straight to the live post (these fields render only when filled); purge SpeedyCache + Cloudflare once at the end; record `backfilled_at` on each library record and `done: [{wp_id, fields}]` on the job.

### Data-driven update jobs (from the Keywords desk, added 24 Oct 2026)
Jobs with `reason` starting `data:` (`data:push`, `data:climb`, `data:ctr`, `data:decay`, `data:expand`, `data:convert`, `data:retire`) were created from measured Search Console + GA4 numbers. They carry `notes` (the data block and, when present, the brief), `brief{title_lock, first_screen, sections[], glance_rows[], cuts[], internal_links[], conversion, schema, expected}`, `target_queries[]` and `rank_snapshot`. The brief is pillar-aware and editor-edited: `brief.sections[]` carry `field` (the pillar's ACF field or `NEW`), `action` (`keep|rewrite|add`), `placement` (`inside <field>` / `after <field>`) and `weight`; `brief.title_lock.seo_title` is the Rank Math title ONLY — never change the on-page headline/H1; `brief_edits{path:value}` are Coco's hand edits and win over everything else (they are already merged into `brief`). A job with `cpt_change{from,to,why}` (reason `data:cpt`) means rebuild the piece in the target format's fields per content-system.md, keep every first-hand detail and the voice, keep the slug if the new CPT allows it and otherwise 301 the old URL; set status `needs-coco` before anything irreversible. When a data job is built, write `built_at` (ms) and, when Coco marks it live, `live_at`; the desk tracks position and clicks from the brief's `baseline` and `library.rank_history`. Treat the brief as the editor's instruction: use its title lock verbatim unless it breaks the format's lock shape, open with its `first_screen`, build its `sections` in order with headings phrased like the queries, keep every first-hand detail, add the glance rows (leave `[COCO VERIFIES]` markers in place), place the conversion line where it says, and never change the URL. `data:retire` means propose noindex + redirect in worker_notes and set status `needs-coco`; do not delete anything. `data:convert` means the booking box / Planning Room line and the GA4 event, not a rewrite. In worker_notes, restate the rank_snapshot and the expected outcome so the Monday run can score the change.

## 5. Research jobs (`type: "research"`)

Queued from the Library's "Request keyword research". For the record `library/<library_id>`: WebSearch the title's subject 3–4 ways (the plain query, "is X worth it" / "A vs B", "where to stay in X", "X travel guide"), note what ranks (titles, shapes, competitors), the questions people ask (People-Also-Ask-style phrasing in results), and 2–3 AI-shaped questions this piece should own. Write back to the library record (pin `if_version`): `keyword`, `secondary_keywords[]`, `ai_questions[]`, `suggestions.seo[]` (title/meta/H1 per the title lock in `content-system.md`), `suggestions.generated_at`, `research_notes`. Mark the job `done`. If another live record already owns the keyword, set the job `needs-coco` naming the pair.

## 6. Library upkeep and the weekly rankings run

- **After any piece is built or updated**: create/update `library/<slug>` with url, title, cpt, pillar, format, category (from the job or `CATEGORY_BY_FORMAT`), goal, keyword (= focus keyword), `secondary_keywords`, `ai_questions`, `last_updated` = today, `status: live` (or `draft` until Coco publishes), `health: ok`. For a respin, set the old record `status: "merged-into:<new id>"`.
- **Monday rankings run** (scheduled "Coco Tran weekly rankings and library health", 7:51 Pacific): follow `rankings-integration.md`. Probe `GET https://searchconsole.googleapis.com/webmasters/v3/sites` (200 = the Network secret injects the Google token; send no auth; else mint a JWT from `GSC_SERVICE_ACCOUNT_JSON` if present). Read site URL + GA4 property id from desk `settings/analytics` (fallback env vars, default `sc-domain:cocotran.com`). Pull Search Console (through the **batch endpoint** per rankings-integration.md: the proxy decodes `%2F` in paths, so the per-site URL 404s outside a batch body) + GA4 (28d vs previous 28d); map URL → `library` doc (id = last path segment; else query on `url`); write `rank{…, pulled_at}` and `health` per `content-system.md`; open at most 5 `mode: update` jobs with `reason` ∈ {decay, stale}, the losing/top queries and a `proposed_fix`; write `07-ai-benchmark/rankings-YYYY-MM-DD.md`; commit. Without credentials: write `runs/rankings-YYYY-MM-DD {status:"skipped-no-credentials"}` and stop.
- **Social**: the desk makes kits (`social` collection); the worker doesn't post. If a job's piece was built, add `social_kit_made: false` to the library record so the desk shows it as pending.

## WordPress check (`type: wp_check`)

Queued from the Library's **Check WordPress for updates** button (job id `wp-check-<date>-<x>`). Coco updates and rewrites old articles with other Claude agents outside the desk, so this job brings the `library` collection back in line with what is actually on cocotran.com. **Read only on WordPress: never create, change or trash anything on the site in this job.**

1. **Start.** Set the job `status: "running"` and `settings/wp_check` `status: "running"`, `started_at` (ms). Keep the doc's other fields.
2. **Read WordPress.** Use Route A auth (probe `/wp-json/wp/v2/users/me`; send no Authorization header when the proxy injects it, otherwise `COCOTRAN_WP_USER` / `COCOTRAN_WP_APP_PASSWORD`). For `posts`, `pages` and every pillar CPT (`property`, `guide`, `brief`, `shortlist`, `dispatch`, plus any other public type in `GET /wp/v2/types`), page through `GET /wp/v2/<cpt>?per_page=100&page=N&status=publish,draft,pending,private,future&context=edit&_fields=id,slug,link,status,type,modified,modified_gmt,title,featured_media,content,acf,meta`. Without auth, read published items only (`status=publish`, no `context=edit`) and say so in `notes`.
3. **Match** each WordPress item to a library record: `wp_id` first, then the URL path (last path segment = record id), then the slug. Library records with `status` `retired`, `redirected` or `merged-into:` are not expected on the site.
4. **Classify** each record and write the library record (pin `if_version`, batches of ≤50):
   - **updated**: WordPress `modified` (site time, `YYYY-MM-DD`) is later than the record's `last_updated` and later than its `wp_modified`, and no desk job made that change (no job with this `library_id`/`wp_id` whose `live_at` or `built_at` is within 24 h of `modified`). Set `updated_outside: true`, `wp_changes[]` (short human phrases: "title changed", "SEO title changed", "body rewritten (+620 words)", "new featured image", "slug changed", "now in Guide"), and refresh from WordPress: `title`, `url` (= `link`), `cpt`, `text` (ACF text fields joined, or `content.rendered` stripped of tags, ≤40k chars), `visible_words`, `seo_title` / `meta_description` / focus keyword (Rank Math meta, when readable), and the featured image in `photos_list[0]` ({id, url, alt}). Set `last_updated` = the WordPress modified date. If `health` was `stale` and the post was modified in the last 90 days, set `health: "ok"`, `health_note: "updated outside the desk <date>"`. Never overwrite the desk's own fields: `category`, `goal`, `notes`, `pillar` (only fill it when empty), `rank`, `rank_history`, `suggestions`, `marketing`; `keyword` only when empty.
   - **new**: a published item with no library record → create `library/<slug>` with `url`, `title`, `cpt`, `pillar` from the CPT (property → The Property, guide → The Guide, brief → The Brief, shortlist → The Shortlist, dispatch → The Dispatch, post → Old blog post), `format` when the format taxonomy says it, `text`, `visible_words`, `last_updated`, `status: "live"`, `health` unset, `new_from_wp: true`, `needs_classify: true`, `source: "wp_check"`. Skip pages that aren't articles (home, about, contact, legal, Planning Room, archive and landing pages); list the skipped ones in `notes`.
   - **unpublished**: the item exists but its status is `draft`, `pending`, `private` or `trash` → `wp_status` = that status.
   - **missing**: the record's URL answers 404 and no item matches → `wp_status: "missing"`. Never delete the record.
   - **unchanged**: nothing to change beyond the check fields.
   Every matched record also gets `wp_id`, `wp_status` (`publish`, …), `wp_modified` (ISO), `wp_type`, `last_wp_check` (ms). Clear `new_from_wp` on records older than 30 days.
5. **Write the run** to `settings/wp_check`: `{status:"done", checked_at (ms), started_at, request_job, since, counts:{checked, updated, new, unpublished, missing, unchanged, skipped}, items:[{kind:"updated"|"new"|"unpublished"|"missing", library_id, wp_id, cpt, title, url, wp_modified, desk_last_updated, changes[]}] (newest first, ≤300; leave out unchanged), history: [{checked_at (ISO), counts}] (this run first, keep 12), notes}`. On failure: `status: "failed"`, `error` (one line), and keep the previous `items`/`counts`.
6. Mark the job `done` with `worker_notes` = the counts and anything Coco should know (e.g. records that changed pillar, posts the desk can't classify). Then, if any record was updated or created, rebuild the link map as in the link-map step, since titles and keywords may have moved.

## 6a. FAM trips and inspections (collections `trips`, `inspections`)

Mirrored from Agency Studio (`https://claude.ai/artifact/7VMcx1iKdmvdtq6KT6cgix`): collection `famtrips` (each doc `{title, updated, data}` where `data` is JSON `{id,name,destination,startDate,endDate,purpose,days[{date,title,place,items[{type:"hotel"|"experience",name,notes,photos,reviewId}]}]}`) and `reviews` (`data` JSON `{id,name,location,visitStart,visitEnd,purpose,status,rating,free,answers{…},photos[],fromTrip{id}}`). Once per run, re-mirror: `trips/<id>` = {id,name,destination,startDate,endDate,purpose(≤600),hotels[] (item names of type hotel or matching check-in/hotel/lodge/camp/palace/resort, "— check-in…" stripped),days,status upcoming|live|past,source,synced_at}; `inspections/<id>` = {id,name,location,visitStart,visitEnd,purpose,status,rating,free(≤1500),answers_text ("key: value" lines, ≤7000),answers_count,photos,fromTrip,source,synced_at}. Pin `if_version` on existing docs; skip the "Untitled trip" placeholder. The desk's Today desk turns these into prep tasks and "Write the Verdict" buttons; for a `past` trip whose inspection has `answers_count ≥ 10` and no library piece whose title contains the property name, add one line to the report: "Inspection notes on file for X — no Verdict yet."

## 6b. Reader questions (Tally form `Nqpavl`)

Readers ask through "Ask Coco about this place" (https://tally.so/r/Nqpavl, embedded under every Verdict/Guide with hidden fields `page_url` and `piece`). Each submission emails hello@cocotran.com. The desk's Reader questions desk lists them (`fetch_submissions`) and "Answer this" prefills an Answer piece; "Queue for the worker" writes a `jobs` doc with `reason: "reader-question"`. Treat those jobs like any `new` job in format `answer`, drafting only from Coco's published text on the subject (site-search it) and marking the rest `[VERIFY]`. Add each answered question to `05-editorial-system/ai-faq-question-bank.md`.

## 6c. Suppliers (`suppliers` collection)

Mirrored from Agency Studio's supplier CRM by `11-builds/suppliers-sync.md`. The desk matches a job's subject against `suppliers` and shows the contact + "Draft the GM email". When a Verdict job is `queued` and no supplier matches, say so in `worker_notes` so Coco can add the contact in Agency Studio; never invent a contact. Re-run the sync (same doc) whenever Agency Studio's records change.

## 6d. The link map (internal autolinks)

After any piece goes live, is retired or merged, and at the end of the Monday run: rebuild the link map from the `library` collection and `POST /wp-json/coco-tran/v1/link-map` per `11-builds/wordpress-assets/link-map-builder.md` (keyword + title + hotel-name aliases + destination; priority Commercial 3 / Decision-support 2 / else 1; skip `retired` and `merged-into:` records). Record `version`, `stored` and any `rejected` in the run report; the desk reads `GET /autolink-report?post=ID` per piece.

## 7. Report

One short message: pieces drafted / researched / built with preview links, anything `needs-coco` and why, VERIFY items left. Lead with corrections that matter to her business (a closure, a rebrand, a rule change found in research).

## Published pieces on the Plan calendar (`settings/published_index`, added 27 Oct 2026)
Coco wants everything she publishes on cocotran.com to show on her Plan calendar by itself, whoever published it.
- **The index.** `settings/published_index` = `{items:{<wp id>:{d:"YYYY-MM-DD", t:title, u:url, y:post type}}, synced_at (ms), count}`. The desk shows every item as a green "published" event on its date, so nothing needs to be written to `plan`.
- **Every run, before the queue:**
  1. Read the doc.
  2. Fetch what's been published since `synced_at`, using an unauthenticated GET of `https://cocotran.com/wp-json/wp/v2/<type>?after=<synced_at as ISO>&per_page=100&_fields=id,date,link,title,type`. The types are posts, property, guide, shortlist, brief and dispatch. Page while 100 come back.
  3. Merge each one in by wp id: `d` is the date's first 10 characters, `t` is the HTML-decoded title, `u` is the link, `y` is the type.
  4. Write the doc back with `synced_at` = now and `count`, pinning `if_version`.
- **A new library record for anything new.** If a published item has no `library` record (matched by `wp_id`, then by URL), add one: `{id: <slug>, title, url, wp_id, cpt: type, format: from the title prefix ("The Rolodex:" → rolodex, ": The Dossier" → dossier, "Postcard From" → postcard, "A Table At" → fieldnotes, and so on), status: "live", new_from_wp: true, published_at: d, source: "published-index <date>"}`. Never overwrite an existing record. Set `published_at` on it only if it has none.
- **Unpublished or deleted posts.** A post that is no longer published stays in the index. The WordPress check marks it in the library instead.
- **Security.** Only titles, URLs, dates and types go in. Nothing from drafts or private posts.

## Notes, reminders and Google Calendar (desk only)
- **`reminders/<id>`** = `{title, date, time, note, remind: none|at|1h|1d, kind: note|reminder, done, gcal:{on, id, cal, synced_at}}`, written by the Plan page. The worker doesn't touch them.
- **Google Calendar runs from the page.** It uses the viewer's own Google Calendar connector (create_event, update_event, delete_event, list_calendars).
  - `settings/gcal` holds the chosen calendar.
  - `settings/gcal_map` maps the ids of synced posts and articles to their Google event ids.
- **The worker never creates or changes Google Calendar events.**

## Agency Studio link (`type: agency_sync` · `agency_promote`; added 28 Oct 2026, desk v72)
Agency Studio (https://claude.ai/artifact/7VMcx1iKdmvdtq6KT6cgix) and the desk describe the same trips, so keep them in step. Do this on every run, before the rest of the queue. Do it first when an `agency_sync` job is queued.

**How a trip is linked**
- One trip has one id: the Agency Studio famtrip id. Its Passport copy (`travel/pp-*`) folds into it in the desk.
- `settings/agency_link` = `{links: {<agency id>: <travel id>}, unlinked: {<agency id>: why}, synced_at, by}`.
- Link a trip automatically by, in order:
  1. its `passportId` (`"pp-" + passportId`);
  2. `pp-a<agency id>`;
  3. a Passport trip whose dates overlap or start within 10 days and whose name or country matches.
- Never change a link already in the map. Add new ones, and say why a trip stays unlinked.
- A piece's `trip_id` can be either id. The desk treats both as the same trip, so never rewrite `trip_id`s just to tidy up.
- If a famtrip's dates span more than 45 days and its Passport copy says otherwise, it's a typo in Agency Studio. Report it once; don't edit Agency's data.

**The full link pass (every run, and first on any "PRIORITY RUN" from Passport, Agency Studio or the desk)**

Coco starts it from three buttons: **Sync to Studio** in the Passport header, **Sync now** on an Agency Studio trip, and **Link & sync now** on the desk's Agency Studio card.

1. **Keep every link Coco already made.** Read them from:
   - `passportId` on famtrips, and `agencyId` on Passport trips;
   - `settings/agency_link.links`, `alias`, `inspections` and `kept`;
   - each inspection's `fromTrip`;
   - each photo-list entry's `trip_key` and `trip_id`;
   - each pipeline piece's `trip_id`.

   Never undo one of these. A trip in `kept` stays its own trip.
2. **Match what's still unlinked.** That's Agency famtrips, Passport trips, desk `travel/*`, photo-list trips (`photo_stash` `own_trip`) and inspections with no `fromTrip`. Try in this order:
   1. Dates that overlap or start within 10 days, plus a matching name, city or country.
   2. **No dates on one side: match by name.** Use the name, cities, and hotel or stop names.
   - Exactly one strong match: link it. For a photo-list trip or a duplicate, that means `alias[<id>] = <trip id>`. For an inspection, `inspections[<inspection id>] = <agency trip id>`, when its name matches a hotel or stop on the trip and its visit date falls inside the trip.
   - Two or more matches: add `{key, name, why, candidates[{id, name}]}` to `confirm` (the desk shows it under "Needs your pick").
   - No match: note it in `no_match`.
   - Never guess between two trips.
3. **Duplicates.** Two Agency famtrips with the same dates and mostly the same stops are one trip. Alias the newer one to the older one, explain in `duplicates`, and never delete either; Coco deletes the copy herself. (Done 28 Oct 2026: "Untitled trip" `mvggm5c91mv` = Brazil `0e3lu7mazo0`.)
4. **Dates both ways.** `settings/agency_link.dates[<agency id>]` = `{agency: [start, end], passport: [start, end], at}` is the baseline from the last pass.
   - **A blank on one side** (no start, or no end): copy it from the other side.
   - **One side changed since the baseline** and the other didn't: copy the change to the other side. Coco fixing a date in either app is the usual case.
   - **Both changed, or they differed before any baseline:** list it in `date_conflicts[{agency_id, name, agency, passport}]` and change nothing. Desk buttons write `date_pick[<agency id>] = {use: "agency" | "passport"}` and queue a `trip_dates` job. Copy the chosen side over, then set `date_pick[id].done = true` and drop the conflict.
   - **Where dates live:**
     - Agency Studio famtrip: `data.startDate` / `data.endDate` (the whole JSON string with everything else kept, `if_version`);
     - Passport trip: `start` / `end`;
     - desk mirrors: `trips/<id>` `startDate` / `endDate`, and `travel/<id>` `start` / `end`.
   - Write the new baseline after each change.
5. **Keep every copy current.** Re-mirror the Passport into the desk's `travel/*` and into Agency Studio's `passport/*` mirror, including `agencyId`.
6. **Report.** Write `link_pass_at` to `settings/agency_link`. Add one line per new link, pick or date change to `settings/agency_sync.last_result.notes`.

**Agency trip → Passport entry (automatic; Coco doesn't type it twice)**
- Coco may add a trip only in Agency Studio, from a PDF, a pasted itinerary, a link or Notion. Every run (Agency Studio fires one about 90 seconds after a change, at most every 30 minutes), check each famtrip that has dates and days.
- It needs a Passport entry when none is linked: no `passportId`, no Passport trip with `agencyId` = its id, and no Passport trip with overlapping dates and a matching name or country. Create one in the Passport's database (https://claude.ai/artifact/TecDEdRmvTRrcU4vysqRJ2), collection `trips`, doc `a<agency id>`:
  `{id: "a<agency id>", agencyId, name (short, no tour codes), start, end, source: "agency", createdAt, notionUrls: [], stops[{city, iso, lat, lng, verdict: "stay"}], hotels[{city, iso, kind: "stay", name, nights}]}`.
  - Cities come from the day places, in order, deduplicated. Leave out "in transit" and "→ home".
  - Hotels come from hotel items, with "— check-in…" stripped and nights taken from "(N nts)". Leave out placeholders such as "<city> hotel" or "assigned at check-in".
  - Add the trip's inspected hotels as `kind: "inspected"` with their `rating`.
- Then mirror it in two places: Agency Studio `passport/a<id>` as `{title, updated, data: <JSON string>}`, and desk `travel/pp-a<id>` (Travel history fields only). Add the link to `settings/agency_link`.
- An existing Passport trip that matches only by dates and name gets `agencyId` merged in (with `if_version`). Never create a second entry for it.
- When the Agency trip later changes (dates, cities, hotels), update the `source: "agency"` Passport entry it created. Never edit Passport trips Coco entered herself, except to add `agencyId`.
- Never copy booking references, confirmation numbers, traveller or guest names, phone numbers or prices into Passport.

**Passport trip → Agency Studio trip (automatic; Coco's no-itinerary path)**
- With no PDF, link or itinerary, Coco adds the trip in the Passport only. Every run, give these Passport trips an Agency Studio famtrip:
  - trips that are upcoming or in progress;
  - trips that ended within the last 60 days;
  - any Passport trip created on or after 28 Oct 2026.
- Skip a trip that is already linked (`agencyId`, a famtrip with its `passportId`, or a match in `settings/agency_link`). Skip "Earlier trips" and undated placeholders.
- Older backlog trips stay opt-in: the desk's "Send to Agency Studio" (`agency_promote`) or Agency Studio's own Passport list.
- Build the famtrip exactly as `agency_promote` does, same shape as Agency Studio's "Write from this trip":
  - for upcoming trips, `past: false`, `purpose: "Upcoming trip — content research and property visits"`, and the first day dated with the start date;
  - for past trips, `past: true`, `purpose: "Past trip — content research"`.
- Then do the rest:
  - merge `agencyId` into the Passport trip (with `if_version`);
  - mirror the famtrip into desk `trips/<id>` and add the link;
  - write its `tripplans/<id>` from the desk's pieces, or from the desk's `plan-<travel id>` job when one exists, keeping its `gm_questions`, `shots`, `reels` and `youtube`.
- Done 28 Oct 2026 for China December Trip (`jawjz2htcdg`) and Zambia & Zimbabwe (`aygtjnfr2nb`).

**Agency Studio → desk (mirror)**
- Follow §6a. Mirror every famtrip that has days (Passport-derived ones too, from `fieldrecords/<id>`) into `trips/<id>`. Mirror every review into `inspections/<id>`. Skip the empty "Untitled trip" placeholder.
- Scrub all mirrored text before writing:
  - booking, confirmation and reference numbers, meaning a keyword followed by a code that contains a digit (write "ref on file");
  - balances and amounts owed;
  - phone numbers and emails;
  - the names of consultants, reps, contacts and tour managers;
  - btl data, guest names and traveller names: never copy these.
- Keep Coco's own words and public trip codes.

**Desk → Agency Studio (what each trip can publish)**
- For every linked trip with pieces, write `tripplans/<agency id>` in Agency Studio's database as `{data: <JSON string>, updated: <epoch ms>, source: "studio-desk"}`.
- The JSON is `{trip_id, written_at, source: "studio-desk", desk_url, rules[], pieces_total, places[{place, priority, why, hotels[], pillars_missing[], target_keywords[], pieces[{id, title, type, keyword, status, publish_by, photos, needs_coco[]}]}]}`.
  - Group pieces by `place`, and rank places by search demand.
  - On a country-level place, `hotels` stays empty; hotels go on their cities.
  - `status` follows the desk: `planned` → `photos picked` (cull approved) → `edited` → `drafted` → `live`.
- Keep every field already on a place that the desk doesn't write: `gm_questions`, `shots`, `reels`, `youtube`, `search_questions`, `site_has`. Also keep places that have no pieces yet.
- Write with `if_version`.
- Agency Studio's "Ask Studio Desk for the plan" fills the same document.

**Agency suggestions → desk pieces**
- A plan place's `pillars_missing`, and inspections that are `ready` or have evidence 2–3, become planned pipeline pieces under the usual Trips pipeline rules:
  - photos first;
  - the rate floor for Verdicts;
  - no Verdict from a past Passport trip unless a stop has evidence 3;
  - `trip_id` = the agency id.
- Never duplicate an existing piece: the same format and place, or the same primary keyword, is a duplicate.

**Send a Passport trip to Agency Studio (`agency_promote`)**
- The desk's "Send to Agency Studio" button queues `{travel_id}`. Build the famtrip from Agency Studio's `passport/<travel id without "pp-">` doc, exactly as Agency Studio's own "Write from this trip" does:
  - `{id: <new 11-char id>, name, destination: first three cities, startDate, endDate, purpose: "Past trip — content research", days: [one per city {id, title: city, place: city, description: "", date: "", items: [hotels on that city {id, type: "hotel", name, time: "", notes: "", photos: [], reviewId: null, nights}], reflect: {}, shots: [], outfit: [], memory: {}}], passportId, countries, past: true, importedFrom: "passport", createdAt}`.
- Write it as `famtrips/<id>` `{title, updated, data: <JSON string>}`.
- Then add the link, write its tripplan, and mark the job done.
- Never copy traveller names, passport numbers or booking references.

**Report**
- Write `settings/agency_sync` = `{status: "done", at, last_result{trips_mirrored, inspections, links_added, plans_written, pieces_added, promoted, notes}}`, then mark the job done.

## Warmth rewrites (`type: warmth_rewrite`, added 27 Oct 2026)
Coco found the recent articles too matter-of-fact. The voice was rewritten (`05-editorial-system/voice.md`, the desk's `const VOICE`), and recent pieces are being rewritten to match. Every rewrite keeps every fact, number, name, link, [VERIFY] and [Coco to fill] marker, and invents no experience she didn't report.
- **The job carries the rewritten text.** It is `draft.voice_pass{at, note}`, with the old text kept in `draft_prev{short_answer, glance, fields}` and the new text in `draft`.
  - **Not built yet** (`drafted`, `queued`): build as usual from the new `draft`.
  - **Built as a WordPress draft** (`built`, a `wp_post_id`, post status draft or pending): push the new `short_answer`, `glance` and `fields` into that post's ACF fields, keep it a draft, set `voice_pushed_at`, and keep the job `built`.
  - **Live on the site:** don't change the live post. Set the job `needs-coco` with `needs_coco: ["Read the warm rewrite and approve it"]`. When she approves, update the live post; WordPress keeps the old revision.
  - **Coco's standing approval (27 Oct 2026: "publishing automatically").** A `warmth_rewrite` job with `auto_publish: true` and `status: "approved"` is already approved. Update the live post right away:
    - push the new short answer, glance values and text fields into the post's ACF fields;
    - set `updated_on` to today;
    - leave everything else as it is: title, slug, URL, images, links, SEO title and categories;
    - then clear the cache and set the job `published` with `voice_pushed_at` and the live URL.

    This is the one exception to "Never publish": it applies only to warm rewrites of posts that are already live, and never to new posts.
  - **`short_answer` is capped at 420 characters** by the site's ACF field. A longer value is silently dropped and the old text stays. Trim it to 420 or under before pushing. After every write, confirm with an unauthenticated GET of `/wp-json/wp/v2/<type>/<id>?_fields=acf` that the live values equal the job's.
  - **Live-rewrite jobs** are `warm-live-<wp id>` and carry `wp_id`, `post_type`, `draft{short_answer, glance, fields}` and `draft_prev`. Map the glance rows back to `glance_N_label` / `glance_N_value` in order. Write only the fields in `draft.fields`.
- **The text lives only on WordPress** (`warmth_rewrite` jobs with `wp_post_id` and no full `draft.fields`): read the post's ACF fields, rewrite every text field in the voice.md voice, and save the result on the job as `draft` with `draft_prev`. Then follow the rules above (draft → push; live → `needs-coco` for her approval).

## Approved jobs: Coco edits in the desk, the worker builds what she approved

Coco resolves everything in the Studio Desk, not in WordPress. In the Queue piece view she edits any field in place, replaces every `[VERIFY …]` / `[Coco to fill …]` bracket with the fact, confirms / corrects / cuts each item of `draft.verify` (stored in `verify_notes[i] = {status: confirmed|corrected|cut, note, item}`), runs the writing checklist (the desk runs `deskPassPrompt`, writes `report.gates`, `checked_at`), and only then can press Approve (`status: "approved"`, `approved_at`, `approved_draft_rev`). So on an approved job:

1. **Never rewrite her text.** `draft` is the final version. Do not regenerate, re-voice or "improve" it. Apply only mechanical mapping to ACF fields.
2. **Apply `verify_notes`** that are `corrected` (replace the claim in the text with her wording) or `cut` (remove the sentence). `confirmed` means leave as written.
3. **Brackets left anywhere in the text (`[VERIFY`, `[Coco`, `[COCO VERIFIES`) stop the build.** Set `status: "needs-coco"`, list them in `worker_notes`, do not touch WordPress.
4. **Final check**: run the ten gates once more over the final text. Write `report.final_check = {gates, checked_at}`. A FAIL on a gate she has not marked `PASS (Coco)` → `needs-coco` with the gate and the reason. Otherwise build.
5. Build. **New pieces**: a WordPress draft as before. **Update jobs (`mode: "update"`) never touch the live post at this step.** Build a DRAFT COPY instead (status draft, never private or publish): same CPT, same taxonomy terms, same ACF values and image IDs as the live post, then the approved changes applied; `post_status: private`; slug `<live-slug>-rewrite-<yyyymmdd>`; Rank Math robots `noindex,nofollow`; title unchanged. Write `status: "built"`, `wp_id` = the copy, `live_wp_id` = the live post, `preview_url` = the copy's permalink. The desk shows "Publish · needs Coco" with Open in WordPress (the copy) and a **Replace the live article with this draft** button.
6. **When the job carries `publish_requested_at`** (she pressed that button): copy every changed ACF field, title, Rank Math title/description/focus keyword from the copy onto the live post (`POST /wp/v2/{cpt}/{live_wp_id}`), keep the live slug and URL, set `last_fact_check`/`updated_on` to today, trash the copy, then write `status: "published"`, `live_url` (the live permalink), `live_at`, `wp_id` = live post, and update `library/<id>` (`text`, `seo_title`, `meta_description`, `last_updated`, `rank_history` checkpoint `moved_after_rewrite`). Only then is the rewrite live.
7. A new piece she published herself in WordPress is still closed with **This is live ✓** in the desk, as before.

## The Respin Pipeline spec is the law for migrations
Read `respin-pipeline.md` in this folder before any respin, merge, redirect or four-pillar set. It overrides anything here that disagrees. The points that bite most:
- A piece is built only if Coco's own photographs can carry it; stock means park and tell her. Contact sheet first.
- Decide the merge before writing. Per destination, where photos allow: Primer, Rolodex, Postcard, Brief, each with its own primary_keyword.
- Voice: warm, first person to "you", private and escorted as the default, top-tier hotels only, no <em>, CTA to https://cocotrantravel.com/contact before "pair it with".
- Field names with typos are reproduced exactly; restricted selects (content_category, disclosure, health, trip_type) and the 420-character short_answer cap fail the whole write.
- Always tag the destination chain: continent, country, city.
- Redirects only through Rank Math → Redirections → Add New, after she has published, then trash, then cache. **Never /rankmath/v1/updateRedirection.**
- Check "Facts worth not re-researching" and "Known traps" before fact-checking or debugging.

## Respin jobs (`mode: "respin"`, queued from the desk's Respin section)
One job per pillar of a destination set. The job carries `cluster` (term_id, chain), `source_urls` / `source_wp_ids` (the old posts to fold in), `format`, `cpt`, `primary_keyword`, `secondary_queries`, `set` (the sibling pillars queued with it), `photos_estimate` and `notes` with the search numbers. Work it exactly as `respin-pipeline.md` says:
1. Survey the old posts and pull their image IDs; make the contact sheet; if Coco's own photography cannot carry this pillar, `status: "needs-coco"` with the count and why (never stock), and stop.
2. Fact-check against primary sources, VERIFIED / UNVERIFIED on each claim; drop the unverified; check "Facts worth not re-researching" first.
3. Build ONE new post for this pillar (draft), merging every source post: format taxonomy term + ACF format string, full destination chain, featured image and alt text, the field map for the format with the exact (typo'd) names, the content-ops layer (short_answer ≤ 420 chars, five glance rows, `primary_keyword` from the job, `secondary_keywords`, `ai_questions`, `content_category` by pillar), Rank Math title ≈60 / description ≈155 / focus keyword = `primary_keyword`. Trust fields blank unless the job says; CTA to https://cocotrantravel.com/contact before "pair it with"; no `<em>`.
4. Keep this pillar's angle and keyword distinct from the siblings in `set`.
5. Write back `status: "built"`, `wp_id`, `preview_url`, `draft` (so the desk piece view can show, edit and checklist it), `redirects: [{from: <old path>, to: <new permalink>}]` for every source URL, `worker_notes` leading with corrections that matter to her business.
6. Coco reviews in the desk, publishes in WordPress, and presses **This is live ✓**; the worker then posts the 301s itself to `/wp-json/coco-tran/v1/redirects` (never Rank Math's API). When she presses **This is live ✓**, on the next run: fetch each old URL; only where it already answers with a 301 to the new piece, trash that old post and set its library record `status: "redirected"`, `redirect_to`. Old URLs that still return 200 stay untouched and are listed in `worker_notes` for her.
