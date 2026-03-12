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

  const itinerary = await prisma.itinerary.findUnique({ where: { clientId } });
  if (!itinerary) {
    return NextResponse.json({ error: 'Itinerary not found. Create itinerary first.' }, { status: 404 });
  }

  const body = await req.json() as {
    flightNumber: string;
    airline: string;
    origin: string;
    originCode: string;
    destination: string;
    destinationCode: string;
    departureTime: string;
    arrivalTime: string;
    terminal?: string;
    gate?: string;
    icao24?: string;
  };

  const flight = await prisma.flight.create({
    data: {
      itineraryId: itinerary.id,
      flightNumber: body.flightNumber,
      airline: body.airline,
      origin: body.origin,
      originCode: body.originCode,
      destination: body.destination,
      destinationCode: body.destinationCode,
      departureTime: new Date(body.departureTime),
      arrivalTime: new Date(body.arrivalTime),
      terminal: body.terminal || null,
      gate: body.gate || null,
      icao24: body.icao24 || null,
    },
  });

  return NextResponse.json(flight, { status: 201 });
}
