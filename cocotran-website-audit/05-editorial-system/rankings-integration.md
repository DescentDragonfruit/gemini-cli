# Rankings: Search Console + GA4 → library → refresh jobs

The worker pulls per-URL performance weekly, writes it to each library record, computes `health`, and opens update jobs for decaying or stale pieces. It needs read access to both Google properties. Nothing is pasted into chat; everything goes in the cloud environment's secrets.

## One-time setup (Coco, ~15 min) — Network secrets

The cloud environment's **Network secrets** inject credentials at the proxy. The session never sees a password or key; it simply calls the host and the proxy adds the header. Open the environment (session title bar → environment name → Edit → Network secrets → Add) and create two secrets:

1. **WordPress** — Secret type **Basic**. Username = the WP login name (Users → Profile → Username). Password = an Application Password; create it at `https://cocotran.com/wp-admin/authorize-application.php?app_name=studio-worker` (this page works even when the Profile form's Add button fails, because it does not use REST). Allowed websites: `cocotran.com`. Path prefixes: `/wp-json/`. On the multisite network the password is created once and works on all sites.
2. **Google** — Secret type **GCP access token (with Service Account Key)**. Paste the whole service-account JSON key file. Allowed websites: `searchconsole.googleapis.com` and `analyticsdata.googleapis.com`. If the form asks for scopes: `https://www.googleapis.com/auth/webmasters.readonly https://www.googleapis.com/auth/analytics.readonly`. (If that type refuses Search Console, use **OAuth 2.0 JWT bearer** with token URL `https://oauth2.googleapis.com/token` and the same scopes.) Before this: Google Cloud → enable **Google Search Console API** + **Google Analytics Data API**; IAM → Service Accounts → create `studio-worker` → Keys → JSON; Search Console → Settings → Users → add its email (Full); GA4 → Admin → Property access management → add it as Viewer.
3. **Not secret, so not a secret:** the Search Console property form and the GA4 Property ID live in the Studio Desk → Keywords → "Search Console & GA4" card (stored at `settings/analytics` = `{gsc_site_url, ga4_property_id}`). Defaults: `sc-domain:cocotran.com`; the property id is the nine-digit number at GA4 → Admin → Property details.

Legacy fallback: plain environment variables `COCOTRAN_WP_USER` / `COCOTRAN_WP_APP_PASSWORD`, `GSC_SERVICE_ACCOUNT_JSON`, `GSC_SITE_URL`, `GA4_PROPERTY_ID` still work if a session has them.

### How the worker detects credentials (never assume)

```
curl -s -o /dev/null -w '%{http_code}' https://cocotran.com/wp-json/wp/v2/users/me          # 200 → WordPress writes allowed (Route A), send NO Authorization header
curl -s -o /dev/null -w '%{http_code}' https://searchconsole.googleapis.com/webmasters/v3/sites   # 200 → Google credentials present, send NO token
```
401/403 with no env-var fallback → Route C for WordPress, `skipped-no-credentials` for rankings. Python `requests` and `curl` both go through the proxy automatically; do not mint tokens or add auth headers when the probe returns 200 (a second Authorization header breaks the injected one).

Endpoints: GA4 `POST https://analyticsdata.googleapis.com/v1beta/properties/{id}:runReport`. Search Console: the property is the **URL-prefix** `https://cocotran.com/` (confirmed 24 Oct 2026 via `GET /webmasters/v3/sites`), and the proxy decodes `%2F` in request paths, so the normal `sites/https%3A%2F%2Fcocotran.com%2F/searchAnalytics/query` URL returns Google's HTML 404. **Always use the batch endpoint**, where the encoded site travels in the body (verified working):

```
POST https://searchconsole.googleapis.com/batch        Content-Type: multipart/mixed; boundary=b1

--b1
Content-Type: application/http
Content-ID: <q1>

POST /webmasters/v3/sites/https%3A%2F%2Fcocotran.com%2F/searchAnalytics/query HTTP/1.1
Content-Type: application/json

{"startDate":"YYYY-MM-DD","endDate":"YYYY-MM-DD","dimensions":["page"],"rowLimit":5000,"startRow":0}
--b1--
```
Several parts per batch are fine (page / page+query / current + previous window). Parse each part's JSON after its blank line. `sites` list and anything without a site in the path can be called directly.

## What the worker pulls (weekly, Monday 7:51 Pacific)

Search Console `searchanalytics/query`, dimensions `page` (and `page,query` for top queries), 28 days ending yesterday and the 28 before that. GA4 `runReport`: `landingPagePlusQueryString` × `sessions`, `engagedSessions`, `conversions` (the booking-request and membership events once tagged), same two windows. Written to `library/<id>.rank` as described in `content-system.md`, and appended to `library/<id>.rank_history[]` as `{date, position, clicks28, impressions28, ctr}` (keep the last 26 entries) so the desk can show whether a page moved after a rewrite.

## Health rules

- `decaying`: impressions −30% **or** average position worse by 3+ vs previous window, with ≥50 impressions in the previous window.
- `stale`: `last_updated` > 365 days, or format ∈ {dossier, primer} and > 180 days.
- `thin`: gated and visible words < 400.
- `duplicate`: two records share `keyword`.
- otherwise `ok`.

For every `decaying`/`stale` record the worker creates a Studio job (`mode: update`, `reason`, the losing/top queries, the proposed fix) and leaves it `queued` for Coco. Never more than 5 new refresh jobs per week — top losers first by clicks lost.

## GA4 channel group for AI referrals

Admin → Data display → Channel groups → create "AI referrals": referrer matches regex `chatgpt\.com|openai\.com|perplexity\.ai|claude\.ai|copilot\.microsoft\.com|gemini\.google\.com|you\.com|phind\.com`. The worker reports sessions from it in the benchmark row.

## Conversion events to tag (so `goal` can be measured)

- `booking_request` — the booking box form submit (hotel + source URL as params)
- `membership_start` — Planning Room / Insider checkout complete
- `inquiry` — Coco Tran Travel contact from a site link
- `newsletter_signup`

## What the data turns into (added 24 Oct 2026)

Once the three secrets exist, the Monday run writes an `insights` document per week (`insights/YYYY-MM-DD`) that the desk shows on Today and in Keywords:

- **Striking distance**: pages at position 4–20 with ≥200 impressions → a title/meta/opening update, queued with the losing queries.
- **Content gaps**: queries with impressions where the landing page is not about them (a Guide ranking for "is X worth it") → a new piece suggestion in the right format, added to `keywords` as "from Search Console".
- **Won and lost**: top 10 gainers and losers by clicks, with the likely cause (new sibling, lost citation, seasonal).
- **AI referrals**: sessions from the "AI referrals" channel group per page → which pieces AI tools cite; those get the Facts-checked refresh first.
- **Social attribution**: GA4 sessions by `instagram.com` / `l.instagram.com` / `pinterest.com` / `youtube.com` referrers and by UTM (`utm_source=instagram&utm_medium=social&utm_campaign=<kit id>` on the link in bio and the booking box) → which posts sent readers and which readers booked (the `booking_request` and `inquiry` events carry `source_url`).
- **Format scorecard**: clicks, Planning Room sign-ups and inquiries per format in the last 28 days → the format mix to write next month.

Every suggestion is written as a job or a `keywords` record with the data attached, so the desk's "Marketing moves" and the Keywords desk show real numbers next to the prediction.

### UTM rule for social
The desk's "Copy for Instagram" appends `?utm_source=instagram&utm_medium=social&utm_campaign=<kit id>` to any cocotran.com URL in the caption; pins use `utm_source=pinterest`; YouTube descriptions `utm_source=youtube`. The link in bio points to the newest piece with its UTM. Change the link when the post goes up.
