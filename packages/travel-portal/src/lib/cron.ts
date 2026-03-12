import cron from 'node-cron';
import { prisma } from '@/lib/prisma';
import {
  sendEmail,
  renderPaymentDueEmail,
  renderTripReminderEmail,
  renderDocumentReminderEmail,
} from '@/lib/email';

const PORTAL_URL = process.env.NEXTAUTH_URL ?? 'http://localhost:3000';

export async function runNotificationJob(): Promise<void> {
  const now = new Date();
  const sevenDaysLater = new Date(now.getTime() + 7 * 24 * 3600 * 1000);
  const oneDayLater = new Date(now.getTime() + 1 * 24 * 3600 * 1000);

  // 1. Payment due reminders
  const overdueInvoices = await prisma.invoice.findMany({
    where: {
      status: 'PENDING',
      dueDate: { lte: sevenDaysLater },
    },
    include: {
      client: {
        include: { user: true },
      },
    },
  });

  for (const invoice of overdueInvoices) {
    const existing = await prisma.notification.findFirst({
      where: {
        clientId: invoice.clientId,
        type: 'PAYMENT_DUE',
        createdAt: { gte: new Date(now.getTime() - 24 * 3600 * 1000) },
        message: { contains: invoice.invoiceNumber },
      },
    });
    if (existing) continue;

    const notification = await prisma.notification.create({
      data: {
        clientId: invoice.clientId,
        title: 'Payment Due Soon',
        message: `Invoice ${invoice.invoiceNumber} for ${invoice.currency} ${invoice.amount.toFixed(2)} is due on ${invoice.dueDate.toLocaleDateString()}.`,
        type: 'PAYMENT_DUE',
      },
    });

    const html = renderPaymentDueEmail(
      `${invoice.client.firstName} ${invoice.client.lastName}`,
      invoice.invoiceNumber,
      invoice.amount,
      invoice.currency,
      invoice.dueDate,
      PORTAL_URL,
    );

    const sent = await sendEmail({
      to: invoice.client.user.email,
      subject: `Payment Due: Invoice ${invoice.invoiceNumber}`,
      html,
    });

    if (sent) {
      await prisma.notification.update({
        where: { id: notification.id },
        data: { emailSent: true },
      });
    }
  }

  // Mark overdue invoices
  await prisma.invoice.updateMany({
    where: {
      status: 'PENDING',
      dueDate: { lt: now },
    },
    data: { status: 'OVERDUE' },
  });

  // 2. Trip reminders (7 days and 1 day)
  const upcomingItineraries = await prisma.itinerary.findMany({
    where: {
      startDate: {
        gte: now,
        lte: sevenDaysLater,
      },
    },
    include: {
      client: {
        include: { user: true },
      },
    },
  });

  for (const itinerary of upcomingItineraries) {
    const daysUntil = Math.ceil(
      (itinerary.startDate.getTime() - now.getTime()) / (24 * 3600 * 1000),
    );

    if (daysUntil !== 7 && daysUntil !== 1) continue;

    const recentNotif = await prisma.notification.findFirst({
      where: {
        clientId: itinerary.clientId,
        type: 'TRIP_REMINDER',
        createdAt: { gte: new Date(now.getTime() - 12 * 3600 * 1000) },
      },
    });
    if (recentNotif) continue;

    const notification = await prisma.notification.create({
      data: {
        clientId: itinerary.clientId,
        title: daysUntil === 1 ? 'Your Trip Starts Tomorrow!' : 'Trip Reminder',
        message: `Your trip "${itinerary.title}" starts ${daysUntil === 1 ? 'tomorrow' : 'in 7 days'} on ${itinerary.startDate.toLocaleDateString()}.`,
        type: 'TRIP_REMINDER',
      },
    });

    const html = renderTripReminderEmail(
      `${itinerary.client.firstName} ${itinerary.client.lastName}`,
      itinerary.title,
      itinerary.startDate,
      daysUntil,
      PORTAL_URL,
    );

    const sent = await sendEmail({
      to: itinerary.client.user.email,
      subject: `Trip Reminder: ${itinerary.title} starts ${daysUntil === 1 ? 'tomorrow' : 'in 7 days'}`,
      html,
    });

    if (sent) {
      await prisma.notification.update({
        where: { id: notification.id },
        data: { emailSent: true },
      });
    }
  }

  // 3. Document due reminders
  const pendingDocuments = await prisma.document.findMany({
    where: {
      status: { in: ['PENDING', 'REJECTED'] },
      dueDate: { lte: sevenDaysLater, gte: now },
    },
    include: {
      itinerary: {
        include: {
          client: {
            include: { user: true },
          },
        },
      },
    },
  });

  for (const doc of pendingDocuments) {
    const client = doc.itinerary.client;
    const existing = await prisma.notification.findFirst({
      where: {
        clientId: client.id,
        type: 'DOCUMENT_REQUIRED',
        createdAt: { gte: new Date(now.getTime() - 24 * 3600 * 1000) },
        message: { contains: doc.name },
      },
    });
    if (existing) continue;

    const notification = await prisma.notification.create({
      data: {
        clientId: client.id,
        title: 'Document Required',
        message: `"${doc.name}" is due by ${doc.dueDate!.toLocaleDateString()}.`,
        type: 'DOCUMENT_REQUIRED',
      },
    });

    const html = renderDocumentReminderEmail(
      `${client.firstName} ${client.lastName}`,
      doc.name,
      doc.dueDate!,
      PORTAL_URL,
    );

    const sent = await sendEmail({
      to: client.user.email,
      subject: `Document Required: ${doc.name}`,
      html,
    });

    if (sent) {
      await prisma.notification.update({
        where: { id: notification.id },
        data: { emailSent: true },
      });
    }
  }

  console.log('[Cron] Notification job completed at', now.toISOString());
}

let cronStarted = false;

export function startCronJobs(): void {
  if (cronStarted) return;
  cronStarted = true;

  // Run daily at 9:00 AM
  cron.schedule('0 9 * * *', () => {
    runNotificationJob().catch(console.error);
  });

  console.log('[Cron] Notification scheduler started');
}
