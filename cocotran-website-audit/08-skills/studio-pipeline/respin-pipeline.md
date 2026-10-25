# Respin Pipeline — Studio Desk Spec
Oct 25, 2026 · Coco. The governing document for migrating old posts into pillar pieces. The worker reads this before any respin; the desk shows it under Playbook.

## What this pipeline does
It takes an old, off-brand SEO post on cocotran.com and rebuilds it as an editorial pillar piece in Coco's voice, carrying her own photography, then redirects the old URL and deletes the original. It is for migrating existing content. Net-new field notes go through Agency Studio instead.

The governing rule: a piece is only worth building if Coco's own photographs can carry it. Everything else (format, voice, fields, redirects) follows from that. A destination with no photography of hers gets parked or redirected, never padded with stock.

One old post rarely equals one new piece. The biggest wins come from merging: five Cinque Terre posts became one Rolodex, twelve Sequoia posts will become four. Decide the merge before writing a word.

What the pipeline produces per destination, where photos allow, is a set of four: a Primer, a Rolodex, a Postcard and a Brief.

## The governing voice
The old posts are DIY, how-to, budget-blogger. The rewrite is the opposite: curated intelligence from an advisor who has actually been there.

Positive, happy and excited. Confident and authoritative, but warm and enjoyable, never cold or matter-of-fact. This is the correction Coco gives most often. Do not write clipped verdicts like "Three nights. One shore. Do not plan around it." Let sentences breathe, show delight in the place, use "I love this coast," "my quiet favourite," "the hour everyone remembers." Keep every fact and every verdict; make the reader feel welcome inside them.

Fields that carry the warmth, rewrite first on a warming pass: opening_note, closing_paragraph, insider_faq, reader_questions_&_answers, the_ask, last_thoughts, the_takeaway, closing_note, dispatch_one_liner.

Rules for every format:
- First person, speaking to "you." Never a listicle, never third-person detached.
- Reframe anything negative (crowds, a rough history, rustic facilities) as charm, or handle it lightly. Never sour.
- Private and escorted is the default: a driver meeting the flight, a private guide, a chartered boat, first entry before the coaches, a villa with staff. Sell it on the joy and access it buys, not on logistics. Mention self-guided as a genuine secondary option, lean much heavier on the private.
- Unless it genuinely isn't practical; then say so in Coco's voice rather than pretending. Walking the Hanoi Old Quarter at six, the Lake Como car ferry, one train hop between Cinque Terre villages are her recommendation, framed as such.
- Hotels: top tier only. Michelin Key, Relais & Châteaux, Four Seasons level, private villas with staff for families. Where no luxury hotel exists, say so plainly.
- Lean on the agency, warmly. "When I plan this for clients…" plus a closing line that she would love to arrange it.
- Preferred-partner perks on hotel pieces: "my clients typically enjoy daily breakfast, a hotel credit and an upgrade whenever one's available," with "wherever they apply."
- No italics. Never put <em> in body HTML.
- Verify upgraded facts against primary sources and leave out anything unconfirmed. Coco would rather omit than misstate.

Strip these DIY tells: breathless titles, "RELATED:" dumps, keyword-stuffed headings, generic tips, budget framing, affiliate "BOOK THIS TOUR" blocks, ratings and price tables, stale prices, opening hours, contact emails. Turn RELATED dumps into a tasteful "pair it with" that links to other respun pieces.

The CTA, near the end, before "pair it with", always to /contact, label matched to the piece:
`<p><a class="button" href="https://cocotrantravel.com/contact" target="_blank" rel="noopener">Inquire about a private Santorini trip</a></p>`

## Photo selection
Photo-led pieces are credited "Photos & Words by Coco," so they must use her own photography. This is the first check on any cluster and decides whether the work happens at all.

| Signal | Means |
|---|---|
| santorini-travel-guide-cocotran-27, things-to-do-in-bilbao-spain-coco-tran-07 | Hers, her naming convention |
| IMG_3948, img_4477, COT01290 | Hers, straight off the camera |
| julien-di-majo-…-unsplash, photo-by-dimitris-mourousiadis | Stock, skip |
| 2560px-peillon_landscape, nice_tramway_ligne_2_plan_2018-svg | Wikimedia or a map, skip |
| Long numeric filenames, screenshot-2024-06-17-at-7-27-05-pm | Skip |

Also skip signage and maps even when she took them.

The trap that matters most: her filenames lie about location and her naming is inconsistent (things-to-dovillefrenche-sur-mer-coco-tran-*, menton-nice-france-coco-tran-* that are Menton, a whole Riviera trip as unlabelled img_44xx). Never decide a cluster on filename search alone. Pull media IDs by upload-ID range around the ones you can identify, then look at them.

The contact sheet is the only reliable way to know what you have. From any wp-admin page: pull image IDs from each source post's content.raw (wp-image-<id>, "id":<n>, "ids":[…]); fetch /wp/v2/media?include=…; inject a fixed grid of thumbnails labelled with media IDs; screenshot; remove the overlay; assign photos from what you see and write captions from the image, not the filename. Sixty thumbnails at 10% width and 130px tall reads in one screenshot.

When to park: only stock → park it and tell Coco (Sapa, Frida's Casa Azul, Oaxaca Día de Muertos; Zakynthos redirected). Check the source post's photos before promising a piece. Build first, photos later if needed.

The one exception: advisor roundups of hotels may use the hotels' own press photos, credited "Photo courtesy of the hotel" in captions plus "Hotel photography courtesy of the hotels featured." Advisor voice, never "I stayed here" unless she says she did.

## The five content types
| Type | URL | Formats | Gated |
|---|---|---|---|
| The Property | /property/ | The Verdict, The Comparison, The Tradeoff | no |
| The Guide | /guide/ | The Roundup, The Rolodex, The Primer | yes |
| The Brief | /brief/ | The Dossier, The Route, The Comparison | yes |
| The Dispatch | /dispatch/ | The Fieldnotes, The Postcard, The Take | no |
| The Shortlist | /shortlist/ | — | no |

Gating is by a Paid Member Subscriptions block, no per-post switch. Gated pieces stay gated as lead magnets for the free Reader tier. A Property/Verdict is handed to the property-review-writer skill.

Matching a subject to a format: everything about a place → Rolodex (default for a destination guide); context before a trip → Primer; curated list on one theme → Roundup (five sections); visas, money, safety, packing → Dossier; multi-day itinerary → Route; single landmark with a story → Fieldnotes; single landmark, short or logistics → Postcard; restaurants in a city → Shortlist; one restaurant → A Table At; two destinations people choose between → Comparison. Not truly luxury → frame honestly and flag it. Some old posts need only a redirect.

Pillar discipline: Rolodex = comprehensive guide, eleven sections (where-to-stay, day trips, rainy-day fallback, insider FAQ are where the advisor value lives). Primer = before they land, not a second Rolodex. Roundup = one theme, five sections. Dossier = dry practical reference, written warm. Postcard = one moment, 400–600 words. Fieldnotes = a real story, 800–1,200 words. A destination can carry both a Roundup and a Rolodex when Coco asks, with different slugs, focus keywords and angles.

The merge rule: several old posts on one topic become one new piece, every old URL redirected to it. Each destination gets the full set (Primer, Rolodex, Postcard, Brief) where the photo count supports it; otherwise say which pillar is short and park it.

## Field maps
Field-name typos are real and must be reproduced exactly: iamge1, tittle_2, image4des, where_to_eat_&_drink. To confirm a template's render order, regex its raw content for `acf_meta\|([A-Za-z0-9_&#;\-]+)`. Only trust fields present in the post's own acf; confirm in a preview.

Template IDs: Primer 27517, Rolodex 27738, Roundup 27762, Dossier 27556, Route 27733, Comparison 30381, Fieldnotes 27776, Postcard 27810, The Take 27804, Verdict 27262, Tradeoff 29436, Shortlist 27818, Footer 26788.

Format taxonomy terms: the-guide-format: Primer 767, Rolodex 763, Roundup 765. brief_format: Dossier 758, Route 759, Comparison 802. dispatches-formats: Postcard 760, Fieldnotes 762, The Take 761. The ACF format string is set separately from the term and both are needed (ACF values still read "Postcard From" and "Field Notes From").

- **Primer**: opening_note → best_season → reader_questions_&_answers → closing_paragraph. Images image_1_left (+image1_left_descp), image_1_right, image_2 (+image2_des), image3 (+image_3_des), image9 (+image9_des), image4 (+image4des), image5 (+image5_des), image6 (+image6_des), image8 (+image8_des), image10 (+image10_des), image11 (+image11_des), image12 (+image12_des), image13 (+image13_des), image7 (+image7_des). image7 must always be filled (baked-in Amalfi fallback).
- **Rolodex**: opening_note → best_season → getting_there → where_to_stay → where_to_eat_&_drink → what_to_do → day_trips → rainy-day_fallback → where_to_shop → insider_faq → closing_paragraph; quote_1_ for the pull quote. Images image_1_left (+image1_left_descp), image_1_right, image_2 (+image2_des), image3 (+image_3_des), image4 (+image4des), image5 (+image5_des), image6 (+image6_des), image7 (+image7_des), image8 (+image8_des), image9 (+image9_des).
- **Roundup**: opening_note → where_to_eat_&_drink → what_to_do → where_to_shop → closing_paragraph (where_to_stay does not display). tittle_2_longer_words_underneath under ~70 characters.
- **Dossier**: title "[Place] —", tittle_word_2 "The dossier". overall → destination_overview → location_ → weather_&_best_time_to_go → getting_around → entry_requirements → currency_&_exchange → tipping_culture → local_customs_&_etiquette → health_&_safety → connectivity → language_&_key_phrases → emergency_contacts → what_to_pack → closing_note (last_thoughts does not display). Images iamge1 (+image_1_desc), image_2, image3 (+image3_desc), image_4 (+image4_des), image_6, image7 (+image7_des), image_8, image_9, image10, image11 (+image11_des).
- **Route**: title = The Route, tittle_word_2 = subtitle, trip_lenght_in_nights. overall, destination_overview, opening_scene, the_ask, the_route (an <h4> per segment), the_details_that_make_it_work, the_moment_that_almost_went_wrong (empty unless Coco gives a story), connectivity, last_thoughts. trip_type only "Honeymoon" or "couple", else blank.
- **Comparison**: side_a_name, side_b_name, best_for_a, best_for_b, comparison_short_answer, comparison_both_share, comparison_the_set_up, comparison_the_location, comparison_the_stays, comparison_the_food, comparison_the_days, comparison_the_price_difference, comparison_who_books_which, comparison_the_verdict, comparison_image_1–4. Bora Bora vs the Maldives is the reference.
- **Postcard**: dispatch_format = Postcard From, title_1 = Postcard, tittle_2 = From [Place], dispatch_one_liner = lede, the_takeaway = body. Images image_1_left (+image_1_left_des), image2, image3 (+image3_des), image_4, image6 (+image6_des). image5 and iamge7 not bound.
- **Fieldnotes** (rebuilt Oct 2026): opening_scene, the_notes, what_id_do_differently, the_takeaway, the_ask, plus a gallery field. Images image_1_left (+image_1_left_des), image2, image3 (+image3_des), image_5 (+image5_des), image6 (+image6_des).
- **Shortlist**: tittle_1 → tittle_2 → trip_type → image_left1 (+image1_left_desc) → overall → opening_note (plain text) → image2 (+image2des) → image3 (+image3_des) → the_picks → the_one_most_people_miss → closing_note → image4 (+image4_des) → image5.

## The content-ops layer (every new piece fills it)
- At a glance: short_answer (capped at 420 characters; over that the whole write fails with "Invalid parameter(s): acf") plus five label/value pairs. Default labels Setting, Best for, Standout, Consider, Stayed; the Comparison uses Choose A if / Choose B if / Price difference / Both share / Stayed.
- Search and AI: primary_keyword, secondary_keywords, ai_questions (literal questions people ask), content_category, content_goal. Newline-separated. A different primary_keyword per piece in a set.
- Trust: visited, visit_basis, disclosure. Never invent; blank and ask. "Trip I took" is the safe default where her photography proves she was there.
- Maintenance: last_fact_check, updated_on (YYYYMMDD), health, social_kit_made, hub.
- Cross-linking: related_property_reviews, related_guide_content, feature_in_shortlist, advisor_roundup.

Restricted selects (an invalid value fails the whole write): content_category = Commercial, Decision-support, Informational, Specialist intel, Authority. disclosure = Paid stay, Hosted stay, Discounted stay, Advisor roundup (not a stay), Trip I planned, Trip I took. health = ok, decaying, stale, thin, duplicate. trip_type = Honeymoon, couple.

Category by pillar: Primer Decision-support; Rolodex Commercial; Roundup Commercial; Route Decision-support; Comparison Commercial; Dossier Informational; Postcard Authority; Fieldnotes Authority.

## Taxonomy
Slug destination, REST base destinations, hierarchical, registered for the five pillar types only. Always tag the full chain: continent, country, city. Continent IDs: Africa 627, Asia 628, Europe 629, North America 630, South America 631, Oceania 632, Antarctica 633. Never guess a term ID; search /wp/v2/destinations?search=<place> and create missing levels.

/destinations/ is page 25271 with eight Kadence Query blocks (Africa 28343, Asia 29124, Europe 29127, North America 29128, South America 28384, Antarctica 28392, Oceania 29129, Country All 28311). Filters must point at destination, not category; the page renders from block markup in content, not from _kad_query_facets; an empty include list shows every term, so delete the filter block instead.

## The build procedure
Always build automatically; do not stop for approval on plan or draft. Research, write, build, then show her and hand her the Publish click. Ask first only when the format is unclear, a blocker means it can't be done right, or a field is a factual claim about her you cannot derive. Surface something every 10–15 minutes.

1. Survey the cluster (old posts, image IDs, how many are hers). 2. Contact sheet. 3. Decide merge and formats. 4. Fact-check against primary sources, VERIFIED/UNVERIFIED on every item; leave out the unconfirmed. 5. Create bare posts (title, slug, draft, featured image, destination chain, format term). 6. Alt text for every photo. 7. Fill ACF in chunks of two or three fields. 8. Content-ops layer. 9. Rank Math meta (title ~60, description ~155, distinct focus keyword). 10. Verify the render at /<cpt>/<slug>/?preview=true. 11. Report, leading with corrections that matter to her business. 12. After she publishes, the redirect sequence.

Working in her Chrome: wp.apiFetch from wp-admin; top-level await; wrap writes in try/catch; never return full URLs or raw HTML; Rank Math meta via POST /rankmath/v1/updateMeta {objectID, objectType:'post', content:'', meta:{…}} with X-WP-Nonce.

## Redirects, trashing and cache (only after she confirms publish, in this order)
1. Re-read the published slug. 2. Create the 301 in Rank Math → Redirections → Add New (source = old path, no leading slash, trailing slash, Exact; "Add another" for every old URL of a merge; destination the full new URL; 301; Activate). 3. Trash the old posts, verifying each slug. 4. Delete all cache. 5. Remind Coco to purge Cloudflare. 6. Test every redirect.
React inputs only register when clicked first. wp.apiFetch is unavailable on the Redirections list page. **Never use /rankmath/v1/updateRedirection**: it only writes post meta and the old URLs bounce to the homepage once the post is trashed.

## Known traps
- A format renders as a 30-word teaser plus a gate → PMS Content Restriction set on the Kadence Element post; Coco unticks the plans.
- Gated pieces look broken to members → SpeedyCache logged_in_user must stay off.
- Captions never appear → templates referenced fields that don't exist (fixed Oct 2026; edit with split/join, never regex).
- Extra fallback photos in a Primer → blocks bound to absent fields; fill image7 always.
- An Amalfi photo on every page → media 24891 in the Footer element and 25296, intentional.
- Destination tabs show only old posts → filters pointing at category.
- "Invalid parameter(s): acf" → a restricted select or short_answer over 420; bisect one field per call.
- A redirect saves empty → the React input wasn't clicked. Old URLs bounce home → made via updateRedirection.
- The extension fails on everything → quit and reopen Chrome.

## Facts worth not re-researching
- Amalfi: Belmond Caruso reopened for the 2026 season (an earlier version wrongly said closed until April 2027). Verify every closure claim.
- Santorini: four MICHELIN stars October 2026 (Lycabettus two, Botrini's, Lauda, Selene one each). Fifteen hotels hold Keys. Akrotiri €20. Cable car €10. Tourist Police 1571. Climate resilience fee €15/night at five-star from 2025.
- Vietnam: 63 provinces to 34 on 1 July 2025. US and Australian passports not visa-exempt. Michelin Keys nationwide; stars in Hanoi, HCMC and Da Nang only.
- Lake Como: Vista Palazzo is now Palazzo Venezia.
- Cinque Terre: no luxury hotel in any of the five villages. The "1.5m visitor cap" never happened.
- Menton: Cocteau museum closure was Storm Adrian, October 2018. Nice's bus 100 became bus 600 in April 2024.
- EU: EES fully operational since 10 April 2026. ETIAS has no launch date.

## Progress log
Vietnam complete (20 → 11). Mexico complete as scoped (7). Mallorca complete (8 from ~27). Japan: five four-pillar sets, 20 pieces from 32. Santorini complete. Italy/France: Lake Como, Cinque Terre, Italy Primer, Tuscany, Rome, Dolomites, Amalfi, Villefranche & Cap Ferrat, Menton, Caruso Verdict. Content-ops backfill done for the 24 Santorini and Japan pieces.

Queued: California (20 old posts, 102 own Sequoia images: Sequoia set from 12 posts, Lake Tahoe set from 5, Point Reyes + Bodega Bay, Kimpton Sawyer Verdict; needs trip dates and the Paradise Ranch disclosure). Spain: Basque Country (San Sebastián 32 photos / 5 posts, Vitoria-Gasteiz 25 / 2, Bilbao 20 / 3).

Parked: Sapa, Frida's Casa Azul, Oaxaca Día de Muertos (stock only). Barcelona, Begur (no photos of hers). Nice, Èze, Antibes, Monaco, Saint-Paul (10 posts; merge into one Riviera Rolodex or redirect). Osaka, Hakone, Fuji, Nara, Kobe, Kamikochi (photo-poor). Venice (Coco said leave it). Portofino, Milan, Locorotondo.

Open items: the ryokan name for Postcard From a Ryokan; whether the Charisma Hotel attribution on the Santorini set is right; whether Japan visited dates should be city-specific.
