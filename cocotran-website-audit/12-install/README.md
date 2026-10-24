# Install on cocotran.com — what to upload where (≈15 min)

Nothing here is installed yet. Do it on staging first if you have one.

1. **Plugins → Add New → Upload Plugin** → `coco-photo-cms.zip` → Activate. Then Settings → Coco Photo CMS: check max long edge (2000), keep originals, serve WebP (turn off LiteSpeed's own WebP replacement if you enable this). Run Bulk → "Regenerate WebP" and the missing-alt list.
2. **Plugins → Add New → Upload Plugin** → `coco-tran-site-snippets.zip` → Activate. Gives you `[ctt_book hotel="…" access="bellini"]` and the paywall/dateModified schema on gated Briefs (set `ct_gated = 0` on the free sample Route).
3. **Custom Fields → Tools → Import** → `acf-answerable-opening.json`, `acf-content-system.json`, `acf-booking-access.json` (one at a time).
4. **Kadence → Elements → Add New** → paste `11-builds/wordpress-assets/kadence-element-answerable-opening.html` in the code editor; show on Properties, Guides, Briefs, Dispatches, Shortlists; hook per its README. Same for the booking box Element (Properties only).
5. **Rank Math titles**: run `resolve-ids.php --dry-run` then `--apply` per `11-builds/wordpress-assets/INSTALL.md`, or paste per page.
6. **Delete all cache** (LiteSpeed) and **purge Cloudflare**.

For the worker to do the WordPress builds itself: WP admin → Users → Profile → Application Passwords → "studio-worker", then add `COCOTRAN_WP_USER` and `COCOTRAN_WP_APP_PASSWORD` to the cloud environment's secrets.
