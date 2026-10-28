# Article templates — make every piece quotable by AI and clear to Google

Principle (from the second review): clear factual context *alongside* the storytelling, not instead of it. The prose stays; each piece gains a few fixed, scannable elements that answer "what, where, when, for whom, how much" without the reader hunting.

Every template below has: one H1, a kicker, a **fact block**, a **verdict/summary box** near the top, descriptive H2s that contain the subject, a dates/disclosure line, and an **"Also in [destination]"** block at the end. These are the elements AI engines extract and cite.

Heading rule: H2s should read as the answer to a search, e.g. "Where to stay in Kyoto for a first visit" not "Stay". Subject + place in at least half of the H2s.

---

## The Verdict (Property)

```
[kicker: The Verdict · Ravello, Amalfi Coast]
# Caruso, A Belmond Hotel, Ravello: An Honest Review          ← single H1

[Fact block — 2-column table or definition list]
Stayed        June 2025, 3 nights
Room          312, Junior Suite, sea view
Rate context  ~€1,400/night in June; from ~€900 shoulder season   (or "Hosted" / "Paid")
Location      Ravello, 365 m above Amalfi; 45 min from Salerno, 1h30 from Naples airport
Best for      Couples; anyone who wants the coast without the coast's crowds
Not for       Beach-first travellers; anyone who needs to walk to dinner
Book via      Coco Tran Travel (Bellini Club: upgrade, breakfast, $100 credit) — or direct

[Verdict box]
**The verdict.** Two or three sentences. Book it if… Skip it if… The one thing that consistently missed.

## The room: Junior Suite 312 and what to ask for
## The pool, and why the morning is the point
## Food: Belvedere, the terrace, and what the Bellini Club perks are worth
## Location: what Ravello gives you and what it costs you
## Service and the small things
## Who should book Caruso — and who should look at [alternative] instead
## Practical details (rates, transfers, best months, how to book)
## FAQ  (3–5 questions people actually search: "Is Caruso worth it?", "Does Caruso have a beach?", "Caruso vs Palazzo Avino?")

Stayed June 2025 · Updated 23 Oct 2026 · Paid stay / Hosted — [state]

## Also in the Amalfi Coast
Rolodex: The Amalfi Coast · Route: … · Verdict: [other property] · Shortlist: …
```

Schema: Review (itemReviewed Hotel, with address + url) + FAQPage for the FAQ block. Comparison pieces ("Four Seasons Anguilla vs Cap Juluca") use the same structure with a two-column fact block and the verdict stating which one for whom.

---

## The Primer (Guide — "read this before you book")

Free in full (recommendation). This is the page AI engines should quote for "when to go to Japan", "is the JR pass worth it".

```
[kicker: The Primer · Japan]
# Japan Travel Guide: What to Know Before You Book

[Summary box — 4–6 bullets, each a complete factual sentence]
- Best months: late March–April (cherry blossom, book 9+ months ahead) and late Oct–Nov (foliage). Avoid Golden Week (29 Apr–5 May) and Obon (mid-Aug).
- The JR Pass is rarely worth it since the 2023 price rise unless you do Tokyo–Kyoto–Hiroshima and back; point-to-point tickets usually win.
- Luggage forwarding (takkyubin) costs ~¥2,500 per bag per hop and is the single best logistics trick.
- …

## When to go to Japan (month by month)
## Getting around: trains, the JR Pass question, and luggage forwarding
## Money: cash, IC cards, and what still doesn't take cards
## What to book first (and how far ahead)
## Etiquette: onsen, tattoos, tipping, shoes
## How long to stay, and the shape of a first trip
## FAQ

Updated 23 Oct 2026 — based on trips in [years]

## Also in Japan
Rolodex · Route: Two Weeks in Japan · Tokyo · Kyoto · Kanazawa · Takayama · Postcards
```

Schema: Article + FAQPage.

---

## The Rolodex (Guide — where to stay, eat, go)

Free lead = intro + "how this guide is organised" + the first neighbourhood in full. Gate the rest; include a visible table of contents of the gated sections so the reader knows what's behind the wall.

```
[kicker: The Rolodex · Tokyo]
# Tokyo Travel Guide: Where to Stay, Eat and Go, by Neighbourhood

[Summary box]
- Base in one neighbourhood and stay there: Ginza/Marunouchi for first-timers, Aoyama/Omotesando for design, Shinjuku for transit.
- Hotels we'd book: [3 names with one-line why] (full list below).
- Book restaurants 1–3 months out via hotel concierge or Omakase/Tableall; expect cancellation fees.

[TOC of sections — visible even when gated]

## Where to stay in Tokyo: the neighbourhoods, ranked for a first visit
## Ginza & Marunouchi — [free in full]
## Aoyama, Omotesando & Harajuku — [members]
## Shinjuku …
## Where to eat: how booking actually works, then the list
## Day trips worth the morning
## FAQ

Updated … · Also in Japan …
```

---

## The Route (Brief)

Gated after "The ask" + "The shape of the trip" table. The free part must make the whole route legible.

```
[kicker: The Route · Japan · 14 nights · Couple]
# Two Weeks in Japan: A First-Timer Route That Works

[Fact block]
Trip length    14 nights
Who            Couple, first visit, mid-40s
When           Late October
Shape          Tokyo 4 · Hakone 1 (ryokan) · Takayama 2 · Kanazawa 2 · Kyoto 4 · Osaka 1
Transport      Trains only; luggage forwarded twice
Budget band    ~$X–Y per night on hotels; [note]

## The ask
## The shape of the trip (table: day · base · the one thing)   ← free
--- members ---
## Days 1–4: Tokyo
## Day 5: Hakone …
## What I'd change next time
## Book this trip  → Coco Tran Travel

Planned/travelled [month year] · Updated …
```

Schema: Article (consider `TouristTrip` later; Article is enough now).

---

## The Shortlist

Free. Each entry needs a one-line **why** and a **who it's for**, not just a name.

```
[kicker: The Shortlist · Hanoi]
# Best Restaurants in Hanoi: An Advisor's Shortlist

[Summary — the top 3 in one line each]

## [Restaurant] — [neighbourhood] · [price band] · [one-line why]
   2–4 sentences. What to order. Booking note.
…
## How to use this list / what we left off and why
Updated … · Also in Hanoi …
```

Schema: Article + ItemList (Rank Math can add ItemList to list-style posts).

---

## The Dispatch (Postcard / Field Notes)

Free. Essay voice stays; add only the fact line and dates.

```
[kicker: Postcard From · Fushimi Inari, Kyoto]
# Fushimi Inari at Dawn: How to Have It to Yourself
Visited [month year] · Open 24h, free · 5 min from Inari JR station
…prose…
Also in Kyoto …
```

---

## Universal rules

1. **One H1** — the piece's name. Kicker is a `<p>`.
2. **First paragraph names the subject and place** in plain words.
3. **Dates:** "Stayed/Visited [month year] · Updated [date]" on every piece; dateModified in schema matches.
4. **Disclosure line** on every Property: paid / hosted / discounted.
5. **"Also in [destination]"** block on everything, linking Primer, Rolodex, Route, Properties, Shortlists for that place — and the hub page once hubs exist.
6. **Alt text on the hero image**: "[subject] at [place], photographed by Coco Tran on film" or a plain description.
7. **No** "(2026)", "ultimate", "best-of" in caps, "look no further", "#1".
