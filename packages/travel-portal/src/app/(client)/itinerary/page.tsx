'use client';

import { useEffect, useState } from 'react';
import dynamic from 'next/dynamic';
import { FlightCard } from '@/components/flights/FlightCard';

const ItineraryMap = dynamic(() => import('@/components/map/ItineraryMap'), {
  ssr: false,
  loading: () => (
    <div className="h-96 bg-gray-100 rounded-xl animate-pulse flex items-center justify-center text-gray-400">
      Loading map...
    </div>
  ),
});

interface Activity {
  id: string;
  name: string;
  description: string | null;
  time: string | null;
  location: string | null;
  type: string;
}

interface ItineraryDay {
  id: string;
  dayNumber: number;
  date: string;
  title: string;
  description: string | null;
  location: string | null;
  activities: Activity[];
}

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

interface Document {
  id: string;
  name: string;
  type: string;
  required: boolean;
  status: string;
  notes: string | null;
  dueDate: string | null;
}

interface MapPoint {
  id: string;
  name: string;
  lat: number;
  lng: number;
  type: string;
  dayNumber: number | null;
  description: string | null;
}

interface ItineraryData {
  id: string;
  title: string;
  description: string | null;
  destination: string | null;
  startDate: string;
  endDate: string;
  days: ItineraryDay[];
  flights: Flight[];
  documents: Document[];
  mapPoints: MapPoint[];
}

const activityTypeIcons: Record<string, string> = {
  SIGHTSEEING: '🏛️',
  DINING: '🍽️',
  TRANSPORT: '🚗',
  ACCOMMODATION: '🏨',
  EXCURSION: '🥾',
  FREE_TIME: '🎭',
};

const docStatusConfig: Record<string, { label: string; className: string }> = {
  PENDING: { label: 'Pending', className: 'bg-amber-100 text-amber-700' },
  SUBMITTED: { label: 'Submitted', className: 'bg-blue-100 text-blue-700' },
  APPROVED: { label: 'Approved', className: 'bg-green-100 text-green-700' },
  REJECTED: { label: 'Rejected', className: 'bg-red-100 text-red-700' },
};

type Tab = 'map' | 'days' | 'flights' | 'documents';

export default function ItineraryPage() {
  const [itinerary, setItinerary] = useState<ItineraryData | null>(null);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState<Tab>('map');
  const [selectedDay, setSelectedDay] = useState<number | null>(null);
  const [expandedDay, setExpandedDay] = useState<string | null>(null);

  useEffect(() => {
    fetch('/api/itinerary')
      .then((r) => r.json())
      .then((data) => {
        setItinerary(data);
        if (data?.days?.length > 0) {
          setExpandedDay(data.days[0].id);
        }
      })
      .catch(console.error)
      .finally(() => setLoading(false));
  }, []);

  if (loading) {
    return (
      <div className="max-w-6xl mx-auto space-y-6 animate-pulse">
        <div className="h-8 bg-gray-200 rounded w-64" />
        <div className="h-96 bg-gray-200 rounded-2xl" />
      </div>
    );
  }

  if (!itinerary) {
    return (
      <div className="max-w-6xl mx-auto text-center py-20 text-gray-400">
        <svg className="w-14 h-14 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
        <p className="text-lg">Your itinerary hasn't been set up yet.</p>
        <p className="text-sm mt-1">Please contact your travel agent.</p>
      </div>
    );
  }

  const tabs: { id: Tab; label: string; count?: number }[] = [
    { id: 'map', label: 'Map' },
    { id: 'days', label: 'Day by Day', count: itinerary.days.length },
    { id: 'flights', label: 'Flights', count: itinerary.flights.length },
    { id: 'documents', label: 'Documents', count: itinerary.documents.length },
  ];

  const pendingDocs = itinerary.documents.filter((d) => d.status === 'PENDING' || d.status === 'REJECTED');

  return (
    <div className="max-w-6xl mx-auto space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900">{itinerary.title}</h1>
        <p className="text-gray-500 mt-0.5">
          {new Date(itinerary.startDate).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })} –{' '}
          {new Date(itinerary.endDate).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })}
          {itinerary.destination && ` · ${itinerary.destination}`}
        </p>
      </div>

      {/* Tabs */}
      <div className="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <div className="flex border-b border-gray-100 overflow-x-auto">
          {tabs.map((tab) => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id)}
              className={`flex items-center gap-1.5 px-6 py-4 text-sm font-medium whitespace-nowrap transition border-b-2 ${
                activeTab === tab.id
                  ? 'border-brand-500 text-brand-600 bg-brand-50/50'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'
              }`}
            >
              {tab.label}
              {tab.count !== undefined && (
                <span className={`px-1.5 py-0.5 rounded-full text-xs ${
                  activeTab === tab.id ? 'bg-brand-100 text-brand-600' : 'bg-gray-100 text-gray-500'
                }`}>
                  {tab.count}
                </span>
              )}
              {tab.id === 'documents' && pendingDocs.length > 0 && (
                <span className="w-2 h-2 bg-orange-500 rounded-full" />
              )}
            </button>
          ))}
        </div>

        <div className="p-6">
          {/* MAP TAB */}
          {activeTab === 'map' && (
            <div className="space-y-4">
              {/* Day filter */}
              {itinerary.days.length > 0 && (
                <div className="flex items-center gap-2 flex-wrap">
                  <button
                    onClick={() => setSelectedDay(null)}
                    className={`px-3 py-1.5 rounded-lg text-sm font-medium transition ${
                      selectedDay === null ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                    }`}
                  >
                    All Days
                  </button>
                  {itinerary.days.map((day) => (
                    <button
                      key={day.id}
                      onClick={() => setSelectedDay(day.dayNumber)}
                      className={`px-3 py-1.5 rounded-lg text-sm font-medium transition ${
                        selectedDay === day.dayNumber ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                      }`}
                    >
                      Day {day.dayNumber}
                    </button>
                  ))}
                </div>
              )}
              <ItineraryMap
                points={itinerary.mapPoints}
                selectedDay={selectedDay}
                height="480px"
              />
              {/* Legend */}
              <div className="flex items-center gap-4 text-xs text-gray-500 flex-wrap">
                <span className="flex items-center gap-1"><span className="w-3 h-3 rounded-full bg-amber-400 inline-block" /> Hotel</span>
                <span className="flex items-center gap-1"><span className="w-3 h-3 rounded-full bg-indigo-500 inline-block" /> Airport</span>
                <span className="flex items-center gap-1"><span className="w-3 h-3 rounded-full bg-sky-500 inline-block" /> Activity</span>
                <span className="flex items-center gap-1"><span className="w-3 h-3 rounded-full bg-emerald-500 inline-block" /> Restaurant</span>
              </div>
            </div>
          )}

          {/* DAYS TAB */}
          {activeTab === 'days' && (
            <div className="space-y-3">
              {itinerary.days.length === 0 ? (
                <p className="text-gray-400 text-center py-10">No days added to itinerary yet.</p>
              ) : (
                itinerary.days
                  .sort((a, b) => a.dayNumber - b.dayNumber)
                  .map((day) => (
                    <div key={day.id} className="border border-gray-200 rounded-xl overflow-hidden">
                      <button
                        onClick={() => setExpandedDay(expandedDay === day.id ? null : day.id)}
                        className="w-full flex items-center justify-between px-5 py-4 hover:bg-gray-50 transition text-left"
                      >
                        <div className="flex items-center gap-4">
                          <div className="w-10 h-10 bg-brand-600 rounded-xl flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                            {day.dayNumber}
                          </div>
                          <div>
                            <p className="font-semibold text-gray-900">{day.title}</p>
                            <p className="text-sm text-gray-500">
                              {new Date(day.date).toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' })}
                              {day.location && ` · ${day.location}`}
                            </p>
                          </div>
                        </div>
                        <div className="flex items-center gap-2">
                          <span className="text-xs text-gray-400">{day.activities.length} activities</span>
                          <svg
                            className={`w-5 h-5 text-gray-400 transition-transform ${expandedDay === day.id ? 'rotate-180' : ''}`}
                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                          >
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                          </svg>
                        </div>
                      </button>

                      {expandedDay === day.id && (
                        <div className="px-5 pb-5 border-t border-gray-100 bg-gray-50/50">
                          {day.description && (
                            <p className="text-sm text-gray-600 mt-4 mb-4">{day.description}</p>
                          )}
                          {day.activities.length === 0 ? (
                            <p className="text-gray-400 text-sm py-4">No activities scheduled.</p>
                          ) : (
                            <div className="space-y-2 mt-4">
                              {day.activities.map((activity) => (
                                <div key={activity.id} className="flex items-start gap-3 bg-white rounded-xl p-3 border border-gray-100">
                                  <span className="text-xl">{activityTypeIcons[activity.type] ?? '📍'}</span>
                                  <div className="flex-1 min-w-0">
                                    <div className="flex items-center gap-2">
                                      <p className="font-medium text-gray-900 text-sm">{activity.name}</p>
                                      {activity.time && (
                                        <span className="text-xs text-gray-400">{activity.time}</span>
                                      )}
                                    </div>
                                    {activity.description && (
                                      <p className="text-xs text-gray-500 mt-0.5">{activity.description}</p>
                                    )}
                                    {activity.location && (
                                      <p className="text-xs text-gray-400 mt-0.5">📍 {activity.location}</p>
                                    )}
                                  </div>
                                </div>
                              ))}
                            </div>
                          )}
                        </div>
                      )}
                    </div>
                  ))
              )}
            </div>
          )}

          {/* FLIGHTS TAB */}
          {activeTab === 'flights' && (
            <div className="space-y-4">
              {itinerary.flights.length === 0 ? (
                <p className="text-gray-400 text-center py-10">No flights added yet.</p>
              ) : (
                itinerary.flights
                  .sort((a, b) => new Date(a.departureTime).getTime() - new Date(b.departureTime).getTime())
                  .map((flight) => (
                    <FlightCard key={flight.id} flight={flight} />
                  ))
              )}
            </div>
          )}

          {/* DOCUMENTS TAB */}
          {activeTab === 'documents' && (
            <div className="space-y-3">
              {itinerary.documents.length === 0 ? (
                <p className="text-gray-400 text-center py-10">No documents required.</p>
              ) : (
                <>
                  {pendingDocs.length > 0 && (
                    <div className="bg-orange-50 border border-orange-200 rounded-xl p-4 flex items-start gap-3">
                      <svg className="w-5 h-5 text-orange-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                      </svg>
                      <p className="text-sm text-orange-700">
                        <strong>{pendingDocs.length} document{pendingDocs.length !== 1 ? 's' : ''}</strong> still need your attention. Please contact your travel agent.
                      </p>
                    </div>
                  )}
                  {itinerary.documents.map((doc) => {
                    const statusConf = docStatusConfig[doc.status] ?? { label: doc.status, className: 'bg-gray-100 text-gray-600' };
                    const isOverdue = doc.dueDate && new Date(doc.dueDate) < new Date() && doc.status === 'PENDING';
                    return (
                      <div key={doc.id} className={`bg-white rounded-xl border p-4 flex items-start gap-4 ${isOverdue ? 'border-red-200' : 'border-gray-200'}`}>
                        <div className={`w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 ${
                          doc.status === 'APPROVED' ? 'bg-green-100' : isOverdue ? 'bg-red-100' : 'bg-gray-100'
                        }`}>
                          {doc.status === 'APPROVED' ? (
                            <svg className="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                              <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                            </svg>
                          ) : (
                            <svg className={`w-5 h-5 ${isOverdue ? 'text-red-400' : 'text-gray-400'}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                          )}
                        </div>
                        <div className="flex-1 min-w-0">
                          <div className="flex items-center justify-between gap-2 flex-wrap">
                            <p className="font-semibold text-gray-900 text-sm">{doc.name}</p>
                            <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${statusConf.className}`}>
                              {statusConf.label}
                            </span>
                          </div>
                          <p className="text-xs text-gray-500 mt-0.5">{doc.type.replace('_', ' ')}</p>
                          {doc.notes && <p className="text-xs text-gray-500 mt-1">{doc.notes}</p>}
                          {doc.dueDate && (
                            <p className={`text-xs mt-1 font-medium ${isOverdue ? 'text-red-600' : 'text-gray-500'}`}>
                              {isOverdue ? 'Overdue: ' : 'Due: '}
                              {new Date(doc.dueDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                            </p>
                          )}
                        </div>
                        {!doc.required && (
                          <span className="text-xs text-gray-400 flex-shrink-0">Optional</span>
                        )}
                      </div>
                    );
                  })}
                </>
              )}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
