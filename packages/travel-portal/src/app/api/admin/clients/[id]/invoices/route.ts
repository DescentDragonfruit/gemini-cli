import { NextRequest, NextResponse } from 'next/server';
import { auth } from '@/lib/auth';
import { prisma } from '@/lib/prisma';

export async function POST(
  req: NextRequest,
  { params }: { params: Promise<{ id: string }> },
) {
  const session = await auth();
  if (!session?.user || session.user.role !== 'ADMIN') {
    return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
  }

  const { id: clientId } = await params;
  const body = await req.json() as {
    invoiceNumber: string;
    amount: string;
    currency?: string;
    dueDate: string;
    description?: string;
    items?: Array<{ description: string; amount: string; quantity: string }>;
  };

  const amount = parseFloat(body.amount);
  if (isNaN(amount) || amount <= 0) {
    return NextResponse.json({ error: 'Invalid amount' }, { status: 400 });
  }

  const invoice = await prisma.invoice.create({
    data: {
      clientId,
      invoiceNumber: body.invoiceNumber,
      amount,
      currency: body.currency ?? 'USD',
      dueDate: new Date(body.dueDate),
      description: body.description || null,
      items: body.items
        ? {
            create: body.items
              .filter((item) => item.description && item.amount)
              .map((item) => ({
                description: item.description,
                amount: parseFloat(item.amount),
                quantity: parseInt(item.quantity) || 1,
              })),
          }
        : undefined,
    },
    include: { items: true },
  });

  // Create notification for client
  await prisma.notification.create({
    data: {
      clientId,
      title: 'New Invoice',
      message: `A new invoice (${invoice.invoiceNumber}) for ${invoice.currency} ${invoice.amount.toFixed(2)} has been created. Due: ${new Date(invoice.dueDate).toLocaleDateString()}.`,
      type: 'PAYMENT_DUE',
    },
  });

  return NextResponse.json(invoice, { status: 201 });
}
