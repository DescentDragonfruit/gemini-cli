import { prisma } from '@/lib/prisma';

export default async function AdminInvoicesPage() {
  const invoices = await prisma.invoice.findMany({
    include: {
      client: {
        select: { firstName: true, lastName: true },
      },
      items: true,
    },
    orderBy: { dueDate: 'asc' },
  });

  const statusConfig: Record<string, { label: string; className: string }> = {
    PENDING: { label: 'Pending', className: 'bg-amber-100 text-amber-700' },
    OVERDUE: { label: 'Overdue', className: 'bg-red-100 text-red-700' },
    PAID: { label: 'Paid', className: 'bg-green-100 text-green-700' },
    CANCELLED: { label: 'Cancelled', className: 'bg-gray-100 text-gray-500' },
  };

  const totalRevenue = invoices.filter((i) => i.status === 'PAID').reduce((s, i) => s + i.amount, 0);
  const totalPending = invoices.filter((i) => i.status === 'PENDING' || i.status === 'OVERDUE').reduce((s, i) => s + i.amount, 0);

  return (
    <div className="max-w-6xl mx-auto space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">All Invoices</h1>
        <p className="text-gray-500 mt-0.5">{invoices.length} invoices total</p>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div className="bg-white rounded-2xl border border-gray-200 p-5">
          <p className="text-sm text-gray-500">Total Invoices</p>
          <p className="text-2xl font-bold text-gray-900 mt-1">{invoices.length}</p>
        </div>
        <div className="bg-green-50 rounded-2xl border border-green-200 p-5">
          <p className="text-sm text-green-600">Revenue Collected</p>
          <p className="text-2xl font-bold text-green-700 mt-1">${totalRevenue.toFixed(2)}</p>
        </div>
        <div className="bg-amber-50 rounded-2xl border border-amber-200 p-5">
          <p className="text-sm text-amber-600">Outstanding Balance</p>
          <p className="text-2xl font-bold text-amber-700 mt-1">${totalPending.toFixed(2)}</p>
        </div>
      </div>

      <div className="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <table className="w-full">
          <thead>
            <tr className="border-b border-gray-100">
              <th className="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Invoice</th>
              <th className="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Client</th>
              <th className="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Due Date</th>
              <th className="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
              <th className="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-50">
            {invoices.map((inv) => {
              const conf = statusConfig[inv.status] ?? statusConfig.PENDING;
              return (
                <tr key={inv.id} className="hover:bg-gray-50 transition">
                  <td className="px-6 py-4">
                    <p className="font-semibold text-gray-900 text-sm">{inv.invoiceNumber}</p>
                    {inv.description && <p className="text-xs text-gray-400 mt-0.5 truncate max-w-32">{inv.description}</p>}
                  </td>
                  <td className="px-6 py-4">
                    <p className="text-sm text-gray-700">{inv.client.firstName} {inv.client.lastName}</p>
                  </td>
                  <td className="px-6 py-4 hidden md:table-cell">
                    <p className={`text-sm ${inv.status === 'OVERDUE' ? 'text-red-600 font-medium' : 'text-gray-600'}`}>
                      {new Date(inv.dueDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                    </p>
                  </td>
                  <td className="px-6 py-4">
                    <p className="text-sm font-semibold text-gray-900">{inv.currency} {inv.amount.toFixed(2)}</p>
                  </td>
                  <td className="px-6 py-4">
                    <span className={`text-xs px-2.5 py-1 rounded-full font-medium ${conf.className}`}>
                      {conf.label}
                    </span>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>

        {invoices.length === 0 && (
          <div className="text-center py-12 text-gray-400">No invoices yet</div>
        )}
      </div>
    </div>
  );
}
