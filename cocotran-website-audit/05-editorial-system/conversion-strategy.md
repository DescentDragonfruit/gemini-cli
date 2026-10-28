# Conversion strategy — from 200 free readers and 0 paid to a working funnel

Inputs (Coco, 24 Oct): bookings convert from the agency hub; cocotran.com's job is to qualify leads, build trust, give the DIY traveller real intel, and offer the conversion. 200 free Reader sign-ups, 0 paid. Ideal payer: the luxury DIY traveller who plans full trips, will book hotels through Coco for the perks (low-hanging fruit), and will pay for insider depth. Future clients too.

## The diagnosis

1. **The gate is in the wrong place.** The reading is gated; the thing people would actually pay for (a usable plan) isn't on offer. So nobody pays, and Google/AI engines can't see the reading either.
2. **The perks are hidden behind the most expensive tier.** "Book through me and get the upgrade, breakfast and credit at the same rate" is the single most convertible offer on the site — it costs the reader nothing and earns Coco commission. Today it's framed as an Insider ($200/yr) benefit. It should be the default call to action on every hotel piece, for everyone.
3. **No nurture.** 200 people gave an email and (as far as the site shows) got a newsletter. There is no sequence that walks them from "nice photos" to "I trust her" to "book through her / buy the plan."
4. **Nothing to sample.** The sales page says "nothing paywalled once you're in" but shows no paid piece in full.

## The model: read free · book through me · pay for the planning layer

| Layer | What it is | Job | Price |
|---|---|---|---|
| **Read (free)** | Every Verdict, Primer, Rolodex, Roundup, Shortlist, Dispatch, Answer, comparison. Full text. | Discovery, trust, AI citations, SEO. The reporting *is* the marketing. | Free; email optional |
| **Book through me (free, the main CTA)** | A box on every Property and every hotel mention: "Book [hotel] through Coco Tran Travel — same rate, plus [upgrade when available, daily breakfast, $100 credit, early/late check-in]." One form. | The low-hanging fruit. Converts the DIY planner into a booking without asking them to become a client. Earns commission. | Free to the reader |
| **The Planning Room (paid — rename The Archive)** | Routes and Dossiers as **usable trip files**: the day-by-day with booking links, a Google Maps list of every place in the Rolodex, the packing/visa/money sheet, "what to book first" with lead times; Private Intel three times a year; a members' Q&A (ask Coco a trip question, answered monthly); early access to new Verdicts. | Sells depth and utility to the DIY traveller. The reading stays free; the *plan* is what you buy. | $100/yr or $12/mo (keep) |
| **The Insider (paid)** | Everything above + $200 planning-fee credit + priority advisory access + a 20-minute trip sanity-check call. | The bridge to becoming a client. | $200/yr (credit makes it net-free for anyone who books a trip) |

What changes on the site:

- **Ungate Guides and Properties entirely** (template-level gate off for `guide` and `property`). Keep the gate on `brief` (Route/Dossier) with a long free lead: "The ask", "The shape of the trip" table, and a visible day-by-day table of contents — then "Get the full route and the trip file → The Planning Room". Paywall markup on Briefs.
- **The booking box** on every Property, every hotel in a Rolodex/Shortlist, every Route day: perks list + "Request this booking" (name, dates, hotel) → Coco Tran Travel. Track it as its own inquiry source.
- **Sample the paid layer**: one complete Route with its trip file, free, labelled "This is what Planning Room members get."
- **The Insider FAQ** says exactly what the perks are and that they come through bookings made via Coco Tran Travel.

## The 30-day plan to convert the 200 (and everyone after)

### Welcome sequence (5 emails, automate in the newsletter tool)

| Day | Subject (draft) | Job |
|---|---|---|
| 0 | "Here's how I review a hotel" | Trust: the method, one Verdict, the paid/hosted disclosure. Reply-to is Coco. |
| 3 | "The room I'd ask for at Caruso" (or the best current Verdict) | Prove the depth. End with the booking box: same rate + perks. |
| 7 | "A real two-week Japan route" | The free sample Route + trip file. Shows what the Planning Room is. |
| 12 | "Book through me, pay the same" | The perks explainer, plainly. One button: request a booking. |
| 18 | "The Planning Room, for the way you travel" | The offer. Founding-member price for the first 200 (see below). One testimonial if there is one. |
| 30 | "Where are you going next?" | A one-question reply email. Replies = leads. |

Voice: hers, first person, short, one idea per email, one link each. No "don't miss out".

### Founding offer to the existing 200
Email 18 goes to the 200 now, with a **founding price** ($49 first year, or $100 with a 20-minute call included — pick one) that expires in 14 days, plus the free sample Route so they see the thing. Expect 2–5% → 4–10 paid. That's the first renewal cohort and the first testimonials.

### On-site
- Short answer + At a glance on every piece (eeat-rollout.md) → the reader gets value in five seconds, which is what makes the booking box credible.
- Booking box live on all 6 Properties and in the Mallorca / Japan / Amalfi Rolodexes first.
- `/subscribe/` rewritten around the three layers with the sample Route embedded (FAQ copy in `03-fixes/subscribe-faq-corrected.md`, retitle Archive → The Planning Room).
- Exit intent or end-of-article prompt on Properties: "Thinking about this hotel? Book it through me — same rate, better room." Not a membership ask.

### Measure (monthly, in `90-day-plan.md`'s table)
Booking requests from the site (new metric — this is the one that matters), free→paid conversion, welcome-sequence open/click per email, paid members, advisory inquiries citing a piece.

## Why this converts better long-term

- A DIY traveller who reads three free Verdicts and books one hotel through Coco is worth more than a $100 membership — and is now a client relationship with zero planning-fee friction.
- Free, full-text reporting is what gets cited by AI engines and ranked by Google; gated reporting is invisible to both. The 0/20 benchmark won't move while the depth is hidden.
- The Planning Room sells something the free reading can't substitute (a usable file + access to Coco), so it doesn't cannibalise.
- Retention: members renew for the next trip file and the Q&A, not for articles they could have read free.

## Open to test later (not now)
A metered gate on Briefs (3/month with email) instead of a hard gate; a single-trip-file purchase ($19) as a lower rung; a "trip file" lead magnet per destination hub.
