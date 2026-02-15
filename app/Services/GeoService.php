<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class GeoService
{
    public function getCoordinatesFromAddress(?string $address, ?string $postalCode = null): ?array
    {
        $query = trim((string) ($address ?? ''));
        $postal = trim((string) ($postalCode ?? ''));

        if ($postal !== '') {
            $query = trim($query . ' ' . $postal);
        }

        if ($query === '') {
            return null;
        }

        $cacheKey = 'geo_coords_' . md5(strtolower($query));

        return Cache::remember($cacheKey, 86400, function () use ($query) {
            $apiKey = config('services.google_maps.key');
            if (!$apiKey) {
                return null;
            }

            $url = 'https://maps.googleapis.com/maps/api/geocode/json';
            $response = Http::get($url, [
                'address' => $query,
                'key' => $apiKey,
            ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            if (($data['status'] ?? null) !== 'OK' || empty($data['results'][0]['geometry']['location'])) {
                return null;
            }

            $location = $data['results'][0]['geometry']['location'];

            return [
                'lat' => (float) ($location['lat'] ?? 0),
                'lng' => (float) ($location['lng'] ?? 0),
                'formatted_address' => $data['results'][0]['formatted_address'] ?? null,
            ];
        });
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
