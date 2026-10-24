# The answerable opening — on-brand design, bulk backfill, and the go-forward workflow

Goal: every piece opens with something a reader, Google and an AI engine can lift in five seconds, without touching the voice, the photography or the gated depth. One template block, five content types, two implementation routes (bulk for the 75 live pieces + 105 old posts; automatic for everything new).

## 1 · The block, designed for the site

The site's language is serif display, generous white space, kicker labels in small caps. The block should read as part of that, not as an SEO box. Three elements, in this order, between the kicker/H1 and the opening image:

```
THE VERDICT · RAVELLO, AMALFI COAST                      ← kicker (already exists)
Caruso, A Belmond Hotel, Ravello                          ← H1 (already exists)

Photos & words by Coco Tran · Stayed June 2025 · Updated 23 Oct 2026   ← byline strip (small caps, hairline rule under)

┌ The short answer ─────────────────────────────────────────────────┐
│ Caruso is the Belmond on the cliff above Ravello, the quietest     │   ← 2–3 sentences, serif italic? No —
│ of the Amalfi grand hotels and the one with the pool everyone      │     keep roman; use the display serif at
│ photographs. Book it for a slow, couples' week above the coast;    │     body size, set off by a thin left rule
│ skip it if you want to walk to the beach.                          │     in the brand accent, no box fill.
└───────────────────────────────────────────────────────────────────┘

AT A GLANCE                                                          ← small caps label
Setting        Ravello, 365 m above the coast · 1h30 from Naples      ← two-column definition list,
Best for       Couples, a slow week, privacy                          hairline rules between rows,
Standout       The infinity pool and the Belvedere terrace            label in small caps grey,
Consider       No beach; 20-min shuttle to Amalfi; June rates ~€1,400 value in body serif.
Stayed         June 2025 · Junior Suite 312 · Paid stay               Collapses to one column on phone.
```

Design rules: no coloured background, no icons, no borders except hairlines; the same type as the body; the "Short answer" is the only place the brand accent appears (a 2px left rule). It should look like the standfirst in a printed magazine. Below it, the piece continues exactly as now ("The opening scene", the MOMENT lede, the narrative). Nothing is removed.

Per-format labels and fields:

| Format | Short-answer label | At-a-glance rows |
|---|---|---|
| The Verdict (Property) | The short answer | Setting · Best for · Standout · Consider · Stayed (month, room, paid/hosted) |
| The Comparison | The short answer | Choose A if · Choose B if · Price difference · Both share · Stayed |
| The Primer | Before you book | Best months · How long · Base yourself in · Book first · Last visit |
| The Rolodex | The short answer | Base in · Stay (3 names) · Book ahead · Don't miss · Last visit |
| The Route | The shape of the trip | Nights · Who · Season · Stops (a·b·c) · Transport |
| The Dossier | In one breath | Visa · Money · Best months · Getting around · Safety note |
| The Shortlist | The three to know | Top pick · Second · Wildcard · Price band · Last visit |
| Postcard / Field Notes | — (keep as is) | one line: Visited · Open hours/cost · Nearest station |

The last line of every block is the **disclosure + method line** in small grey text: "Paid stay. Hotels here can be booked through Coco Tran Travel. [How we review](/how-we-review/)."

## 2 · Where the data lives (ACF)

Add one ACF field group **"Answerable opening"** attached to all five CPTs + posts:

| Field | Key | Type |
|---|---|---|
| Short answer | `short_answer` | Textarea (plain, 2–3 sentences, no HTML) |
| Glance row 1–5 label/value | `glance_1_label` … `glance_5_value` | Text ×10 (labels default per format) |
| Visited / stayed | `visited` | Text ("June 2025") |
| Room / basis | `visit_basis` | Text ("Junior Suite 312 · Paid stay") |
| Updated | `updated_on` | Date |
| Disclosure | `disclosure` | Select: Paid stay / Hosted stay / Discounted stay / Advisor roundup (not a stay) / Trip I planned / Trip I took |
| Not a stay? | `advisor_roundup` | True/False |

Then in each Kadence Element template (27262 Verdict, 29436 Tradeoff, 27517 Primer, 27738 Rolodex, 27762 Roundup, 27733 Route, 27556 Dossier, 27818 Shortlist, 27776/27810 Dispatch) insert one reusable **Kadence Element → "Answerable opening"** (a single Element with `show_conditionals` = all five CPTs, hooked after the title block) that binds to those fields with `kadenceDynamic` and hides rows whose value is empty. One Element, edited once, shows everywhere. Edit Elements with string split/join, never regex.

Schema: Rank Math's Article/Review per post type already covers headline/author/dates; map `updated_on` → `dateModified` via a `rank_math/json_ld` filter (10 lines) so the visible date and the schema date can't drift.

## 3 · Bulk backfill of the 75 live pieces (+ old posts)

Do it in batches by destination, in a session with the site connected (Application Password or Coco's logged-in Chrome via `wp.apiFetch`, as the respin skill does):

1. `GET /wp/v2/{cpt}?per_page=100&_fields=id,slug,title,acf` — pull each piece's existing ACF fields (Verdict: `overall`, `the_location`, `last_thoughts`, `good_to_know`, `rate_from`; Rolodex: `opening_note`, `best_season`, `where_to_stay`; Route: `overall`, `the_ask`, `trip_lenght_in_nights`, …).
2. For each piece, Claude drafts `short_answer` + 5 glance rows **only from facts already in the piece** (prompt in `publishing-checklist.md` Part 3, step 2), in Coco's voice, and flags blanks. Output as JSON per piece.
3. Coco reviews the JSON in one pass (the Editorial Desk artifact has a "Backfill review" tab: shows the piece's title, the proposed block, Approve / Edit / Skip).
4. `POST /wp/v2/{cpt}/{id}` with `{acf: {...}}` for approved ones, 2–3 per call, with try/catch; then verify at `?preview=true`.
5. `visited` and `disclosure` cannot be inferred — Coco fills those from her own records (a 75-row sheet, one afternoon). Until filled, the block shows without that row.
6. Old posts (105): same, but only for the ones kept after the respin/merge decision (`02-action-plan.md` phase 5). Posts headed for a 301 get nothing.

Batch order: Properties (6) → Primers (10) → Rolodexes → Routes → Shortlists → Dossiers → Roundups → Dispatches (one-line only).

## 4 · Go-forward workflow for new pieces (Claude-assisted)

1. **Intake** — Coco gives the link/notes/photos (per the property-review-writer or respin skill).
2. **Draft** — Claude writes the ACF fields per the format's field map, in the calibrated voice.
3. **Answerable opening** — Claude generates `short_answer` + glance rows + proposed `disclosure` from the draft. Coco confirms visited/basis.
4. **Pre-publication prompt** — the draft goes through `publishing-checklist.md` Part 3 (the Editorial Desk does this with one click). Fix the FIXes.
5. **Photos** — through Coco Photo CMS: descriptive filenames, alt, credit, WebP; the artifact pushes them straight into the post's image fields.
6. **Build the WordPress draft** (REST/ACF) including the opening block fields and Rank Math title/description/focus keyword.
7. **Coco publishes.** After publish: redirects if respun, cache purge, Cloudflare purge, `recheck.sh` on the URL, add to the AI-benchmark question bank if it answers a new question.

## 5 · What stays untouched

- The MOMENT opening, the narrative fields, the photography sequence, the gated depth — all unchanged.
- The format names and kickers.
- The voice. The block is written in it too (the Short answer is prose, not bullets; "Book it for a slow couples' week; skip it if you want the beach" is Coco, not a spec sheet).
