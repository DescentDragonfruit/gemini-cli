---
name: studio-pipeline
description: The worker half of the Coco Tran Studio Desk. Picks up jobs Coco queued in the Studio Desk artifact, drafts anything not yet drafted, does the search research (title/meta/H1/keyword against what actually ranks), reads Proposal Studio / Agency Studio records, finalises the piece in her voice and pillar structure with the answerable opening, builds the WordPress draft (REST with an Application Password, or Coco's logged-in Chrome in Cowork), and writes status back. Use when asked to "run the studio queue", "process the desk", "build the queued pieces", or on the scheduled run.
---

# Studio pipeline — the worker

Coco fills the Studio Desk (https://claude.ai/artifact/NVykejaCwzyJDdN2G1NMQt); this skill does everything after. Pair it with `coco-editorial-standards` (voice, gates, opening block) and, for a Verdict, `property-review-writer`; for migrations, `respin-old-content` (field maps, Kadence/REST mechanics).

## 0. Read the queue

`ToolSearch select:ArtifactData`, then `ArtifactData query` on url `https://claude.ai/artifact/NVykejaCwzyJDdN2G1NMQt`, collection `jobs`, where `status in ["queued","drafted","approved"]`, order_by updatedAt asc. Each job carries: `format` (key), `subject`, `dest`, `basis`, `disclosure`, `photos`, `notes`, `granola[]` (title/date/notes), `proposal`, `agency`, and for drafted jobs `draft` (post_title, kicker, fields{}, selects{}, short_answer, glance[], seo{}, links[], verify[], photos_needed[]) + `report`. Every write back pins `if_version`.

Nothing queued → say so in one line and stop.

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

Use the Studio Desk's own prompt structure (format rules + field map + opening block + SEO set — copy from the artifact's `FORMATS` and `draftPrompt`, or from `coco-editorial-standards`), with the full context from step 1. Then run the gates/voice pass (`deskPassPrompt`). Write `draft`, `report`, `status: "drafted"`.

## 3. Research (status `drafted` → `researched`)

Make the SEO set true to what ranks, not just plausible:
1. WebSearch (standard) the `seo.primary_query` and 2–3 `secondary_queries`. Note the top titles, whether they're "X vs Y" / "is X worth it" / "where to stay in X" shapes, any People-Also-Ask-style questions in results, and which competitors hold the page (`07-ai-benchmark/` lists the usual ones).
2. Site-search `site:cocotran.com <subject>` to find sibling pieces to link and any **duplicate** already covering the query (if one exists: stop, write `worker_notes` naming it and set `status: "needs-coco"` — one topic, one piece).
3. Finalise: `seo_title` ≤60 in the winning shape (hotel pieces: "[Hotel] Review: …" / "[A] vs [B]: Which…"; guides: "[Place] Travel Guide: Where to Stay, Eat and Explore"; answers: the question), `meta_description` ≤155, `h1`, `slug`, `focus_keyword`, and the `links` list resolved to real cocotran.com URLs. For a Primer or Answer, make sure the FAQ/short answer covers the questions people actually ask (from step 1).
4. Fact pass: every `[VERIFY]` item — verify from a primary source (hotel site, official tourism, government) via WebSearch/WebFetch; replace with the fact and a one-line source note in `worker_notes`, or leave the marker and list it under `needs_coco`. Never remove a marker without a source. Closure/rebrand claims get two sources.
5. Write back: `draft.seo`, `draft.links`, `draft.fields` (only where a VERIFY was resolved), `research: {queries:[], competitors:[], shapes:[], notes:""}`, `status: "researched"`.

Coco approves in the Desk (status → `approved`). If a job is `researched` for more than 7 days, leave it.

## 4. Build in WordPress (status `approved` → `built`)

Route A — **REST with an Application Password** (preferred when `COCOTRAN_WP_USER` / `COCOTRAN_WP_APP_PASSWORD` env vars exist in the environment; auth header `Authorization: Basic base64(user:app_password)`; drafts and `context=edit` reads also need it):
1. Confirm the live ACF keys: `GET https://cocotran.com/wp-json/wp/v2/{cpt}?per_page=1&_fields=acf` on a published example of the same format; map provisional keys (Primer, Comparison) to the real ones; drop keys that don't exist rather than guessing.
2. Destination terms: `GET /wp/v2/destinations?search=<city>` for continent + country + city IDs (hierarchical — all three).
3. Create the draft: `POST /wp/v2/{cpt}` with `{status:"draft", title, slug, destinations:[ids], acf:{…fields, …selects, format/dispatch_format/brief_format term where the format lives in a taxonomy — see respin-old-content for the term IDs (Field Notes 762, Postcard 760, Roundup 765, Rolodex 763, Dossier 758, Route 759)}}`. Send ACF text in 2–3 updates if large.
4. Answerable opening: if the `Answerable opening` ACF group exists (`short_answer`, `glance_*`, `visit_basis`, `disclosure`, `updated_on`) write those fields; if not, prepend the short answer as the first `<p>` of the first text field and put the glance rows as a `<p>` block after it, so nothing is lost.
5. Rank Math: `POST /wp-json/rankmath/v1/updateMeta` with `{objectID, objectType:"post", meta:{rank_math_title, rank_math_description, rank_math_focus_keyword}}` (raw fetch with the nonce when in Chrome; basic auth on REST).
6. Photos: if `job.photos` names WP media (a filename stem), `GET /wp/v2/media?search=<stem>&per_page=50`, assign IDs to the format's image slots in order (field maps in respin-old-content), set the featured image; write alt via the Coco Photo CMS fields if the plugin is installed. If photos are elsewhere, leave slots empty and say so.
7. Verify: `GET /{cpt}/{slug}/?preview=true` renders (or `wp/v2/{cpt}/{id}?context=edit` shows the fields saved), no placeholder text, title fits.
8. Write back: `status: "built"`, `preview_url`, `wp_id`, `worker_notes` (what was mapped, what's missing).

Route B — **Cowork with Coco's logged-in Chrome**: as respin-old-content describes (`wp.apiFetch` from a `/wp-admin/edit.php?post_type=guide` page, small calls, try/catch, never return raw HTML/URLs through the extension). Same steps.

Route C — **no site access**: write the full payload to `cocotran-website-audit/09-studio-output/<slug>.json` (the Desk's "WordPress payload" shape), commit + push on `cocotran-website-audit`, set `status: "needs-site"` with `worker_notes` saying where the file is. Cowork pushes it next time.

Never publish. Coco clicks Publish. After she reports it published: redirects if it replaces an old post (Rank Math → Redirections → Add New), cache + Cloudflare purge reminder, `recheck.sh` on the URL, add the piece's question to `ai-faq-question-bank.md`.

## 5. Research jobs (`type: "research"`)

Queued from the Library's "Request keyword research". For the record `library/<library_id>`: WebSearch the title's subject 3–4 ways (the plain query, "is X worth it" / "A vs B", "where to stay in X", "X travel guide"), note what ranks (titles, shapes, competitors), the questions people ask (People-Also-Ask-style phrasing in results), and 2–3 AI-shaped questions this piece should own. Write back to the library record (pin `if_version`): `keyword`, `secondary_keywords[]`, `ai_questions[]`, `suggestions.seo[]` (title/meta/H1 per the title lock in `content-system.md`), `suggestions.generated_at`, `research_notes`. Mark the job `done`. If another live record already owns the keyword, set the job `needs-coco` naming the pair.

## 6. Library upkeep and the weekly rankings run

- **After any piece is built or updated**: create/update `library/<slug>` with url, title, cpt, pillar, format, category (from the job or `CATEGORY_BY_FORMAT`), goal, keyword (= focus keyword), `secondary_keywords`, `ai_questions`, `last_updated` = today, `status: live` (or `draft` until Coco publishes), `health: ok`. For a respin, set the old record `status: "merged-into:<new id>"`.
- **Monday rankings run** (scheduled "Coco Tran weekly rankings and library health", 7:51 Pacific): follow `rankings-integration.md`. Pull Search Console + GA4 (28d vs previous 28d) with a service-account JWT from `GSC_SERVICE_ACCOUNT_JSON`; map URL → `library` doc (id = last path segment; else query on `url`); write `rank{…, pulled_at}` and `health` per `content-system.md`; open at most 5 `mode: update` jobs with `reason` ∈ {decay, stale}, the losing/top queries and a `proposed_fix`; write `07-ai-benchmark/rankings-YYYY-MM-DD.md`; commit. Without credentials: write `runs/rankings-YYYY-MM-DD {status:"skipped-no-credentials"}` and stop.
- **Social**: the desk makes kits (`social` collection); the worker doesn't post. If a job's piece was built, add `social_kit_made: false` to the library record so the desk shows it as pending.

## 7. Report

One short message: pieces drafted / researched / built with preview links, anything `needs-coco` and why, VERIFY items left. Lead with corrections that matter to her business (a closure, a rebrand, a rule change found in research).
