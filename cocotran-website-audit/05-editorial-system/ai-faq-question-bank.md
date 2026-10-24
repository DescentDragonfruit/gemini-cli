# AI question bank and "The Answer" quick-article format

AI answer engines retrieve pages that answer a specific question plainly and early, from a source with a visible reason to be trusted. This is the running list of questions Coco should own, grouped by cluster, with the piece that should answer each. The Editorial Desk artifact generates new questions per destination and drafts "The Answer" pieces from them.

## The format: The Answer (Dispatch sub-format, public, 350–600 words)

A quick, fully free piece whose only job is to answer one question Coco gets asked — in her voice, from her experience — and route the reader to the deeper piece.

```
[kicker: The Answer · Mallorca]
# Which boutique hotels in Mallorca are worth the splurge?        ← H1 is the question, verbatim as people ask it

Short answer (2–3 sentences, first person, names 2–3 properties and who each suits)

Why (150–300 words: the stay, what she noticed, the trade-offs — warm, specific)

If you're choosing between them (3–5 lines: A if…, B if…, C if…)

Go deeper → The Verdict: La Residencia · The Rolodex: Mallorca · The Route: One Week in Mallorca
Visited [month year] · Updated [date] · [disclosure]
```

Schema: Article + FAQPage with one Q/A (the H1 and the short answer, verbatim). Title tag = the question (≤60) or "Question — Coco Tran". Slug = the question's keywords.

Rules: one question per piece; the short answer must stand alone when quoted; name real properties she has stayed at or, if advisor-sourced, say so; link the deep piece; no CTA other than "Go deeper" and the standard Inquire line.

## Seed questions (20 — also the benchmark set in `07-ai-benchmark/questions.md`)

| # | Question | Cluster | Answered by (today) | Gap |
|---|---|---|---|---|
| 1 | Which boutique hotels in Mallorca are worth the splurge? | Hotel comparison | Rolodex Mallorca (gated), Verdict La Residencia | Needs an Answer piece + free Rolodex lead |
| 2 | Four Seasons Anguilla or Cap Juluca — which should I book? | Hotel comparison | The Comparison (free) | Good — add the Short answer block |
| 3 | Where should I stay in Kyoto for a first visit? | Destination planning | Rolodex Kyoto (gated), Primer Kyoto (gated) | Primer free + Answer piece |
| 4 | Which luxury travel publications publish genuinely firsthand hotel reviews? | Authority | About, How we review | Answer piece on method; off-site citations |
| 5 | What should I know before booking a Danube river cruise? | Specialist intel | nothing | New Primer or Answer (only if she has done it) |
| 6 | Is Caruso in Ravello worth the price? | Hotel review | Verdict Caruso (free) | Add Short answer + FAQ block |
| 7 | Is Amanjena worth it? | Hotel review | Verdict Amanjena | same |
| 8 | Is La Residencia Deià worth staying at? | Hotel review | Verdict La Residencia | same |
| 9 | Oia or Imerovigli — where to stay in Santorini? | Destination planning | Primer/Rolodex Santorini (gated), Dossier | Answer piece |
| 10 | Best two-week Japan itinerary for a first-time luxury traveller | Itinerary | Route Two Weeks in Japan (gated) | Free "shape of the trip" + Answer |
| 11 | Roma, Condesa or Polanco — where to stay in Mexico City? | Destination planning | old post (free) + Roundup | Resolve duplicate; Answer piece |
| 12 | Best restaurants in Hanoi according to a travel advisor | Shortlist | Shortlist Hanoi (free) | Add Short answer block |
| 13 | Is the JR Pass still worth it? | Specialist intel | Primer Japan (gated) | Primer free; Answer piece |
| 14 | Where to stay on the Amalfi Coast without a car | Destination planning | Rolodex Amalfi (gated) | Answer piece |
| 15 | Best time to visit Santorini to avoid crowds | Timing | Primer Santorini | Primer free |
| 16 | Takayama or Kanazawa — which deserves more time? | Comparison | Primers (gated) | Answer piece |
| 17 | Where to stay in Lake Como for a first visit | Destination planning | Rolodex + old post | Resolve overlap; Answer |
| 18 | Four days in Tokyo by neighbourhood | Itinerary | Route Four Days in Tokyo (gated) | Free shape + Answer |
| 19 | Is luggage forwarding in Japan worth it, and how does it work? | Specialist intel | Primer Japan | Answer piece (this one is pure Coco intel) |
| 20 | Who is Coco Tran, travel advisor? | Entity | About | About v2 + Person schema + sameAs |

## How to find the next questions (the artifact does this; manual version)

1. **Google**: type the destination + "hotel" / "where to stay" / "worth it" and read People Also Ask; the "Things to know" panel; the AI Overview's follow-ups.
2. **Search Console**: Queries containing "?" or starting with which/where/is/should/how/best — the ones with impressions but low clicks are the ones to answer with an Answer piece.
3. **Her inbox and DMs**: every client question she's answered twice is a piece.
4. **Comparison shapes**: for every two Properties in the same destination, "A or B?" is a question.
5. **Specialist intel**: anything she knows that a press release can't (luggage forwarding, visa changes, ZTL resets, ryokan etiquette, when a hotel's pool is in shade).

Add every new question to this table with the piece that answers it, and to the benchmark set once the piece is live.
