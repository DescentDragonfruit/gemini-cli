# Kadence Element — "Answerable opening"

One Element, hooked under the title on every single-post template, that renders the ACF group **Answerable opening** (`acf-answerable-opening.json`): byline strip → short answer → At a glance → disclosure line. Design per `05-editorial-system/eeat-rollout.md` §1: no fill, no icons, hairlines only, the brand accent appears once (the 2px left rule on the short answer).

Prerequisites: Kadence Theme + Kadence Blocks **Pro** (dynamic content is a Pro feature) + ACF Pro with the field group imported (`INSTALL.md` step 1).

## The exact clicks (≈4 minutes)

1. **Appearance → Kadence → Elements → Add New.** Title: `Answerable opening`.
2. Top-right ⋮ menu → **Code editor**. Select all, paste the whole of `kadence-element-answerable-opening.html`. Switch back to the visual editor; you should see five grey "dynamic text placeholder" rows plus the static labels. If Kadence re-numbers the `uniqueID`s on save, that's fine — the CSS targets the class names, not the IDs.
3. Right sidebar → **Element Settings**:
   - **Type:** Default (Content).
   - **Placement → Hook:** `Single Content: Before` (`kadence_single_before_content`). If the H1 is rendered *inside* the content by the format's Element (the Verdict/Primer/etc. templates print their own `title`/`tittle_word_1` blocks), use **Custom Hook** instead and type the name of a `do_action()` placed just under the title in each template — see "Where it sits" below.
   - **Priority:** `5` (lower than the format templates, which run at 10, so it lands above them when both use the same hook). The respin skill notes priority 1 beats 10 — anything below 10 works.
   - **Display Settings → Show on:** add six rules joined by OR: `Singular: Properties`, `Singular: Guides`, `Singular: Briefs`, `Singular: Dispatches`, `Singular: Shortlists`, `Singular: Posts`. Leave **Expires** off, **User Settings** = All users.
4. **Publish.** Open one Property with the ACF fields filled (`?preview=true` works) and check the block sits between the kicker/H1 and the opening image.
5. **Do not tick any plan in the Paid Member Subscriptions "Content Restriction" box on this Element post** — that metabox trims the whole output to a 30-word teaser (the Dossier incident in the respin skill).
6. Admin bar → **Delete all Cache**, then purge Cloudflare.

### Where it sits on each template
The format templates (27262 Verdict, 29436 Tradeoff, 27517 Primer, 27738 Rolodex, 27762 Roundup, 27733 Route, 27556 Dossier, 27818 Shortlist, 27776/27810 Dispatch) draw the H1 from ACF inside their own Element, so "before content" puts this block *above their headline*, not under it. Two fixes, pick one:

- **Custom hook (cleanest):** in each template, insert a *Shortcode* block containing `[ct_hook name="ct_after_title"]` directly under the title blocks — or, simpler, a Kadence *Advanced Text* block is not needed: Kadence Elements accept a **Custom Hook name** and fire on `do_action('ct_after_title')`. Add a 3-line mu-plugin — `add_shortcode('ct_hook', function ($a) { ob_start(); do_action(sanitize_key($a['name'] ?? '')); return ob_get_clean(); });` — and set this Element's hook to Custom: `ct_after_title`. Edit templates with string split/join, never regex.
- **CSS order (zero template edits):** keep `Single Content: Before`, and in the Element's `<style>` add `.ct-ao{order:2}` with the single-content wrapper set to `display:flex;flex-direction:column` — only if the theme wrapper tolerates it. Test on one template first.

## How empty rows are hidden
Kadence Blocks Pro dynamic content has a **Fallback** but no native "hide block if empty" on Advanced Text, so the Element ships a 25-line script (base64 in the last `wp:html` block — WordPress mangles `&&` in raw `<script>` tags, hence `new Function(atob(...))()`, the pattern the respin skill uses). On load it:

- hides the whole block if `short_answer` is empty (a piece not yet backfilled shows nothing, not an empty box);
- hides any `At a glance` row whose value is empty, and the whole table if all five are;
- reads `advisor_roundup`: when true, prints "Advisor roundup" and drops "Stayed …";
- hides empty byline segments and inserts the " · " separators between the ones left;
- hides the disclosure select text if unset (the "Hotels here can be booked…" sentence and the How-we-review link stay).

Before the script runs the rows render with their label; there is no flash in practice because the script runs on `DOMContentLoaded` and the block is above the fold only after the hero image loads. If your Kadence Blocks Pro has block-level **Conditional Display** (Advanced tab on the block, Pro ≥ 2.4 — check your version), you can additionally set each row's *value* block to display only when the custom field is not empty; the script then does nothing on those rows. Either way, keep the script: it also handles the byline separators.

## Bindings (what the JSON says)
Each dynamic block carries `"kadenceDynamic":{"content":{"enable":true,"field":"post|post_custom_field","para":"acf_meta|<field>","shouldReplace":true}}` and the inner `<span class="kb-inline-dynamic">dynamic text placeholder</span>` — both are needed, as the respin skill documents. Fields: `visited`, `updated_on`, `advisor_roundup`, `short_answer`, `glance_1_label` … `glance_5_value`, `disclosure`. `visit_basis` is not printed in the byline; it is the usual value of the "Stayed" row.

`updated_on` prints in the ACF return format (`j M Y` → "23 Oct 2026"). If it prints as `20261023`, Kadence is reading raw meta: set the ACF field's Return Format to `j M Y` (already in the JSON) or change the binding to `post|post_custom_field` + `para: "updated_on"` and add a `date` format in the block's dynamic settings.

## Per-format label
The label above the short answer is static ("The short answer"). Primer / Route / Dossier / Shortlist want "Before you book" / "The shape of the trip" / "In one breath" / "The three to know" (eeat-rollout §1). Options: (a) accept one label site-wide for now; (b) add a `short_answer_label` text field to the ACF group and bind the label block to it; (c) duplicate the Element per format with a narrower Show-on rule (Taxonomy term = The Route, etc.). (b) is the 2-minute fix once the block is live.

## Styling tokens
The `<style>` uses Kadence's globals: `--global-palette1` (accent → `--ct-accent`), `--global-palette3/5/7` (ink / grey / hairline), `--global-heading-font-family` for the short answer, `--global-body-font-family` for values, and a system monospace stack in small caps for labels. If the brand accent is not palette 1, override `--ct-accent` on `.ct-ao`. `--ct-ao-max` (720px) sets the measure; match it to the single-post content width.
