#!/usr/bin/env bash
# Re-run the quick technical checks from the audit. Usage: ./recheck.sh [outdir]
# Needs: curl, python3, node + playwright (for perf.js).
set -u
OUT=${1:-./recheck-$(date +%Y%m%d)}; mkdir -p "$OUT"; cd "$OUT"
H=https://cocotran.com
echo "== robots / sitemap / feed / llms.txt / 404"
curl -sS $H/robots.txt | head -5
curl -sS -o sitemap_index.xml -w "sitemap_index %{http_code}\n" $H/sitemap_index.xml
grep -o '<loc>[^<]*</loc>' sitemap_index.xml | sed 's/<[^>]*>//g'
for t in property guide brief dispatch shortlist; do curl -sS -o /dev/null -w "$t-sitemap.xml %{http_code} -> %{redirect_url}\n" $H/$t-sitemap.xml; done
curl -sS -o /dev/null -w "feed %{http_code}\n" $H/feed/
curl -sS -o /dev/null -w "llms.txt %{http_code} -> %{redirect_url}\n" $H/llms.txt
curl -sS -o /dev/null -w "404-test %{http_code} -> %{redirect_url}\n" $H/this-page-does-not-exist-xyz/
echo "== on-page: key pages"
for u in "" subscribe/ about/ editorial/ destinations/ guide/kyoto-guide/ property/caruso-ravello/; do
  curl -sS -A "Mozilla/5.0" "$H/$u" -o page.html; echo "--- /$u"; python3 -I "$(dirname "$0")/onpage.py" page.html | grep -E '^(title|desc|h1s|schema|words|imgs):'
done
echo "== paywall visibility to Googlebot"
curl -sS -A "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)" $H/guide/kyoto-guide/ -o gb.html
echo "members-wall present: $(grep -c 'FOR MEMBERS ONLY' gb.html); isAccessibleForFree present: $(grep -c isAccessibleForFree gb.html)"
echo "== mobile perf (homepage)"; node "$(dirname "$0")/perf.js" $H/ 2>/dev/null || echo "perf.js skipped (needs playwright)"
