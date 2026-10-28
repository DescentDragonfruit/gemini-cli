# Rank Math & WordPress settings — click-by-click

Covers plan items 1.6–1.11. All paths are WP admin → Rank Math SEO unless stated.

## 1. Sitemap: include the new content types, drop the dead weight

**Rank Math → Sitemap Settings**

- **General:** "Include Images" on. "Exclude Posts" — leave empty.
- **Left tabs — Post Types:** you'll see a tab per post type. Set:
  - Posts → Include in Sitemap: **On**
  - Pages → **On**
  - Products → On only if you sell on-site; else Off
  - **Properties → On** · **Guides → On** · **Briefs → On** · **Dispatches → On** · **Shortlists → On**
  - kadence_* / kt_gallery / thirstylink → **Off**
- **Taxonomies:** Categories → On only if category archives have unique intro text; otherwise Off. Product categories → Off.
- **Authors:** On (one author; make sure the profile is filled — item 2.7).
- Save. Then **Rank Math → Status & Tools → Database Tools → Clear Sitemap Cache**. Visit `/sitemap_index.xml` and confirm `property-sitemap.xml`, `guide-sitemap.xml`, `brief-sitemap.xml`, `dispatch-sitemap.xml`, `shortlist-sitemap.xml` appear.
- **Search Console → Sitemaps:** remove any old entries, add `https://cocotran.com/sitemap_index.xml`.

The 118 redirected URLs will drop out of `post-sitemap.xml` on their own once the old posts are trashed or set to redirect (Rank Math excludes redirected posts if the Redirections module made them). If they're still there after clearing cache, those old posts are still published — check **Posts → All** and trash the ones that now redirect (the redirect lives in Rank Math → Redirections, not in the post, so trashing is safe; verify one first).

## 2. Noindex the utility pages

**Pages → All Pages → edit each:** `/login/`, `/register/`, `/password-reset/`, `/account/`, `/cart/`, `/checkout/`, `/pricing-4/`, `/social-links/`, `/refund_returns/` (keep indexable: `/privacy-policy/`, `/terms-and-conditions/`, `/disclosures/`).

In the Rank Math box below the editor → **Advanced** tab → Robots Meta → tick **No Index** (leave Follow). Save. These pages then leave the sitemap automatically.

Alternatively delete `/pricing-4/` outright — it's a placeholder duplicate of `/subscribe/`.

## 3. Schema defaults per content type

**Rank Math → Titles & Meta → (left tabs) Post Types:**

| Tab | Schema Type | Article Type | Notes |
|---|---|---|---|
| Properties | **Review** | — | Rank Math's Review schema lets you set "Item Reviewed" type = **Hotel** per post. Rating optional (your pieces are verdicts, not stars — leave rating blank unless you want stars in SERPs). |
| Guides | Article | **Article** | |
| Briefs | Article | Article | |
| Dispatches | Article | Article | |
| Shortlists | Article | Article | |
| Posts | Article | **BlogPosting → change to Article** | Old posts currently use BlogPosting |

Also on each tab:
- **Title pattern:** `%title% %sep% %sitename%` with separator `—` (Rank Math → Titles & Meta → Global → Separator Character). Site name = "Coco Tran" (Settings → General → Site Title; currently it seems to be blank, which is why titles end in " •").
- **Description pattern:** `%excerpt%` (and write a real excerpt on every piece — the description field in `meta-title-rewrites.csv` goes in the Rank Math "Description" box).
- **Remove " •" from titles:** that's the current separator with an empty site name. Set Site Title to "Coco Tran" and the pattern above.

**Author:** Rank Math → Titles & Meta → Authors: Author Archives **On** (one author, it's your E-E-A-T page). Then **Users → Your Profile:** Display name **Coco Tran**; fill Biographical Info (use the trust block from `about-page-trust-block.md`, short version); upload a real avatar via the profile picture plugin or the Rank Math "Author Image" field; add social URLs in the Rank Math Social Meta fields (these become `sameAs`).

**Knowledge graph (site identity):** Rank Math → Titles & Meta → Local SEO → Person or Company: **Person**. Name: `Coco Tran`. Logo: upload the wordmark. Then, because Rank Math's fields can't carry everything in `schema-person-organization.json`, add the rest via Rank Math → General → **Edit robots.txt**? No — via **Rank Math → Schema Templates (Pro)** or by pasting the JSON into a Kadence Element set to "Hook: wp_head, Display: entire site". Either works; the Kadence Element route is five minutes.

## 4. Real 404 instead of redirect to homepage

The 301-to-home for unknown URLs is almost certainly **Rank Math → General Settings → 404 Monitor / Redirections → "Auto Redirect 404s" or "Fallback Behavior: Redirect to Homepage"**. Set **Fallback Behavior: Default 404**. Save.

If it persists, check: a Cloudflare Page Rule / Redirect Rule; a snippet in the theme's functions.php; the membership plugin's "redirect unknown pages to home" option.

Then build the 404 page: Kadence → Theme → 404 Page (or create a Kadence Element → 404 template) using `404-page.md`.

## 5. RSS feed (currently HTTP 500 "No feed available")

That exact message is WordPress's `wp_die` from a `do_feed` filter being removed — i.e. a **"Disable RSS feeds"** toggle. Check in this order:
1. **LiteSpeed Cache → Page Optimization / Tuning** — no feed option there; skip.
2. **Perfmatters / WP Rocket / Asset CleanUp / "Disable Everything" / security plugin (Solid Security, Wordfence won't; but "Disable Feeds" or "Clearfy" will).** Look for *Disable RSS / Remove Feed Links* and turn it off.
3. **Code Snippets plugin** or theme `functions.php` — search for `do_feed` or `wp_die`.
Confirm with `curl -sI https://cocotran.com/feed/` → 200. Then also check `/feed/` shows the new content types: add to a snippet:

```php
add_filter('request', function ($qv) {
    if (isset($qv['feed']) && !isset($qv['post_type'])) {
        $qv['post_type'] = ['post', 'property', 'guide', 'brief', 'dispatch', 'shortlist'];
    }
    return $qv;
});
```

## 6. robots.txt

Rank Math → General Settings → Edit robots.txt:

```
User-agent: *
Disallow: /wp-admin/
Allow: /wp-admin/admin-ajax.php
Disallow: /cart/
Disallow: /checkout/
Disallow: /account/

User-agent: AhrefsBot
Disallow: /

Sitemap: https://cocotran.com/sitemap_index.xml
```

Do **not** block GPTBot, ClaudeBot, PerplexityBot, Google-Extended, Applebot-Extended — AI search visibility is a goal.

## 7. Verify

Run `04-scripts/recheck.sh`. Expected: sitemap index lists 5 new sitemaps; `/feed/` 200; unknown URL 404; `/login/` has `noindex`; Caruso page schema list includes `Review`; a Guide includes `Article`.
