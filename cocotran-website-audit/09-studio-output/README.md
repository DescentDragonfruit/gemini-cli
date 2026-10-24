# Studio output

When the worker cannot reach WordPress (no Application Password in the environment), it writes each approved piece here as `<slug>.json` — the exact payload the Studio Desk's "Copy WordPress payload" produces: `{cpt, format, post_title, acf{...}, rank_math{title,description,focus_keyword}, slug, destination_terms, links, photos, photos_needed, verify}`.

A Cowork session with Coco's logged-in Chrome pushes them: `wp.apiFetch` per `respin-old-content`, then sets the job's status to `built` in the Studio Desk store.

To let the scheduled worker push directly instead, add two environment variables to the cloud environment: `COCOTRAN_WP_USER` and `COCOTRAN_WP_APP_PASSWORD` (WP admin → Users → Profile → Application Passwords → "studio-worker").
