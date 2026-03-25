#!/usr/bin/env node
/**
 * keyword-research.js
 *
 * Free keyword research via Google, Bing, and DuckDuckGo autocomplete APIs.
 * No API keys required. Uses only Node.js built-in modules.
 *
 * Usage:
 *   node keyword-research.js "seed keyword" [options]
 *
 * Options:
 *   --lang <code>     Language code (default: en)
 *   --country <code>  Country code (default: us)
 *   --no-alphabet     Skip alphabet expansion (faster)
 *   --no-questions    Skip question prefix expansion
 *   --limit <n>       Max suggestions to return (default: 200)
 *
 * Output: JSON to stdout
 */

import https from 'https';

// ─── CLI args ────────────────────────────────────────────────────────────────

const args = process.argv.slice(2);
if (args.length === 0 || args[0] === '--help') {
  process.stderr.write('Usage: node keyword-research.js "seed keyword" [--lang en] [--country us] [--no-alphabet] [--no-questions] [--limit 200]\n');
  process.exit(args[0] === '--help' ? 0 : 1);
}

const seedKeyword = args[0];
const lang = getFlag('--lang', 'en');
const country = getFlag('--country', 'us');
const skipAlphabet = args.includes('--no-alphabet');
const skipQuestions = args.includes('--no-questions');
const limit = parseInt(getFlag('--limit', '200'), 10);

function getFlag(name, defaultVal) {
  const i = args.indexOf(name);
  return i !== -1 && args[i + 1] ? args[i + 1] : defaultVal;
}

// ─── Query variants ──────────────────────────────────────────────────────────

const QUESTION_PREFIXES = [
  'what is', 'what are', 'what does',
  'how to', 'how do', 'how does', 'how much', 'how many',
  'why is', 'why does', 'why do',
  'when is', 'when does', 'when to',
  'where to', 'where is', 'where can',
  'who is', 'who are',
  'which is', 'which are',
  'best', 'top', 'cheap', 'free',
  '', // bare seed
];

const SUFFIX_MODIFIERS = [
  'vs', 'review', 'reviews', 'for beginners', 'tutorial',
  'tips', 'guide', 'examples', 'ideas', 'tools',
  'near me', 'online', 'price', 'cost', 'benefits',
];

const ALPHABET = 'abcdefghijklmnopqrstuvwxyz'.split('');

function buildQueries(seed) {
  const queries = new Set();

  if (!skipQuestions) {
    for (const prefix of QUESTION_PREFIXES) {
      queries.add(prefix ? `${prefix} ${seed}` : seed);
    }
    for (const suffix of SUFFIX_MODIFIERS) {
      queries.add(`${seed} ${suffix}`);
    }
  } else {
    queries.add(seed);
  }

  if (!skipAlphabet) {
    for (const letter of ALPHABET) {
      queries.add(`${seed} ${letter}`);
    }
  }

  return [...queries];
}

// ─── HTTP fetch (with timeout) ───────────────────────────────────────────────

function fetchJson(url, timeoutMs = 5000) {
  return new Promise((resolve) => {
    const req = https.get(url, {
      headers: {
        'User-Agent': 'Mozilla/5.0 (compatible; KeywordResearchBot/1.0)',
        'Accept': 'application/json, text/javascript, */*',
      },
    }, (res) => {
      let data = '';
      res.on('data', (chunk) => { data += chunk; });
      res.on('end', () => resolve({ ok: true, data, status: res.statusCode }));
    });
    req.on('error', (err) => resolve({ ok: false, error: err.message }));
    req.setTimeout(timeoutMs, () => {
      req.destroy();
      resolve({ ok: false, error: 'timeout' });
    });
  });
}

// ─── Autocomplete API wrappers ───────────────────────────────────────────────

async function fetchGoogle(query) {
  const url = `https://suggestqueries.google.com/complete/search?client=firefox&hl=${lang}&gl=${country}&q=${encodeURIComponent(query)}`;
  const res = await fetchJson(url);
  if (!res.ok) return [];
  try {
    const parsed = JSON.parse(res.data);
    // Firefox client returns: [query, [suggestions], ...]
    return Array.isArray(parsed[1]) ? parsed[1].map(s => String(s).toLowerCase()) : [];
  } catch {
    return [];
  }
}

async function fetchBing(query) {
  const url = `https://api.bing.com/osjson.aspx?query=${encodeURIComponent(query)}&language=${lang}`;
  const res = await fetchJson(url);
  if (!res.ok) return [];
  try {
    const parsed = JSON.parse(res.data);
    // Returns: [query, [suggestions]]
    return Array.isArray(parsed[1]) ? parsed[1].map(s => String(s).toLowerCase()) : [];
  } catch {
    return [];
  }
}

async function fetchDDG(query) {
  const url = `https://duckduckgo.com/ac/?q=${encodeURIComponent(query)}&type=list&kl=${lang}-${country}`;
  const res = await fetchJson(url);
  if (!res.ok) return [];
  try {
    const parsed = JSON.parse(res.data);
    // Returns: [query, [suggestions]] OR [{phrase: "..."}]
    if (Array.isArray(parsed[1])) return parsed[1].map(s => String(s).toLowerCase());
    if (Array.isArray(parsed) && parsed[0] && typeof parsed[0] === 'object') {
      return parsed.map(item => String(item.phrase || '').toLowerCase()).filter(Boolean);
    }
    return [];
  } catch {
    return [];
  }
}

// ─── Concurrency limiter ─────────────────────────────────────────────────────

async function runWithConcurrency(tasks, maxConcurrent) {
  const results = new Array(tasks.length);
  let i = 0;

  async function worker() {
    while (i < tasks.length) {
      const taskIndex = i++;
      results[taskIndex] = await tasks[taskIndex]();
    }
  }

  const workers = Array.from({ length: Math.min(maxConcurrent, tasks.length) }, worker);
  await Promise.all(workers);
  return results;
}

// ─── Main ────────────────────────────────────────────────────────────────────

async function main() {
  const queries = buildQueries(seedKeyword);

  // Build all fetch tasks
  const tasks = [];

  for (const query of queries) {
    tasks.push(() => fetchGoogle(query).then(r => ({ source: 'google', query, results: r })));
    tasks.push(() => fetchBing(query).then(r => ({ source: 'bing', query, results: r })));
    tasks.push(() => fetchDDG(query).then(r => ({ source: 'ddg', query, results: r })));
  }

  // Run with max 6 concurrent requests to avoid rate limiting
  const allResults = await runWithConcurrency(tasks, 6);

  // Aggregate: keyword -> { frequency, sources: Set }
  const map = new Map();

  for (const { source, results } of allResults) {
    for (const kw of results) {
      const cleaned = kw.trim().toLowerCase();
      if (!cleaned || cleaned === seedKeyword.toLowerCase()) continue;
      if (!map.has(cleaned)) {
        map.set(cleaned, { keyword: cleaned, frequency: 0, sources: new Set() });
      }
      const entry = map.get(cleaned);
      entry.frequency++;
      entry.sources.add(source);
    }
  }

  // Sort by frequency desc, then alphabetically
  const sorted = [...map.values()]
    .sort((a, b) => b.frequency - a.frequency || a.keyword.localeCompare(b.keyword))
    .slice(0, limit)
    .map(({ keyword, frequency, sources }) => ({
      keyword,
      frequency,
      sources: [...sources].sort(),
    }));

  // Build output
  const output = {
    seedKeyword: seedKeyword.toLowerCase(),
    generatedAt: new Date().toISOString(),
    lang,
    country,
    totalQueriesSent: queries.length * 3,
    totalSuggestions: sorted.length,
    multiSourceCount: sorted.filter(s => s.sources.length >= 2).length,
    suggestions: sorted,
  };

  process.stdout.write(JSON.stringify(output, null, 2));
  process.stdout.write('\n');
}

main().catch(err => {
  process.stderr.write(`Error: ${err.message}\n`);
  process.exit(1);
});
