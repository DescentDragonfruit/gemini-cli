# Kadence Element — "Booking box" (Property) + `[ctt_book]` shortcode

The default call to action on every hotel piece (`05-editorial-system/conversion-strategy.md`): **"Book [hotel] through Coco Tran Travel — same rate, plus perks"** with the perks line and one button that opens `https://cocotrantravel.com/contact?hotel=…&source=…` (hotel name and the current permalink, URL-encoded, so the agency form can read them as hidden fields and the inquiry is tracked as its own source).

Two pieces, one look:

| Piece | Where | Needs |
|---|---|---|
| `kadence-element-booking-box.html` | Kadence Element shown on **CPT Property**. Heading bound to the post title with Kadence dynamic content; perks + button come from the shortcodes. | Kadence Blocks Pro, `booking-box-shortcode.php` in `mu-plugins` |
| `booking-box-shortcode.php` | `[ctt_book hotel="Caruso, A Belmond Hotel" perks="upgrade, breakfast, $100 credit"]` inside a Rolodex "Where to stay", a Shortlist pick, or a Route day. Also `[ctt_book_perks]` and `[ctt_book_button]` (used by the Element). | nothing else |

Install the mu-plugin **first**; until it exists the Element prints the literal `[ctt_book_perks]` text.

## Clicks for the Element (≈3 minutes)
1. Copy `booking-box-shortcode.php` to `wp-content/mu-plugins/` (SFTP or the host's file manager; create the folder if missing).
2. **ACF → Tools → Import** `acf-booking-access.json` (adds the *Booking box* side panel to Properties: `booking_access` select + optional `booking_perks_override`).
3. **Appearance → Kadence → Elements → Add New**, title `Booking box`, ⋮ → **Code editor**, paste the file.
4. Element Settings → **Placement → Hook:** `Single Content: After` (`kadence_single_after_content`) — it then sits under the Verdict's last section, before the footer. Priority `10`. **Show on:** `Singular: Properties`. Publish.
5. On each Property, set **Booking access** (Virtuoso / Four Seasons Preferred Partner / Belmond Bellini Club / Marriott STARS / Hyatt Privé / Direct). Empty = the generic line.
6. Delete all Cache; purge Cloudflare.

One-liner alternative: a Kadence Element containing a single *Shortcode* block with `[ctt_book]` renders the same box (title included) — use this if dynamic content misbehaves in your Kadence version.

## The perks line, per `booking_access`
Defaults live in `ctt_book_perks_map()` in the mu-plugin (filter `ctt_book_perks_map` to change them). Wording follows the voice rule — "typically", "when available", "wherever the programme applies":

| Value | Programme | Default sentence |
|---|---|---|
| `virtuoso` | Virtuoso | Through Virtuoso, my clients typically enjoy a room upgrade when one's available, daily breakfast for two, a US$100 hotel credit, and early check-in or late check-out when available. The hotel pays me, not you. |
| `fspp` | Four Seasons Preferred Partner | …an upgrade when one's available, daily breakfast for two, a hotel credit set by the property (usually US$100), and early check-in or late check-out when available. The hotel pays me, not you. |
| `bellini` | Belmond Bellini Club | …an upgrade when one's available, daily breakfast for two, a US$100 hotel credit, and early check-in or late check-out when available. The hotel pays me, not you. |
| `stars` | Marriott STARS | …an upgrade when one's available, daily breakfast for two, a US$100 hotel credit, and early check-in or late check-out — wherever the hotel participates. The hotel pays me, not you. |
| `prive` | Hyatt Privé | …an upgrade when one's available, daily breakfast for two, a US$100 hotel credit, and early check-in or late check-out when available. The hotel pays me, not you. |
| `direct` | Direct | This hotel isn't in a preferred-partner programme, so I book it direct at the hotel's own rate and ask for the room, the view and the small courtesies myself. You pay the same; I handle the arrival. |
| *(empty)* | — | Same rate as booking direct. Through my preferred-partner programmes, my clients typically enjoy a room upgrade when one's available, daily breakfast, a hotel credit, and early check-in or late check-out, wherever the programme applies. The hotel pays me, not you. |

Verify the credit amounts against each programme's current terms before going live — the US$100 figure is the common case, not a guarantee (Four Seasons credits are set per property; Marriott STARS hotels can opt out).

## Shortcode attributes
`[ctt_book hotel="…" perks="…" access="…" source="…" label="…"]`
- `hotel` — defaults to the post title on a Property; required elsewhere.
- `perks` — short list ("upgrade, breakfast, $100 credit") → "my clients typically enjoy upgrade, breakfast, $100 credit, wherever the programme applies…". Omit to use the `access` default.
- `access` — one of the values above; defaults to the post's `booking_access`.
- `source` — defaults to the current permalink; `label` — button text (default "Request this booking").

The link opens in a new tab with `rel="noopener"` and carries `data-ctt-source="booking-box"` for click tracking. On cocotrantravel.com, read `hotel` and `source` from the query string into hidden fields on the contact form so each request arrives tagged.

## Styling
Same tokens as the Answerable opening (`--global-palette1/3/5/7`, heading serif, monospace small caps). The box is a hairline-topped aside, no fill, with the hotel name in the accent colour. The CSS is in the Element's last `wp:html` block and, identically, in the mu-plugin (printed once per page by `[ctt_book]`) so a Rolodex with three boxes gets one stylesheet.
