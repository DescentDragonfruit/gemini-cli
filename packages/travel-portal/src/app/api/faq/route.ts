import { NextResponse } from 'next/server';
import { prisma } from '@/lib/prisma';

export async function GET() {
  const faqs = await prisma.fAQ.findMany({
    orderBy: [{ category: 'asc' }, { order: 'asc' }],
  });
  return NextResponse.json(faqs);
}
