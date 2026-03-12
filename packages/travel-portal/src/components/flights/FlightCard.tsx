'use client';

import { useState, useEffect } from 'react';

interface Flight {
  id: string;
  flightNumber: string;
  airline: string;
  origin: string;
  originCode: string;
  destination: string;
  destinationCode: string;
  departureTime: string;
  arrivalTime: string;
  status: string | null;
  terminal: string | null;
  gate: string | null;
}

interface LiveStatus {
  status: string;
  actualDeparture?: string;
  actualArrival?: string;
  lastUpdated: string;
}

export function FlightCard({ flight }: { flight: Flight }) {
  const [liveStatus, setLiveStatus] = useState<LiveStatus | null>(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    fetchLiveStatus();
  }, [flight.id]);

  async function fetchLiveStatus() {
    setLoading(true);
    try {
      const res = await fetch(`/api/flights/${encodeURIComponent(flight.flightNumber)}`);
      if (res.ok) {
        const data = await res.json();
        setLiveStatus(data);
      }
    } catch {
      // ignore
    } finally {
      setLoading(false);
    }
  }

  const effectiveStatus = liveStatus?.status ?? flight.status ?? 'UNKNOWN';

  const statusConfig: Record<string, { label: string; bg: string; text: string; dot: string }> = {
    ON_TIME: { label: 'On Time', bg: 'bg-green-50', text: 'text-green-700', dot: 'bg-green-500' },
    LANDED: { label: 'Landed', bg: 'bg-blue-50', text: 'text-blue-700', dot: 'bg-blue-500' },
    DELAYED: { label: 'Delayed', bg: 'bg-amber-50', text: 'text-amber-700', dot: 'bg-amber-500' },
    CANCELLED: { label: 'Cancelled', bg: 'bg-red-50', text: 'text-red-700', dot: 'bg-red-500' },
    UNKNOWN: { label: 'Checking...', bg: 'bg-gray-50', text: 'text-gray-600', dot: 'bg-gray-400' },
  };

  const status = statusConfig[effectiveStatus] ?? statusConfig.UNKNOWN;
  const departureTime = new Date(flight.departureTime);
  const arrivalTime = new Date(flight.arrivalTime);

  return (
    <div className="bg-white rounded-2xl border border-gray-200 p-5 hover:shadow-sm transition">
      {/* Header */}
      <div className="flex items-start justify-between mb-4">
        <div>
          <div className="flex items-center gap-2">
            <span className="font-bold text-gray-900 text-lg">{flight.flightNumber}</span>
            <span className="text-sm text-gray-500">{flight.airline}</span>
          </div>
        </div>
        <div className={`flex items-center gap-1.5 px-2.5 py-1 rounded-full ${status.bg}`}>
          {loading ? (
            <svg className="w-3 h-3 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
              <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
              <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
          ) : (
            <span className={`w-2 h-2 rounded-full ${status.dot}`} />
          )}
          <span className={`text-xs font-semibold ${status.text}`}>{status.label}</span>
        </div>
      </div>

      {/* Route */}
      <div className="flex items-center gap-3">
        {/* Origin */}
        <div className="flex-1 text-center">
          <p className="text-2xl font-bold text-gray-900">{flight.originCode}</p>
          <p className="text-xs text-gray-500 mt-0.5 truncate">{flight.origin}</p>
          <p className="text-sm font-semibold text-gray-700 mt-1">
            {departureTime.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}
          </p>
          <p className="text-xs text-gray-400">
            {departureTime.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}
          </p>
        </div>

        {/* Flight path */}
        <div className="flex-shrink-0 flex flex-col items-center">
          <svg className="w-6 h-6 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
          </svg>
          <div className="w-16 h-px bg-gray-300 my-1" />
          <span className="text-xs text-gray-400">
            {Math.round((arrivalTime.getTime() - departureTime.getTime()) / (60 * 60 * 1000))}h{' '}
            {Math.round(((arrivalTime.getTime() - departureTime.getTime()) % (60 * 60 * 1000)) / 60000)}m
          </span>
        </div>

        {/* Destination */}
        <div className="flex-1 text-center">
          <p className="text-2xl font-bold text-gray-900">{flight.destinationCode}</p>
          <p className="text-xs text-gray-500 mt-0.5 truncate">{flight.destination}</p>
          <p className="text-sm font-semibold text-gray-700 mt-1">
            {arrivalTime.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}
          </p>
          <p className="text-xs text-gray-400">
            {arrivalTime.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}
          </p>
        </div>
      </div>

      {/* Terminal / Gate */}
      {(flight.terminal ?? flight.gate) && (
        <div className="flex gap-3 mt-4 pt-4 border-t border-gray-100">
          {flight.terminal && (
            <div className="text-xs">
              <span className="text-gray-400">Terminal </span>
              <span className="font-semibold text-gray-700">{flight.terminal}</span>
            </div>
          )}
          {flight.gate && (
            <div className="text-xs">
              <span className="text-gray-400">Gate </span>
              <span className="font-semibold text-gray-700">{flight.gate}</span>
            </div>
          )}
        </div>
      )}

      {/* Live update info */}
      {liveStatus && (
        <div className="mt-3 text-xs text-gray-400">
          Live data updated {new Date(liveStatus.lastUpdated).toLocaleTimeString()}
          <button onClick={fetchLiveStatus} className="ml-2 text-brand-500 hover:underline">
            Refresh
          </button>
        </div>
      )}
    </div>
  );
}
