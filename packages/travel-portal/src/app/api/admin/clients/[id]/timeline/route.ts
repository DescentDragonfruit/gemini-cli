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
  const { title, description, dueDate, order } = await req.json() as {
    title: string;
    description?: string;
    dueDate: string;
    order?: number;
  };

  if (!title || !dueDate) {
    return NextResponse.json({ error: 'Title and due date are required' }, { status: 400 });
  }

  const step = await prisma.timelineStep.create({
    data: {
      clientId,
      title,
      description: description || null,
      dueDate: new Date(dueDate),
      order: order ?? 0,
    },
  });

  return NextResponse.json(step, { status: 201 });
}
