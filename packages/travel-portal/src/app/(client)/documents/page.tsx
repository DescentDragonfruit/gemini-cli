import { auth } from '@/lib/auth';
import { redirect } from 'next/navigation';
import { prisma } from '@/lib/prisma';

const docTypeLabels: Record<string, string> = {
  PASSPORT: 'Passport',
  VISA: 'Visa',
  INSURANCE: 'Travel Insurance',
  VACCINATION: 'Vaccination Certificate',
  FLIGHT_TICKET: 'Flight Ticket',
  HOTEL_BOOKING: 'Hotel Booking',
  OTHER: 'Other',
};

const statusConfig: Record<string, { label: string; className: string }> = {
  PENDING: { label: 'Pending', className: 'bg-amber-100 text-amber-700' },
  SUBMITTED: { label: 'Submitted', className: 'bg-blue-100 text-blue-700' },
  APPROVED: { label: 'Approved', className: 'bg-green-100 text-green-700' },
  REJECTED: { label: 'Action Required', className: 'bg-red-100 text-red-700' },
};

export default async function DocumentsPage() {
  const session = await auth();
  if (!session?.user?.clientId) redirect('/login');

  const itinerary = await prisma.itinerary.findUnique({
    where: { clientId: session.user.clientId },
    include: {
      documents: { orderBy: { createdAt: 'asc' } },
    },
  });

  const documents = itinerary?.documents ?? [];
  const pending = documents.filter((d) => d.status === 'PENDING');
  const submitted = documents.filter((d) => d.status === 'SUBMITTED');
  const approved = documents.filter((d) => d.status === 'APPROVED');
  const rejected = documents.filter((d) => d.status === 'REJECTED');

  return (
    <div className="max-w-3xl mx-auto space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Documents</h1>
        <p className="text-gray-500 mt-0.5">Track your required travel documents</p>
      </div>

      {/* Progress summary */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
        {[
          { label: 'Total', count: documents.length, className: 'bg-gray-50 border-gray-200 text-gray-700' },
          { label: 'Approved', count: approved.length, className: 'bg-green-50 border-green-200 text-green-700' },
          { label: 'Submitted', count: submitted.length, className: 'bg-blue-50 border-blue-200 text-blue-700' },
          { label: 'Pending', count: pending.length + rejected.length, className: 'bg-amber-50 border-amber-200 text-amber-700' },
        ].map((stat) => (
          <div key={stat.label} className={`rounded-2xl border p-4 text-center ${stat.className}`}>
            <p className="text-2xl font-bold">{stat.count}</p>
            <p className="text-sm font-medium mt-0.5">{stat.label}</p>
          </div>
        ))}
      </div>

      {/* Progress bar */}
      {documents.length > 0 && (
        <div className="bg-white rounded-2xl border border-gray-200 p-5">
          <div className="flex items-center justify-between mb-2">
            <p className="text-sm font-medium text-gray-700">Overall Progress</p>
            <p className="text-sm font-bold text-gray-900">
              {Math.round((approved.length / documents.length) * 100)}%
            </p>
          </div>
          <div className="h-3 bg-gray-100 rounded-full overflow-hidden">
            <div
              className="h-full bg-gradient-to-r from-brand-500 to-green-500 rounded-full transition-all"
              style={{ width: `${(approved.length / documents.length) * 100}%` }}
            />
          </div>
          <p className="text-xs text-gray-400 mt-2">
            {approved.length} of {documents.length} documents approved
          </p>
        </div>
      )}

      {/* Documents list */}
      {documents.length === 0 ? (
        <div className="text-center py-16 text-gray-400 bg-white rounded-2xl border border-gray-200">
          <svg className="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
          </svg>
          <p>No documents required yet</p>
        </div>
      ) : (
        <div className="bg-white rounded-2xl border border-gray-200 divide-y divide-gray-100">
          {documents.map((doc) => {
            const conf = statusConfig[doc.status] ?? statusConfig.PENDING;
            const isOverdue = doc.dueDate && new Date(doc.dueDate) < new Date() && doc.status !== 'APPROVED';

            return (
              <div key={doc.id} className="flex items-center gap-4 p-5">
                {/* Status icon */}
                <div className={`w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0 ${
                  doc.status === 'APPROVED' ? 'bg-green-100' :
                  doc.status === 'SUBMITTED' ? 'bg-blue-100' :
                  doc.status === 'REJECTED' ? 'bg-red-100' :
                  'bg-gray-100'
                }`}>
                  {doc.status === 'APPROVED' ? (
                    <svg className="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                      <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                    </svg>
                  ) : doc.status === 'REJECTED' ? (
                    <svg className="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
                    </svg>
                  ) : (
                    <svg className={`w-5 h-5 ${doc.status === 'SUBMITTED' ? 'text-blue-500' : 'text-gray-400'}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                  )}
                </div>

                {/* Content */}
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2 flex-wrap">
                    <p className="font-semibold text-gray-900 text-sm">{doc.name}</p>
                    <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${conf.className}`}>
                      {conf.label}
                    </span>
                    {!doc.required && (
                      <span className="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">Optional</span>
                    )}
                  </div>
                  <p className="text-xs text-gray-500 mt-0.5">{docTypeLabels[doc.type] ?? doc.type}</p>
                  {doc.notes && <p className="text-xs text-gray-500 mt-1 italic">{doc.notes}</p>}
                  {doc.dueDate && (
                    <p className={`text-xs mt-1 font-medium ${isOverdue ? 'text-red-600' : 'text-gray-400'}`}>
                      {isOverdue ? '⚠ Overdue: ' : 'Due: '}
                      {new Date(doc.dueDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                    </p>
                  )}
                </div>
              </div>
            );
          })}
        </div>
      )}

      <div className="bg-brand-50 border border-brand-100 rounded-2xl p-5">
        <h3 className="font-semibold text-brand-900 text-sm mb-1">Need help with documents?</h3>
        <p className="text-brand-700 text-sm">
          Contact your travel agent to submit documents or check on the status of pending items.
        </p>
      </div>
    </div>
  );
}
