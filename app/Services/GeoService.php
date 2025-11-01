<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class GeoService
{
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
