/** Mirrors the server-side Geofence::MAX_ACCURACY_CREDIT_METERS. */
export const MAX_ACCURACY_CREDIT_METERS = 100;

/** Great-circle distance (haversine), same formula as the server. */
export function distanceInMeters(lat1: number, lng1: number, lat2: number, lng2: number): number {
    const rad = (deg: number) => (deg * Math.PI) / 180;
    const dLat = rad(lat2 - lat1);
    const dLng = rad(lng2 - lng1);
    const a = Math.sin(dLat / 2) ** 2 + Math.cos(rad(lat1)) * Math.cos(rad(lat2)) * Math.sin(dLng / 2) ** 2;

    return 2 * 6371000 * Math.asin(Math.min(1, Math.sqrt(a)));
}

export function formatDistance(meters: number): string {
    return meters >= 1000 ? `${Math.round(meters / 100) / 10} km` : `${meters} m`;
}
