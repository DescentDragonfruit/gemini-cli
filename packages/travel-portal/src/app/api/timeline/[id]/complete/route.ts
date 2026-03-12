import { NextRequest, NextResponse } from 'next/server';
import { auth } from '@/lib/auth';
import { prisma } from '@/lib/prisma';

export async function POST(
  req: NextRequest,
  { params }: { params: Promise<{ id: string }> },
) {
  const session = await auth();
  if (!session?.user?.clientId) {
    return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
  }

  const { id } = await params;

  const step = await prisma.timelineStep.findFirst({
    where: { id, clientId: session.user.clientId },
  });

  if (!step) {
    return NextResponse.json({ error: 'Not found' }, { status: 404 });
  }

  await prisma.timelineStep.update({
    where: { id },
    data: { completed: true, completedAt: new Date() },
  });

  return NextResponse.json({ success: true });
}
