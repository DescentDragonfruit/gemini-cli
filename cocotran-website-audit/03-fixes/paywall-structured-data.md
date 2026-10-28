# Paywall structured data (Google "subscription and paywalled content")

Why: Google only indexes gated text — and only avoids treating the lead-in as "thin" — when the page (a) serves the full text in the HTML to Googlebot and (b) declares which part is gated via `isAccessibleForFree` + `hasPart`. Reference: https://developers.google.com/search/docs/appearance/structured-data/paywalled-content

Two parts: the markup, and making sure the gated HTML is actually in the page for Googlebot.

## 1. Mark the gated section in the template

Wrap the members-only content in an element with a stable class. In the Kadence Element / membership plugin template:

```html
<div class="free-lead"> … free portion … </div>
<div class="members-only"> … gated portion (rendered in HTML, hidden by CSS/JS for non-members) … </div>
```

## 2. Add this JSON-LD to every gated piece

Rank Math: either **Schema → Custom Schema** per post, or (better) one Kadence Element hooked to `wp_head`, display condition = post types Guide/Brief/Property (plus whichever are gated), with the JSON below. Replace the Article fields with Rank Math variables if the Element supports them; otherwise hard-code per template type.

```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Article",
  "@id": "%url%#article",
  "headline": "%title%",
  "image": "%featured_image%",
  "datePublished": "%date(c)%",
  "dateModified": "%modified(c)%",
  "author": { "@id": "https://cocotran.com/#coco-tran" },
  "publisher": { "@id": "https://cocotran.com/#organization" },
  "isAccessibleForFree": false,
  "hasPart": {
    "@type": "WebPageElement",
    "isAccessibleForFree": false,
    "cssSelector": ".members-only"
  }
}
</script>
```

For Properties use `"@type": "Review"` with `"itemReviewed": {"@type": "Hotel", "name": "…"}` instead of Article — or keep Rank Math's Review schema and add only the two `isAccessibleForFree` / `hasPart` properties to it (Rank Math Pro → Schema → Advanced → add property).

If Rank Math's Article schema is already set per post type (item 1.11), you only need to **add** `isAccessibleForFree` and `hasPart` to it rather than emit a second Article.

## 3. Make the full text reachable by Googlebot

Check how the membership plugin gates: if it **removes** the gated HTML server-side for logged-out visitors (most do), Googlebot never sees it and the markup does nothing. Options, in order of preference:

1. **Plugin setting** — many membership plugins have "Show full content to search engine crawlers" / "Googlebot bypass". Turn it on. (Verify Googlebot by reverse DNS — the plugin should do this; if it only checks the UA string, it's trivially spoofable but Google accepts that risk as the publisher's.)
2. **Client-side gate** — render everything, hide `.members-only` with CSS, remove the node with JS for non-members. Google indexes it; a determined reader can view-source. Acceptable for most publishers.
3. **Leave it server-gated** and accept ranking on the free lead only — in which case make the free lead substantial (phase 2.1).

Verify with Search Console → URL Inspection → Test Live URL → View Tested Page → check the gated text is in the rendered HTML.

## 4. Don't

- Don't show Googlebot the full text **without** the markup — that's cloaking.
- Don't put `isAccessibleForFree: false` on pages that are fully free (Dispatch, Shortlist, most Properties).
