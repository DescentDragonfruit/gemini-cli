import { auth } from '@/lib/auth';
import { redirect } from 'next/navigation';
import { prisma } from '@/lib/prisma';
import { TimelineView } from '@/components/timeline/TimelineView';
import Link from 'next/link';

export default async function DashboardPage() {
  const session = await auth();
  if (!session?.user?.clientId) redirect('/login');

  const clientId = session.user.clientId;

  const [client, itinerary, invoices, timelineSteps, unreadNotifs] = await Promise.all([
    prisma.client.findUnique({
      where: { id: clientId },
      include: { user: { select: { email: true } } },
    }),
    prisma.itinerary.findUnique({
      where: { clientId },
      include: { documents: true },
    }),
    prisma.invoice.findMany({
      where: { clientId },
      orderBy: { dueDate: 'asc' },
    }),
    prisma.timelineStep.findMany({
      where: { clientId },
      orderBy: { order: 'asc' },
    }),
    prisma.notification.count({ where: { clientId, read: false } }),
  ]);

  if (!client) redirect('/login');

  const now = new Date();
  const daysUntilTrip = itinerary
    ? Math.ceil((itinerary.startDate.getTime() - now.getTime()) / (24 * 3600 * 1000))
    : null;

  const pendingInvoices = invoices.filter((i) => i.status === 'PENDING' || i.status === 'OVERDUE');
  const nextDueInvoice = pendingInvoices[0];
  const pendingDocs = itinerary?.documents.filter((d) => d.status === 'PENDING' || d.status === 'REJECTED') ?? [];
  const completedSteps = timelineSteps.filter((s) => s.completed).length;

  return (
    <div className="max-w-6xl mx-auto space-y-6">
      {/* Welcome */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900">
          Welcome back, {client.firstName}!
        </h1>
        <p className="text-gray-500 mt-0.5">Here's an overview of your upcoming trip.</p>
      </div>

      {/* Summary Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        {/* Trip countdown */}
        <div className="bg-gradient-to-br from-brand-600 to-brand-500 rounded-2xl p-5 text-white">
          <div className="flex items-center gap-2 mb-3">
            <svg className="w-5 h-5 text-brand-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
            <span className="text-brand-100 text-sm font-medium">Trip Countdown</span>
          </div>
          {daysUntilTrip !== null ? (
            <>
              <p className="text-4xl font-bold">
                {daysUntilTrip > 0 ? daysUntilTrip : daysUntilTrip === 0 ? 'Today!' : 'Ongoing'}
              </p>
              {daysUntilTrip > 0 && <p className="text-brand-100 text-sm mt-1">days until departure</p>}
              <p className="text-brand-100 text-xs mt-3 font-medium truncate">{itinerary?.title}</p>
            </>
          ) : (
            <p className="text-brand-100 text-sm mt-2">No trip scheduled yet</p>
          )}
        </div>

        {/* Payment status */}
        <div className={`rounded-2xl p-5 ${pendingInvoices.length > 0 ? 'bg-amber-50 border border-amber-200' : 'bg-green-50 border border-green-200'}`}>
          <div className="flex items-center gap-2 mb-3">
            <svg className={`w-5 h-5 ${pendingInvoices.length > 0 ? 'text-amber-500' : 'text-green-500'}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
            <span className={`text-sm font-medium ${pendingInvoices.length > 0 ? 'text-amber-700' : 'text-green-700'}`}>
              Payments
            </span>
          </div>
          {nextDueInvoice ? (
            <>
              <p className="text-2xl font-bold text-gray-900">
                {nextDueInvoice.currency} {nextDueInvoice.amount.toFixed(2)}
              </p>
              <p className="text-sm text-gray-600 mt-1">
                Due {new Date(nextDueInvoice.dueDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}
              </p>
              {pendingInvoices.length > 1 && (
                <p className="text-xs text-gray-500 mt-2">+{pendingInvoices.length - 1} more invoice{pendingInvoices.length > 2 ? 's' : ''}</p>
              )}
              <Link href="/invoices" className="text-xs text-brand-600 font-medium mt-2 block hover:underline">
                View invoices →
              </Link>
            </>
          ) : (
            <p className="text-green-700 font-semibold mt-1">All paid up!</p>
          )}
        </div>

        {/* Documents */}
        <div className={`rounded-2xl p-5 ${pendingDocs.length > 0 ? 'bg-orange-50 border border-orange-200' : 'bg-gray-50 border border-gray-200'}`}>
          <div className="flex items-center gap-2 mb-3">
            <svg className={`w-5 h-5 ${pendingDocs.length > 0 ? 'text-orange-500' : 'text-gray-400'}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            <span className={`text-sm font-medium ${pendingDocs.length > 0 ? 'text-orange-700' : 'text-gray-600'}`}>
              Documents
            </span>
          </div>
          <p className="text-2xl font-bold text-gray-900">{pendingDocs.length}</p>
          <p className="text-sm text-gray-600 mt-1">
            {pendingDocs.length === 0 ? 'All documents ready' : `pending submission`}
          </p>
          {pendingDocs.length > 0 && (
            <Link href="/documents" className="text-xs text-brand-600 font-medium mt-2 block hover:underline">
              Review documents →
            </Link>
          )}
        </div>

        {/* Progress */}
        <div className="bg-white rounded-2xl p-5 border border-gray-200">
          <div className="flex items-center gap-2 mb-3">
            <svg className="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span className="text-sm font-medium text-gray-600">Trip Progress</span>
          </div>
          <p className="text-2xl font-bold text-gray-900">
            {timelineSteps.length > 0 ? Math.round((completedSteps / timelineSteps.length) * 100) : 0}%
          </p>
          <p className="text-sm text-gray-600 mt-1">
            {completedSteps}/{timelineSteps.length} steps completed
          </p>
          <div className="mt-3 bg-gray-200 rounded-full h-1.5">
            <div
              className="bg-brand-500 h-1.5 rounded-full transition-all"
              style={{ width: `${timelineSteps.length > 0 ? (completedSteps / timelineSteps.length) * 100 : 0}%` }}
            />
          </div>
        </div>
      </div>

      {/* Main content grid */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Timeline */}
        <div className="lg:col-span-2 bg-white rounded-2xl border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-5">
            <h2 className="font-semibold text-gray-900">Your Timeline & Next Steps</h2>
            <span className="text-xs text-gray-400 bg-gray-100 px-2.5 py-1 rounded-full">
              {timelineSteps.length} steps
            </span>
          </div>
          {timelineSteps.length > 0 ? (
            <TimelineView
              steps={timelineSteps.map((s) => ({
                ...s,
                dueDate: s.dueDate.toISOString(),
              }))}
            />
          ) : (
            <div className="text-center py-12 text-gray-400">
              <svg className="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
              <p>No timeline steps yet</p>
            </div>
          )}
        </div>

        {/* Sidebar info */}
        <div className="space-y-4">
          {/* Trip details */}
          {itinerary && (
            <div className="bg-white rounded-2xl border border-gray-200 p-5">
              <h3 className="font-semibold text-gray-900 mb-4">Trip Details</h3>
              <div className="space-y-3">
                <div className="flex gap-3">
                  <div className="w-8 h-8 bg-brand-50 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg className="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    </svg>
                  </div>
                  <div>
                    <p className="text-xs text-gray-500">Destination</p>
                    <p className="text-sm font-medium text-gray-900">{itinerary.destination ?? 'TBD'}</p>
                  </div>
                </div>
                <div className="flex gap-3">
                  <div className="w-8 h-8 bg-green-50 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg className="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                  </div>
                  <div>
                    <p className="text-xs text-gray-500">Travel Dates</p>
                    <p className="text-sm font-medium text-gray-900">
                      {new Date(itinerary.startDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} –{' '}
                      {new Date(itinerary.endDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                    </p>
                  </div>
                </div>
                <div className="flex gap-3">
                  <div className="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg className="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                  <div>
                    <p className="text-xs text-gray-500">Duration</p>
                    <p className="text-sm font-medium text-gray-900">
                      {Math.ceil((new Date(itinerary.endDate).getTime() - new Date(itinerary.startDate).getTime()) / (24 * 3600 * 1000))} days
                    </p>
                  </div>
                </div>
              </div>
              <Link
                href="/itinerary"
                className="mt-4 w-full flex items-center justify-center gap-1.5 text-sm font-medium text-brand-600 hover:text-brand-700 bg-brand-50 hover:bg-brand-100 py-2 rounded-xl transition"
              >
                View Full Itinerary
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                </svg>
              </Link>
            </div>
          )}

          {/* Client info */}
          <div className="bg-white rounded-2xl border border-gray-200 p-5">
            <h3 className="font-semibold text-gray-900 mb-4">Your Profile</h3>
            <div className="space-y-2 text-sm">
              <div className="flex justify-between">
                <span className="text-gray-500">Name</span>
                <span className="font-medium text-gray-900">{client.firstName} {client.lastName}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-500">Email</span>
                <span className="font-medium text-gray-900 truncate max-w-32">{client.user.email}</span>
              </div>
              {client.nationality && (
                <div className="flex justify-between">
                  <span className="text-gray-500">Nationality</span>
                  <span className="font-medium text-gray-900">{client.nationality}</span>
                </div>
              )}
              {client.passportNumber && (
                <div className="flex justify-between">
                  <span className="text-gray-500">Passport</span>
                  <span className="font-medium text-gray-900">••••{client.passportNumber.slice(-4)}</span>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
