# Rankings: Search Console + GA4 → library → refresh jobs

The worker pulls per-URL performance weekly, writes it to each library record, computes `health`, and opens update jobs for decaying or stale pieces. It needs read access to both Google properties. Nothing is pasted into chat; everything goes in the cloud environment's secrets.

## One-time setup (Coco, ~20 min)

1. **Google Cloud project** (any) → APIs & Services → enable **Google Search Console API** and **Google Analytics Data API**.
2. **Service account** → create → Keys → add key (JSON) → download.
3. **Search Console** → cocotran.com property → Settings → Users and permissions → add the service account's email as **Full** (read is enough: "Restricted" works for queries).
4. **GA4** → Admin → Property access management → add the same email as **Viewer**. Note the numeric **Property ID**.
5. **Cloud environment** (session title bar → environment menu → Edit) → Network secrets / environment variables:
   - `GSC_SERVICE_ACCOUNT_JSON` = the whole JSON key file's contents
   - `GSC_SITE_URL` = `sc-domain:cocotran.com` (or `https://cocotran.com/` if URL-prefix property)
   - `GA4_PROPERTY_ID` = the numeric id
6. Done. The Monday worker run picks them up.

## What the worker pulls (weekly, Monday 7:51 Pacific)

Search Console `searchanalytics/query`, dimensions `page` (and `page,query` for top queries), 28 days ending yesterday and the 28 before that. GA4 `runReport`: `landingPagePlusQueryString` × `sessions`, `engagedSessions`, `conversions` (the booking-request and membership events once tagged), same two windows. Written to `library/<id>.rank` as described in `content-system.md`.

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
