# Wire "The Comparison" (Brief) in WordPress — ≈25 minutes with site access

Place-vs-place comparisons (Bora Bora vs Maldives) live on the **Brief** CPT under a new `brief_format` term **The Comparison** (`05-editorial-system/content-system.md` → "Where a Comparison lives"). Hotel-vs-hotel stays on the Property CPT as The Trade-Off and needs nothing here.

The kit, all in this folder:

| File | What it is |
|---|---|
| `acf-comparison-brief.json` | The ACF group `group_ctt_comparison_brief` (19 fields, location = post type `brief` AND `brief_format:the-comparison`, all `show_in_rest`). |
| `kadence-element-brief-comparison.html` | The Kadence Element template: kicker → H1 → short answer → Choose A / Choose B → eight H2 sections → five images → booking box. Paste into the Code editor. |
| `brief-comparison-setup.php` | One idempotent script: creates the term, imports the ACF group, creates the Bora Bora / Maldives destination terms, prints the IDs. Nothing deleted. |
| this file | The clicks (path A) and the commands (path B), the test build, the checklist. |

Prerequisites already on the site: Kadence Theme + Kadence Blocks **Pro** (dynamic content), ACF Pro, Rank Math, and the mu-plugin `booking-box-shortcode.php` (INSTALL.md step 2) for `[ctt_book]`. The Answerable-opening Element (INSTALL.md step 3) should already be live at priority 5; the Comparison Element does not duplicate it.

Nothing here is destructive. The script skips anything that already exists; the Element is a new post; no template or post is edited.

---

## Path A — Cowork with Coco's logged-in Chrome (admin UI)

Work from a `wp-admin` tab. Order matters: term → ACF → destinations → Element → schema → test.

### A1. The format term (1 min)
**Briefs → Formats** (the `brief_format` taxonomy; it may be listed as "Formats" or "Brief formats" under the Briefs menu). Add New: Name `The Comparison`, Slug `the-comparison`, no parent. Note the term ID from the URL after saving (`tag_ID=…`). If a term with that slug already exists, use it.

### A2. The ACF group (2 min)
**Custom Fields → Tools → Import Field Groups** → choose `acf-comparison-brief.json` → Import. Then **Custom Fields → Field Groups**: "Comparison (Brief · destination vs destination)" is active and its Location reads *Post Type is equal to Brief* **and** *Post Taxonomy is equal to brief_format: The Comparison*. If ACF shows the taxonomy rule as unresolved (red), open the group, re-pick *brief_format → The Comparison* in the location rule, Save.

Already imported? ACF → Field Groups will show it; skip. Do not import twice (ACF updates in place by key, but there is no reason to).

### A3. Destination terms (2 min)
**Briefs (or Posts) → Destinations**. The taxonomy is hierarchical, so pick the parent each time:
- `Bora Bora` — parent **French Polynesia** (which sits under **Oceania**). Expected IDs from the audit: Oceania 632, French Polynesia 654 — look them up by name, do not assume. If French Polynesia does not exist, create it under Oceania first.
- `Maldives` — parent **Asia** (expected 628; look it up by name).
Write the two new term IDs down; the build needs them (step A6).

Alternative for A1–A3 in one go: **Code Snippets → Add New → PHP**, paste the whole of `brief-comparison-setup.php` (strip nothing — it is safe to paste as-is), set *Only run once*, Save and Execute. It prints what it found and created, including the IDs, and it embeds the ACF JSON so no upload is needed.

### A4. The Kadence Element (5 min)
1. **Appearance → Kadence → Elements → Add New.** Title: `Brief — The Comparison`.
2. ⋮ (top right) → **Code editor** → select all → paste the whole of `kadence-element-brief-comparison.html` → switch back to the visual editor. You should see the static kicker, "dynamic text placeholder" rows, two columns, eight H2s, five empty image blocks and one Shortcode block (`[ctt_book hotel="" perks=""]`). Kadence may renumber `uniqueID`s on save; the CSS targets class names, so that is fine.
3. Right sidebar → **Element Settings**:
   - **Type:** Default (Content).
   - **Placement → Hook:** `Single Content: Before` (`kadence_single_before_content`). **Priority:** `10` — the same level as the other format templates (Verdict, Dossier, Route…), so the Answerable opening at priority 5 lands above it.
   - **Display Settings → Show on:** `Singular: Briefs`, then **Select items → By taxonomy → brief_format → The Comparison**. (Kadence stores this as `show_conditionals` with `select: "tax"`, `subRule: "brief_format"`.) If your Kadence version has no "By taxonomy" sub-selector on Singular rules, use two rules joined by AND: `Singular: Briefs` + `Taxonomy: brief_format = The Comparison`. Result: Dossiers and Routes never see this Element.
   - **User Settings:** All users. **Expires:** off.
4. **Do not tick any plan in the Paid Member Subscriptions "Content Restriction" box on this Element post** — PMS trims the whole template to a 30-word teaser (the Dossier incident in `respin-old-content`). Comparisons are commercial pieces and render public.
5. **Publish.**

What the Element expects and what it hides (the base64 script in the last `wp:html` block — plain source in the appendix below):
- H1 = the post title (`post|post_title`). Rank Math's SEO title is separate (step A6). Kicker is the static word "The Comparison".
- Short answer = `comparison_short_answer`. When the Answerable-opening Element has already printed `short_answer` above, this one hides itself, so the worker can fill both without a double.
- "Choose **{side_a_name}** if" + `best_for_a` / "Choose **{side_b_name}** if" + `best_for_b`; a side with either value empty disappears, and the block disappears when both do.
- Eight sections, static H2 + the WYSIWYG field (`comparison_the_set_up`, `_the_location`, `_the_stays`, `_the_food`, `_the_days`, `_the_price_difference`, `_who_books_which`, `_the_verdict`). An empty field hides its section. If the body starts with an `<h3>` that repeats the H2 ("The set-up"), the script removes that heading — so text drafted for the Trade-Off, which carries its own `<h3>`s, pastes straight in.
- Images 1–5 after sections 1, 3, 5, 6, 8 through the **Kadence Image block's dynamic source** (`kadenceDynamic.url` → `post|post_custom_field` → `acf_meta|comparison_image_n`). The ACF fields return an attachment **ID**; Kadence Blocks Pro resolves IDs, ACF image arrays and URLs for image sources, but this is the one binding not yet seen live on this site — **confirm it in the preview (A7)**. If a figure stays blank with an image set, change the five image fields' *Return Format* to **Image URL** (ACF → the group → each image field) and reload: REST writes of IDs are unaffected, only the read changes. An image field with no value hides its figure.
- Booking box = Shortcode block `[ctt_book hotel="" perks=""]` placed **before "Who books which"**. The Element is shared by every Comparison, so `hotel` cannot be typed per post: with `hotel=""` the shortcode prints "Book *this hotel* through Coco Tran Travel" and the script replaces *this hotel* (title and the `hotel=` query parameter on the button) with the two side names, e.g. "Bora Bora or the Maldives". The worker's job is therefore to fill `side_a_name` / `side_b_name` and `comparison_booking_note` (one line, prints under the box: the perks that apply, the room to ask for). To hard-wire a specific stay instead, edit the Shortcode block in the Element — but that changes it for every Comparison, so prefer the fields. Optional server-side version (8 lines, Code Snippets, *run everywhere*): `add_filter('shortcode_atts_ctt_book', function($out){ if ($out['hotel']==='' && get_post_type()==='brief') { $a=get_post_meta(get_the_ID(),'side_a_name',true); $b=get_post_meta(get_the_ID(),'side_b_name',true); if ($a&&$b) $out['hotel']="$a or $b"; if ($out['perks']==='') $out['perks']=(string)get_post_meta(get_the_ID(),'comparison_booking_note',true);} return $out; });` — then the script finds nothing to replace and does nothing.

### A5. Rank Math schema (2 min)
Comparisons carry **Article** schema (not Review, no `itemReviewed`). **Rank Math → Titles & Meta → Post Types → Briefs → Schema Type** is almost certainly Article already (the Dossier/Route share it); leave it. Two things to check:
- The mu-plugin `paywall-schema-snippet.php` marks **every Brief** as gated (`isAccessibleForFree:false`) unless the post carries `ct_gated` meta. A Comparison is public, so each Comparison post needs post meta **`ct_gated = 0`** (ACF → the Content-system group has no such field; set it with Custom Fields → Add, or `wp post meta update <id> ct_gated 0`, or via REST `meta` if registered). Without it the page claims a paywall it does not have — a policy violation. Until that meta exists, or `ct_schema_is_gated` is taught to read the term, do not publish a Comparison.
- Per post: Rank Math tab → Schema → Article → Article Type "Article" (not BlogPosting), headline = the SEO title. No FAQPage unless a visible FAQ block exists.

### A6. Test build — `test-bora-bora-vs-maldives` (8 min)
The job in the Studio Desk (`jobs/test-bora-bora-vs-maldives`, status `researched`, Route C payload `09-studio-output/bora-bora-vs-maldives.json`) was drafted against the Trade-Off keys before the Brief format existed. Build it as a **draft** on the Brief CPT with this remap (the `_notes.field_map` in the JSON maps to Trade-Off keys; this is the Comparison equivalent):

| Comparison field (write) | From the JSON |
|---|---|
| post title | `seo.h1` from the Desk job: `Bora Bora vs the Maldives: Which Should You Choose for Your Honeymoon?` (H1 binds to the title); Rank Math title = `rank_math.title` (47 chars, title lock) |
| slug | `bora-bora-vs-maldives` |
| `brief_format` | the term ID from A1 (REST key `brief_format: [id]`, as the Dossier/Route use 758/759) |
| `destinations` | `[632, 654, <Bora Bora>, 628, <Maldives>]` — continent + country + place for both sides |
| `comparison_short_answer` | `acf.short_answer` (also write it to the Answerable-opening `short_answer`; the Element de-duplicates) |
| `side_a_name` / `side_b_name` | `Bora Bora` / `the Maldives` |
| `best_for_a` / `best_for_b` | `acf.glance_1_value` / `acf.glance_2_value` (the fuller "You're flying from the US…" lines; `tradeoff_best_for_a/b` "Honeymoons"/"Privacy" are too thin) |
| `comparison_the_set_up` | `acf.tradeoff_the_set_up` |
| `comparison_the_location` | `acf.tradeoff_the_location` |
| `comparison_the_stays` | `acf.tradeoff_the_rooms` |
| `comparison_the_food` | `acf.tradeoff_the_food_beverage` |
| `comparison_the_days` | `acf.tradeoff_the_amenities` |
| `comparison_the_price_difference` | `acf.tradeoff_the_price_difference` |
| `comparison_who_books_which` | `acf.tradeoff_who_books_which` |
| `comparison_the_verdict` | `acf.tradeoff_the_verdict` |
| `comparison_booking_note` | leave for Coco (needs_coco: booking access for Four Seasons / St. Regis Bora Bora) |
| `comparison_image_1..4` | media IDs `7164, 7136, 7167, 7132` (the Desk job's `media_ids`, her four frames in slot order); `comparison_image_5` empty (no Maldives photo of her own); featured image 7164 |
| Answerable opening | `short_answer`, `glance_1..5_label/value`, `visit_basis`, `disclosure`, `updated_on` = today; Content system: `content_category`, `content_goal`, `primary_keyword` |
| Rank Math | `rank_math.title`, `.description`, `.focus_keyword` via `POST /wp-json/rankmath/v1/updateMeta` |

Leading `<h3>` headings in those fields can stay; the Element drops the one that repeats the H2. The `[VERIFY: …]` markers stay visible in the draft until the fact pass resolves them — this is a test build, Coco publishes nothing until they are gone. Then update the Desk job: `cpt: "brief"`, `status: "built"`, `wp_id`, `preview_url`, and fix `_notes.redirect` to the Brief path.

### A7. Verify
Open `/brief/bora-bora-vs-maldives/?preview=true`: kicker, H1, short answer once (not twice), two "Choose … if" columns, eight H2s in order with no repeated `<h3>`, four photos rendered after The set-up / The stays / The days / The price difference, no empty figure where image 5 would be, booking box reading "Book Bora Bora or the Maldives through Coco Tran Travel" before "Who books which", button URL carrying `hotel=` and `source=`. View source: one `application/ld+json` Article, no `isAccessibleForFree:false` once `ct_gated=0` is set. Then **Delete all Cache** (admin bar) and **Cloudflare → Purge Everything**.

### A8. After Coco publishes
**Rank Math → Redirections → Add New**: source `bora-bora-vs-maldives/` (no leading slash, trailing slash, **Exact**), destination `https://cocotran.com/brief/bora-bora-vs-maldives/`, 301, Activate. Click each field before typing (React inputs ignore a JS focus). Then trash old post 7102 (`DELETE /wp/v2/posts/7102` after checking its slug), clear cache, purge Cloudflare, fetch `https://cocotran.com/bora-bora-vs-maldives/` and confirm it lands on `/brief/…`. The Desk job's `_notes.redirect` still says `/property/…` from the first draft — the Brief path is the right one.

---

## Path B — REST + wp-cli (no browser)

Env vars: `COCOTRAN_WP_USER`, `COCOTRAN_WP_APP_PASSWORD` (an Application Password for an admin user). Base `https://cocotran.com/wp-json`. Auth header `Authorization: Basic base64(user:app_password)`; curl's `-u` does that.

```bash
WP=https://cocotran.com/wp-json
AUTH="$COCOTRAN_WP_USER:$COCOTRAN_WP_APP_PASSWORD"

# 0. Which taxonomies and REST bases exist (confirms destination → destinations, brief_format → ?)
curl -s -u "$AUTH" "$WP/wp/v2/taxonomies?context=edit" | python3 -c 'import json,sys; [print(k, "→ rest_base:", v.get("rest_base"), "| hierarchical:", v.get("hierarchical")) for k,v in json.load(sys.stdin).items()]'

# 1. brief_format term (create only if the search returns nothing). Replace brief_format with its rest_base if step 0 shows a different one.
curl -s -u "$AUTH" "$WP/wp/v2/brief_format?slug=the-comparison"
curl -s -u "$AUTH" -X POST "$WP/wp/v2/brief_format" -H 'Content-Type: application/json' \
  -d '{"name":"The Comparison","slug":"the-comparison"}'

# 2. Destination parents by name — never assume 632 / 654 / 628
curl -s -u "$AUTH" "$WP/wp/v2/destinations?search=French%20Polynesia&per_page=5&_fields=id,name,parent,slug"
curl -s -u "$AUTH" "$WP/wp/v2/destinations?search=Oceania&per_page=5&_fields=id,name,parent,slug"
curl -s -u "$AUTH" "$WP/wp/v2/destinations?search=Asia&per_page=5&_fields=id,name,parent,slug"
# If French Polynesia is missing: create it under Oceania first
curl -s -u "$AUTH" -X POST "$WP/wp/v2/destinations" -H 'Content-Type: application/json' -d '{"name":"French Polynesia","parent":632}'
# Then the two places (skip any that the search already returns)
curl -s -u "$AUTH" "$WP/wp/v2/destinations?search=Bora%20Bora&_fields=id,name,parent"
curl -s -u "$AUTH" -X POST "$WP/wp/v2/destinations" -H 'Content-Type: application/json' -d '{"name":"Bora Bora","parent":654}'
curl -s -u "$AUTH" "$WP/wp/v2/destinations?search=Maldives&_fields=id,name,parent"
curl -s -u "$AUTH" -X POST "$WP/wp/v2/destinations" -H 'Content-Type: application/json' -d '{"name":"Maldives","parent":628}'
```

A `term_exists` error (HTTP 400, code `term_exists`) means it is already there — the response carries the existing `term_id`. Nothing to do.

**ACF group + everything above in one command** (needs shell access to the server): copy `brief-comparison-setup.php` and `acf-comparison-brief.json` into the same folder on the server, then

```bash
wp eval-file brief-comparison-setup.php --path=/path/to/wordpress
# or, without wp-cli, from a folder beside wp-load.php:
php brief-comparison-setup.php
```

Run it twice if you like: the second run prints "Found …" for every line and changes nothing. There is no REST route that imports ACF groups (ACF's `acf-field-group` posts are not exposed), so without shell access the group goes in through the admin UI (A2) or a Code Snippet (A3 note). Delete the PHP file from the server afterwards.

**The Kadence Element over REST**: possible (`POST /wp/v2/kadence_element` with `content` = the HTML and the meta `_kad_element_hook`, `_kad_element_hook_priority`, `_kad_element_show_conditionals`, `_kad_element_type`) but the meta keys are not registered `show_in_rest`, so prefer the admin UI (A4). If you must, create it over REST as a draft and finish the Element Settings in the UI.

**Test build over REST** — the same remap as A6, as the studio-pipeline skill's Route A:

```bash
# create the draft bare, then ACF in 2–3 updates
curl -s -u "$AUTH" -X POST "$WP/wp/v2/brief" -H 'Content-Type: application/json' -d '{
  "status":"draft","title":"Bora Bora vs the Maldives: Which Should You Choose for Your Honeymoon?","slug":"bora-bora-vs-maldives",
  "featured_media":7164,"brief_format":[<TERM_ID>],"destinations":[632,654,<BORA_BORA>,628,<MALDIVES>]}'
curl -s -u "$AUTH" -X POST "$WP/wp/v2/brief/<ID>" -H 'Content-Type: application/json' -d '{"acf":{
  "comparison_short_answer":"…","side_a_name":"Bora Bora","side_b_name":"the Maldives","best_for_a":"…","best_for_b":"…",
  "comparison_image_1":7164,"comparison_image_2":7136,"comparison_image_3":7167,"comparison_image_4":7132}}'
# then the eight WYSIWYG fields, then the Answerable-opening + Content-system fields, then Rank Math:
curl -s -u "$AUTH" -X POST "$WP/wp-json/rankmath/v1/updateMeta" -H 'Content-Type: application/json' -d '{"objectID":<ID>,"objectType":"post","meta":{"rank_math_title":"Bora Bora vs Maldives: Which Should You Choose?","rank_math_description":"…","rank_math_focus_keyword":"bora bora vs maldives"}}'
wp post meta update <ID> ct_gated 0
```

Verify with `GET $WP/wp/v2/brief/<ID>?context=edit&_fields=acf,brief_format,destinations` (every key echoed back; a key silently missing means the group is not active for that post — check the term is assigned, the location rule depends on it) and the preview URL (needs a logged-in cookie; use Chrome for that one look).

---

## Verification checklist (6 lines)
1. `GET /wp/v2/brief_format?slug=the-comparison` returns one term; ACF → Field Groups shows "Comparison (Brief · destination vs destination)" active with the two location rules.
2. `GET /wp/v2/destinations?search=Bora%20Bora` → parent = French Polynesia (under Oceania); `?search=Maldives` → parent = Asia.
3. Kadence → Elements shows "Brief — The Comparison", hook Single Content: Before, priority 10, Show on Briefs + The Comparison, no PMS plan ticked.
4. A Brief draft with the term + fields: preview renders kicker, H1, short answer once, Choose A/B, eight H2s, four photos, booking box before "Who books which"; a Dossier preview is unchanged.
5. `GET /wp/v2/brief/<ID>?context=edit&_fields=acf` echoes every `comparison_*` key written (nothing dropped).
6. View source on the draft: one Article JSON-LD, `isAccessibleForFree` absent/true after `ct_gated=0`; cache deleted, Cloudflare purged.

**Time:** A1–A3 5 min (or 1 min with the script) · A4 5 min · A5 2 min · A6 8 min · A7 3 min · cache 1 min → **≈25 minutes**, plus the 301 after Coco publishes (3 min).

## Not confirmed from here (no site access in the session that built this kit)
- The `brief_format` taxonomy's REST base (the Dossier/Route builds post `brief_format: [758|759]` on the post, so the key is `brief_format`; the term route may differ — step B0 shows it).
- Term IDs 632 / 654 / 628 come from the audit and the Desk job; the script and the curl steps look everything up by name.
- That the Brief single template does not also print the post title (the other Brief formats print their own H1 from ACF inside their Elements; if the theme title appears above the kicker, turn off the title for Briefs in Kadence → Customizer → Post/Page Layout → Brief, or remove the H1 block from this Element).
- The Kadence Image block resolves an ACF image **ID** (see A4); the fallback is Return Format = URL.
- Kadence's "By taxonomy" sub-selector on the Singular display rule exists on the installed Kadence Blocks Pro version (the AND-rule alternative is in A4).

## Appendix — the hide-if-empty script (plain source of the base64 in the Element)

```js
(function () {
  function txt(el) { return (el.textContent || '').replace(/ /g, ' ').trim(); }
  function run() {
    document.querySelectorAll('.ct-cmp').forEach(function (b) {
      var ans = b.querySelector('.ct-cmp-answer'), ao = document.querySelector('.ct-ao .ct-ao-answer');
      if (ans && (!txt(ans) || (ao && txt(ao)))) ans.hidden = true;                      // short answer: empty, or already printed by the Answerable opening
      b.querySelectorAll('.ct-cmp-side').forEach(function (s) {                           // Choose A / Choose B
        var n = s.querySelector('.ct-cmp-side-name'), v = s.querySelector('.ct-cmp-side-val');
        if (!n || !v || !txt(n) || !txt(v)) s.hidden = true;
      });
      var ch = b.querySelector('.ct-cmp-choose');
      if (ch && !ch.querySelector('.ct-cmp-side:not([hidden])')) ch.hidden = true;
      b.querySelectorAll('.ct-cmp-sec').forEach(function (s) {                            // sections: hide empty, drop a repeated leading heading
        var body = s.querySelector('.ct-cmp-body'), h = s.querySelector('.ct-cmp-h2');
        if (!body || !txt(body)) { s.hidden = true; return; }
        var first = body.firstElementChild;
        while (first && first.classList && first.classList.contains('kb-inline-dynamic')) first = first.firstElementChild;
        if (first && h && /^H[2-4]$/.test(first.tagName) && txt(first).toLowerCase().replace(/[^a-z]/g, '') === txt(h).toLowerCase().replace(/[^a-z]/g, '')) first.remove();
      });
      b.querySelectorAll('.ct-cmp-fig').forEach(function (f) {                            // figures with no resolved image
        var img = f.querySelector('img'), src = img ? (img.getAttribute('src') || img.getAttribute('data-src') || '') : '';
        if (!img || !src || /^(#|about:blank)/.test(src) || /placeholder/i.test(src)) f.hidden = true;
      });
      var box = b.querySelector('.ct-cmp-book'), em = box && box.querySelector('.ctt-book__title em'), btn = box && box.querySelector('.ctt-book__btn');
      var names = [];                                                                     // booking box: "this hotel" → "A or B"
      b.querySelectorAll('.ct-cmp-side:not([hidden]) .ct-cmp-side-name').forEach(function (n) { var t = txt(n).replace(/^Choose\s+/i, '').replace(/\s+if$/i, ''); if (t) names.push(t); });
      if (em && /^this hotel$/i.test(txt(em)) && names.length) {
        var label = names.join(' or '); em.textContent = label;
        if (btn) { try { var u = new URL(btn.href, location.href); u.searchParams.set('hotel', label); btn.href = u.toString(); } catch (e) {} }
      }
      var note = b.querySelector('.ct-cmp-booknote');
      if (note && !txt(note)) note.hidden = true;
      if (box && !box.querySelector('.ctt-book') && !(note && !note.hidden)) box.hidden = true;
      b.classList.add('ct-cmp-ready');
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run); else run();
})();
```

To change it: edit the source, `base64 -w0 ct-cmp.js`, and replace the string inside `atob("…")` in the Element — with string split/join, never regex (Kadence block comments break under regex replace). It ships base64 because WordPress rewrites `&&` inside raw `<script>` tags in post content.
