import { auth } from '@/lib/auth';
import { redirect } from 'next/navigation';
import { prisma } from '@/lib/prisma';
import { InvoiceList } from '@/components/invoices/InvoiceList';

export default async function InvoicesPage() {
  const session = await auth();
  if (!session?.user?.clientId) redirect('/login');

  const invoices = await prisma.invoice.findMany({
    where: { clientId: session.user.clientId },
    include: { items: true },
    orderBy: { dueDate: 'asc' },
  });

  const totalPaid = invoices
    .filter((i) => i.status === 'PAID')
    .reduce((sum, i) => sum + i.amount, 0);

  const totalPending = invoices
    .filter((i) => i.status === 'PENDING' || i.status === 'OVERDUE')
    .reduce((sum, i) => sum + i.amount, 0);

  return (
    <div className="max-w-4xl mx-auto space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Invoices & Payments</h1>
        <p className="text-gray-500 mt-0.5">Manage your travel payments</p>
      </div>

      {/* Summary */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div className="bg-white rounded-2xl border border-gray-200 p-5">
          <p className="text-sm text-gray-500">Total Invoices</p>
          <p className="text-2xl font-bold text-gray-900 mt-1">{invoices.length}</p>
        </div>
        <div className="bg-green-50 rounded-2xl border border-green-200 p-5">
          <p className="text-sm text-green-600">Total Paid</p>
          <p className="text-2xl font-bold text-green-700 mt-1">
            ${totalPaid.toFixed(2)}
          </p>
        </div>
        <div className="bg-amber-50 rounded-2xl border border-amber-200 p-5">
          <p className="text-sm text-amber-600">Remaining Balance</p>
          <p className="text-2xl font-bold text-amber-700 mt-1">
            ${totalPending.toFixed(2)}
          </p>
        </div>
      </div>

      <InvoiceList
        invoices={invoices.map((inv) => ({
          ...inv,
          dueDate: inv.dueDate.toISOString(),
          paidAt: inv.paidAt?.toISOString() ?? null,
          createdAt: inv.createdAt.toISOString(),
          items: inv.items,
        }))}
      />
    </div>
  );
}
