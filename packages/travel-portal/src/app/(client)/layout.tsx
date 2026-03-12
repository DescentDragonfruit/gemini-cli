import { auth } from '@/lib/auth';
import { redirect } from 'next/navigation';
import { prisma } from '@/lib/prisma';
import { ClientSidebar } from '@/components/layout/ClientSidebar';
import { Header } from '@/components/layout/Header';

export default async function ClientLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const session = await auth();
  if (!session?.user || session.user.role !== 'CLIENT') {
    redirect('/login');
  }

  const client = await prisma.client.findFirst({
    where: { userId: session.user.id },
    select: { firstName: true, lastName: true },
  });

  const clientName = client ? `${client.firstName} ${client.lastName}` : session.user.email;

  return (
    <div className="flex min-h-screen bg-gray-50">
      <ClientSidebar />
      <div className="flex-1 flex flex-col min-w-0">
        <Header clientName={clientName ?? ''} />
        <main className="flex-1 p-6">{children}</main>
      </div>
    </div>
  );
}
