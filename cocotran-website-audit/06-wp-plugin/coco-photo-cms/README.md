# Coco Photo CMS

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

Other routes (same auth): `GET /usages/{id}`, `POST /rename/{id}` (`stem`), `POST /replace/{id}` (`mode=everywhere` + `file`/`data_url`, or `mode=here` + `post_id`, `location`, `new_id`), `GET /settings`.

CORS headers are sent only for origins in the allowlist, so a page on claude.ai can call this directly from the browser with the Application Password. Treat that password like a login: it can upload and edit media.

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
- Requires PHP 8.0+, WordPress 6.2+ (uses `wp_content_img_tag`).

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
includes/class-ccpc-settings.php    settings page, bulk tools
assets/admin.js, assets/admin.css
uninstall.php
```
