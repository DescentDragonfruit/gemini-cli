'use client';

import { useEffect } from 'react';
import { MapContainer, TileLayer, Marker, Popup, Polyline } from 'react-leaflet';
import type { LatLngTuple } from 'leaflet';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';

// Fix leaflet default icons
if (typeof window !== 'undefined') {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  delete (L.Icon.Default.prototype as any)._getIconUrl;
  L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
  });
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

interface ItineraryMapProps {
  points: MapPoint[];
  selectedDay?: number | null;
  height?: string;
}

const typeColors: Record<string, string> = {
  hotel: '#f59e0b',
  airport: '#6366f1',
  activity: '#0ea5e9',
  restaurant: '#10b981',
  default: '#6b7280',
};

function createColoredMarker(color: string) {
  return L.divIcon({
    className: '',
    html: `<div style="
      width: 28px; height: 28px;
      background: ${color};
      border: 3px solid white;
      border-radius: 50%;
      box-shadow: 0 2px 8px rgba(0,0,0,0.3);
      display: flex; align-items: center; justify-content: center;
    "></div>`,
    iconSize: [28, 28],
    iconAnchor: [14, 14],
    popupAnchor: [0, -14],
  });
}

export default function ItineraryMap({ points, selectedDay, height = '400px' }: ItineraryMapProps) {
  const filteredPoints =
    selectedDay != null ? points.filter((p) => p.dayNumber === selectedDay) : points;

  if (filteredPoints.length === 0) {
    return (
      <div
        style={{ height }}
        className="bg-gray-100 rounded-xl flex items-center justify-center text-gray-400"
      >
        <div className="text-center">
          <svg className="w-10 h-10 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
          </svg>
          <p>No locations for this day</p>
        </div>
      </div>
    );
  }

  const center: LatLngTuple = [
    filteredPoints.reduce((sum, p) => sum + p.lat, 0) / filteredPoints.length,
    filteredPoints.reduce((sum, p) => sum + p.lng, 0) / filteredPoints.length,
  ];

  // Draw a route line connecting points in order
  const routeCoords: LatLngTuple[] = filteredPoints.map((p) => [p.lat, p.lng]);

  return (
    <MapContainer
      center={center}
      zoom={12}
      style={{ height, width: '100%', borderRadius: '12px' }}
    >
      <TileLayer
        attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
      />

      {/* Route polyline */}
      {routeCoords.length > 1 && (
        <Polyline
          positions={routeCoords}
          color="#0ea5e9"
          weight={2}
          opacity={0.5}
          dashArray="8, 8"
        />
      )}

      {filteredPoints.map((point) => {
        const color = typeColors[point.type] ?? typeColors.default;
        return (
          <Marker
            key={point.id}
            position={[point.lat, point.lng]}
            icon={createColoredMarker(color)}
          >
            <Popup>
              <div className="min-w-32">
                <p className="font-semibold text-gray-900">{point.name}</p>
                {point.description && (
                  <p className="text-xs text-gray-500 mt-1">{point.description}</p>
                )}
                {point.dayNumber && (
                  <p className="text-xs text-brand-600 mt-1 font-medium">Day {point.dayNumber}</p>
                )}
                <span
                  className="inline-block text-xs px-2 py-0.5 rounded-full mt-1 font-medium text-white"
                  style={{ background: color }}
                >
                  {point.type}
                </span>
              </div>
            </Popup>
          </Marker>
        );
      })}
    </MapContainer>
  );
}
