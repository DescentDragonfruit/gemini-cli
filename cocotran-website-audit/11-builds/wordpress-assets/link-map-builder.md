# Building the link map from the library (worker, studio-pipeline §6d)

The site snippets plugin (`12-install/coco-tran-site-snippets.zip`, `autolink.php`) links the first plain-text mention of each keyword in every piece to the piece that owns that keyword. It does not decide what the keywords are: the worker builds the **link map** from the `library` collection and POSTs it to WordPress. Rebuild and re-POST after any piece goes live, is retired or is merged, and at the end of the Monday rankings run.

## 1. One entry per live record

Read every doc in `library`. Keep a record when **all** are true:

- `status` is `live` or `live-old` (skip `draft`, `retired`, anything starting `merged-into:`).
- `url` is on `https://cocotran.com` (the endpoint rejects anything else).
- it has a `keyword` of at least 4 characters, or a title it can be derived from.

Build:

```json
{
  "keyword":     "<library.keyword>",
  "aliases":     ["<hotel name>", "<title without the pattern tail>", "<secondary_keywords[] that are proper names>"],
  "url":         "<library.url>",
  "title":       "<library.title>",
  "post_id":     <WP post ID when known, else omit — WordPress resolves it from the URL>,
  "pillar":      "<library.cpt: property | guide | brief | dispatch | shortlist | post>",
  "destination": "<destination term name, e.g. Amalfi Coast>",
  "priority":    3 | 2 | 1
}
```

**Keyword.** `library.keyword` (the focus keyword). If empty, derive it from the title by the title lock in `content-system.md`: the text before the first `:` or ` — `, with `An Honest Review` / `Review` stripped.

**Aliases** (each ≥ 4 characters, proper names only — never generic words like "hotel", "guide", "coast"):

- Verdicts (`cpt: property`): the hotel name from the title pattern `[Hotel], [Place]: …` → `[Hotel]`; also the short brand form when it is unambiguous ("Caruso" for "Caruso, A Belmond Hotel"; "Aman Kyoto"; not "Four Seasons" on its own — it matches several pieces).
- Comparisons: both sides as `[A] vs [B]`; never A or B alone (those belong to their own Verdicts).
- Guides / Briefs / Shortlists: the title head (`Amalfi Coast travel guide`, `5 days on the Amalfi Coast`, `best hotels in Kyoto`), and `secondary_keywords[]` entries that are place or property names.
- Dispatches / Answers: usually no alias; the keyword is enough.

**Priority** from `category`: **Commercial → 3**, **Decision-support → 2**, everything else (**Informational**, Specialist intel, Authority) → **1**. Slots per piece are limited (`ct_autolink_max`, default 6); priority decides who gets them, so Verdicts and Comparisons win over primers.

**Destination**: the record's destination term name (from WP `/wp/v2/destinations` for the post, or the library's `destination` field when present). It is informational on the map (the Pair block reads the taxonomy directly).

## 2. Rules the endpoint enforces (so pre-clean to keep the `rejected` list short)

- every `url` on the site host; keyword 4–80 characters; priority clamped to 1–3;
- one owner per phrase: duplicate keywords (case-insensitive) → the higher priority keeps it, the other is rejected; an alias that collides with another entry's keyword or alias is silently dropped;
- two entries with the same URL are merged (aliases union);
- map ≤ 3000 entries.

A rejected entry is reported, not fatal: the map stores whatever validated. If `stored` is 0 the endpoint returns 400 and the previous map stays.

## 3. POST it

```
POST https://cocotran.com/wp-json/coco-tran/v1/link-map
Authorization: Basic base64(user:application_password)   # omitted in a cloud session: the Network secret injects it
Content-Type: application/json

{"entries":[ … ]}            ← or a bare JSON array
→ 200 {"stored": 178, "rejected": [{"index": 12, "reason": "url is not on cocotran.com"}], "version": 1761331200}
```

`version` is the Unix timestamp WordPress stamps on the map (`ct_link_map_version`); storing it also clears every "Pair it with" cache. Write `version` and `stored` to `runs/link-map-YYYY-MM-DD` and list `rejected` in the run report when it is not empty.

Check it back: `GET /wp-json/coco-tran/v1/link-map` → `{version, count, max, entries[]}`.

## 4. Per-piece dry run (what the Studio Desk shows)

`GET /wp-json/coco-tran/v1/autolink-report?post=<ID>` → `{post, post_type, permalink, off, max, map_version, map_entries, count, links:[{keyword, matched, url, title, post_id, priority, pillar, destination, context}]}`. `context` is ±40 characters around the match; `off: true` means the post carries `ct_autolink_off` and the page shows no autolinks (the list is still what would be added). Use it after a POST on the 3–5 most important pieces to confirm the map reads the way Coco expects, and surface the list in the desk's piece view.

## 5. Per-post switches Coco can set (post meta, also over `/wp/v2/{cpt}/{id}` with `{"meta":{…}}` when the key is registered, else raw meta)

`ct_autolink_off = 1` (no autolinks on this piece) · `ct_pair_off = 1` (no "Pair it with") · `ct_author_off = 1` (no author block, no Person schema) · `ct_hub_url` (override the destination hub link in the Pair block).
