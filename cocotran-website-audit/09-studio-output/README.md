# Studio output

When the worker cannot reach WordPress (no Application Password in the environment), it writes each approved piece here as `<slug>.json` — the exact payload the Studio Desk's "Copy WordPress payload" produces: `{cpt, format, post_title, acf{...}, rank_math{title,description,focus_keyword}, slug, destination_terms, links, photos, photos_needed, verify}`.

A Cowork session with Coco's logged-in Chrome pushes them: `wp.apiFetch` per `respin-old-content`, then sets the job's status to `built` in the Studio Desk store.

To let the scheduled worker push directly instead, add a **Network secret** to the cloud environment: type Basic, WP username + an Application Password (create at `https://cocotran.com/wp-admin/authorize-application.php?app_name=studio-worker`), allowed website `cocotran.com`, path prefix `/wp-json/`. Full steps: `05-editorial-system/rankings-integration.md` → One-time setup.
