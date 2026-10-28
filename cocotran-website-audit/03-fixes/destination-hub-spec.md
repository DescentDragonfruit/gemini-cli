# Destination hubs — architecture and build spec

Goal: each strong destination becomes a connected body of work with one landing page that Google and AI engines treat as *the* Coco Tran page for that place, and from which every format links out and back. Build the four with the most material first; don't build hubs for destinations with one or two pieces.

## Hub page spec (`/destinations/kyoto/` or `/kyoto/`)

URL pattern: `/destinations/[place]/` as a child of the existing `/destinations/` page (Kadence Advanced Page or a plain Page with a Query Loop block per format). One per place. Breadcrumb: Home › Destinations › Japan › Kyoto.

```
[kicker: Destination · Japan]
# Kyoto                                              ← H1 = the place name
[one-paragraph standfirst: what Kyoto is for, in your voice]

[Fact strip]
Best months · How long · Base neighbourhood · Nearest airport/transfer · Our last visit

## Start here
  The Primer: Kyoto  — what to know before you book
  The Rolodex: Kyoto — where to stay, eat and go
## Routes
  The Route: Three Days in Kyoto · Two Weeks in Japan (Kyoto days)
## Where to stay
  [Property cards: Verdicts in/near Kyoto; until there are Verdicts, the "where to stay" section of the Rolodex]
## Where to eat
  Shortlists / Rolodex food section
## Postcards
  Fushimi Inari at dawn · ryokan · …
## Nearby
  Kanazawa · Takayama · Osaka · Nara (old posts until respun)
## Plan this trip → Coco Tran Travel
```

Title tag: `Kyoto Travel Guide: Where to Stay, Eat and Explore — Coco Tran`
Description: `Everything Coco Tran has written about Kyoto: the primer, the neighbourhood rolodex, a three-day route, where to stay and eat, and postcards from the city.`
Schema: `CollectionPage` + `BreadcrumbList`; optionally `Place`/`City` with `sameAs` Wikidata.

Linking rules:
- Hub → every piece for the place (above).
- Every piece → hub (breadcrumb + the "Also in Kyoto" block).
- Sibling pieces link each other where the prose already refers ("is in the Primer").
- Country hub (Japan) → city hubs; city hubs → country hub.
- `/destinations/` → continent → country/city hubs. Replace the current tab-based archive (whose tab labels leak into the meta description) with hub cards.

## Hub map — what exists today (from the crawl)

| Hub | Primer | Rolodex / Roundup | Route / Dossier | Properties | Shortlists | Dispatches | Old posts still live |
|---|---|---|---|---|---|---|---|
| **Japan** (country) | japan-primer | japan-guide | two-weeks-in-japan | — | — | postcard-from-a-ryokan | 2-day-hakone-itinerary, things-to-do-in-hakone-japan, visiting-kamikochi-japan, where-to-stay-in-fukuoka, luggage/suica/cash posts (→ Primer) |
| Tokyo | tokyo-primer | tokyo-guide | four-days-in-tokyo | — | — | postcard-shibuya-crossing | tokyo-to-mount-fuji-day-trip-guide, fuji-day-trip-from-tokyo, kawagoe-day-trip, teamlab-planets-review, visiting-oishi-park-japan, best-place-to-see-mount-fuji-views, ryokans-near-mt-fuji |
| Kyoto | kyoto-primer | kyoto-guide | three-days-in-kyoto | — | — | postcard-fushimi-inari | nara-1-day-itinerary |
| Kanazawa | kanazawa-primer | kanazawa-guide | two-days-in-kanazawa | — | — | postcard-kanazawa-gold-leaf | — |
| Takayama | takayama-primer | takayama-guide | two-days-in-takayama | — | — | postcard-takayama-morning-market | — |
| Osaka | — | — | — | — | — | — | osaka-street-food-guide, 3-days-osaka-itinerary, 2-day-osaka-itinerary, kobe-day-trip-from-osaka |
| **Italy** (country) | regions-of-italy | — | — | — | — | — | northern-italy-vs-southern-italy, best-places-to-visit-in-northern-italy, one-day-milan-itinerary, locorotondo-puglia-italy, beaches-in-portofino-italy |
| Amalfi Coast | — | amalfi-coast-guide | — | caruso-ravello | — | — | — |
| Lake Como | — | lake-como-planning-guide (Rolodex) + lake-como-guide (Roundup) | lake-como-the-dossier | — | — | — | villa-del-balbianello, where-to-stay-in-lake-como, lake-como-1-day-itinerary, lake-como-versus-lake-garda |
| Tuscany / Rome / Dolomites / Cinque Terre | — | one Rolodex each | — | — | — | — | saturnia, scenic drives, rome-in-a-day |
| **Mallorca** | — | mallorca-guide, deia-tramuntana-guide, valldemossa-guide | one-week-in-mallorca, one-day-in-palma | la-residencia-deia | mallorca-calos-beaches | cas-patro-march-deia | renting-a-car-in-mallorca, santanyi-market, cala-deia, deia, fornalutx |
| **Santorini** | santorini-primer | santorini-guide | santorini-dossier | — | — | postcard-from-oia | travel-to-santorini-…, best-place-to-stay-in-santorini |
| **Mexico City** | — | mexico-city-hotels, best-restaurants-mexico-city, mexico-city-museums-galleries | three-days-in-mexico-city | — | — | rosetta, grutas-tolantongo, teotihuacan, parque-quetzalcoatl, biblioteca-vasconcelos | where-to-stay-in-mexico-city, mexico-city-5-day-itinerary, mexico-city-travel-guide, day-trips-from-mexico-city, frida-kahlo, oaxaca |
| **Vietnam** (country) | — | — | vietnam-the-dossier, two-weeks-in-vietnam | — | — | golden-bridge | ninh-van-bay, ana-mandara |
| Hanoi | — | hanoi-city-guide (Rolodex) + things-to-do-in-hanoi (Roundup) | hanoi-the-dossier | — | best-restaurants-hanoi, best-cafes-hanoi | tam-vi-hanoi | — |
| Hoi An / Da Nang / Ninh Binh / Ha Long | — | one each | da-nang-the-dossier | — | best-restaurants-hoi-an | — | — |
| **French Riviera** | — | villefranche-cap-ferrat-guide, menton-france | — | — | — | — | eze, nice ×6, antibes, saint-paul, monaco, day-trips-from-nice |
| **Marrakech** | — | — | — | amanjena-marrakech | marrakech | — | — |
| **Anguilla** | — | — | — | four-seasons-anguilla, cap-juluca, the comparison | — | cap-juluca-anguilla | — |
| Paris | — | — | paris-the-dossier | — | — | — | — |
| Tulum / Yucatán | — | — | — | — | — | — | 9 old posts |
| Basque Country | — | — | — | — | — | — | 10 old posts |
| Sequoia / Tahoe / NorCal | — | — | — | — | — | — | ~20 old posts |

## Build order

1. **Japan** (country hub + Tokyo, Kyoto, Kanazawa, Takayama) — complete Primer/Rolodex/Route/Postcard set already exists. Fold the old Hakone/Fuji/Osaka posts in under "Nearby" until respun.
2. **Mallorca** — Rolodex ×3, Route ×2, Verdict, Shortlist, Dispatch.
3. **Mexico City** — three Roundups, a Route, five Dispatches; resolve old-vs-new hotels page first (3.4).
4. **Vietnam** (country + Hanoi) — resolve Rolodex-vs-Roundup overlap for Hanoi: make the Roundup "Things to do" and the Rolodex "Where to stay & eat", retitle both.
5. Then Italy (country + Amalfi, Lake Como), Santorini, French Riviera.

Skip hubs for: Paris, Marrakech, Anguilla (one or two pieces — the pieces link to each other instead), Tulum/Basque/Sequoia (old posts only — decide respin vs retire first).

## Resolving the overlaps while building

- **Lake Como:** `lake-como-planning-guide` = The Rolodex (where to stay/eat), `lake-como-guide` = The Roundup → retitle "Things to Do in Lake Como" or merge into the Rolodex; Dossier stays. Old `where-to-stay-in-lake-como` → 301 to Rolodex.
- **Mexico City hotels:** old `/where-to-stay-in-mexico-city/` (4,451 words, likely ranking) vs new `/guide/mexico-city-hotels/`. Check Search Console; if the old one has clicks, respin the new Roundup's content *into* the old URL's slot (keep the URL, new format) and 301 the new one — or the reverse if the old has nothing. Don't keep both.
- **Tokyo→Fuji day trip:** two old posts on the same query. Merge into one (keep the URL with more impressions), 301 the other.
- **Santorini:** old `/travel-to-santorini-…/` → 301 to the Primer.
