import { NextRequest, NextResponse } from 'next/server';
import { auth } from '@/lib/auth';
import { getFlightStatus } from '@/lib/opensky';
import { prisma } from '@/lib/prisma';

export async function GET(
  req: NextRequest,
  { params }: { params: Promise<{ flightNumber: string }> },
) {
  const session = await auth();
  if (!session?.user) {
    return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
  }

  const { flightNumber } = await params;
  const decodedFlightNumber = decodeURIComponent(flightNumber);

  // Find stored flight data for additional context
  let icao24: string | null = null;
  let departureTime: Date | undefined;

  if (session.user.clientId) {
    const flight = await prisma.flight.findFirst({
      where: {
        flightNumber: decodedFlightNumber,
        itinerary: { clientId: session.user.clientId },
      },
    });
    if (flight) {
      icao24 = flight.icao24;
      departureTime = flight.departureTime;
    }
  }

  const status = await getFlightStatus(decodedFlightNumber, icao24, departureTime);
  return NextResponse.json(status);
}
