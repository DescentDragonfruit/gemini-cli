interface OpenSkyFlight {
  icao24: string;
  firstSeen: number;
  estDepartureAirport: string | null;
  lastSeen: number;
  estArrivalAirport: string | null;
  callsign: string | null;
  estDepartureAirportHorizDistance: number | null;
  estDepartureAirportVertDistance: number | null;
  estArrivalAirportHorizDistance: number | null;
  estArrivalAirportVertDistance: number | null;
  departureAirportCandidatesCount: number;
  arrivalAirportCandidatesCount: number;
}

interface FlightStatus {
  flightNumber: string;
  status: 'ON_TIME' | 'DELAYED' | 'LANDED' | 'CANCELLED' | 'UNKNOWN';
  actualDeparture?: Date;
  actualArrival?: Date;
  departureAirport?: string;
  arrivalAirport?: string;
  lastUpdated: Date;
}

// Simple in-memory cache with TTL
const cache = new Map<string, { data: FlightStatus; expiresAt: number }>();
const CACHE_TTL_MS = 5 * 60 * 1000; // 5 minutes

function getCachedFlight(key: string): FlightStatus | null {
  const entry = cache.get(key);
  if (!entry) return null;
  if (Date.now() > entry.expiresAt) {
    cache.delete(key);
    return null;
  }
  return entry.data;
}

function setCachedFlight(key: string, data: FlightStatus): void {
  cache.set(key, { data, expiresAt: Date.now() + CACHE_TTL_MS });
}

export async function getFlightStatus(
  flightNumber: string,
  icao24?: string | null,
  departureTime?: Date,
): Promise<FlightStatus> {
  const cacheKey = `flight:${flightNumber}`;
  const cached = getCachedFlight(cacheKey);
  if (cached) return cached;

  // Calculate time window: 2 hours before/after departure
  const now = Math.floor(Date.now() / 1000);
  const baseTime = departureTime ? Math.floor(departureTime.getTime() / 1000) : now;
  const begin = baseTime - 2 * 3600;
  const end = baseTime + 24 * 3600;

  let result: FlightStatus = {
    flightNumber,
    status: 'UNKNOWN',
    lastUpdated: new Date(),
  };

  try {
    const credentials =
      process.env.OPENSKY_USERNAME && process.env.OPENSKY_PASSWORD
        ? `${process.env.OPENSKY_USERNAME}:${process.env.OPENSKY_PASSWORD}@`
        : '';

    if (icao24) {
      // Use specific aircraft ICAO24 for more accurate results
      const url = `https://${credentials}opensky-network.org/api/flights/aircraft?icao24=${icao24.toLowerCase()}&begin=${begin}&end=${end}`;
      const response = await fetch(url, {
        headers: { 'Accept': 'application/json' },
        signal: AbortSignal.timeout(8000),
      });

      if (response.ok) {
        const flights: OpenSkyFlight[] = await response.json();
        if (flights.length > 0) {
          const flight = flights[flights.length - 1];
          const depTime = new Date(flight.firstSeen * 1000);
          const arrTime = new Date(flight.lastSeen * 1000);
          const isLanded = Date.now() / 1000 > flight.lastSeen;

          result = {
            flightNumber,
            status: isLanded ? 'LANDED' : 'ON_TIME',
            actualDeparture: depTime,
            actualArrival: isLanded ? arrTime : undefined,
            departureAirport: flight.estDepartureAirport ?? undefined,
            arrivalAirport: flight.estArrivalAirport ?? undefined,
            lastUpdated: new Date(),
          };
        }
      }
    } else {
      // Fallback: search by departure airport using callsign pattern
      // Extract IATA airline code and flight number from format like "AA123"
      const match = flightNumber.match(/^([A-Z]{2,3})(\d+)$/);
      if (match) {
        const [, , num] = match;
        // Try to find flights with matching callsign
        const url = `https://${credentials}opensky-network.org/api/flights/all?begin=${begin}&end=${end}`;
        const response = await fetch(url, {
          headers: { 'Accept': 'application/json' },
          signal: AbortSignal.timeout(8000),
        });

        if (response.ok) {
          const flights: OpenSkyFlight[] = await response.json();
          const matching = flights.find(
            (f) => f.callsign && f.callsign.trim().endsWith(num),
          );
          if (matching) {
            const isLanded = Date.now() / 1000 > matching.lastSeen;
            result = {
              flightNumber,
              status: isLanded ? 'LANDED' : 'ON_TIME',
              actualDeparture: new Date(matching.firstSeen * 1000),
              actualArrival: isLanded ? new Date(matching.lastSeen * 1000) : undefined,
              departureAirport: matching.estDepartureAirport ?? undefined,
              arrivalAirport: matching.estArrivalAirport ?? undefined,
              lastUpdated: new Date(),
            };
          }
        }
      }
    }
  } catch {
    // Silently fail and return UNKNOWN status
    result.status = 'UNKNOWN';
  }

  setCachedFlight(cacheKey, result);
  return result;
}
