import { NextRequest, NextResponse } from 'next/server';
import { auth } from '@/lib/auth';
import { prisma } from '@/lib/prisma';
import { z } from 'zod';

const updateClientSchema = z.object({
  firstName: z.string().min(1).optional(),
  lastName: z.string().min(1).optional(),
  phone: z.string().optional(),
  nationality: z.string().optional(),
  passportNumber: z.string().optional(),
  passportExpiry: z.string().optional(),
});

export async function GET(
  req: NextRequest,
  { params }: { params: Promise<{ id: string }> },
) {
  const session = await auth();
  if (!session?.user || session.user.role !== 'ADMIN') {
    return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
  }

  const { id } = await params;

  const client = await prisma.client.findUnique({
    where: { id },
    include: {
      user: { select: { email: true, id: true } },
      itinerary: {
        include: {
          days: { include: { activities: true }, orderBy: { dayNumber: 'asc' } },
          flights: true,
          documents: true,
          mapPoints: true,
        },
      },
      invoices: { include: { items: true }, orderBy: { dueDate: 'asc' } },
      timelineSteps: { orderBy: { order: 'asc' } },
      notifications: { orderBy: { createdAt: 'desc' }, take: 10 },
    },
  });

  if (!client) {
    return NextResponse.json({ error: 'Client not found' }, { status: 404 });
  }

  return NextResponse.json(client);
}

export async function PATCH(
  req: NextRequest,
  { params }: { params: Promise<{ id: string }> },
) {
  const session = await auth();
  if (!session?.user || session.user.role !== 'ADMIN') {
    return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
  }

  const { id } = await params;
  const body = await req.json();
  const parsed = updateClientSchema.safeParse(body);

  if (!parsed.success) {
    return NextResponse.json({ error: 'Invalid input' }, { status: 400 });
  }

  const client = await prisma.client.update({
    where: { id },
    data: {
      ...parsed.data,
      passportExpiry: parsed.data.passportExpiry
        ? new Date(parsed.data.passportExpiry)
        : undefined,
    },
  });

  return NextResponse.json(client);
}

export async function DELETE(
  req: NextRequest,
  { params }: { params: Promise<{ id: string }> },
) {
  const session = await auth();
  if (!session?.user || session.user.role !== 'ADMIN') {
    return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
  }

  const { id } = await params;

  const client = await prisma.client.findUnique({
    where: { id },
    include: { user: true },
  });

  if (!client) {
    return NextResponse.json({ error: 'Not found' }, { status: 404 });
  }

  // Cascade delete through user
  await prisma.user.delete({ where: { id: client.userId } });

  return NextResponse.json({ success: true });
}
