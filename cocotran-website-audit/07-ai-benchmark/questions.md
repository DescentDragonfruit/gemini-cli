# AI search visibility benchmark — question set (keep verbatim)

1. Which boutique hotels in Mallorca are worth the splurge?
2. Four Seasons Anguilla or Cap Juluca — which should I book?
3. Where should I stay in Kyoto for a first visit?
4. Which luxury travel publications publish genuinely firsthand hotel reviews?
5. What should I know before booking a Danube river cruise?
6. Is Caruso, A Belmond Hotel in Ravello worth the price?
7. Is Amanjena in Marrakech worth it?
8. La Residencia Deià review — is it worth staying?
9. Where to stay in Santorini: Oia or Imerovigli?
10. Best two-week Japan itinerary for a first-time luxury traveller
11. Where to stay in Mexico City for a first visit — Roma, Condesa or Polanco?
12. Best restaurants in Hanoi according to a travel advisor
13. Is the JR Pass still worth it in 2026?
14. Where to stay on the Amalfi Coast without a car
15. Best time to visit Santorini to avoid crowds
16. Takayama or Kanazawa — which is worth more time?
17. Where to stay in Lake Como for a first visit
18. Four days in Tokyo itinerary by neighbourhood
19. Is luggage forwarding in Japan worth it and how does it work?
20. Who is Coco Tran travel advisor?

## Keyword forms used in this run (step b)
1 boutique hotels Mallorca worth it · 2 Four Seasons Anguilla vs Cap Juluca · 3 where to stay Kyoto first time · 4 luxury travel publications firsthand hotel reviews · 5 Danube river cruise tips before booking · 6 Caruso Belmond Ravello review worth it · 7 Amanjena Marrakech review · 8 La Residencia Deia Belmond review · 9 Oia vs Imerovigli where to stay · 10 luxury Japan itinerary 2 weeks first time · 11 Roma Condesa or Polanco where to stay Mexico City · 12 best restaurants Hanoi travel advisor picks · 13 JR Pass worth it 2026 · 14 Amalfi Coast without a car where to stay · 15 Santorini best time to visit avoid crowds · 16 Takayama vs Kanazawa · 17 Lake Como where to stay first time · 18 4 day Tokyo itinerary neighborhoods · 19 Japan luggage forwarding takkyubin how it works · 20 Coco Tran luxury travel advisor cocotran.com

## Method (repeat identically)
1. Tooling: Claude Code WebSearch (mode "standard", US results) + WebFetch. No ChatGPT, Perplexity or Google AI Overview access; web search + Claude's answer synthesis is the proxy for an AI answer engine.
2. For each question, run WebSearch with the question verbatim.
3. Record whether any cocotran.com URL appears (URL + position) and the top 5 domains that do.
4. Run a second WebSearch with the keyword form listed above; record the same.
5. Write a 3–5 sentence answer using only what the two searches returned; note the sources you would cite.
6. Record would-cite-cocotran (Y/N) and, if cocotran appeared, whether it was the most relevant article.
7. If a cocotran.com page is cited, WebFetch it and check the written answer does not misrepresent it.
8. Afterwards, run two site-restricted searches (allowed_domains = cocotran.com) to confirm indexation, and WebFetch the homepage, robots.txt and the two most relevant articles to check bot accessibility.
9. Output: summary table, per-question notes, competitor tally, observations, limitations; under ~1,800 words.
10. Save as 07-ai-benchmark/YYYY-MM-DD-<label>.md; compare against the previous run's table.
