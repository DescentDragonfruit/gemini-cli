# Cowork runbook — install the snippets, remove the duplicates (25 Oct 2026)

Paste the block below into a Claude session that runs ON COCO'S MAC (Claude Desktop app → Cowork, with Chrome
where she is logged in to cocotran.com). A cloud session cannot click inside WordPress admin.

Files it needs (both repos carry them, folder `wordpress-setup/` in ctbusiness):
- `wpcode-import/wpcode-import-all-three.json`
- `acf-comparison-brief.json`
- `snippet-booking-popup.php` is NOT installed yet (form choice still open) — skip it.

---------------------------------------------------------------- PROMPT ----------------------------------------------------------------
You are working in my Chrome, logged in as admin on https://cocotran.com. Do exactly this, confirm each step with a screenshot, change nothing else.

1. Open https://cocotran.com/wp-admin/admin.php?page=wpcode-tools&view=import . Upload the file
   `wpcode-import-all-three.json` (from the ctbusiness repo, wordpress-setup/wpcode-import/). Press Import.
   Expected: three new snippets, all Active, titles starting "Coco Tran ·":
   - Coco Tran · Article chrome (…)
   - Coco Tran · Footer blocks style (…)
   - Coco Tran · Comparison booking box (…)

2. Open https://cocotran.com/wp-admin/admin.php?page=wpcode . For every OTHER PHP snippet whose code does the same job,
   move it to Trash (hover → Trash). Recognise them by content, the titles were typed by hand:
   - any snippet containing `ct_pair_css` or `ct_author_css`            → duplicate of "Footer blocks style"
   - any snippet containing `id=\"ct-article-chrome\"` or `get_the_terms` with `destination`  → duplicate of "Article chrome"
   - any snippet containing `shortcode_atts_ctt_book` or `$out['hotel']`  → duplicate of "Comparison booking box"
   - a snippet whose code is JSON (starts with `[` or `{`, or shows `\n` sequences) → broken paste, Trash it
   Keep exactly ONE active copy of each of the three. Do not touch snippets about anything else
   (Rank Math, WooCommerce, Kadence, memberships, email) and do not touch the Coco Tran Site Snippets plugin.

3. Open https://cocotran.com/wp-admin/edit.php?post_type=acf-field-group&page=acf-tools . Under Import Field Groups,
   upload `acf-comparison-brief.json` (ctbusiness repo, wordpress-setup/). Press Import.
   Expected message: field group "Comparison (Brief · destination vs destination)" updated/imported.
   Open that group and confirm a text field "Both share" (name comparison_both_share) sits after "Choose B if".

4. Purge caches: SpeedyCache → Purge all (https://cocotran.com/wp-admin/admin.php?page=speedycache), then Cloudflare
   dashboard → cocotran.com → Caching → Purge Everything.

5. Open https://cocotran.com/property/la-residencia-deia/ on desktop width and on a phone width. Screenshot the facts
   strip, the "Pair it with" block and the author block. Then report: which snippets were trashed (titles), the three
   that remain active, the ACF confirmation, and the two screenshots.
---------------------------------------------------------------------------------------------------------------------------------------

After it reports, tell the cloud session "snippets in" and it will write the Bora Bora "Both share" line and verify the
live render.
