import { prisma } from '@/lib/prisma';
import Link from 'next/link';

export default async function AdminClientsPage() {
  const clients = await prisma.client.findMany({
    include: {
      user: { select: { email: true } },
      itinerary: { select: { title: true, startDate: true, endDate: true } },
      invoices: { select: { status: true, amount: true } },
    },
    orderBy: { createdAt: 'desc' },
  });

  return (
    <div className="max-w-6xl mx-auto space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Clients</h1>
          <p className="text-gray-500 mt-0.5">{clients.length} total clients</p>
        </div>
        <Link
          href="/admin/clients/new"
          className="flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white font-semibold px-5 py-2.5 rounded-xl transition"
        >
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
          </svg>
          Add Client
        </Link>
      </div>

      {clients.length === 0 ? (
        <div className="text-center py-16 bg-white rounded-2xl border border-gray-200 text-gray-400">
          <p>No clients yet. Add your first client.</p>
        </div>
      ) : (
        <div className="bg-white rounded-2xl border border-gray-200 overflow-hidden">
          <table className="w-full">
            <thead>
              <tr className="border-b border-gray-100">
                <th className="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Client</th>
                <th className="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Trip</th>
                <th className="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">Payment</th>
                <th className="text-right px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-50">
              {clients.map((client) => {
                const pendingAmount = client.invoices
                  .filter((i) => i.status === 'PENDING' || i.status === 'OVERDUE')
                  .reduce((sum, i) => sum + i.amount, 0);
                const hasOverdue = client.invoices.some((i) => i.status === 'OVERDUE');

                return (
                  <tr key={client.id} className="hover:bg-gray-50 transition">
                    <td className="px-6 py-4">
                      <div className="flex items-center gap-3">
                        <div className="w-9 h-9 bg-brand-600 rounded-full flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                          {client.firstName.charAt(0)}{client.lastName.charAt(0)}
                        </div>
                        <div>
                          <p className="font-semibold text-gray-900 text-sm">
                            {client.firstName} {client.lastName}
                          </p>
                          <p className="text-xs text-gray-400">{client.user.email}</p>
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4 hidden md:table-cell">
                      {client.itinerary ? (
                        <div>
                          <p className="text-sm font-medium text-gray-900">{client.itinerary.title}</p>
                          <p className="text-xs text-gray-400">
                            {new Date(client.itinerary.startDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} –{' '}
                            {new Date(client.itinerary.endDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                          </p>
                        </div>
                      ) : (
                        <span className="text-xs text-gray-400">No itinerary</span>
                      )}
                    </td>
                    <td className="px-6 py-4 hidden sm:table-cell">
                      {pendingAmount > 0 ? (
                        <span className={`text-sm font-semibold ${hasOverdue ? 'text-red-600' : 'text-amber-600'}`}>
                          ${pendingAmount.toFixed(2)} {hasOverdue ? '(overdue)' : 'pending'}
                        </span>
                      ) : (
                        <span className="text-xs text-green-600 font-medium">All paid</span>
                      )}
                    </td>
                    <td className="px-6 py-4 text-right">
                      <div className="flex items-center gap-2 justify-end">
                        <Link
                          href={`/admin/clients/${client.id}`}
                          className="text-xs font-medium text-brand-600 hover:text-brand-700 bg-brand-50 hover:bg-brand-100 px-3 py-1.5 rounded-lg transition"
                        >
                          View
                        </Link>
                        <Link
                          href={`/admin/clients/${client.id}/itinerary`}
                          className="text-xs font-medium text-gray-600 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded-lg transition"
                        >
                          Itinerary
                        </Link>
                      </div>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
