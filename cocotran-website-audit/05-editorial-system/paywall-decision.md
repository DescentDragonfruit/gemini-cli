# The paywall decision — framework (decision pending Coco's answers)

The question isn't "free or paid". It's which layer does which job:

| Layer | Job | Who it's for |
|---|---|---|
| **Free** | Prove the quality, get found, get cited, start the relationship | Search/AI visitors, first-timers, the luxury DIY traveller |
| **Archive (paid)** | Depth that saves a planner real time and money | The DIY traveller who plans seriously; the client-to-be |
| **Insider** | The bridge into the advisory practice | People who will book through Coco Tran Travel |

What the current setup gets wrong, from the crawl: the acquisition layer is the most gated (Primers, 257 free words), the proof-of-quality layer doesn't exist on the sales page (no samples), and the gate lands mid-sentence with no sense of what's behind it. Meanwhile the formats most likely to convert a reader to a client (Verdicts, comparisons) are mostly free.

## The recommendation (to be confirmed after the qualifying questions)

**Free, in full:** The Primer (every destination), The Shortlist, The Dispatch, The Answer, comparisons, and the **Short answer + At a glance** block on every gated piece.
**Free lead, then gated:** The Rolodex (intro + how-it's-organised + first neighbourhood in full + visible TOC), The Route (the ask + the shape of the trip table + TOC), The Dossier (overview + visa/money rows).
**Gated:** the rest of the Rolodex/Route/Dossier; the room-by-room detail of The Verdict (keep the short answer, the who-it's-for and the practical details free).
**Insider:** unchanged, plus precise eligibility conditions in the FAQ.
**Everywhere gated:** paywall structured data; Googlebot sees full text.
**Sales page:** 2–3 complete sample pieces, the comparison table, consistent tier copy.

Why this split: the Primer is the page that answers "what should I know before…" — the question AI engines field most, and the one that makes a reader trust the Rolodex enough to pay for it. The Rolodex's value is the whole list, so a long lead gives away one neighbourhood and sells the other six. A Verdict's commercial value to Coco is mostly the advisory inquiry, not the $100 membership, so its verdict should be as findable as possible.

## Qualifying questions (asked in-session; answers decide the split)

1. In the last 6 months, what has produced more revenue: memberships, or advisory bookings that started from the site? (Tells us whether the paywall's job is revenue or lead qualification.)
2. Roughly how many paid members today, and what's the renewal rate so far?
3. Of advisory inquiries, how many came from reading a specific piece vs Instagram/referral?
4. Is the gated content in the Guides materially deeper than the free lead — i.e. would a member feel cheated if the Primer went free? (If the Primer and Rolodex overlap, the gate is protecting the wrong thing.)
5. Is there a technical constraint — the membership plugin gates per template, not per post (the respin skill notes "no per-post ungate switch"). Can the gate position be moved, or does ungating a format mean ungating the whole CPT?
6. Who is the paying member you actually want: the luxury DIY planner, or the future client? (The answer changes whether Archive should be cheap and wide or scarce and deep.)
7. Appetite for a metered model (3 free gated pieces/month with email) as an alternative to hard gates?

## Decision (24 Oct 2026, after Coco's answers)

Coco's answers: bookings convert from the agency hub; cocotran.com exists to qualify leads, build trust and give DIY intel with an option to convert; **200 free readers, 0 paid**; the ideal payer is the luxury DIY traveller who will book hotels through her for the perks and pay for insider depth; gate mechanics: "whichever converts best long-term".

**Decided:**
1. **Guides and Properties go fully free** (template gate off for `guide` and `property`). The reporting is the marketing and the AI-citation layer.
2. **Briefs stay gated** behind a long free lead (The ask + The shape of the trip + visible TOC) with paywall markup. The paid product is repositioned as **The Planning Room**: Routes/Dossiers as usable trip files + Private Intel + members' Q&A + early access. Same price.
3. **"Book through me — same rate, plus perks" becomes the primary call to action** on every hotel piece, for everyone, not an Insider perk. Insider keeps the planning-fee credit, priority access and a short call.
4. **A 5-email welcome sequence and a founding-member offer** to the existing 200 go out within 30 days; one complete Route + trip file is published free as the sample.
5. Not doing: a meter, a hard gate on Guides, or chasing the $100 before the booking relationship exists.

Full rationale, the email sequence and the 30-day plan: `conversion-strategy.md`. Plan items: `02-action-plan.md` 2.1–2.2 and 2c.
