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

## v1.1

Date: 2026-10-24. Plugin `coco-photo-cms` **1.1.0** copied into the same throwaway WordPress 7.1.3 (SQLite, PHP 8.3.6, GD 2.3.3 with FreeType + WebP, no Imagick, no AVIF). `php -S 127.0.0.1:8089 -t wordpress router.php` (docroot must be `wordpress/`, not `.`, or static uploads 404). Tested with wp-cli `eval`, REST (Application Password), the admin cookie session (`cookies.txt`) for admin-post/admin-ajax/upload.php flows, and plain curl for the front end. `debug.log` empty after every step unless noted. Nothing committed.

| # | Feature | Result |
|---|---------|--------|
| 1 | Focal point + social crops (eval, REST, attachment screen, export download, bulk action, Media column) | PASS after fix E |
| 2 | Credit watermark on exports only (bitmap + TTF) | PASS after fix F |
| 3 | In-use delete guard (CLI, row link, bulk, REST 409, force paths, "Delete anyway") | PASS after fix G |
| 4 | Resize one photo (REST + AJAX) | PASS |
| 5 | Optimise all images (estimate, batches, resume, re-encode, presets, AJAX + REST) | PASS |
| 6 | Speed set on singular + archive pages, WebP and non-WebP | PASS after fixes H, I |
| 7 | Media list "Social" column + focal dot | PASS |
| 8 | Settings tabs / tab-aware sanitize / WebP presets | PASS |
| 9 | Deactivate / reactivate, boot with 1.0 settings | PASS |

**1.** Boot: 31 settings (16 old + 15 new) with defaults, 12 routes under `coco-photo-cms/v1`, `CCPC_Image::engine()` = gd, WebP yes. `generate(5, [social_45, og_191], {0.8, 0.3})` → `uploads/ccpc-crops/5/<format>-<10-char key>.jpg|webp`, verified on disk 1080×1350 and 1200×630 JPEG + WebP; second call reuses the cache (mtime unchanged); changing the focal point produces a new key, `cached()` drops the stale slot and the sweep deletes its files. Pixel test on a synthetic 3000×2000 image (red square centred at 80 %/30 %, resized to 2000 on upload, `/originals/` kept): the crops are cut from the 3000 px original; 9:16 and 2:3 put the square at (50 %, 30 %) (x clamped by the image width, y preserved), 4:5 at (62.5 %, 30 %) and OG at (80 %, 38 %), i.e. clamped at the right/top edge as expected. REST: `GET/POST /focal` (400 on non-numeric), `POST /crops` with JSON `formats`+`focal` override, form-encoded comma list + `watermark=1` → slot `og_191:wm`; `GET /crops` lists `social_45, social_916, pin_23, og_191, og_191:wm`; crop URLs serve 200 `image/jpeg`; unauthenticated 401; non-image id 404. Attachment screen: meta box with the preview (`aspect-ratio` set, dot at `left:80%;top:30%`), 2 hidden focal inputs, 4 export buttons (Pin styled), watermark checkbox, JPEG/WebP select, 4 status dots, resize field. `admin-post.php?action=ccpc_export` with the page nonce → `200 image/jpeg`, `Content-Disposition: attachment; filename="focal-test-social-45-credit.jpg"`, real 1080×1350; `type=webp` → `image/webp` 1200×630; bad nonce 403; logged out 400. Bulk action `ccpc_social_crops` on 2 ids → 302 `?ccpc_crops_done=2`, both 4/4, success notice. Form save path (`attachment_fields_to_save`): echoing the 0.5/0.5 default does not create a stored point; 1.7 is clamped to 1. Media column: dot `is-on` with "Focal point set (80 % / 30 %)" title, "4/4 crops" link.

**2.** Watermark drawn on a 4:5 crop at 70 % opacity, 2.2 % of width: bitmap fallback and a system TTF both produce bright glyph pixels in the chosen corner (156 / 164 sampled). The site full-size file's corner stays pure blue (never watermarked). Before fix F the bitmap font rendered `©` and `·` as garbage glyphs (GD's built-in font is ASCII only); after it the fallback reads `(c) Coco Tran - cocotranstudio.com`. TTF path validated in settings (non-readable path cleared with a settings error; `/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf` accepted). Theme font autodetect found nothing (TT25 ships woff2) → blank default, as documented.

**3.** Seeded post 21 with G1 (block + `wp-image`), G2 (plain `<img class="wp-image-…">`), G3 as `_thumbnail_id`, G4 unused. `wp_delete_attachment(G1, true)` → `false`, post and file intact. Row link `post.php?action=delete&post=G1&_wpnonce=…` → 302 back to the referer, G1 intact, the Media list shows the red notice ("still used in 1 place", post link, `content`, **Replace it first**, **Delete anyway**), and only once. Bulk delete `media[]=G1,G4` → G1 blocked (notice), G4 deleted (`?deleted=1`). `DELETE /wp/v2/media/G2?force=true` → `409 ccpc_in_use` with `data.usages`; `DELETE /coco-photo-cms/v1/media/G2` → 409; with `force=1` → `{deleted:true, usages_were:1}`; `wp/v2` with `ccpc_force=1` on G3 → deleted. No `ccpc_force_delete_*` transients left behind. **"Delete anyway" failed (403)** before fix G: the redirect carried `&amp;` from `wp_nonce_url()`. After the fix: ticket granted → core delete → `?deleted=1`, G1 gone, crop folder removed, ticket consumed.

**4.** #16 (2000×1333, original 3000×2000): `POST /resize/16 {long_edge:1200}` → 1200×800 from `originals`, 4 sizes (the stale `-1536x1024` jpg/webp deleted), 5 WebP, `_ccpc_version` set, `?v=` on the URL, 104 345 → 44 484 B, original untouched. `long_edge=2600` → 2600×1733 (from the original, no `-scaled`, no `original_image` key). 100 → 400; non-attachment → 404; unauthenticated 401. AJAX `ccpc_resize` back to 2000 → success JSON. The crop cache is invalidated by the version bump (0 cached slots).

**5.** Setup: #16 resized to 2400 (over the 2000 cap), #5's six WebP files deleted. Estimate: `{count:4, bytes:346845, no_webp:1, over_cap:1}`. `POST /optimise/job {restart, reencode_jpeg}` → one batch processed 4: #16 "resized 2400→2000 px; jpeg re-encoded q82 progressive (43 KB→17 KB); webp ×6", #5 re-encoded 42→24 KB with 6 WebP regenerated, totals 346 845 → 227 952 B, `finished` set; the JPEG has the SOF2 (progressive) marker and `filesize` meta matches disk. A second run is idempotent (bytes unchanged, "already smaller than q82"). With 7 images the first batch stops at `offset 5, finished 0`, `GET /optimise/job` returns the persisted state, the next POST finishes at 7/7. AJAX `ccpc_optimise_estimate` / `_batch` / `_reset` with the page nonce behave the same; subscriber Application Password → 403 on estimate and on resize. Presets: `webp_preset=small` forces `webp_quality` 72, `custom` keeps the typed value.

**6.** Post 22 (featured #16; content: block image #5, plain `<img>` #7, #13, a `ct-hero` image, a mock Kadence slider with two `kb-slide-item`s, and a 768 px image with `sizes="(max-width: 2000px) 100vw, 2000px"`); post 23 the same without a featured image. Final output, with and without `Accept: image/webp`: one `<link rel="preload" as="image" … imagesrcset imagesizes fetchpriority="high">` for `focal-test-1536x1024.(webp|jpg)?v=…` (capped size, WebP only when accepted); featured `<img>` `loading="eager" fetchpriority="high"` with the same 1536 src; content images 1–2 `eager`, 3rd `lazy`, `ct-hero` `eager`, first slide `eager`, second slide `lazy`; every image `decoding="async"`; the 2000px `sizes` rewritten to `(max-width: 1600px) 100vw, 1600px`; exactly one `fetchpriority="high"` `<img>` per page. Post 23: the first content image carries `fetchpriority="high"` and the preload. Home (archive): budget of 2 applies to the page, later posts' images lazy. Before fix H every content image was `lazy` and the featured image lost its priority: Twenty Twenty-Five runs `wp_filter_content_tags()` a second time over the whole template (`context = 'template'`), which my filter treated as "not main content". Before fix I the first slide was `lazy` only in the WebP variant (the Serve filter had already swapped `src` to `.webp` before the eager-marker lookup).

**7./8.** Media list: new "Social" column next to "Used in"; both bulk actions present. Settings: General (19 inputs, hidden `_tab=general`, preset select wired to the number), Exports & speed (15 inputs: export quality, watermark text/position/opacity/size/font/default, delete guard, 6 speed fields), Bulk (dry run, re-encode checkbox, Start/Resume/Pause/Reset, progress bar, log). Saving one tab keeps the other tab's values, including unchecked checkboxes.

**9.** Deactivate → front page 200, images still served; reactivate → 1.1.0 active, the 1.0 option gains the new keys through defaults. `uninstall.php` now also removes `ccpc_optimise_job`.

### Bugs found and fixed during the v1.1 run

- **E** `includes/class-ccpc-crops.php` / test harness — not a plugin bug: `esc_url()` prints `&#038;` in the export button `href`; curl sent it literally and got "Not allowed." (browsers decode it). Recorded so nobody chases it again.
- **F** `includes/class-ccpc-image.php` watermark bitmap fallback — GD's built-in font is ASCII only, so `©`/`·` rendered as wrong glyphs. Now transliterates typographic characters (`©`→`(c)`, `·`→`-`, dashes, quotes) and strips the rest before `imagestring()`.
- **G** `includes/class-ccpc-guard.php:handle_force()` — the "Delete anyway" redirect was built with `wp_nonce_url()`, which HTML-escapes `&`; the `Location` header pointed at `post.php?action=delete&amp;post=…` and core answered 403. Rebuilt with `add_query_arg()`.
- **H** `includes/class-ccpc-speed.php` — block themes call `wp_filter_content_tags()` again with context `template` over the rendered page; the second pass re-lazied the eager images and stripped the featured image's `fetchpriority`. Eager decisions are now explicit (`loading="eager"`) and the template pass leaves any image that already carries `loading`/`fetchpriority` untouched. `is_main_content()` also accepts a `core/post-content` render outside a classic loop.
- **I** `includes/class-ccpc-speed.php` — eager markers (ct-hero / first slide) are keyed on the extension-less path so they match after `CCPC_Serve` swaps `src` to `.webp`/`.avif` or appends `?v=`; the eager budget and first-slider lookup now run once per request instead of once per post.
- Also changed while testing: `CCPC_Serve::cap_full()` treats an unregistered size name (TT25's `post-thumbnail`) like `full`, so the featured image `src` matches the preload href; `CCPC_Processor` gained `delete_generated()` (used by replace + resize) and a `$force` flag on `generate_variants()`; `read/write_credit_exif()` made public for the resize path.

`php -l` clean on all 16 PHP files; `admin.js` parses.

### Not covered / known gaps (v1.1)

- Imagick paths of `CCPC_Image` (crop, annotate watermark, progressive save) are written and feature-detected but ran only the GD branch here. AVIF untested (not available).
- Real Kadence slider markup was simulated with `kb-gallery-type-slider` / `kb-slide-item` classes; Kadence's `splide` output is matched by the same regex family but not exercised.
- The grid-view (modal) delete cannot display the guard notice (core AJAX `wp_die(0)`); the delete is refused and the notice appears on the next admin page load.
- The focal click UI and the optimise progress bar were verified as rendered markup + their AJAX endpoints, not driven in a browser.
- `wm_font` theme autodetect only looks for `.ttf`/`.otf` under `/fonts/` and `/assets/fonts/`; Kadence child themes with fonts elsewhere need the path typed in.

## v1.2

Date: 2026-10-24. Plugin `coco-photo-cms` **1.2.0** deployed with `deploy.sh` into the same throwaway WordPress 7.1.3 (SQLite, PHP 8.3.6, GD, no Imagick), `php -S 127.0.0.1:8089 -t wordpress router.php`. A rig-only mu-plugin registers a `property` CPT (`public`, `rewrite`, thumbnails) because the real site's CPT is not on the rig. Tested with wp-cli (`post`, `cron event`, `eval`), REST with Application Passwords (admin + a subscriber), the admin cookie session for the attachment and settings screens, and anonymous curl for the crop files. `debug.log` empty after every step (the only two lines seen all run were core's "Automatic updates starting/complete" from `wp_version_check`, not the plugin). Nothing left scheduled.

| # | Feature | Result |
|---|---------|--------|
| 1 | Boot: 1.2.0, 3 new settings with defaults, 13 routes under `coco-photo-cms/v1` (+2) | PASS |
| 2 | Auto crops on publish via WP-Cron (property with featured + 3 ACF-style metas) | PASS |
| 3 | Never regenerate existing crops (mtimes), focal change re-cuts only that image | PASS |
| 4 | `auto_crops_refresh` off/on, setting off → nothing, `DISABLE_WP_CRON` → synchronous | PASS |
| 5 | `GET /post-crops/{id}`: shape, public URLs serve 200, `missing: true`, `generate=1` | PASS |
| 6 | `GET /crops-for-url`: permalink, CPT permalink, query string, 404/400 cases | PASS |
| 7 | Auth: anonymous 401, subscriber 403 on both routes and on `generate=1` | PASS |
| 8 | Attachment screen "Crops: 4/4 · generated automatically when … · regenerate" | PASS |
| 9 | Settings tab fields, "Last runs" log, tab-aware sanitize | PASS |

**1.** `ccpc_get_settings()` → `auto_crops_on_publish 1, auto_crops_refresh 0, auto_crops_max 6`; routes `/post-crops/(?P<id>\d+)` and `/crops-for-url` registered. Five 1200×800 JPEGs imported (#27–31), later a sixth (#35).

**2.** Property #32 created as draft with `_thumbnail_id=27`, `hero_image=28` + `_hero_image=field_…`, `image_1_left=29` + `_image_1_left=field_…`, `gallery_images=a:1:{i:0;i:30;}` and a decoy `room_count=31`. `collect_ids()` → `[27, 28, 29, 30]` (the decoy is excluded: non-image key), `targets()` → featured 27 + `[28, 29, 30]`. `wp post update 32 --post_status=publish` with `DISABLE_WP_CRON=false` → `wp cron event list` shows `ccpc_auto_crops  10 seconds  [32]`, nothing on disk yet. `wp cron event run ccpc_auto_crops` (2.5 s) → `uploads/ccpc-crops/{27,28,29,30}/{social_45,social_916,pin_23,og_191}-<key>.{jpg,webp}` = 32 files, no folder for #31; verified 1080×1920 and 1200×630 on disk. Log option: `post 32 (property) via cron: 4 images, 16 crops generated, 0 reused (2.53s)`. #27 got `_ccpc_crops_source {auto:true, source:cron, post_id:32, time, formats[4]}`, the post `_ccpc_auto_crops {time, ids[4], generated:16, reused:0}`. Event gone after the run.

**3.** `run(32)` again → `generated 0, reused 16, 0.01 s`; `stat` of all 16 JPEGs before/after identical. `set_focal(28, 0.8, 0.3)` → `missing_formats(28)` lists all four (new key), `run(32)` → `4 generated, 12 reused`, #28's folder now holds only the new key's 8 files (old ones swept), #27/#29/#30 untouched.

**4.** With `auto_crops_refresh=0`, `wp post update 32 --post_title=…` schedules nothing; with it on, the same save schedules `ccpc_auto_crops [32]`, which runs to `0 generated, 16 reused`. `auto_crops_on_publish=0`: post #33 (featured #31) published → no event, no `ccpc-crops/31`. Setting back on, `DISABLE_WP_CRON=true`: post #36 (featured #27 + a `core/image` block of #35) published from wp-cli → no event, and by the time the command returned `ccpc-crops/35` held 8 files; log `post 36 (post) via sync: 2 images, 4 crops generated, 4 reused (0.7s)` (#27's four reused).

**5.** `GET /post-crops/32` (admin) → `post_id 32, post_type property, permalink http://127.0.0.1:8089/property/caruso-test-property/, generated_at 2026-10-24T19:21:44+00:00, featured {attachment_id 27, focal {0.5, 0.5, set:false}, auto:true, crops {social_45 {url …/ccpc-crops/27/social_45-41a46d6788.jpg, 1080, 1350} …}}, images [28 (focal set), 29, 30]`. All 16 JPEG `url`s and the WebP `webp` URLs fetched **without auth** → `200 image/jpeg` / `200 image/webp`. `GET /post-crops/33` (no crops yet) → `generated_at null`, all four formats `{url: null, width, height, missing: true}`, `images []`; `?generate=1` → `generated {generated:4, reused:0, errors:[]}`, four URLs, `auto:false` (REST-made, not auto-publish), 8 files on disk, log line `via rest`. `/post-crops/999` → `404 ccpc_post`.

**6.** `crops-for-url?url=http://127.0.0.1:8089/property/caruso-test-property/` → post 32, 3 images, `resolved_url` echoed; `…/sync-publish-post/` → post 36, `images [35]`; the same URL with `?utm_source=x` still resolves (32); `/no-such-post/` → `404 ccpc_url_unresolved` with the URL in `data`; `ftp://x` → `400 ccpc_url`; no `url` → `400 rest_missing_callback_param`.

**7.** Anonymous → `401` on both routes. Subscriber Application Password → `403 rest_forbidden` on `/post-crops/32`, `/crops-for-url` and `/post-crops/32?generate=1`.

**8.** `post.php?post=27&action=edit` (cookie session) → `Crops: 4/4 · generated automatically when “Caruso test property” was published (October 24, 2026 7:20 pm) · regenerate` with the `ccpc-regen-crops` button (`data-force=1`, confirm dialog, reuses the `ccpc_make_crops` AJAX with `force=1`); #31 (REST-generated) shows `Crops: 4/4 · regenerate` without the auto-publish sentence.

**9.** Exports & speed tab renders `auto_crops_on_publish` (checked), `auto_crops_max` (6, min 0 max 30), `auto_crops_refresh` (unchecked) and a read-only "Last runs" textarea with the log lines. `CCPC_Settings::sanitize({_tab: exports, auto_crops_max: 99})` → `max 30`, both checkboxes 0, General-tab values kept; `sanitize({_tab: general, …})` keeps `auto_crops_on_publish 1` and `auto_crops_max 6`.

### Bugs found and fixed during the v1.2 run

- None in the new code. Two design points settled while testing: (a) the run must not happen at `transition_post_status` time because the block editor and ACF write featured image / fields *after* `wp_insert_post()` — hence the 10 s cron delay and, with `DISABLE_WP_CRON`, a `shutdown`-hook run instead of an inline one; (b) `url_to_postid()` only resolves a CPT permalink when the CPT is registered with rewrite rules (the rig needed the mu-plugin and a `rewrite flush`).
- Also changed: `register_deactivation_hook` unschedules `ccpc_auto_crops`; `uninstall.php` removes `ccpc_auto_crops_log` and the hook; deactivate/reactivate not re-run this round beyond `php -l`.

`php -l` clean on all 17 PHP files; `admin.js` parses.

### Not covered / known gaps (v1.2)

- Publishing from the block editor / ACF UI itself was not driven (no browser); the REST and wp-cli publish paths fire the same `transition_post_status`, and the meta-after-insert ordering is handled by the delay, but a real ACF save was simulated with raw meta + `_field` keys.
- Kadence gallery JSON in meta was exercised only through the `"id":N` regex on a string value, not with a real Kadence block saved by the editor.
- The WP-Cron event was run with `wp cron event run`, not by a front-end request hitting `wp-cron.php`.
- The regenerate button was verified as markup + its existing AJAX endpoint, not clicked in a browser.
- `property`, `guide`, `brief`, `dispatch`, `shortlist` on the live site come from ACF/CPT UI; only `property` (rig CPT) and `post` were published here.
