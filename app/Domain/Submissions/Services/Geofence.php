<?php

namespace App\Domain\Submissions\Services;

use App\Models\CampaignLocation;
use Illuminate\Validation\ValidationException;

/**
 * Checks that a public entry is submitted near its campaign location.
 */
class Geofence
{
    /** GPS accuracy credited to the submitter, capped so a vague fix cannot cover a whole city. */
    public const MAX_ACCURACY_CREDIT_METERS = 100;

    /**
     * @param  array{latitude?: float|null, longitude?: float|null, accuracy?: float|null}  $position
     * @return array{latitude: float|null, longitude: float|null, location_accuracy_meters: int|null, distance_meters: int|null}
     *
     * @throws ValidationException when the location has a map point and the device is missing or too far away
     */
    public function check(CampaignLocation $location, array $position): array
    {
        $latitude = isset($position['latitude']) ? (float) $position['latitude'] : null;
        $longitude = isset($position['longitude']) ? (float) $position['longitude'] : null;
        $accuracy = isset($position['accuracy']) ? max(0, (int) round((float) $position['accuracy'])) : null;

        $result = [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'location_accuracy_meters' => $accuracy,
            'distance_meters' => null,
        ];

        // Locations without a map point cannot be geofenced.
        if ($location->latitude === null || $location->longitude === null) {
            return $result;
        }

        if ($latitude === null || $longitude === null) {
            throw ValidationException::withMessages([
                'position' => 'Allow location access so we can confirm you are at '.$location->name.'.',
            ]);
        }

        $distance = (int) round($this->distanceInMeters($location->latitude, $location->longitude, $latitude, $longitude));
        $credit = min($accuracy ?? 0, self::MAX_ACCURACY_CREDIT_METERS);

        if ($distance - $credit > $location->radius_meters) {
            throw ValidationException::withMessages([
                'position' => 'You need to be at '.$location->name.' to submit this form. You appear to be '.$this->humanDistance($distance).' away.',
            ]);
        }

        return [...$result, 'distance_meters' => $distance];
    }

    /**
     * Great-circle distance (haversine).
     */
    public function distanceInMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earthRadius * asin(min(1, sqrt($a)));
    }

    private function humanDistance(int $meters): string
    {
        return $meters >= 1000 ? round($meters / 1000, 1).' km' : $meters.' m';
    }
}
