'use client';

import { useState } from 'react';
import { loadStripe } from '@stripe/stripe-js';

const stripePromise = loadStripe(process.env.NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY!);

interface InvoiceItem {
  id: string;
  description: string;
  amount: number;
  quantity: number;
}

interface Invoice {
  id: string;
  invoiceNumber: string;
  amount: number;
  currency: string;
  status: string;
  dueDate: string;
  paidAt: string | null;
  createdAt: string;
  description: string | null;
  items: InvoiceItem[];
}

const statusConfig: Record<string, { label: string; className: string; bg: string }> = {
  PENDING: { label: 'Due', className: 'bg-amber-100 text-amber-700', bg: 'bg-amber-50 border-amber-200' },
  OVERDUE: { label: 'Overdue', className: 'bg-red-100 text-red-700', bg: 'bg-red-50 border-red-200' },
  PAID: { label: 'Paid', className: 'bg-green-100 text-green-700', bg: 'bg-white border-gray-200' },
  CANCELLED: { label: 'Cancelled', className: 'bg-gray-100 text-gray-500', bg: 'bg-gray-50 border-gray-200' },
};

export function InvoiceList({ invoices }: { invoices: Invoice[] }) {
  const [expandedId, setExpandedId] = useState<string | null>(null);
  const [paying, setPaying] = useState<string | null>(null);

  async function handlePay(invoice: Invoice) {
    setPaying(invoice.id);
    try {
      const res = await fetch('/api/stripe/checkout', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ invoiceId: invoice.id }),
      });

      if (!res.ok) {
        alert('Failed to create payment session. Please try again.');
        return;
      }

      const { clientSecret } = await res.json();
      const stripe = await stripePromise;

      if (!stripe || !clientSecret) {
        alert('Payment system unavailable. Please contact your agent.');
        return;
      }

      // Redirect to Stripe hosted payment page
      const { error } = await stripe.confirmPayment({
        clientSecret,
        confirmParams: {
          return_url: `${window.location.origin}/invoices?payment=success`,
        },
      });

      if (error) {
        alert(error.message ?? 'Payment failed. Please try again.');
      }
    } catch {
      alert('An error occurred. Please try again.');
    } finally {
      setPaying(null);
    }
  }

  if (invoices.length === 0) {
    return (
      <div className="text-center py-16 text-gray-400 bg-white rounded-2xl border border-gray-200">
        <svg className="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
        </svg>
        <p>No invoices yet</p>
      </div>
    );
  }

  return (
    <div className="space-y-3">
      {invoices.map((invoice) => {
        const conf = statusConfig[invoice.status] ?? statusConfig.PENDING;
        const isExpanded = expandedId === invoice.id;
        const dueDate = new Date(invoice.dueDate);
        const isOverdue = invoice.status === 'OVERDUE';

        return (
          <div key={invoice.id} className={`rounded-2xl border ${conf.bg}`}>
            <button
              onClick={() => setExpandedId(isExpanded ? null : invoice.id)}
              className="w-full flex items-center justify-between p-5 text-left hover:opacity-90 transition"
            >
              <div className="flex items-center gap-4">
                <div className={`w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 ${conf.className}`}>
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </div>
                <div>
                  <div className="flex items-center gap-2 flex-wrap">
                    <p className="font-semibold text-gray-900">{invoice.invoiceNumber}</p>
                    <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${conf.className}`}>
                      {conf.label}
                    </span>
                  </div>
                  <p className="text-sm text-gray-500 mt-0.5">
                    {invoice.description ?? `Invoice ${invoice.invoiceNumber}`}
                  </p>
                  <p className={`text-xs mt-0.5 ${isOverdue ? 'text-red-600 font-medium' : 'text-gray-400'}`}>
                    {invoice.status === 'PAID'
                      ? `Paid ${new Date(invoice.paidAt!).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`
                      : `Due ${dueDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`}
                  </p>
                </div>
              </div>
              <div className="flex items-center gap-3">
                <p className="text-xl font-bold text-gray-900">
                  {invoice.currency} {invoice.amount.toFixed(2)}
                </p>
                <svg
                  className={`w-5 h-5 text-gray-400 transition-transform ${isExpanded ? 'rotate-180' : ''}`}
                  fill="none" stroke="currentColor" viewBox="0 0 24 24"
                >
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                </svg>
              </div>
            </button>

            {isExpanded && (
              <div className="px-5 pb-5 border-t border-gray-100">
                {invoice.items.length > 0 && (
                  <div className="mt-4 space-y-2">
                    <p className="text-sm font-medium text-gray-700 mb-2">Invoice Items</p>
                    {invoice.items.map((item) => (
                      <div key={item.id} className="flex items-center justify-between text-sm">
                        <span className="text-gray-600">
                          {item.description}
                          {item.quantity > 1 && <span className="text-gray-400"> × {item.quantity}</span>}
                        </span>
                        <span className="font-medium text-gray-900">
                          {invoice.currency} {(item.amount * item.quantity).toFixed(2)}
                        </span>
                      </div>
                    ))}
                    <div className="flex items-center justify-between text-sm font-semibold pt-2 border-t border-gray-200">
                      <span>Total</span>
                      <span>{invoice.currency} {invoice.amount.toFixed(2)}</span>
                    </div>
                  </div>
                )}

                {(invoice.status === 'PENDING' || invoice.status === 'OVERDUE') && (
                  <button
                    onClick={() => handlePay(invoice)}
                    disabled={paying === invoice.id}
                    className="mt-4 flex items-center gap-2 bg-brand-600 hover:bg-brand-700 disabled:bg-brand-400 text-white font-semibold px-6 py-3 rounded-xl transition"
                  >
                    {paying === invoice.id ? (
                      <>
                        <svg className="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                          <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                          <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        Processing...
                      </>
                    ) : (
                      <>
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        Pay Now — {invoice.currency} {invoice.amount.toFixed(2)}
                      </>
                    )}
                  </button>
                )}
              </div>
            )}
          </div>
        );
      })}
    </div>
  );
}
