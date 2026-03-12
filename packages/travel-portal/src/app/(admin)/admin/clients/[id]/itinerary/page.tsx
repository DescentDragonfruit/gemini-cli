'use client';

import { useState, useEffect, use } from 'react';
import Link from 'next/link';

interface ItineraryData {
  id?: string;
  title: string;
  description: string;
  destination: string;
  startDate: string;
  endDate: string;
}

interface TimelineStep {
  id?: string;
  title: string;
  description: string;
  dueDate: string;
  order: number;
}

interface InvoiceItem {
  description: string;
  amount: string;
  quantity: string;
}

interface InvoiceData {
  invoiceNumber: string;
  amount: string;
  currency: string;
  dueDate: string;
  description: string;
  items: InvoiceItem[];
}

interface FlightData {
  flightNumber: string;
  airline: string;
  origin: string;
  originCode: string;
  destination: string;
  destinationCode: string;
  departureTime: string;
  arrivalTime: string;
  terminal: string;
  gate: string;
}

type ActiveSection = 'itinerary' | 'timeline' | 'invoice' | 'flight';

export default function AdminItineraryPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id: clientId } = use(params);
  const [activeSection, setActiveSection] = useState<ActiveSection>('itinerary');
  const [saving, setSaving] = useState(false);
  const [success, setSuccess] = useState('');
  const [error, setError] = useState('');

  const [itinerary, setItinerary] = useState<ItineraryData>({
    title: '',
    description: '',
    destination: '',
    startDate: '',
    endDate: '',
  });

  const [timelineStep, setTimelineStep] = useState<TimelineStep>({
    title: '',
    description: '',
    dueDate: '',
    order: 0,
  });

  const [invoice, setInvoice] = useState<InvoiceData>({
    invoiceNumber: `INV-${Date.now()}`,
    amount: '',
    currency: 'USD',
    dueDate: '',
    description: '',
    items: [{ description: '', amount: '', quantity: '1' }],
  });

  const [flight, setFlight] = useState<FlightData>({
    flightNumber: '',
    airline: '',
    origin: '',
    originCode: '',
    destination: '',
    destinationCode: '',
    departureTime: '',
    arrivalTime: '',
    terminal: '',
    gate: '',
  });

  useEffect(() => {
    // Load existing itinerary if any
    fetch(`/api/admin/clients/${clientId}`)
      .then((r) => r.json())
      .then((data) => {
        if (data.itinerary) {
          const it = data.itinerary;
          setItinerary({
            id: it.id,
            title: it.title ?? '',
            description: it.description ?? '',
            destination: it.destination ?? '',
            startDate: it.startDate ? new Date(it.startDate).toISOString().split('T')[0] : '',
            endDate: it.endDate ? new Date(it.endDate).toISOString().split('T')[0] : '',
          });
        }
      })
      .catch(console.error);
  }, [clientId]);

  function showSuccess(msg: string) {
    setSuccess(msg);
    setError('');
    setTimeout(() => setSuccess(''), 3000);
  }

  async function saveItinerary() {
    setSaving(true);
    try {
      const res = await fetch(`/api/admin/clients/${clientId}/itinerary`, {
        method: itinerary.id ? 'PATCH' : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(itinerary),
      });
      if (!res.ok) {
        const e = await res.json();
        setError(e.error ?? 'Failed to save itinerary');
      } else {
        const data = await res.json();
        setItinerary((prev) => ({ ...prev, id: data.id }));
        showSuccess('Itinerary saved!');
      }
    } catch {
      setError('An error occurred');
    } finally {
      setSaving(false);
    }
  }

  async function saveTimelineStep() {
    setSaving(true);
    try {
      const res = await fetch(`/api/admin/clients/${clientId}/timeline`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(timelineStep),
      });
      if (!res.ok) {
        const e = await res.json();
        setError(e.error ?? 'Failed to save step');
      } else {
        setTimelineStep({ title: '', description: '', dueDate: '', order: 0 });
        showSuccess('Timeline step added!');
      }
    } catch {
      setError('An error occurred');
    } finally {
      setSaving(false);
    }
  }

  async function saveInvoice() {
    setSaving(true);
    try {
      const res = await fetch(`/api/admin/clients/${clientId}/invoices`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(invoice),
      });
      if (!res.ok) {
        const e = await res.json();
        setError(e.error ?? 'Failed to save invoice');
      } else {
        setInvoice({
          invoiceNumber: `INV-${Date.now()}`,
          amount: '',
          currency: 'USD',
          dueDate: '',
          description: '',
          items: [{ description: '', amount: '', quantity: '1' }],
        });
        showSuccess('Invoice created!');
      }
    } catch {
      setError('An error occurred');
    } finally {
      setSaving(false);
    }
  }

  async function saveFlight() {
    if (!itinerary.id) {
      setError('Please save the itinerary first');
      return;
    }
    setSaving(true);
    try {
      const res = await fetch(`/api/admin/clients/${clientId}/flights`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(flight),
      });
      if (!res.ok) {
        const e = await res.json();
        setError(e.error ?? 'Failed to save flight');
      } else {
        setFlight({
          flightNumber: '',
          airline: '',
          origin: '',
          originCode: '',
          destination: '',
          destinationCode: '',
          departureTime: '',
          arrivalTime: '',
          terminal: '',
          gate: '',
        });
        showSuccess('Flight added!');
      }
    } catch {
      setError('An error occurred');
    } finally {
      setSaving(false);
    }
  }

  const sections: { id: ActiveSection; label: string }[] = [
    { id: 'itinerary', label: 'Itinerary Details' },
    { id: 'timeline', label: 'Add Timeline Step' },
    { id: 'invoice', label: 'Create Invoice' },
    { id: 'flight', label: 'Add Flight' },
  ];

  const inputClass = 'w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500';
  const labelClass = 'block text-sm font-medium text-gray-700 mb-1.5';

  return (
    <div className="max-w-3xl mx-auto space-y-6">
      <div className="flex items-center gap-3">
        <Link href={`/admin/clients/${clientId}`} className="text-gray-400 hover:text-gray-600 transition">
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
          </svg>
        </Link>
        <h1 className="text-2xl font-bold text-gray-900">Manage Client Itinerary</h1>
      </div>

      {/* Section tabs */}
      <div className="flex gap-2 flex-wrap">
        {sections.map((s) => (
          <button
            key={s.id}
            onClick={() => { setActiveSection(s.id); setError(''); setSuccess(''); }}
            className={`px-4 py-2 rounded-xl text-sm font-medium transition ${
              activeSection === s.id
                ? 'bg-brand-600 text-white'
                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
            }`}
          >
            {s.label}
          </button>
        ))}
      </div>

      {/* Messages */}
      {success && (
        <div className="flex items-center gap-2 p-3 bg-green-50 border border-green-200 rounded-xl text-green-700 text-sm">
          <svg className="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
          </svg>
          {success}
        </div>
      )}
      {error && (
        <div className="p-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
          {error}
        </div>
      )}

      {/* Itinerary form */}
      {activeSection === 'itinerary' && (
        <div className="bg-white rounded-2xl border border-gray-200 p-6 space-y-4">
          <h2 className="font-semibold text-gray-900">Trip Details</h2>
          <div>
            <label className={labelClass}>Trip Title *</label>
            <input className={inputClass} value={itinerary.title} onChange={(e) => setItinerary({ ...itinerary, title: e.target.value })} placeholder="e.g. European Adventure" />
          </div>
          <div>
            <label className={labelClass}>Destination</label>
            <input className={inputClass} value={itinerary.destination} onChange={(e) => setItinerary({ ...itinerary, destination: e.target.value })} placeholder="e.g. Paris, Rome, Barcelona" />
          </div>
          <div>
            <label className={labelClass}>Description</label>
            <textarea className={inputClass} rows={3} value={itinerary.description} onChange={(e) => setItinerary({ ...itinerary, description: e.target.value })} placeholder="Brief description of the trip..." />
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className={labelClass}>Start Date *</label>
              <input type="date" className={inputClass} value={itinerary.startDate} onChange={(e) => setItinerary({ ...itinerary, startDate: e.target.value })} />
            </div>
            <div>
              <label className={labelClass}>End Date *</label>
              <input type="date" className={inputClass} value={itinerary.endDate} onChange={(e) => setItinerary({ ...itinerary, endDate: e.target.value })} />
            </div>
          </div>
          <button onClick={saveItinerary} disabled={saving || !itinerary.title || !itinerary.startDate || !itinerary.endDate} className="bg-brand-600 hover:bg-brand-700 disabled:bg-brand-400 text-white font-semibold px-6 py-2.5 rounded-xl transition text-sm">
            {saving ? 'Saving...' : itinerary.id ? 'Update Itinerary' : 'Create Itinerary'}
          </button>
        </div>
      )}

      {/* Timeline step form */}
      {activeSection === 'timeline' && (
        <div className="bg-white rounded-2xl border border-gray-200 p-6 space-y-4">
          <h2 className="font-semibold text-gray-900">Add Timeline Step</h2>
          <div>
            <label className={labelClass}>Step Title *</label>
            <input className={inputClass} value={timelineStep.title} onChange={(e) => setTimelineStep({ ...timelineStep, title: e.target.value })} placeholder="e.g. Submit visa application" />
          </div>
          <div>
            <label className={labelClass}>Description</label>
            <textarea className={inputClass} rows={2} value={timelineStep.description} onChange={(e) => setTimelineStep({ ...timelineStep, description: e.target.value })} placeholder="Optional details..." />
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className={labelClass}>Due Date *</label>
              <input type="date" className={inputClass} value={timelineStep.dueDate} onChange={(e) => setTimelineStep({ ...timelineStep, dueDate: e.target.value })} />
            </div>
            <div>
              <label className={labelClass}>Order (position)</label>
              <input type="number" className={inputClass} value={timelineStep.order} onChange={(e) => setTimelineStep({ ...timelineStep, order: parseInt(e.target.value) || 0 })} />
            </div>
          </div>
          <button onClick={saveTimelineStep} disabled={saving || !timelineStep.title || !timelineStep.dueDate} className="bg-brand-600 hover:bg-brand-700 disabled:bg-brand-400 text-white font-semibold px-6 py-2.5 rounded-xl transition text-sm">
            {saving ? 'Saving...' : 'Add Step'}
          </button>
        </div>
      )}

      {/* Invoice form */}
      {activeSection === 'invoice' && (
        <div className="bg-white rounded-2xl border border-gray-200 p-6 space-y-4">
          <h2 className="font-semibold text-gray-900">Create Invoice</h2>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className={labelClass}>Invoice Number *</label>
              <input className={inputClass} value={invoice.invoiceNumber} onChange={(e) => setInvoice({ ...invoice, invoiceNumber: e.target.value })} />
            </div>
            <div>
              <label className={labelClass}>Due Date *</label>
              <input type="date" className={inputClass} value={invoice.dueDate} onChange={(e) => setInvoice({ ...invoice, dueDate: e.target.value })} />
            </div>
          </div>
          <div>
            <label className={labelClass}>Description</label>
            <input className={inputClass} value={invoice.description} onChange={(e) => setInvoice({ ...invoice, description: e.target.value })} placeholder="e.g. Trip deposit" />
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className={labelClass}>Total Amount *</label>
              <input type="number" step="0.01" className={inputClass} value={invoice.amount} onChange={(e) => setInvoice({ ...invoice, amount: e.target.value })} placeholder="0.00" />
            </div>
            <div>
              <label className={labelClass}>Currency</label>
              <select className={inputClass} value={invoice.currency} onChange={(e) => setInvoice({ ...invoice, currency: e.target.value })}>
                <option>USD</option>
                <option>EUR</option>
                <option>GBP</option>
                <option>AUD</option>
                <option>CAD</option>
              </select>
            </div>
          </div>
          <button onClick={saveInvoice} disabled={saving || !invoice.invoiceNumber || !invoice.amount || !invoice.dueDate} className="bg-brand-600 hover:bg-brand-700 disabled:bg-brand-400 text-white font-semibold px-6 py-2.5 rounded-xl transition text-sm">
            {saving ? 'Saving...' : 'Create Invoice'}
          </button>
        </div>
      )}

      {/* Flight form */}
      {activeSection === 'flight' && (
        <div className="bg-white rounded-2xl border border-gray-200 p-6 space-y-4">
          <h2 className="font-semibold text-gray-900">Add Flight</h2>
          {!itinerary.id && (
            <div className="p-3 bg-amber-50 border border-amber-200 rounded-xl text-amber-700 text-sm">
              Save the itinerary details first before adding flights.
            </div>
          )}
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className={labelClass}>Flight Number *</label>
              <input className={inputClass} value={flight.flightNumber} onChange={(e) => setFlight({ ...flight, flightNumber: e.target.value })} placeholder="e.g. AA123" />
            </div>
            <div>
              <label className={labelClass}>Airline *</label>
              <input className={inputClass} value={flight.airline} onChange={(e) => setFlight({ ...flight, airline: e.target.value })} placeholder="e.g. American Airlines" />
            </div>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className={labelClass}>Origin City *</label>
              <input className={inputClass} value={flight.origin} onChange={(e) => setFlight({ ...flight, origin: e.target.value })} placeholder="e.g. New York" />
            </div>
            <div>
              <label className={labelClass}>Origin Code (IATA) *</label>
              <input className={inputClass} value={flight.originCode} onChange={(e) => setFlight({ ...flight, originCode: e.target.value.toUpperCase() })} placeholder="JFK" maxLength={4} />
            </div>
            <div>
              <label className={labelClass}>Destination City *</label>
              <input className={inputClass} value={flight.destination} onChange={(e) => setFlight({ ...flight, destination: e.target.value })} placeholder="e.g. London" />
            </div>
            <div>
              <label className={labelClass}>Destination Code (IATA) *</label>
              <input className={inputClass} value={flight.destinationCode} onChange={(e) => setFlight({ ...flight, destinationCode: e.target.value.toUpperCase() })} placeholder="LHR" maxLength={4} />
            </div>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className={labelClass}>Departure Time *</label>
              <input type="datetime-local" className={inputClass} value={flight.departureTime} onChange={(e) => setFlight({ ...flight, departureTime: e.target.value })} />
            </div>
            <div>
              <label className={labelClass}>Arrival Time *</label>
              <input type="datetime-local" className={inputClass} value={flight.arrivalTime} onChange={(e) => setFlight({ ...flight, arrivalTime: e.target.value })} />
            </div>
            <div>
              <label className={labelClass}>Terminal</label>
              <input className={inputClass} value={flight.terminal} onChange={(e) => setFlight({ ...flight, terminal: e.target.value })} placeholder="e.g. T1" />
            </div>
            <div>
              <label className={labelClass}>Gate</label>
              <input className={inputClass} value={flight.gate} onChange={(e) => setFlight({ ...flight, gate: e.target.value })} placeholder="e.g. B22" />
            </div>
          </div>
          <button onClick={saveFlight} disabled={saving || !flight.flightNumber || !flight.origin || !flight.departureTime} className="bg-brand-600 hover:bg-brand-700 disabled:bg-brand-400 text-white font-semibold px-6 py-2.5 rounded-xl transition text-sm">
            {saving ? 'Saving...' : 'Add Flight'}
          </button>
        </div>
      )}
    </div>
  );
}
