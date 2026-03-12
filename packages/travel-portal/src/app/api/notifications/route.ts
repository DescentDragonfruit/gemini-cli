import { NextRequest, NextResponse } from 'next/server';
import { auth } from '@/lib/auth';
import { prisma } from '@/lib/prisma';
import { runNotificationJob } from '@/lib/cron';

export async function GET() {
  const session = await auth();
  if (!session?.user?.clientId) {
    return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
  }

  const notifications = await prisma.notification.findMany({
    where: { clientId: session.user.clientId },
    orderBy: { createdAt: 'desc' },
    take: 20,
  });

  return NextResponse.json(notifications);
}

// Mark all as read
export async function PATCH() {
  const session = await auth();
  if (!session?.user?.clientId) {
    return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
  }

  await prisma.notification.updateMany({
    where: { clientId: session.user.clientId, read: false },
    data: { read: true },
  });

  return NextResponse.json({ success: true });
}

// Manually trigger notification job (dev only)
export async function POST(req: NextRequest) {
  if (process.env.NODE_ENV !== 'development') {
    return NextResponse.json({ error: 'Not allowed' }, { status: 403 });
  }

  await runNotificationJob();
  return NextResponse.json({ success: true });
}
