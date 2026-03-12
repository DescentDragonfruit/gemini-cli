import { prisma } from '@/lib/prisma';
import Link from 'next/link';

export default async function AdminDashboardPage() {
  const [clientCount, invoiceStats, pendingDocs, upcomingTrips] = await Promise.all([
    prisma.client.count(),
    prisma.invoice.groupBy({
      by: ['status'],
      _sum: { amount: true },
      _count: true,
    }),
    prisma.document.count({ where: { status: { in: ['PENDING', 'REJECTED'] } } }),
    prisma.itinerary.findMany({
      where: {
        startDate: { gte: new Date() },
      },
      orderBy: { startDate: 'asc' },
      take: 5,
      include: { client: true },
    }),
  ]);

  const pendingPayments = invoiceStats.find((s) => s.status === 'PENDING');
  const overduePayments = invoiceStats.find((s) => s.status === 'OVERDUE');
  const totalRevenue = invoiceStats.find((s) => s.status === 'PAID')?._sum.amount ?? 0;
  const totalPending = ((pendingPayments?._sum.amount ?? 0) + (overduePayments?._sum.amount ?? 0));

  return (
    <div className="max-w-6xl mx-auto space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Admin Dashboard</h1>
        <p className="text-gray-500 mt-0.5">Overview of all client travel bookings</p>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <StatCard
          title="Total Clients"
          value={clientCount.toString()}
          icon="👥"
          href="/admin/clients"
          color="bg-blue-50 border-blue-200"
          textColor="text-blue-700"
        />
        <StatCard
          title="Revenue Collected"
          value={`$${totalRevenue.toFixed(0)}`}
          icon="💰"
          href="/admin/invoices"
          color="bg-green-50 border-green-200"
          textColor="text-green-700"
        />
        <StatCard
          title="Pending Payments"
          value={`$${totalPending.toFixed(0)}`}
          icon="⏳"
          href="/admin/invoices"
          color="bg-amber-50 border-amber-200"
          textColor="text-amber-700"
        />
        <StatCard
          title="Pending Documents"
          value={pendingDocs.toString()}
          icon="📄"
          href="/admin/clients"
          color="bg-orange-50 border-orange-200"
          textColor="text-orange-700"
        />
      </div>

      {/* Upcoming trips */}
      <div className="bg-white rounded-2xl border border-gray-200 p-6">
        <div className="flex items-center justify-between mb-5">
          <h2 className="font-semibold text-gray-900">Upcoming Trips</h2>
          <Link href="/admin/clients" className="text-sm text-brand-600 hover:underline">
            View all clients →
          </Link>
        </div>

        {upcomingTrips.length === 0 ? (
          <p className="text-gray-400 text-sm py-4 text-center">No upcoming trips scheduled</p>
        ) : (
          <div className="space-y-3">
            {upcomingTrips.map((trip) => {
              const daysUntil = Math.ceil(
                (trip.startDate.getTime() - Date.now()) / (24 * 3600 * 1000),
              );
              return (
                <Link
                  key={trip.id}
                  href={`/admin/clients/${trip.clientId}`}
                  className="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition"
                >
                  <div className="w-10 h-10 bg-brand-600 rounded-xl flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                    {trip.client.firstName.charAt(0)}{trip.client.lastName.charAt(0)}
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="font-medium text-gray-900 text-sm">
                      {trip.client.firstName} {trip.client.lastName}
                    </p>
                    <p className="text-xs text-gray-500 truncate">{trip.title}</p>
                  </div>
                  <div className="text-right flex-shrink-0">
                    <p className="text-sm font-semibold text-gray-900">
                      {trip.startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}
                    </p>
                    <p className={`text-xs ${daysUntil <= 7 ? 'text-red-500' : 'text-gray-400'}`}>
                      {daysUntil === 0 ? 'Today' : daysUntil === 1 ? 'Tomorrow' : `In ${daysUntil} days`}
                    </p>
                  </div>
                </Link>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
}

function StatCard({
  title,
  value,
  icon,
  href,
  color,
  textColor,
}: {
  title: string;
  value: string;
  icon: string;
  href: string;
  color: string;
  textColor: string;
}) {
  return (
    <Link href={href} className={`rounded-2xl border p-5 hover:opacity-90 transition ${color}`}>
      <div className="flex items-center justify-between mb-3">
        <span className="text-2xl">{icon}</span>
      </div>
      <p className={`text-3xl font-bold ${textColor}`}>{value}</p>
      <p className={`text-sm font-medium mt-1 ${textColor} opacity-70`}>{title}</p>
    </Link>
  );
}
