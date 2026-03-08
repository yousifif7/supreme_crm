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

        $cacheKey = 'geo_coords_v3_' . md5(strtolower($fullQuery));

        return Cache::remember($cacheKey, 86400, function () use ($fullQuery, $postal, $addressClean) {
            $apiKey = config('services.google_maps.key');
            if (!$apiKey) {
                return null;
            }

            // Tier 1 — UK postcode first.
            // UK postcodes (e.g. "FY3 9YZ") are extremely precise and unambiguous.
            // Trying the postcode first avoids Google misreading a building/company name
            // prefix (e.g. "Atos building") as the primary search term and snapping to
            // a city centroid instead of the actual street.
            $isUkPostcode = $postal !== ''
                && preg_match('/^[A-Z]{1,2}\d{1,2}[A-Z]?\s*\d[A-Z]{2}$/i', $postal);

            if ($isUkPostcode) {
                $postcodeResult = $this->geocodeRequest($postal, 'GB', $apiKey);
                if ($postcodeResult !== null) {
                    return $postcodeResult;
                }
            }

            // Tier 2: full address + postcode + country:GB restriction.
            $result = $this->geocodeRequest($fullQuery, 'GB', $apiKey);

            if ($result !== null) {
                $locType = $result['location_type'] ?? '';

                // ROOFTOP / RANGE_INTERPOLATED = precise match → keep it.
                if (in_array($locType, ['ROOFTOP', 'RANGE_INTERPOLATED'])) {
                    return $result;
                }

                // GEOMETRIC_CENTER / APPROXIMATE = city/area centroid — try postcode
                // as a fallback even for non-UK postcodes.
                if ($postal !== '' && !$isUkPostcode) {
                    $postcodeResult = $this->geocodeRequest($postal, 'GB', $apiKey);
                    if ($postcodeResult !== null) {
                        return $postcodeResult;
                    }
                }

                // Use the imprecise result rather than nothing.
                return $result;
            }

            // Tier 3: full address failed and no postcode result — try address alone.
            if ($addressClean !== '' && $postal !== '') {
                return $this->geocodeRequest($addressClean, 'GB', $apiKey);
            }

            return null;
        });
    }

    /**
     * Send a single geocode request and return a normalised result array, or null on failure.
     */
    private function geocodeRequest(string $query, string $countryRestriction, string $apiKey): ?array
    {
        $params = [
            'address'    => $query,
            'key'        => $apiKey,
        ];

        if ($countryRestriction !== '') {
            $params['components'] = 'country:' . $countryRestriction;
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
