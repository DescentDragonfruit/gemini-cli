import { prisma } from '@/lib/prisma';

export default async function GuidePage() {
  const guides = await prisma.travelGuide.findMany({
    orderBy: { order: 'asc' },
  });

  if (guides.length === 0) {
    return (
      <div className="max-w-3xl mx-auto space-y-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Travel Guide</h1>
          <p className="text-gray-500 mt-0.5">Tips and information for your trip</p>
        </div>
        <div className="text-center py-16 text-gray-400 bg-white rounded-2xl border border-gray-200">
          <svg className="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
          </svg>
          <p>Travel guide content coming soon</p>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-3xl mx-auto space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Travel Guide</h1>
        <p className="text-gray-500 mt-0.5">Everything you need to know for your trip</p>
      </div>

      <div className="space-y-4">
        {guides.map((guide) => (
          <div key={guide.id} className="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <div className="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
              {guide.icon && <span className="text-2xl">{guide.icon}</span>}
              <h2 className="font-semibold text-gray-900">{guide.title}</h2>
            </div>
            <div className="px-6 py-5">
              <div
                className="prose text-gray-700 text-sm leading-relaxed"
                dangerouslySetInnerHTML={{ __html: guide.content.replace(/\n/g, '<br />') }}
              />
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
