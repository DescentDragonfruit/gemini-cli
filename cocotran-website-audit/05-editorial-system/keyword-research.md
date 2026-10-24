# Keyword research, reader first — the method the desk's Keywords desk follows

Google's own guidance (Search Central, 2025): there is no special optimisation for AI Overviews or AI Mode. The same fundamentals decide both the blue link and the citation: helpful, reliable, people-first content with unique value; a page that is crawlable, fast and renders its main content; structured data that matches what is visible; clear dates and authorship; and, for AI features, coverage of the sub-questions a complex query fans out into. Perplexity and ChatGPT search behave the same way in practice: they cite pages that answer directly in the first screen, carry a date and a named author, and use lists or tables where the answer is a comparison.

So the method starts with the reader's decision, not a keyword list.

## 1. Start from the decision
Write the decision the reader is making in one line ("Bora Bora or the Maldives for a honeymoon from the US"). Everything below serves that line. A query without a decision behind it (pure curiosity, "facts about Bora Bora") is not a piece for cocotran.com.

## 2. The query set
- **Primary**: the decision in the words people type, in the title-lock shape (`[A] vs [B]: Which Should You Choose?`, `Is [X] Worth It?`, `Where to Stay in [X]`).
- **Secondary (3–6)**: the same decision from other angles (price, season, who it suits, which room).
- **Questions (6–10)**: People-also-ask style, the way they appear in results.
- **AI-search phrasings (4–6)**: full sentences with context, the way someone asks Perplexity, ChatGPT or Google AI Mode ("We're from LA, honeymoon in March, want overwater and great snorkelling, Bora Bora or the Maldives?"). These reveal the fan-out sub-questions the page must cover: flights, season, price, snorkelling, seclusion.
- **Intent** on every query: book / decide / learn / trust. The piece's primary ask follows the dominant intent.

## 3. What the results page looks like
For the primary query: the shapes that rank (comparison, review, listicle, forum), whether an AI Overview appears, and which competitors hold the page. The worker verifies this live with web search; the desk's first pass predicts it. A query where forums rank is a query that wants first-hand detail; a query where listicles rank wants a verdict.

## 4. Cannibalisation check
One topic, one piece. If a live record already owns the primary query, the new piece either takes a different decision or becomes an update to that record.

## 5. Google's people-first self-assessment, applied to this piece
Six questions, answered honestly before writing:
1. Does it offer original information, reporting, research or first-hand experience?
2. Would someone bookmark, share or recommend it?
3. Is there a clear author with demonstrated expertise, and a date?
4. Does it provide substantial value compared with other pages in results?
5. Does it answer the query and the sub-questions in the first screen, then earn the scroll?
6. Would the reader feel they got what they came for, with no need to search again?
A "no" on any of them is the brief for the piece, not a reason to skip it.

## 6. The output the desk hands to the piece
Title (lock shape), H1, meta, slug, focus keyword, the sub-questions as section heads, the glance rows, the AI-search phrasings as the FAQ, two outside authority sources, and the three siblings to link. Nothing here is keyword stuffing: the words appear because the reader uses them.

## 7. After publishing
Search Console says what really ranked. The weekly run compares predicted queries with real ones, flags the page-one-adjacent queries, and feeds the next update. Nothing in this method changes when Google changes; the reader doesn't.

## Acting on the numbers (Keywords desk → "Act on the numbers")

Once the Monday run has written `rank` to the library, the desk triages every measured page into one move, sorted by what the fix is worth in clicks per month:

| Move | Trigger | What to write |
|---|---|---|
| Push to page one | position 4–20, ≥300 impressions | Title states the answer; first screen answers the top query; one section per sub-query |
| Build it out to rank | position >20, ≥1,000 impressions | The page is thin for the demand: full format, glance rows, first-hand detail, sources |
| Fix the title and meta | CTR under 60% of what the position should earn | New title with the number or verdict; meta in first person; no body rewrite |
| Stop the decay | clicks down ≥25% on ≥20 previous clicks | Refresh facts and date, restore the answer-first opening, add the losing queries as sections |
| Expand | ≥2 ranking queries with no section on the page | Add those sections, headings phrased like the queries |
| Convert the traffic | ≥60 clicks, ≥20 sessions, zero bookings/inquiries | Booking box placement + CTA line + GA4 event; no rewrite |
| Retire or merge | <30 impressions, >1 year old, <900 words | Noindex + redirect proposal to the pillar; Coco decides |
| Write new | Search Console content gap | Research in the desk, then New piece in the suggested format |

**Brief** generates the what-and-how from the data (diagnosis, intent, title lock, publishable first screen, sections in order with the query each answers, glance rows, cuts, links, conversion line, schema, expected position, effort). **Rewrite now** queues an `update` job with the data block and brief in `notes` and starts the worker; **Queue** parks it at priority 2 for push/CTR moves and 1 otherwise. The worker follows studio-pipeline "Data-driven update jobs".
