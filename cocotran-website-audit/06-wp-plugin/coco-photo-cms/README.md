# Coco Photo CMS (1.1.0)

The photo layer WordPress is missing, built for cocotran.com (Kadence + ACF + Rank Math + LiteSpeed + Cloudflare). Plain PHP, no build step, no Composer.

What it does:

| Feature | Where |
|---|---|
| Resize originals (default max 2000 px long edge), strip EXIF (keeps Copyright/Artist), keep the untouched upload in `/originals/` | automatic on upload |
| Generate `.webp` (and `.avif` if the server supports it) for the full image and every size; serve them when the browser accepts them | automatic; toggle in settings |
| Property / Place / Detail / Credit / Visited / License fields on every image, with the filename built from a template (`{property}-{place}-{subject}-{n}-coco-tran`) | media modal and attachment edit screen |
| Alt text required: the REST intake refuses uploads without it; in wp-admin an empty alt is flagged (`_ccpc_alt_missing`), outlined red in the modal, and raised as an admin notice; the Bulk tab lists every image still missing it | everywhere |
| Rename a file: every size and WebP is renamed, every post/ACF field/Kadence block that references it is rewritten, and a 301 is registered (Rank Math Redirections if present) | attachment screen → Filename → Rename…; Media list bulk action "Rename from Coco Photo fields" |
| Replace an image **everywhere** (same ID, same URL, new pixels, cache-busted) or **only here** (swap one post/field to another image) | attachment screen → "Replace & usages" box |
| "Used in" column in the Media list and a usages table per image (`ccpc_find_usages()` for code) | Media → Library (list view) |
| Credit appended to captions, `ImageObject` JSON-LD on single posts (creator, credit, copyright, license, place, date) | front end |
| REST intake so a Claude artifact (or any script) can push images with all the metadata and place them in a post/ACF field | `/wp-json/coco-photo-cms/v1/*` |
| **1.1** Focal point per image (click the preview) and social crops cut around it: 4:5 (1080×1350), 9:16 (1080×1920), Pin 2:3 (1000×1500), Open Graph (1200×630), JPEG + WebP, cached until the point or the image changes | attachment screen → "Focal point, social exports & resize"; Media list bulk action "Generate social crops"; `GET/POST /crops/{id}` |
| **1.1** Credit watermark on the exports only (never on site images), TTF or GD bitmap fallback | Settings → Exports & speed; per-export checkbox or `watermark=1` |
| **1.1** In-use guard: "Delete permanently" is blocked while posts/fields still reference the image, with the list of places and a one-shot "Delete anyway" | Media list, attachment screen; REST delete → `409` |
| **1.1** Resize one photo in place (from the backed-up original), regenerate sizes + WebP, cache-bust | attachment screen → "Resize now"; `POST /resize/{id}` |
| **1.1** Optimise all images: batched, resumable job with dry-run estimate, bytes before/after, WebP quality presets, optional progressive JPEG re-encode | Settings → Bulk |
| **1.1** Speed set: `decoding=async`, lazy-loading with an eager budget, `fetchpriority=high` + `<link rel=preload>` for the LCP image, `sizes` sanity | front end, each a toggle |
| **1.1** Media list shows social-crop status (n/4) and a focal-point dot | Media → Library (list view) |

## Install

1. Zip the `coco-photo-cms` folder, upload via Plugins → Add New → Upload, activate. Or copy the folder to `wp-content/plugins/`.
2. Settings → Coco Photo CMS. Defaults are sensible; check two things:
   - **Serve WebP/AVIF**: if LiteSpeed Cache → Image Optimization → *Image WebP Replacement* is on, turn **one** of them off. Both on = double rewriting. If you keep LiteSpeed's, leave this plugin generating WebP (it still does) and untick "Serve".
   - **REST intake — allowed origins**: `https://claude.ai` and `https://*.claude.ai` by default.
3. Bulk tab → **Generate WebP for images that don't have one** → Start. Then **Find** images with empty alt and fix the ones that are used in posts first (the list shows usage counts).
4. Purge LiteSpeed and Cloudflare after the bulk run.

## The fields

On any image (media modal sidebar, or Media → edit):

- **Property / subject** — "Caruso Belmond", "Amanjena", "Fushimi Inari"
- **Place** — "Ravello", "Marrakech", "Kyoto"
- **Detail** — "junior suite 312 terrace", "breakfast pool", "torii at dawn"
- **Credit** — defaults to `Photo: Coco Tran`
- **Visited** — "June 2025"
- **License / usage note** — free text

Saving the fields with *auto-rename* on renames the file to e.g. `caruso-belmond-ravello-junior-suite-312-terrace-coco-tran.jpg`. If a file with that name exists, WordPress appends `-1`, `-2`.

Alt text stays WordPress's own field. It is **required** on the REST route and flagged everywhere else (WordPress's own media-modal alt save bypasses plugin filters, so it cannot be hard-blocked there without replacing core UI): write what is in the frame ("Terrace of junior suite 312 at Caruso, looking over the Amalfi coast"), not a keyword list. The "build alt from fields" option exists for bulk rescue only and is off by default.

## Rename → redirect caveat

The plugin records every old→new URL and (a) adds exact-match 301s to Rank Math Redirections when that module is active, and (b) keeps its own list and redirects on `init`.

Both only fire when a request **reaches PHP**. Image URLs normally don't: Apache/LiteSpeed serve a file directly, and if the file is gone they return their own 404 without starting WordPress. Cloudflare may also serve the old file from cache for a while. So for hard-linked images (Pinterest pins, other sites, Google Images) do one of these after a rename:

- **.htaccess** (Apache / LiteSpeed): Settings → Coco Photo CMS → *Redirect rules* → paste the `Redirect 301 …` lines **above** `# BEGIN WordPress`.
- **Cloudflare**: Rules → Bulk Redirects → import the CSV lines from the same tab.

Renaming a brand-new upload that nothing references yet records no redirect (nothing links to it).

## Replace

**Everywhere** — upload a new file of the same type (jpg→jpg, png→png). The attachment ID and URL don't change, so every post that uses it updates at once. Sizes and WebP are regenerated; `?v=<timestamp>` is appended to the attachment URL and srcset so Cloudflare and browsers refetch. Purge LiteSpeed/Cloudflare afterwards for pages that cached the HTML.

**Only here** — the "Used in" table lists each post and *where* (`thumbnail`, `content`, `acf:image_1_left`, `meta:…`). Type the ID of the image you want instead and click Swap. That one field/post changes; everything else keeps the original. Within a single post's content, every occurrence of the old image in *that post* is swapped (it can't tell two identical `<img>` blocks in one post apart).

## REST intake (how an artifact pushes photos)

Auth: create an Application Password (Users → Profile → Application Passwords, name it "claude-artifact"). Use HTTP Basic: `username:application-password`. The user needs `upload_files`.

```
POST https://cocotran.com/wp-json/coco-photo-cms/v1/upload
Authorization: Basic …
Content-Type: multipart/form-data

file=@photo.jpg
alt=Terrace of junior suite 312 at Caruso, Ravello        (required)
property=Caruso Belmond
place=Ravello
subject=junior suite 312 terrace
n=3                                                      (optional, for {n})
caption=The terrace at seven, before anyone else was up
credit=Photo: Coco Tran                                  (optional, default from settings)
visited=June 2025
post_id=29123                                            (optional)
acf_field=image_1_left                                   (optional: set that ACF image field)
set_featured=1                                           (optional)
insert_into_content=1                                    (optional: append a core/image block)
```

Or JSON: `{"data_url":"data:image/jpeg;base64,…","filename":"optional.jpg","alt":"…", …same fields…}`.

Response:

```json
{ "id": 31207, "filename": "caruso-belmond-ravello-junior-suite-312-terrace-3-coco-tran.jpg",
  "url": "https://cocotran.com/wp-content/uploads/2026/10/…jpg", "webp": "…webp",
  "alt": "…", "credit": "Photo: Coco Tran", "usages": 1, "warnings": [] }
```

All routes (same auth; `upload_files` to read, `edit_post` on the attachment to change it, `manage_options` for the optimise job):

| Route | Body / params | Returns |
|---|---|---|
| `POST /upload` | see above | attachment description |
| `GET /usages/{id}` | — | `[{post_id, post_type, location, title, edit_link}]` |
| `POST /rename/{id}` | `stem` | description + `redirects` map |
| `POST /replace/{id}` | `mode=everywhere` + `file`/`data_url`, or `mode=here` + `post_id`, `location`, `new_id` | description / `{ok}` |
| `GET /settings` | — | settings (minus CORS list) |
| `GET /focal/{id}` (1.1) | — | `{x, y, set}` |
| `POST /focal/{id}` (1.1) | `{x, y}` (0–1) or `{reset: 1}` | `{x, y, set}` |
| `GET /crops/{id}` (1.1) | — | `{focal, formats, crops}` — cached only, nothing generated |
| `POST /crops/{id}` (1.1) | `{formats: ["social_45", …] (default all), focal?: {x, y}, watermark?: bool, force?: bool}` | `{focal, crops: {slot: {jpg, webp, width, height, watermark, bytes_jpg, bytes_webp}}}`; slot is `format` or `format:wm` |
| `POST /resize/{id}` (1.1) | `{long_edge}` 400–6000 | description + `resize: {width, height, bytes_before, bytes_after, source}` |
| `DELETE /media/{id}` (1.1) | `force=1` to override | `{deleted, id, usages_were}` or `409 ccpc_in_use` with `usages` |
| `GET /optimise/estimate` (1.1) | `offset` (50 per page; `next` = null at the end) | `{count, processed, bytes, no_webp, over_cap, next}` |
| `GET` / `POST` / `DELETE /optimise/job` (1.1) | POST: `{restart?: bool, reencode_jpeg?: bool}` runs one batch of 5 | job state `{offset, total, processed, before, after, resized, reencoded, started, finished, last[]}` |

`DELETE /wp/v2/media/{id}` is also guarded (`409` with usages unless `ccpc_force=1`).

CORS headers are sent only for origins in the allowlist, so a page on claude.ai can call this directly from the browser with the Application Password. Treat that password like a login: it can upload and edit media.

## Focal point and social exports (1.1)

Open an image (Media → Library → Edit). The **Focal point** preview: click where the subject is; the dot moves and the point saves immediately (`_ccpc_focal` = `{x, y}` in 0–1, default centre). Every social crop is the largest rectangle of the target ratio placed so that the point keeps its relative position, clamped at the image edge, then scaled to the exact pixel size. The crop is cut from the untouched upload in `/originals/` when one exists (more pixels than the resized site copy).

| Key | Size | Use |
|---|---|---|
| `social_45` | 1080 × 1350 | Instagram / Facebook feed |
| `social_916` | 1080 × 1920 | Stories, Reels cover |
| `pin_23` | 1000 × 1500 | Pinterest |
| `og_191` | 1200 × 630 | Open Graph / Twitter card |

**Export for social** buttons download the file (JPEG at the export quality, or WebP). Tick **Add credit watermark** to burn the credit line into that download; untick for a clean file. Both variants are cached under `uploads/ccpc-crops/<id>/` and reused until the focal point, the file (`_ccpc_version`), the quality settings or the watermark text change. "Generate all now" fills the cache; the Media list bulk action **Generate social crops** does the same for many images. Deleting the attachment removes its crop folder.

**Watermark** (Settings → Exports & speed): text (default `© Coco Tran · cocotranstudio.com`), bottom-right/left, opacity, font size as % of the image width, and an optional absolute path to a `.ttf`/`.otf` on the server. No font is bundled: the default is the first font found in the theme's `/fonts/` (or `/assets/fonts/`) folder, else GD's built-in bitmap font (plain, ASCII-transliterated, always works). The watermark is applied **only** to exports requested with the watermark option; site images, sizes and WebP are never touched.

## Delete guard (1.1)

With the guard on (default), "Delete permanently" on an image that `ccpc_find_usages()` still finds in a post, featured image, ACF field or other meta is refused. The Media list shows a notice: where it is used (count + first 5 links), **Replace it first** (opens the usages table) and **Delete anyway**. "Delete anyway" sets a one-shot ticket (transient, 10 minutes, this user) and runs the normal delete once. Bulk deletes go through for the unused images and are refused for the used ones. Programmatic `wp_delete_attachment()` returns `false` for an in-use image unless a ticket exists. REST: `DELETE /wp/v2/media/{id}` and `DELETE /coco-photo-cms/v1/media/{id}` return `409 ccpc_in_use` with the usages; send `ccpc_force=1` (wp/v2) or `force=1` (plugin route) to override.

The grid-view (modal) delete uses an AJAX path that cannot show the notice; it fails silently there, and the explanation appears on the next admin screen.

## Resize one photo (1.1)

Attachment screen → **Resize this photo** → long edge → **Resize now** (or `POST /resize/{id}` `{long_edge}`, 400–6000). The source is the `/originals/` backup when present (so you can go *up* to the original's size), else the current file. The full-size file is re-encoded in place, every size and WebP is regenerated, `-scaled` is suppressed, `_ccpc_version` is bumped (cache-buster) and the social-crop cache is invalidated.

## Optimise all images (1.1)

Settings → Bulk → **Optimise all images**. **Dry run** counts the images, the bytes on disk (full + sizes + WebP), how many lack WebP and how many are over the long-edge cap. **Start** runs 5 images per request and for each: resize if over the cap (as above), regenerate WebP for every size at the configured quality, and, if ticked, re-encode the full-size JPEG at quality 82, progressive, 4:2:0 (kept only when it is smaller; GD or Imagick). Progress, bytes before/after and the per-image log are shown; the job state is stored in an option, so closing the tab and pressing **Resume** continues where it stopped. Safe to re-run (idempotent on already-optimised files).

**WebP quality presets** (General tab): *Smallest file* 72 / *Balanced* 82 / *Quality* 88 / *Custom*. Changing the preset and re-running the job re-encodes every WebP.

## Speed set (1.1)

Six toggles, all on by default (Settings → Exports & speed), implemented via `wp_content_img_tag`, `wp_get_attachment_image_attributes` and `wp_head`:

- `decoding="async"` on every image.
- `loading="lazy"` on every image except the first *N* (default 2) in the main content, any image with class `ct-hero`, and the images inside the first slide of the first Kadence slider/carousel on the page. Those get an explicit `loading="eager"`.
- `fetchpriority="high"` on the featured image (or, without one, the first content image) of singular pages; any other `high` is removed so there is exactly one.
- `<link rel="preload" as="image" … imagesrcset imagesizes fetchpriority="high">` for that image in `<head>`, at the capped size and as WebP when the request accepts it.
- `sizes` sanity: `sizes="(max-width: Npx) 100vw, Npx"` with *N* above the front-end cap is rewritten to the cap (core writes 2000px for a 2000px image that is served at 1536px).

Block themes run `wp_filter_content_tags()` twice (content, then the whole template); the plugin keeps the first pass's decisions.

## Schema

On single posts the plugin emits one `ImageObject` per image for the featured image + the first N images (content `wp-image-…` and Kadence `"id":…` references, then ACF fields starting `image`/`iamge`). Fields are only emitted when they have a value: `contentUrl`, `width/height`, `name`, `description` (= alt), `caption`, `creditText`, `copyrightNotice`, `license`, `acquireLicensePage`, `datePublished`, `creator` (Coco Tran), `contentLocation` (Place), `dateCreated` (Visited). No ratings, no fake data.

## Limitations

- **EXIF**: GD strips everything on re-save and cannot write; the plugin re-applies Copyright/Artist only when Imagick is installed. Check with Site Health → Info → Media Handling.
- **HEIC/TIFF/RAW** are not processed (JPEG and PNG only). Export JPEG from the scanner/Lightroom.
- **Replace everywhere** requires the same file type as the original so the URL is unchanged.
- **Replace only here** cannot distinguish two uses of the same image inside one post's content.
- **Usages** are found by attachment ID and filename. An image referenced only by a hand-typed URL to a *different* size name that WordPress never generated will be missed.
- **Serving WebP** via URL swap depends on the page cache varying on `Accept`. LiteSpeed does this when its own WebP option is on. If you use this plugin's serving instead and see JPEGs on Chrome, turn LiteSpeed's "Image WebP Replacement" on and this plugin's "Serve" off — the generated `.webp` siblings use LiteSpeed's expected naming when its "WebP for extra srcset" is set to the `.webp`-extension-swap style; otherwise keep this plugin serving and exclude image URLs from LiteSpeed page cache vary rules.
- **Redirects** for static files need the .htaccess / Cloudflare rule (above).
- **Bulk rename** uses the stored fields; images with empty Property/Place/Detail are skipped.
- **Social crops** are generated with GD (preferred) or Imagick directly, because `WP_Image_Editor` refuses to upscale; without either library the export buttons and resize are disabled (the settings page says so). AVIF exports are not produced.
- **Watermark** with the bitmap fallback is ASCII only (`©` becomes `(c)`); set a TTF path for proper typography.
- **Delete guard** cannot show its notice in the grid-view modal (core AJAX path); the delete is still refused.
- **Speed set** eager/LCP decisions are made per request; on archives the budget covers the first *N* images of the page, not of each post. The LCP guess is the featured image, else the first content image.
- **Optimise all** re-encodes the full-size JPEG only (intermediate sizes are regenerated from it on a resize, otherwise left as-is).
- Requires PHP 8.0+, WordPress 6.2+ (uses `wp_content_img_tag`); the speed set assumes 6.3+ for `wp_high_priority_element_flag()` (feature-detected).

## Files

```
coco-photo-cms.php             loader, settings helpers, ccpc_find_usages()
includes/class-ccpc-processor.php   resize / strip / originals / WebP+AVIF
includes/class-ccpc-naming.php      fields, required alt, filename template
includes/class-ccpc-rename.php      rename + rewrite references + 301s
includes/class-ccpc-replace.php     replace everywhere / only here + usages box
includes/class-ccpc-usages.php      usage finder + Media list column
includes/class-ccpc-serve.php       WebP/AVIF URL swap, cache-buster
includes/class-ccpc-schema.php      alt on output, credits, ImageObject JSON-LD
includes/class-ccpc-rest.php        REST intake + CORS
includes/class-ccpc-settings.php    settings page (General / Exports & speed / Bulk / Redirect rules), bulk tools
includes/class-ccpc-image.php       1.1 GD/Imagick wrapper: focal crop, watermark, JPEG re-encode
includes/class-ccpc-crops.php       1.1 focal point, social crops, exports, bulk action, Media column
includes/class-ccpc-guard.php       1.1 in-use delete guard (admin flows + REST 409)
includes/class-ccpc-optimise.php    1.1 resize one, optimise-all job, dry-run estimate
includes/class-ccpc-speed.php       1.1 decoding/lazy/fetchpriority/preload/sizes
assets/admin.js, assets/admin.css
uninstall.php
```
