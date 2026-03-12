import { NextRequest, NextResponse } from 'next/server';
import { auth } from '@/lib/auth';
import { prisma } from '@/lib/prisma';
import { hash } from 'bcryptjs';
import { z } from 'zod';

const createClientSchema = z.object({
  email: z.string().email(),
  password: z.string().min(8),
  firstName: z.string().min(1),
  lastName: z.string().min(1),
  phone: z.string().optional(),
  nationality: z.string().optional(),
  passportNumber: z.string().optional(),
  passportExpiry: z.string().optional(),
});

export async function GET() {
  const session = await auth();
  if (!session?.user || session.user.role !== 'ADMIN') {
    return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
  }

  const clients = await prisma.client.findMany({
    include: {
      user: { select: { email: true } },
      itinerary: { select: { title: true, startDate: true } },
      invoices: { select: { status: true, amount: true } },
    },
    orderBy: { createdAt: 'desc' },
  });

  return NextResponse.json(clients);
}

export async function POST(req: NextRequest) {
  const session = await auth();
  if (!session?.user || session.user.role !== 'ADMIN') {
    return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
  }

  const body = await req.json();
  const parsed = createClientSchema.safeParse(body);

  if (!parsed.success) {
    return NextResponse.json(
      { error: parsed.error.errors[0]?.message ?? 'Invalid input' },
      { status: 400 },
    );
  }

  const { email, password, firstName, lastName, phone, nationality, passportNumber, passportExpiry } = parsed.data;

  const existing = await prisma.user.findUnique({ where: { email } });
  if (existing) {
    return NextResponse.json({ error: 'Email already in use' }, { status: 400 });
  }

  const hashedPassword = await hash(password, 12);

  const client = await prisma.client.create({
    data: {
      firstName,
      lastName,
      phone: phone || null,
      nationality: nationality || null,
      passportNumber: passportNumber || null,
      passportExpiry: passportExpiry ? new Date(passportExpiry) : null,
      user: {
        create: {
          email,
          password: hashedPassword,
          role: 'CLIENT',
        },
      },
    },
  });

  return NextResponse.json(client, { status: 201 });
}
