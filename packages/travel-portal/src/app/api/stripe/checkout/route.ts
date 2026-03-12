import { NextRequest, NextResponse } from 'next/server';
import { auth } from '@/lib/auth';
import { prisma } from '@/lib/prisma';
import { stripe } from '@/lib/stripe';

export async function POST(req: NextRequest) {
  const session = await auth();
  if (!session?.user?.clientId) {
    return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
  }

  const { invoiceId } = await req.json() as { invoiceId: string };

  const invoice = await prisma.invoice.findFirst({
    where: {
      id: invoiceId,
      clientId: session.user.clientId,
      status: { in: ['PENDING', 'OVERDUE'] },
    },
  });

  if (!invoice) {
    return NextResponse.json({ error: 'Invoice not found' }, { status: 404 });
  }

  const paymentIntent = await stripe.paymentIntents.create({
    amount: Math.round(invoice.amount * 100),
    currency: invoice.currency.toLowerCase(),
    metadata: {
      invoiceId: invoice.id,
      invoiceNumber: invoice.invoiceNumber,
      clientId: session.user.clientId,
    },
  });

  // Store the payment intent ID
  await prisma.invoice.update({
    where: { id: invoice.id },
    data: { stripePaymentIntentId: paymentIntent.id },
  });

  return NextResponse.json({ clientSecret: paymentIntent.client_secret });
}
