import { NextResponse } from 'next/server';
import { auth } from '@/lib/auth';
import { prisma } from '@/lib/prisma';

export async function GET() {
  const session = await auth();
  if (!session?.user?.clientId) {
    return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
  }

  const itinerary = await prisma.itinerary.findUnique({
    where: { clientId: session.user.clientId },
    include: {
      days: {
        include: { activities: { orderBy: { time: 'asc' } } },
        orderBy: { dayNumber: 'asc' },
      },
      flights: { orderBy: { departureTime: 'asc' } },
      documents: { orderBy: { createdAt: 'asc' } },
      mapPoints: { orderBy: { dayNumber: 'asc' } },
    },
  });

  if (!itinerary) {
    return NextResponse.json(null, { status: 200 });
  }

  return NextResponse.json(itinerary);
}
