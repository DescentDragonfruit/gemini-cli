# Coco Photo CMS — runtime test report

Date: 2026-10-24. Plugin: `coco-photo-cms` 1.0.0 (symlinked into a throwaway WordPress). Nothing committed by the tester.

## Environment

- PHP 8.3.6 CLI, extensions: gd (WebP yes, AVIF **no**), exif, mbstring, curl, zip, pdo_sqlite, sqlite3. Imagick **not** installed (EXIF re-write path untested).
- WordPress 7.1.3 (`latest.zip`), SQLite Database Integration 3.1.1 as `wp-content/db.php` (no MySQL), wp-cli from `wp-cli.phar`, `php -S 127.0.0.1:8089` with a router script for pretty permalinks. All downloads succeeded through the proxy.
- `WP_DEBUG` + `WP_DEBUG_LOG` on; `debug.log` checked after every step.
- Application Password: `wp user application-password create admin studio --porcelain`; used as HTTP Basic. Needed `WP_ENVIRONMENT_TYPE=local` because core refuses app passwords over plain HTTP (environment, not plugin).

## Results

| # | Test | Result |
|---|------|--------|
| 1 | Activation, settings page | PASS |
| 2 | Upload: resize, WebP, originals, template, alt required | PASS |
| 3 | Rename via REST | PASS after fix B |
| 4 | Replace everywhere / only here, usages | PASS after fixes C, D |
| 5 | ImageObject JSON-LD, alt on output | PASS |
| 6 | WebP serving by `Accept` | PASS after fix A |
| 7 | Deactivate / uninstall | PASS |

**1.** `wp plugin activate coco-photo-cms` → active, `wp option get ccpc_settings` returns all 16 defaults, `ccpc_redirects` = `[]`, debug.log empty. Logged-in curl of the three settings tabs → 200 each, `<h1>`, 13 `ccpc_settings[...]` inputs, `admin.js` enqueued, bulk buttons and redirects textarea present.

**2.** 3000×2000 GD-generated JPEG. `wp media import` → stored 2000×1333, 5 sizes, `ccpc_variants` lists 6 `.webp` (all on disk), `originals/<file>.jpg` is the untouched 3000×2000, `_ccpc_credit` defaulted. REST `POST /wp-json/coco-photo-cms/v1/upload` without `alt` → `400 ccpc_alt_required`. With alt + property/place/subject/n=3 + `post_id`, `acf_field=image_1_left`, `set_featured=1`, `insert_into_content=1` → 200, filename `caruso-belmond-ravello-junior-suite-312-terrace-3-coco-tran.jpg`, 6/6 WebP, originals kept, all `_ccpc_*` meta and caption written, featured + meta + `core/image` block placed, warning "ACF not active: wrote raw post meta".

**3.** Seeded: post 6 (block with URL + `wp-image-7`, `_thumbnail_id`, `image_1_left`=7 with `_image_1_left=field_…` to simulate ACF, `hero_url`=full URL, serialized `gallery_images`), page 11 (hand-typed `<img src>` only). `POST /rename/7 stem=amalfi-coast-terrace-caruso` → 12 files renamed (6 jpg + 6 webp), 0 old names left, `originals/` renamed, `_wp_attached_file`, every `sizes[].file`, `ccpc_variants`, guid updated; post 6 content and `hero_url` rewritten; ID fields untouched; `ccpc_redirects` holds 12 path→URL entries; `CCPC_Rename::rules_text()` emits `Redirect 301 …` + Cloudflare CSV; `curl old-url` → `301 → new-url` via the init fallback. **Page 11 was not rewritten** → fix B; re-test on attachment 5 with a URL-only page + URL postmeta → both rewritten, `usages: 2`.

**4.** `GET /usages/7` listed `thumbnail`, `content`×2, `acf:image_1_left`, `meta:hero_url`, `meta:gallery_images` (6, correct). `mode=here` for `content`, `acf:image_1_left`, `thumbnail` → each moved to the new ID; `hero_url` untouched; usages of old/new IDs reflect it; bogus location → 400. `mode=everywhere` multipart → **"Not an image" 400** (fix C); after fix: 2400×1600 red JPEG → 2000×1333 on disk, pixel check 200,30,30, `_ccpc_version` set, `?v=` on URL and every srcset entry, originals replaced, WebP mtimes refreshed. `data_url` variant → **fatal `wp_tempnam()` undefined** (fix D); after fix → 200, 1200×800 green. PNG onto JPG → `400 ccpc_ext`. Admin form (`admin-post.php`, real nonce from the attachment screen) → 302 `ccpc_replaced=1`, same checks pass.

**5.** `curl` of the single post: one `application/ld+json` block, one `ImageObject` with `contentUrl 2000×1333, name, description(=alt), caption, creditText, copyrightNotice, license, acquireLicensePage, datePublished, creator, contentLocation=Ravello, dateCreated=June 2025`; recursive scan found **no empty/null fields**. `wp_get_attachment_image()` with alt deleted → `alt=""` (decorative), with Detail/Property/Place set → `alt="breakfast pool, Amanjena, Marrakech"`. Credit span appended inside `<figcaption>`.

**6.** Same page with `Accept: text/html,image/webp` vs without: featured `src` `.jpg` → `.webp`, 5-entry srcset all `.webp`, content `<img>` swapped via `wp_content_img_tag`, `Vary: Accept` sent. Before fix A the featured image under WebP **lost `srcset`/`sizes` entirely** and JSON-LD `contentUrl` flipped to `.webp`. After fix: srcset intact, `contentUrl` stable `.jpg`. Note: `wp_get_attachment_image_src()` itself now returns the `.jpg` by design; the swap happens on the `<img>` attributes, srcset and content tags.

**7.** `wp plugin deactivate` → clean, front page 200, images still served. `wp plugin uninstall --skip-delete` → both options gone, `_ccpc_*` meta and `.webp`/`originals/` left as documented. Re-activate restores defaults. debug.log empty throughout.

## Bugs found and fixed

- **A** `includes/class-ccpc-serve.php:19,84` — swapping inside `wp_get_attachment_image_src` broke core `wp_calculate_image_srcset()` matching (no srcset on featured images for WebP browsers; schema URL varied by Accept). Moved the `src` swap to `wp_get_attachment_image_attributes`.
- **B** `includes/class-ccpc-rename.php:76-78` (was after the metadata update, old line 130) — `CCPC_Usages::find()` ran after `_wp_attached_file` changed, so filename-only references were never rewritten or redirected. Usages are now collected before the physical rename.
- **C** `includes/class-ccpc-replace.php:76-83,250`, `includes/class-ccpc-rest.php:237,250` — `wp_check_filetype_and_ext()` was given PHP's extension-less upload temp name, so every multipart/admin "Replace everywhere" failed with "Not an image". The client filename is now passed through.
- **D** `includes/class-ccpc-rest.php:220-221` — `replace()` never loaded `wp-admin/includes/file.php`, so the `data_url` path fataled on `wp_tempnam()`. Also `:246` deletes the empty placeholder `wp_tempnam()` leaves behind.

`php -l` clean on all 11 PHP files after the changes.

## Remaining limitations / not covered

- AVIF and Imagick EXIF re-write untested (not available here). HEIC/TIFF unchanged.
- `?v=` cache-buster is on the full URL and srcset only; the intermediate-size `src` attribute (e.g. `-1024x682.jpg`) carries no `?v=` because `image_downsize()` rebuilds it from the basename. Browsers use srcset first, so the practical impact is small.
- Rank Math, ACF, Kadence and LiteSpeed were not installed; those branches (`update_field`, `\RankMath\Redirections\DB::add`, `litespeed_purge_all`) ran only as the fallbacks (raw postmeta, option-based redirects).
- `CCPC_Usages::find()` skips meta keys starting with `_`, so the `kadence:` classification for `_kad_*` keys can never trigger (harmless; such keys are also skipped on rewrite).
- Media-modal alt flow and the bulk WebP/missing-alt AJAX tools were verified to render only.
