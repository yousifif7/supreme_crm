<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class GeoService
{
    public function getAddressFromCoordinates($lat, $lng): ?string
    {
        return Cache::remember("geo_address_{$lat}_{$lng}", 86400, function () use ($lat, $lng) {
            $apiKey = config('services.google_maps.key');
            $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$lat},{$lng}&key={$apiKey}";

            $response = Http::get($url);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['status']) && $data['status'] === 'OK' && !empty($data['results'])) {
                    $first = $data['results'][0];
                    $formatted = $first['formatted_address'] ?? null;

                    // Google may return a Plus Code (e.g. "8G3P97FP+R5") when a
                    // human-friendly street address isn't available for the coords.
                    // In that case try to assemble a readable address from
                    // address_components (city / admin / country). If those
                    // components are also missing, fall back to the formatted value.
                    $looksLikePlusCode = false;
                    if ($formatted) {
                        // Plus codes often contain a "+" and are short
                        if (strpos($formatted, '+') !== false || preg_match('/^[0-9A-Z+\- ]{3,}$/i', $formatted)) {
                            // Heuristic: if formatted contains a '+' and no comma it's likely a plus-code
                            if (strpos($formatted, '+') !== false && strpos($formatted, ',') === false) {
                                $looksLikePlusCode = true;
                            }
                        }
                    }

                    if ($looksLikePlusCode && !empty($first['address_components'])) {
                        $components = $first['address_components'];
                        $wanted = ['route','sublocality','locality','postal_town','administrative_area_level_2','administrative_area_level_1','country'];
                        $parts = [];
                        foreach ($wanted as $type) {
                            foreach ($components as $c) {
                                if (in_array($type, $c['types'] ?? [])) {
                                    $parts[] = $c['long_name'];
                                    break;
                                }
                            }
                        }
                        // Remove duplicates and empty
                        $parts = array_values(array_filter(array_unique($parts)));
                        if (!empty($parts)) {
                            return implode(', ', $parts);
                        }
                    }

                    // Default: return the formatted address (may still be a plus code)
                    return $formatted;
                }
            }

            return null;
        });
    }
}
