import { NextRequest, NextResponse } from 'next/server';
import { stripe } from '@/lib/stripe';
import { prisma } from '@/lib/prisma';
import { sendEmail } from '@/lib/email';
import type Stripe from 'stripe';

export async function POST(req: NextRequest) {
  const body = await req.text();
  const sig = req.headers.get('stripe-signature');

  if (!sig || !process.env.STRIPE_WEBHOOK_SECRET) {
    return NextResponse.json({ error: 'Missing signature' }, { status: 400 });
  }

  let event: Stripe.Event;
  try {
    event = stripe.webhooks.constructEvent(body, sig, process.env.STRIPE_WEBHOOK_SECRET);
  } catch {
    return NextResponse.json({ error: 'Invalid signature' }, { status: 400 });
  }

  if (event.type === 'payment_intent.succeeded') {
    const paymentIntent = event.data.object as Stripe.PaymentIntent;
    const invoiceId = paymentIntent.metadata.invoiceId;

    if (invoiceId) {
      const invoice = await prisma.invoice.update({
        where: { id: invoiceId },
        data: {
          status: 'PAID',
          paidAt: new Date(),
          stripePaymentIntentId: paymentIntent.id,
        },
        include: {
          client: {
            include: { user: true },
          },
        },
      });

      // Create notification
      await prisma.notification.create({
        data: {
          clientId: invoice.clientId,
          title: 'Payment Received',
          message: `Your payment of ${invoice.currency} ${invoice.amount.toFixed(2)} for invoice ${invoice.invoiceNumber} has been received.`,
          type: 'GENERAL',
        },
      });

      // Send confirmation email
      await sendEmail({
        to: invoice.client.user.email,
        subject: `Payment Confirmed: Invoice ${invoice.invoiceNumber}`,
        html: `
          <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <div style="background: linear-gradient(135deg, #059669, #10b981); padding: 32px; border-radius: 12px 12px 0 0;">
              <h1 style="color: white; margin: 0;">Payment Confirmed ✓</h1>
            </div>
            <div style="padding: 32px; background: #f8fafc; border-radius: 0 0 12px 12px; border: 1px solid #e2e8f0;">
              <p>Dear ${invoice.client.firstName} ${invoice.client.lastName},</p>
              <p>Your payment has been successfully processed.</p>
              <div style="background: white; border-radius: 8px; padding: 20px; margin: 20px 0;">
                <p><strong>Invoice:</strong> ${invoice.invoiceNumber}</p>
                <p><strong>Amount:</strong> ${invoice.currency} ${invoice.amount.toFixed(2)}</p>
                <p><strong>Date:</strong> ${new Date().toLocaleDateString()}</p>
              </div>
              <p style="color: #6b7280; font-size: 14px;">Thank you for your payment!</p>
            </div>
          </div>
        `,
      });
    }
  }

  return NextResponse.json({ received: true });
}
