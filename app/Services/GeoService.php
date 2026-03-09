<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class GeoService
{
    public function getCoordinatesFromAddress(?string $address, ?string $postalCode = null): ?array
    {
        $addressClean = trim((string) ($address ?? ''));
        $postal       = trim((string) ($postalCode ?? ''));

        // Build combined query (avoid double-appending when postcode is already in address)
        if ($postal !== '' && strpos($addressClean, $postal) === false) {
            $fullQuery = trim($addressClean . ', ' . $postal);
        } else {
            $fullQuery = $addressClean !== '' ? $addressClean : $postal;
        }

        if ($fullQuery === '') {
            return null;
        }

        // Bump version to v5 so previously cached (potentially wrong postcode-centroid)
        // results are discarded and re-geocoded with the improved strategy.
        $cacheKey = 'geo_coords_v5_' . md5(strtolower($fullQuery));

        return Cache::remember($cacheKey, 86400, function () use ($fullQuery, $postal, $addressClean) {
            $apiKey = config('services.google_maps.key');
            if (!$apiKey) {
                return null;
            }

            $isUkPostcode = $postal !== ''
                && preg_match('/^[A-Z]{1,2}\d{1,2}[A-Z]?\s*\d[A-Z]{2}$/i', $postal);

            // Tier 1 — full address + postal_code component constraint.
            // This is the most specific query: Google anchors results to the exact
            // postcode sector while still resolving the full street/building address.
            // NOTE: We intentionally do NOT fall back to postcode-only first because
            // postcode centroids (e.g. "CB25 9TL") cover large areas and return a
            // point far from the actual building.
            if ($postal !== '') {
                $result = $this->geocodeRequest($fullQuery, 'GB', $apiKey, $postal);
                if ($result !== null) {
                    $locType = $result['location_type'] ?? '';
                    // ROOFTOP / RANGE_INTERPOLATED = exact match — use immediately.
                    if (in_array($locType, ['ROOFTOP', 'RANGE_INTERPOLATED'])) {
                        return $result;
                    }
                    // Keep this as a candidate but continue trying for something better.
                    $candidate = $result;
                } else {
                    $candidate = null;
                }
            } else {
                $candidate = null;
            }

            // Tier 2 — full address, country:GB only (no postcode component).
            // Sometimes removing the postcode component lets Google resolve an address
            // that it couldn't pin down when restricted to a specific postal area.
            $result2 = $this->geocodeRequest($fullQuery, 'GB', $apiKey);
            if ($result2 !== null) {
                $locType2 = $result2['location_type'] ?? '';
                if (in_array($locType2, ['ROOFTOP', 'RANGE_INTERPOLATED'])) {
                    return $result2;
                }
                // Prefer this over the Tier 1 candidate if it is at least as precise.
                if ($candidate === null) {
                    $candidate = $result2;
                }
            }

            // Tier 3 — if we already have a candidate (GEOMETRIC_CENTER / APPROXIMATE),
            // return it because it is still better than a raw postcode centroid.
            if ($candidate !== null) {
                return $candidate;
            }

            // Tier 4 — postcode only. Last resort: at least centres the map somewhere
            // near the right town/district when the address fails completely.
            if ($postal !== '' && $isUkPostcode) {
                $postcodeResult = $this->geocodeRequest($postal, 'GB', $apiKey);
                if ($postcodeResult !== null) {
                    return $postcodeResult;
                }
            }

            // Tier 5 — address without postcode suffix (handles cases where the
            // combined query confuses the geocoder).
            if ($addressClean !== '') {
                return $this->geocodeRequest($addressClean, 'GB', $apiKey);
            }

            return null;
        });
    }

    /**
     * Send a single geocode request and return a normalised result array, or null on failure.
     *
     * @param string $postalCode  When provided, adds a postal_code component constraint so
     *                            Google anchors results to that postcode area rather than
     *                            snapping to a business name registered elsewhere.
     */
    private function geocodeRequest(string $query, string $countryRestriction, string $apiKey, string $postalCode = ''): ?array
    {
        $params = [
            'address' => $query,
            'key'     => $apiKey,
        ];

        $componentParts = [];
        if ($countryRestriction !== '') {
            $componentParts[] = 'country:' . $countryRestriction;
        }
        if ($postalCode !== '') {
            $componentParts[] = 'postal_code:' . $postalCode;
        }
        if (!empty($componentParts)) {
            $params['components'] = implode('|', $componentParts);
        }

        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', $params);

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();

        if (($data['status'] ?? null) !== 'OK' || empty($data['results'][0]['geometry']['location'])) {
            return null;
        }

        $location = $data['results'][0]['geometry']['location'];

        return [
            'lat'               => (float) ($location['lat'] ?? 0),
            'lng'               => (float) ($location['lng'] ?? 0),
            'formatted_address' => $data['results'][0]['formatted_address'] ?? null,
            'location_type'     => $data['results'][0]['geometry']['location_type'] ?? '',
        ];
    }

    public function distanceInMeters($lat1, $lng1, $lat2, $lng2): float
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad((float) $lat2 - (float) $lat1);
        $dLng = deg2rad((float) $lng2 - (float) $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad((float) $lat1)) * cos(deg2rad((float) $lat2))
            * sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    public function getAddressFromCoordinates($lat, $lng): ?array
    {
        return Cache::remember("geo_address_{$lat}_{$lng}", 86400, function () use ($lat, $lng) {
            $apiKey = config('services.google_maps.key');
            $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$lat},{$lng}&key={$apiKey}";

            $response = Http::get($url);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['status']) && $data['status'] === 'OK' && !empty($data['results'])) {
                    $addressComponents = $data['results'][0]['address_components'];
                    $formattedAddress = $data['results'][0]['formatted_address'];

                    $city = $this->getComponent($addressComponents, 'locality')
                        ?? $this->getComponent($addressComponents, 'administrative_area_level_2');
                    $street = $this->getComponent($addressComponents, 'route');
                    $streetNumber = $this->getComponent($addressComponents, 'street_number');
                    $country = $this->getComponent($addressComponents, 'country');
                    $postalCode = $this->getComponent($addressComponents, 'postal_code');

                    return [
                        'formatted_address' => $formattedAddress,
                        'street' => trim("{$streetNumber} {$street}"),
                        'city' => $city,
                        'country' => $country,
                        'postal_code' => $postalCode,
                        'lat' => $lat,
                        'lng' => $lng,
                    ];
                }
            }

            return null;
        });
    }

    private function getComponent($components, $type)
    {
        foreach ($components as $component) {
            if (in_array($type, $component['types'])) {
                return $component['long_name'];
            }
        }
        return null;
    }
}
