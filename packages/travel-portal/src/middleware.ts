import { auth } from '@/lib/auth';
import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

export default auth((req: NextRequest & { auth?: { user?: { role?: string } } | null }) => {
  const { pathname } = req.nextUrl;
  const session = req.auth;

  // Public paths
  if (pathname.startsWith('/login') || pathname.startsWith('/api/auth')) {
    if (session?.user) {
      const role = session.user.role;
      return NextResponse.redirect(
        new URL(role === 'ADMIN' ? '/admin' : '/dashboard', req.url),
      );
    }
    return NextResponse.next();
  }

  // Protected: require auth
  if (!session?.user) {
    return NextResponse.redirect(new URL('/login', req.url));
  }

  const role = session.user.role;

  // Admin routes require ADMIN role
  if (pathname.startsWith('/admin') && role !== 'ADMIN') {
    return NextResponse.redirect(new URL('/dashboard', req.url));
  }

  // Client routes require CLIENT role
  if (
    (pathname.startsWith('/dashboard') ||
      pathname.startsWith('/itinerary') ||
      pathname.startsWith('/invoices') ||
      pathname.startsWith('/documents') ||
      pathname.startsWith('/guide') ||
      pathname.startsWith('/faq')) &&
    role !== 'CLIENT'
  ) {
    return NextResponse.redirect(new URL('/admin', req.url));
  }

  return NextResponse.next();
});

export const config = {
  matcher: [
    '/((?!_next/static|_next/image|favicon.ico|.*\\.png|.*\\.jpg|.*\\.svg).*)',
  ],
};
