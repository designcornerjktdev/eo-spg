import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import L from 'leaflet';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';
import 'leaflet/dist/leaflet.css';
import { LoaderCircle, MapPin, Search } from 'lucide-react';
import { useEffect, useState } from 'react';
import { MapContainer, Marker, TileLayer, useMap, useMapEvents } from 'react-leaflet';

// Leaflet's default icons aren't bundled by Vite; point them at the package assets manually.
const pinIcon = L.icon({
    iconUrl: markerIcon,
    iconRetinaUrl: markerIcon2x,
    shadowUrl: markerShadow,
    iconSize: [25, 41],
    iconAnchor: [12, 41],
});

const DEFAULT_CENTER: [number, number] = [-6.2, 106.816]; // Jakarta
const NOMINATIM = 'https://nominatim.openstreetmap.org';

export interface PickedLocation {
    latitude: number;
    longitude: number;
    address?: string;
    area?: string;
    name?: string;
}

interface NominatimResult {
    lat: string;
    lon: string;
    display_name: string;
    name?: string;
    address?: Record<string, string>;
}

// Area = city/regency when available, to match the "Area" column used by report filters.
function areaFrom(address?: Record<string, string>): string | undefined {
    if (!address) return undefined;
    // In Jakarta, OSM stores the province in `city` and the administrative city (e.g. Jakarta Pusat) in `city_district`.
    return address.city_district ?? address.city ?? address.regency ?? address.county ?? address.state_district ?? address.town ?? address.state;
}

function toPicked(result: NominatimResult): PickedLocation {
    return {
        latitude: Number(result.lat),
        longitude: Number(result.lon),
        address: result.display_name,
        area: areaFrom(result.address),
        name: result.name || undefined,
    };
}

function ClickToPick({ onPick }: { onPick: (lat: number, lng: number) => void }) {
    useMapEvents({ click: (event) => onPick(event.latlng.lat, event.latlng.lng) });
    return null;
}

function FlyTo({ position }: { position: [number, number] | null }) {
    const map = useMap();
    useEffect(() => {
        if (position) map.flyTo(position, Math.max(map.getZoom(), 16), { duration: 0.6 });
    }, [map, position]);
    return null;
}

/**
 * Pick a location point: search an address, click the map or drag the pin. Address & area are filled in
 * automatically via OpenStreetMap geocoding (Nominatim); the EO can still edit them in the form.
 */
export default function LocationPicker({
    latitude,
    longitude,
    onPick,
}: {
    latitude: number | null;
    longitude: number | null;
    onPick: (location: PickedLocation) => void;
}) {
    const position: [number, number] | null = latitude !== null && longitude !== null ? [latitude, longitude] : null;
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<NominatimResult[]>([]);
    const [busy, setBusy] = useState(false);
    const [message, setMessage] = useState<string | null>(null);

    const search = async () => {
        if (query.trim().length < 3) return;
        setBusy(true);
        setMessage(null);
        try {
            const params = new URLSearchParams({
                q: query,
                format: 'jsonv2',
                addressdetails: '1',
                countrycodes: 'id',
                limit: '5',
                'accept-language': 'id',
            });
            const response = await fetch(`${NOMINATIM}/search?${params}`);
            const data: NominatimResult[] = await response.json();
            setResults(data);
            if (data.length === 0) setMessage('No place found. Try another keyword or click directly on the map.');
        } catch {
            setMessage('Search is unavailable right now. Click on the map to pick a point.');
        } finally {
            setBusy(false);
        }
    };

    const pickPoint = async (lat: number, lng: number) => {
        onPick({ latitude: lat, longitude: lng });
        try {
            const params = new URLSearchParams({
                lat: String(lat),
                lon: String(lng),
                format: 'jsonv2',
                addressdetails: '1',
                'accept-language': 'id',
            });
            const response = await fetch(`${NOMINATIM}/reverse?${params}`);
            const data: NominatimResult = await response.json();
            if (data?.display_name) onPick({ ...toPicked(data), latitude: lat, longitude: lng, name: undefined });
        } catch {
            // The point is kept even if the address lookup fails.
        }
    };

    return (
        <div className="space-y-2">
            <div className="flex gap-2">
                <Input
                    placeholder="Search a place, e.g. PIK Avenue Jakarta"
                    value={query}
                    onChange={(e) => setQuery(e.target.value)}
                    onKeyDown={(e) => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            search();
                        }
                    }}
                />
                <Button type="button" variant="outline" onClick={search} disabled={busy} aria-label="Search place">
                    {busy ? <LoaderCircle className="size-4 animate-spin" /> : <Search className="size-4" />}
                </Button>
            </div>
            {results.length > 0 && (
                <ul className="divide-y rounded-md border text-sm">
                    {results.map((result) => (
                        <li key={`${result.lat},${result.lon}`}>
                            <button
                                type="button"
                                className="hover:bg-muted flex w-full items-start gap-2 px-3 py-2 text-left"
                                onClick={() => {
                                    onPick(toPicked(result));
                                    setResults([]);
                                }}
                            >
                                <MapPin className="text-muted-foreground mt-0.5 size-4 shrink-0" />
                                <span>{result.display_name}</span>
                            </button>
                        </li>
                    ))}
                </ul>
            )}
            {message && <p className="text-muted-foreground text-sm">{message}</p>}
            <div className="relative z-0 h-64 overflow-hidden rounded-md border">
                <MapContainer center={position ?? DEFAULT_CENTER} zoom={position ? 16 : 11} scrollWheelZoom={false} className="h-full w-full">
                    <TileLayer
                        attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                    />
                    <ClickToPick onPick={pickPoint} />
                    <FlyTo position={position} />
                    {position && (
                        <Marker
                            position={position}
                            icon={pinIcon}
                            draggable
                            eventHandlers={{
                                dragend: (event) => {
                                    const { lat, lng } = (event.target as L.Marker).getLatLng();
                                    pickPoint(lat, lng);
                                },
                            }}
                        />
                    )}
                </MapContainer>
            </div>
            <p className="text-muted-foreground text-xs">
                {position
                    ? `Point: ${position[0].toFixed(6)}, ${position[1].toFixed(6)} — click the map or drag the pin to move it.`
                    : 'No point yet. Search a place or click the map.'}
            </p>
        </div>
    );
}
