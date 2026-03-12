'use client';

import { useEffect, useState } from 'react';

interface FAQ {
  id: string;
  question: string;
  answer: string;
  category: string | null;
  order: number;
}

export default function FAQPage() {
  const [faqs, setFaqs] = useState<FAQ[]>([]);
  const [expandedId, setExpandedId] = useState<string | null>(null);
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetch('/api/faq')
      .then((r) => r.json())
      .then(setFaqs)
      .catch(console.error)
      .finally(() => setLoading(false));
  }, []);

  const filtered = faqs.filter(
    (faq) =>
      faq.question.toLowerCase().includes(search.toLowerCase()) ||
      faq.answer.toLowerCase().includes(search.toLowerCase()),
  );

  const categories = [...new Set(faqs.map((f) => f.category).filter(Boolean))] as string[];

  const grouped = categories.length > 0
    ? categories.reduce<Record<string, FAQ[]>>((acc, cat) => {
        acc[cat] = filtered.filter((f) => f.category === cat);
        return acc;
      }, {})
    : { General: filtered };

  if (loading) {
    return (
      <div className="max-w-3xl mx-auto space-y-4 animate-pulse">
        <div className="h-8 bg-gray-200 rounded w-40" />
        <div className="h-12 bg-gray-200 rounded-xl" />
        {[1, 2, 3].map((i) => <div key={i} className="h-16 bg-gray-200 rounded-xl" />)}
      </div>
    );
  }

  return (
    <div className="max-w-3xl mx-auto space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Frequently Asked Questions</h1>
        <p className="text-gray-500 mt-0.5">Find answers to common travel questions</p>
      </div>

      {/* Search */}
      <div className="relative">
        <svg className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          type="search"
          placeholder="Search questions..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="w-full pl-11 pr-4 py-3 bg-white border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent"
        />
      </div>

      {filtered.length === 0 ? (
        <div className="text-center py-12 text-gray-400">
          <p>No questions found for &ldquo;{search}&rdquo;</p>
        </div>
      ) : (
        Object.entries(grouped).map(([category, items]) =>
          items.length === 0 ? null : (
            <div key={category}>
              {categories.length > 0 && (
                <h2 className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">
                  {category}
                </h2>
              )}
              <div className="space-y-2">
                {items.map((faq) => (
                  <div key={faq.id} className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <button
                      onClick={() => setExpandedId(expandedId === faq.id ? null : faq.id)}
                      className="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-gray-50 transition"
                    >
                      <p className="font-medium text-gray-900 text-sm pr-4">{faq.question}</p>
                      <svg
                        className={`w-5 h-5 text-gray-400 flex-shrink-0 transition-transform ${expandedId === faq.id ? 'rotate-180' : ''}`}
                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                      >
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                      </svg>
                    </button>
                    {expandedId === faq.id && (
                      <div className="px-5 pb-4 border-t border-gray-100 bg-gray-50/50">
                        <p className="text-sm text-gray-600 mt-3 leading-relaxed">{faq.answer}</p>
                      </div>
                    )}
                  </div>
                ))}
              </div>
            </div>
          )
        )
      )}
    </div>
  );
}
