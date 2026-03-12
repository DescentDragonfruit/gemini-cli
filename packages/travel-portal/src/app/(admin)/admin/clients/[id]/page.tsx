import { prisma } from '@/lib/prisma';
import { notFound } from 'next/navigation';
import Link from 'next/link';

export default async function AdminClientPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;

  const client = await prisma.client.findUnique({
    where: { id },
    include: {
      user: { select: { email: true } },
      itinerary: { select: { title: true, startDate: true, endDate: true, destination: true } },
      invoices: { include: { items: true }, orderBy: { dueDate: 'asc' } },
      timelineSteps: { orderBy: { order: 'asc' } },
      notifications: { orderBy: { createdAt: 'desc' }, take: 5 },
    },
  });

  if (!client) notFound();

  const totalRevenue = client.invoices
    .filter((i) => i.status === 'PAID')
    .reduce((sum, i) => sum + i.amount, 0);

  const pendingAmount = client.invoices
    .filter((i) => i.status === 'PENDING' || i.status === 'OVERDUE')
    .reduce((sum, i) => sum + i.amount, 0);

  return (
    <div className="max-w-5xl mx-auto space-y-6">
      {/* Header */}
      <div className="flex items-center gap-3">
        <Link href="/admin/clients" className="text-gray-400 hover:text-gray-600 transition">
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
          </svg>
        </Link>
        <div className="flex-1">
          <h1 className="text-2xl font-bold text-gray-900">
            {client.firstName} {client.lastName}
          </h1>
          <p className="text-gray-500 text-sm">{client.user.email}</p>
        </div>
        <Link
          href={`/admin/clients/${client.id}/itinerary`}
          className="bg-brand-600 hover:bg-brand-700 text-white font-semibold px-5 py-2.5 rounded-xl transition text-sm"
        >
          Edit Itinerary
        </Link>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Left column */}
        <div className="space-y-4">
          {/* Client details */}
          <div className="bg-white rounded-2xl border border-gray-200 p-5">
            <h2 className="font-semibold text-gray-900 mb-4">Profile</h2>
            <div className="space-y-2 text-sm">
              {[
                { label: 'Phone', value: client.phone },
                { label: 'Nationality', value: client.nationality },
                { label: 'Passport', value: client.passportNumber ? `••••${client.passportNumber.slice(-4)}` : null },
                {
                  label: 'Passport Expiry',
                  value: client.passportExpiry
                    ? new Date(client.passportExpiry).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
                    : null,
                },
              ].map((item) =>
                item.value ? (
                  <div key={item.label} className="flex justify-between">
                    <span className="text-gray-400">{item.label}</span>
                    <span className="font-medium text-gray-900">{item.value}</span>
                  </div>
                ) : null,
              )}
            </div>
          </div>

          {/* Financial summary */}
          <div className="bg-white rounded-2xl border border-gray-200 p-5">
            <h2 className="font-semibold text-gray-900 mb-4">Finances</h2>
            <div className="space-y-3">
              <div className="bg-green-50 rounded-xl p-3">
                <p className="text-xs text-green-600">Collected</p>
                <p className="text-xl font-bold text-green-700">${totalRevenue.toFixed(2)}</p>
              </div>
              <div className="bg-amber-50 rounded-xl p-3">
                <p className="text-xs text-amber-600">Pending</p>
                <p className="text-xl font-bold text-amber-700">${pendingAmount.toFixed(2)}</p>
              </div>
            </div>
            <Link href="/admin/invoices" className="text-xs text-brand-600 hover:underline mt-3 block">
              Manage invoices →
            </Link>
          </div>
        </div>

        {/* Right column */}
        <div className="lg:col-span-2 space-y-4">
          {/* Trip info */}
          <div className="bg-white rounded-2xl border border-gray-200 p-5">
            <div className="flex items-center justify-between mb-4">
              <h2 className="font-semibold text-gray-900">Itinerary</h2>
              <Link href={`/admin/clients/${client.id}/itinerary`} className="text-xs text-brand-600 hover:underline">
                Edit →
              </Link>
            </div>
            {client.itinerary ? (
              <div className="space-y-2 text-sm">
                <div className="flex justify-between">
                  <span className="text-gray-400">Trip</span>
                  <span className="font-medium text-gray-900">{client.itinerary.title}</span>
                </div>
                {client.itinerary.destination && (
                  <div className="flex justify-between">
                    <span className="text-gray-400">Destination</span>
                    <span className="font-medium text-gray-900">{client.itinerary.destination}</span>
                  </div>
                )}
                <div className="flex justify-between">
                  <span className="text-gray-400">Dates</span>
                  <span className="font-medium text-gray-900">
                    {new Date(client.itinerary.startDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} –{' '}
                    {new Date(client.itinerary.endDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                  </span>
                </div>
              </div>
            ) : (
              <div className="text-center py-6 text-gray-400">
                <p className="text-sm">No itinerary created yet</p>
                <Link href={`/admin/clients/${client.id}/itinerary`} className="text-brand-600 text-sm mt-1 inline-block hover:underline">
                  Create itinerary →
                </Link>
              </div>
            )}
          </div>

          {/* Timeline */}
          <div className="bg-white rounded-2xl border border-gray-200 p-5">
            <h2 className="font-semibold text-gray-900 mb-4">Timeline Steps ({client.timelineSteps.length})</h2>
            {client.timelineSteps.length === 0 ? (
              <p className="text-gray-400 text-sm">No timeline steps</p>
            ) : (
              <div className="space-y-2">
                {client.timelineSteps.slice(0, 5).map((step) => (
                  <div key={step.id} className="flex items-center gap-3 text-sm">
                    <div className={`w-5 h-5 rounded-full flex-shrink-0 flex items-center justify-center ${step.completed ? 'bg-green-500' : 'bg-gray-200'}`}>
                      {step.completed && (
                        <svg className="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                          <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                        </svg>
                      )}
                    </div>
                    <span className={`flex-1 ${step.completed ? 'line-through text-gray-400' : 'text-gray-700'}`}>{step.title}</span>
                    <span className="text-gray-400 text-xs">
                      {new Date(step.dueDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}
                    </span>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Recent invoices */}
          <div className="bg-white rounded-2xl border border-gray-200 p-5">
            <h2 className="font-semibold text-gray-900 mb-4">Invoices</h2>
            {client.invoices.length === 0 ? (
              <p className="text-gray-400 text-sm">No invoices</p>
            ) : (
              <div className="space-y-2">
                {client.invoices.map((inv) => {
                  const statusColors: Record<string, string> = {
                    PAID: 'text-green-600 bg-green-50',
                    PENDING: 'text-amber-600 bg-amber-50',
                    OVERDUE: 'text-red-600 bg-red-50',
                    CANCELLED: 'text-gray-400 bg-gray-50',
                  };
                  return (
                    <div key={inv.id} className="flex items-center justify-between text-sm">
                      <span className="text-gray-600">{inv.invoiceNumber}</span>
                      <div className="flex items-center gap-2">
                        <span className="font-semibold text-gray-900">${inv.amount.toFixed(2)}</span>
                        <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${statusColors[inv.status] ?? ''}`}>
                          {inv.status}
                        </span>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
