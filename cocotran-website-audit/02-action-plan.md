# Action plan — cocotran.com

Work top to bottom. Each item names the fix file, who does it (**Coco** = needs you in WP admin or editorial judgement; **Claude** = can be done from a session once the site is connected or from the files here; **Host** = hosting/dev), rough effort, and status.

Status key: `[ ]` not started · `[~]` drafted here, needs applying · `[x]` done on the live site.

---

## Phase 0 — Prerequisites (today)

- [ ] **0.1 Connect the site so changes can be applied from a session.** Either (a) Jetpack → connect cocotran.com to the WordPress.com account for hello@cocotran.com, or (b) WP admin → Users → Profile → Application Passwords → create one named "claude-audit" (REST API write access). — *Coco, 10 min*
- [ ] **0.2 Grant Search Console access** (or export: Performance → last 16 months → Pages + Queries CSV) so title rewrites and paywall decisions are checked against real queries. — *Coco, 5 min*
- [ ] **0.3 Back up** (LiteSpeed/host snapshot) before phase 1. — *Coco, 5 min*

## Phase 1 — Stop the bleeding (this week, ~3–4 hours total)

### Visible errors
- [~] **1.1 Fix the `/subscribe/` FAQ.** Paste `03-fixes/subscribe-faq-corrected.md`. Two answers need your confirmation (marked ⚠ in the file): monthly→annual switching policy, and whether Insider is really "complimentary for active clients" with a code. — *Coco, 20 min*
- [~] **1.2 Rewrite the Kimpton Sawyer intro and title.** `03-fixes/kimpton-sawyer-intro-rewrite.md`. — *Coco, 15 min*
- [ ] **1.3 Remove placeholder text.** `/editorial/`: replace H1 "lorei ipsum" and the Nullam description. `/pricing-4/`: delete or noindex (it duplicates `/subscribe/`). Homepage: set a real meta description (text in `meta-title-rewrites.csv`). `/destinations/`, `/about/`, `/shop/`: set descriptions. — *Coco, 30 min*
- [ ] **1.4 Fix the Brief template label "Trip Lenght" → "Trip length".** Kadence Element / ACF label. — *Coco, 5 min*
- [ ] **1.5 Fix the Cap Juluca property slug + title** (`belmond-cap-jaluca-review` → `belmond-cap-juluca-review`, Rank Math adds the redirect automatically if Redirections is on; title → "The Verdict: Cap Juluca, A Belmond Hotel, Anguilla"). Fix "Da Nang —" title. — *Coco, 10 min*

### Indexing
- [ ] **1.6 Sitemap: include the five CPTs, exclude utility pages.** `03-fixes/rank-math-settings.md` §1. Then resubmit `sitemap_index.xml` in Search Console. — *Coco, 15 min*
- [ ] **1.7 Noindex `/login/`, `/register/`, `/password-reset/`, `/account/`, `/cart/`, `/checkout/`, `/pricing-4/`, `/social-links/`.** `rank-math-settings.md` §2. — *Coco, 10 min*
- [ ] **1.8 Turn the RSS feed back on.** `rank-math-settings.md` §5 (it's almost certainly a "disable feeds" toggle in a security/perf plugin or a snippet). — *Coco/Host, 10 min*
- [ ] **1.9 Real 404 page instead of redirect-to-home.** `rank-math-settings.md` §4 + `03-fixes/404-page.md`. — *Coco, 20 min*
- [ ] **1.10 Add `Sitemap: https://cocotran.com/sitemap_index.xml` to robots.txt.** Rank Math → General → Edit robots.txt. — *Coco, 2 min*

### Schema
- [ ] **1.11 Set default schema per content type:** Property → Review (itemReviewed Hotel), Guide/Brief/Shortlist/Dispatch → Article, with author = Coco Tran. `rank-math-settings.md` §3. — *Coco, 15 min*
- [~] **1.12 Replace the site identity schema** with `03-fixes/schema-person-organization.json` (Rank Math → Titles & Meta → Local SEO / Knowledge Graph, plus the author profile fields). — *Coco, 20 min*

## Phase 2 — Paywall strategy & AI-search readiness (weeks 2–3)

- [x] **2.1 Gate decision made (24 Oct):** Guides + Properties fully free; Briefs gated after a long free lead with paywall markup; paid tier repositioned as The Planning Room; "book through me" is the main CTA. See `05-editorial-system/paywall-decision.md` + `conversion-strategy.md`.
- [ ] **2.2 Implement:** untick the PMS plans on the Guide and Property Kadence Element templates (Coco — the auto-mode guardrail blocks Claude here); on the Route/Dossier templates move the `pms/content-restriction-start` block below The ask / The shape of the trip / TOC. — *Coco, 30 min*

### 2c — Conversion (added 24 Oct; from `conversion-strategy.md`)
- [ ] **2c.1 Booking box** ("Book [hotel] through Coco Tran Travel — same rate, plus perks") as a Kadence Element on every Property and under hotel mentions in Rolodexes/Shortlists; form posts to CTT with the hotel + piece as source. — *Coco/Claude, 1 h*
- [ ] **2c.2 Rename The Archive → The Planning Room** on `/subscribe/`, the paywall box and the FAQ; add the trip-file deliverables and the members' Q&A to the tier copy. — *Coco, 30 min*
- [ ] **2c.3 Publish one complete Route + trip file free** as the sample (Two Weeks in Japan is the obvious one). — *Coco + Claude*
- [ ] **2c.4 Build the first trip file** for that Route (Google Maps list + day-by-day with booking links + what-to-book-first sheet). — *Claude drafts, Coco checks*
- [ ] **2c.5 Welcome sequence** (5 emails, drafted in her voice from the table in `conversion-strategy.md`) automated in the newsletter tool. — *Claude drafts, Coco approves, 1 h setup*
- [ ] **2c.6 Founding-member email** to the 200 with the 14-day price and the sample Route. — *within 30 days*
- [ ] **2c.7 Track booking requests from the site** as their own inquiry source in the form + GA4. — *30 min*
- [~] **2.3 Add paywall structured data** on every gated piece. `03-fixes/paywall-structured-data.md` (Rank Math custom schema or a 20-line snippet). — *Claude/Host, 1 h*
- [~] **2.4 Apply the title/description rewrites.** `03-fixes/meta-title-rewrites.csv` — import via Rank Math → Import/Export (CSV) or paste per page. Check Search Console first for any page where the current title is already ranking for a query the new one drops. — *Coco/Claude, 2 h*
- [~] **2.5 Drop `llms.txt` at the site root** (upload via file manager or a mu-plugin rewrite). — *Coco/Host, 5 min*
- [~] **2.6 Add the "Why trust this" block to `/about/`** and fix its title/description. Fill in only what's true. `03-fixes/about-page-trust-block.md`. — *Coco, 30 min*
- [ ] **2.7 Author page:** add bio, portrait (not Gravatar), jobTitle, sameAs links; byline "Coco Tran" not "Coco". — *Coco, 20 min*
- [ ] **2.8 Link the whole entity:** footer links to cocotrantravel.com, cocotranstudio.com, YouTube, both Instagrams. — *Coco, 10 min*
- [ ] **2.9 Publish 2–3 full sample paid pieces** (one Verdict, one Route) labelled "Sample — this is what members read", linked from `/subscribe/`. — *Coco, editorial*
- [ ] **2.10 Membership page: one clear comparison table** (Reader / Archive / Insider × each pillar), consistent with the FAQ. The corrected FAQ file includes the table. — *Coco, 30 min*

## Phase 3 — Template & structure fixes (weeks 3–4)

- [ ] **3.1 One H1 per page.** Property/Dispatch: kicker ("The Verdict —") becomes `<p class="kicker">`. Brief/Shortlist: title becomes `<h1>`. About/Subscribe/Destinations/Contact: add H1. (Kadence Elements / block templates.) — *Coco/Host, 1 h*
- [~] **3.2 Adopt the article templates** (`03-fixes/article-templates.md`): Verdict box at the top of every Property; "Fact block" (where / when stayed / room / rate context / who it suits); "Stayed / Updated" dates; FAQ block on Primers; "Also in [destination]" links block on everything. Start with the 6 Properties and the 10 Primers. — *Coco (editorial) + template change, ongoing*
- [~] **3.3 Build destination hubs** per `03-fixes/destination-hub-spec.md`. First four: Japan (Tokyo/Kyoto/Kanazawa/Takayama), Mallorca, Mexico City, Vietnam — these already have Primer + Rolodex + Route + Shortlist. Each hub: overview, when to go, the pieces grouped by format, hotels, food, routes. — *Coco/Claude, 2 h per hub*
- [ ] **3.4 Resolve competing pages.** For each pair in `01-audit-findings.md` §F: give each a distinct primary query + title, cross-link, or merge (301) the weaker into the stronger. Priority: Mexico City hotels (old vs new), the two Tokyo→Fuji day-trip posts, Lake Como ×3, Hanoi ×5. — *Coco, 2 h*
- [ ] **3.5 Normalise format labels** (one kicker per type: The Verdict / The Primer / The Rolodex / The Route / The Dossier / The Shortlist / Postcard From / Field Notes From) in H1 kickers, title-tag patterns and cards. — *Coco, 1 h*
- [ ] **3.6 Hero image alt text** on new pieces (template currently emits empty alt). Alt = "[subject], [place] — photographed by Coco Tran" or descriptive. — *Coco/Host, 30 min*

## Phase 4 — Performance (weeks 4–5)

- [ ] **4.1 Homepage slider: serve sized WebP** (max 1600 px long edge, ~150–250 KB each) instead of 2 MB originals. Either re-upload sized versions or make the slider use `srcset`. Target: homepage < 4 MB, LCP < 2.5 s. — *Coco/Host, 1 h*
- [ ] **4.2 Image optimiser covers 2025–26 uploads.** Check LiteSpeed Image Optimization (or whatever generated the old WebPs) is running on new uploads and that the Kadence gallery/advanced-image blocks output WebP. Bulk-regenerate. — *Host, 1 h*
- [ ] **4.3 CSS: enable Kadence "Load CSS inline only above the fold"/LiteSpeed CSS combine + critical CSS;** target < 100 KB inline per page. — *Host, 1 h*
- [ ] **4.4 CLS on Property pages:** reserve height for the hero (`aspect-ratio` on the block), stop the membership bar from pushing content (position: fixed, not in-flow). Target CLS < 0.1. — *Host, 1 h*
- [ ] **4.5 Mobile first-screen:** cookie banner → bottom sheet, not centred modal; sticky subscribe bar collapsed by default on first visit; fix white-on-white logo on Property header; show the hero image within the first viewport on Property pages. — *Coco/Host, 1 h*
- [ ] **4.6 Ask host about 500/429 under concurrent requests** (Cloudflare rate-limit rule or LiteSpeed crawler throttling). — *Host*

## Phase 5 — Old-post programme (ongoing, ~2/week)

- [ ] **5.1 For each of the 105 live old posts, pick one:** (a) respin into the new format with the respin-old-content skill, (b) rewrite the intro + title only (keep the body), or (c) 301 to its new-format sibling. Priority order: posts with Search Console impressions, then hotel reviews, then destination guides. `data/meta_inventory.csv` has the word counts; `meta-title-rewrites.csv` has the titles.
- [ ] **5.2 Add "Also in [destination]" + hub links** to every old post kept.

## Phase 1b — Added 24 Oct: the second-pass items (this week)

- [ ] **1b.1 Unblock AI fetchers in Cloudflare.** Security → Bots (Bot Fight Mode off for verified bots) and WAF rules (skip challenge for Verified Bots + GPTBot/ClaudeBot/PerplexityBot/OAI-SearchBot). Re-test per `01-audit-findings.md` §I2. — *Coco, 15 min* ← **do first; nothing else in AI search works while this is on**
- [~] **1b.2 Publish `/how-we-review/`.** `05-editorial-system/methodology-page.md`. Link from footer, About, every Property. — *Coco, 20 min*
- [~] **1b.3 About page v2.** `05-editorial-system/about-page-v2.md` (fill the credentials). — *Coco, 30 min*
- [~] **1b.4 Adopt the publishing checklist + structured-data checklist** for every piece: `05-editorial-system/publishing-checklist.md`; run drafts through the Editorial Desk artifact (https://claude.ai/artifact/NVykejaCwzyJDdN2G1NMQt). — *Coco + Claude, ongoing*
- [~] **1b.5 Add the skill** `08-skills/coco-editorial-standards/SKILL.md` to the Coco Tran Collective project / synced skills so every session drafts to it. — *Coco, 5 min*

## Phase 2b — Answerable opening + photo CMS (weeks 2–4)

- [~] **2b.1 ACF "Answerable opening" field group + one Kadence Element** per `05-editorial-system/eeat-rollout.md` §1–2. — *Coco/Claude, 1–2 h*
- [ ] **2b.2 Bulk backfill** Properties → Primers → Rolodexes → Routes (Claude drafts from existing fields; Coco approves in the Desk; REST writes). — *2–3 sessions*
- [ ] **2b.3 Coco fills visited / basis / disclosure** for the 75 pieces (one sheet). — *Coco, afternoon*
- [ ] **2b.4 Install Coco Photo CMS** (`06-wp-plugin/coco-photo-cms/`): settings, bulk WebP regenerate, alt sweep, redirect rule in LiteSpeed/.htaccess. Create an Application Password for the artifact intake. — *Coco/Host, 1 h*
- [ ] **2b.5 Citation routine** running weekly per `05-editorial-system/citation-opportunities.md`; log in the Desk tracker. — *Coco, 60 min/week*
- [ ] **2b.6 First four "The Answer" pieces** from `ai-faq-question-bank.md` (#1, 3, 13, 19). — *Coco + Claude*

## Phase 6 — Verify (after each phase)

- [ ] Run `04-scripts/recheck.sh` — sitemap includes CPTs, feed 200, 404 returns 404, llms.txt 200, no "lorei", schema types show Article/Review, isAccessibleForFree present on gated pages.
- [ ] Search Console: resubmit sitemap; URL-inspect one piece per type; check Coverage for "Excluded by noindex" matching the utility pages only.
- [ ] Rich Results Test on one Property (Review) and one Primer (Article + FAQ).
- [ ] PageSpeed Insights mobile on homepage + one Property (when quota resets): LCP < 2.5 s, CLS < 0.1.
- [ ] Four weeks later: compare clicks/impressions on the 118 redirected URLs' targets vs the month before.
- [x] **AI benchmark** runs automatically on the 1st and 15th (scheduled task "Coco Tran AI-visibility benchmark", 8:47am Pacific): report to `07-ai-benchmark/`, row in the Desk's benchmark log, push notification with the summary. Baseline 24 Oct: 0/20. Ask for an ad-hoc run any time.
- [ ] 90-day sequencing and the four outcome metrics: `05-editorial-system/90-day-plan.md`.

---

## Decisions needed from Coco

1. Paywall per format (2.1). My recommendation: Primers free, everything else a long free lead + markup.
2. The two ⚠ FAQ answers (1.1).
3. Credentials to state on About (2.6) — only what's true.
4. Whether to keep both `/where-to-stay-in-mexico-city/` and `/guide/mexico-city-hotels/` or merge (3.4).
5. Site connection route (0.1) so the next session can apply instead of hand-off.
