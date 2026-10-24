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
