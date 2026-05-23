<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GeoService
{
    public function getCoordinatesFromAddress(?string $address, ?string $postalCode = null, ?string $explicitPlusCode = null): ?array
    {
        $addressClean = trim((string) ($address ?? ''));
        $postal       = trim((string) ($postalCode ?? ''));

        // Build combined query (avoid double-appending when postcode is already in address)
        if ($postal !== '' && strpos($addressClean, $postal) === false) {
            $fullQuery = trim($addressClean . ', ' . $postal);
        } else {
            $fullQuery = $addressClean !== '' ? $addressClean : $postal;
        }

        if ($fullQuery === '' && ($explicitPlusCode === null || $explicitPlusCode === '')) {
            return null;
        }

        // Bump version to v6 — invalidates old results that were incorrectly
        // forced into the UK even for non-UK addresses.
        $cacheKey = 'geo_coords_v6_' . md5(strtolower($fullQuery));

        // Detect Open Location / plus-code patterns (e.g. "F357+3Q") and
        // lift them out so we can try geocoding the plus-code directly. Plus
        // codes are highly precise and often resolve where free-form
        // business-name addresses do not.
        // An explicitly stored plus_code takes highest priority.
        $plusCode = null;
        if ($explicitPlusCode !== null && $explicitPlusCode !== '') {
            $plusCode = trim($explicitPlusCode);
        } elseif (preg_match('/[23456789CFGHJMPQRVWX]{1,}\+\w{2,}/i', $fullQuery, $m)) {
            $plusCode = $m[0];
        }

        return Cache::remember($cacheKey, 86400, function () use ($fullQuery, $postal, $addressClean, $plusCode) {
            $apiKey = config('services.google_maps.key');
            if (!$apiKey) {
                return null;
            }

            $isUkPostcode = $postal !== ''
                && preg_match('/^[A-Z]{1,2}\d{1,2}[A-Z]?\s*\d[A-Z]{2}$/i', $postal);

            // Only apply UK-specific country restriction when we have strong evidence
            // the address is actually in the UK. Otherwise geocode globally so that
            // addresses in Pakistan, UAE, etc. are resolved correctly.
            $isLikelyUk = $isUkPostcode || $this->isLikelyUkAddress($fullQuery);

            // Initialize candidate placeholder used by later fallbacks.
            $candidate = null;

            // Prefer the Places API (findplacefromtext) which matches what
            // Google Maps UI returns. Try plus-code first (if present), then
            // the full query. If a Places result is found, treat it as the
            // authoritative location.
            //
            // Plus codes come in two forms:
            //   • global (e.g. "9C4W9RFG+R3"): uniquely resolvable on its own
            //   • short / compound (e.g. "9R8H+GX"): MUST be combined with a
            //     locality, otherwise Google may snap to the wrong country
            //     (e.g. "9R8H+GX" alone → Northampton County, NC instead of
            //     Northampton, UK). Detect a short plus code by length of the
            //     prefix before '+' (global codes have 8 chars, short have 4).
            if ($plusCode) {
                $isShortPlus = (bool) preg_match('/^[23456789CFGHJMPQRVWX]{4}\+\w{2,3}$/i', $plusCode);
                $plusQueries = [];
                if ($isShortPlus) {
                    // Build progressively wider context. Order: most specific first.
                    if ($postal !== '') {
                        $plusQueries[] = $plusCode . ' ' . $postal;
                    }
                    if ($addressClean !== '') {
                        $plusQueries[] = $plusCode . ' ' . $addressClean;
                    }
                    if ($isLikelyUk) {
                        $plusQueries[] = $plusCode . ' United Kingdom';
                    }
                    // Last resort: bare plus code
                    $plusQueries[] = $plusCode;
                } else {
                    // Global plus code — always uniquely resolvable; use as-is.
                    $plusQueries[] = $plusCode;
                }

                foreach ($plusQueries as $plusQuery) {
                    $placePlus = $this->placeFindRequest($plusQuery, $isLikelyUk ? 'GB' : '', $apiKey);
                    if ($placePlus !== null) {
                        return $placePlus;
                    }
                }
            }

            $placeFull = $this->placeFindRequest($fullQuery, $isLikelyUk ? 'GB' : '', $apiKey);
            if ($placeFull !== null) {
                return $placeFull;
            }

            if ($isLikelyUk) {
                // ── UK path ────────────────────────────────────────────────────────
                // Tier 1 — full address + postal_code component constraint.
                // This is the most specific query: Google anchors results to the exact
                // postcode sector while still resolving the full street/building address.
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
                $result2 = $this->geocodeRequest($fullQuery, 'GB', $apiKey);
                if ($result2 !== null) {
                    $locType2 = $result2['location_type'] ?? '';
                    if (in_array($locType2, ['ROOFTOP', 'RANGE_INTERPOLATED'])) {
                        return $result2;
                    }
                    if ($candidate === null) {
                        $candidate = $result2;
                    }
                }

                // Tier 3 — return GEOMETRIC_CENTER / APPROXIMATE candidate if we have one.
                if ($candidate !== null) {
                    return $candidate;
                }

                // Tier 4 — postcode only (last resort for UK).
                if ($postal !== '' && $isUkPostcode) {
                    $postcodeResult = $this->geocodeRequest($postal, 'GB', $apiKey);
                    if ($postcodeResult !== null) {
                        return $postcodeResult;
                    }
                }

                // Tier 5 — address without postcode suffix.
                if ($addressClean !== '') {
                    return $this->geocodeRequest($addressClean, 'GB', $apiKey);
                }

                return null;
            }

            // ── Non-UK path (global geocoding — no country restriction) ────────────
            // Tier 1 — full query (address + postal code if present), no restriction.
            $result = $this->geocodeRequest($fullQuery, '', $apiKey);
            if ($result !== null) {
                $locType = $result['location_type'] ?? '';
                if (in_array($locType, ['ROOFTOP', 'RANGE_INTERPOLATED'])) {
                    return $result;
                }
                $candidate = $result;
            } else {
                $candidate = null;
            }

            // Tier 2 — address only (drop postal code to avoid confusing the geocoder).
            if ($addressClean !== '' && $addressClean !== $fullQuery) {
                $result2 = $this->geocodeRequest($addressClean, '', $apiKey);
                if ($result2 !== null) {
                    $locType2 = $result2['location_type'] ?? '';
                    if (in_array($locType2, ['ROOFTOP', 'RANGE_INTERPOLATED'])) {
                        return $result2;
                    }
                    if ($candidate === null) {
                        $candidate = $result2;
                    }
                }
            }

            // Tier 3 — return best candidate found so far.
            if ($candidate !== null) {
                return $candidate;
            }

            // Tier 4 — postal code alone (last resort for non-UK).
            if ($postal !== '') {
                return $this->geocodeRequest($postal, '', $apiKey);
            }

            return null;
        });
    }

    /**
     * Returns true when the query string contains keywords that strongly suggest
     * a UK address. Used to decide whether to apply country:GB restriction.
     */
    private function isLikelyUkAddress(string $query): bool
    {
        $lower = strtolower($query);
        $ukKeywords = [
            'united kingdom', ' uk', ', uk', 'england', 'scotland', 'wales',
            'northern ireland', 'great britain',
        ];
        foreach ($ukKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                return true;
            }
        }
        return false;
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
            if (config('services.google_maps.debug')) {
                Log::warning('GeoService geocode HTTP failure', ['query' => $query, 'params' => $params, 'status' => $response->status()]);
            }
            return null;
        }

        $data = $response->json();
        $status = $data['status'] ?? null;
        $results = $data['results'] ?? [];

        if ($status !== 'OK' || empty($results[0]['geometry']['location'])) {
            if (config('services.google_maps.debug')) {
                Log::info('GeoService geocode response (non-OK or empty)', [
                    'query' => $query,
                    'params' => $params,
                    'status' => $status,
                    'results_count' => count($results),
                ]);
            }
            return null;
        }

        $first = $results[0];
        $location = $first['geometry']['location'] ?? [];

        if (config('services.google_maps.debug')) {
            Log::info('GeoService geocode result', [
                'query' => $query,
                'params' => $params,
                'status' => $status,
                'formatted_address' => $first['formatted_address'] ?? null,
                'place_id' => $first['place_id'] ?? null,
                'location_type' => $first['geometry']['location_type'] ?? null,
                'lat' => $location['lat'] ?? null,
                'lng' => $location['lng'] ?? null,
                'types' => $first['types'] ?? [],
            ]);
        }

        return [
            'lat'               => (float) ($location['lat'] ?? 0),
            'lng'               => (float) ($location['lng'] ?? 0),
            'formatted_address' => $first['formatted_address'] ?? null,
            'location_type'     => $first['geometry']['location_type'] ?? '',
            'place_id'          => $first['place_id'] ?? null,
            'types'             => $first['types'] ?? [],
        ];
    }

    /**
     * Use the Places API findplacefromtext endpoint to locate the query.
     * Returns the same normalized array as geocodeRequest on success, or null.
     */
    private function placeFindRequest(string $query, string $countryRestriction, string $apiKey): ?array
    {
        $params = [
            'input' => $query,
            'inputtype' => 'textquery',
            'fields' => 'formatted_address,geometry,place_id,types,plus_code',
            'key' => $apiKey,
        ];

        // locationbias supports country:<CC> which helps bias results to a country.
        if ($countryRestriction !== '') {
            $params['locationbias'] = 'country:' . $countryRestriction;
        }

        $response = Http::get('https://maps.googleapis.com/maps/api/place/findplacefromtext/json', $params);

        if (!$response->successful()) {
            if (config('services.google_maps.debug')) {
                Log::warning('GeoService places HTTP failure', ['query' => $query, 'params' => $params, 'status' => $response->status()]);
            }
            return null;
        }

        $data = $response->json();
        $status = $data['status'] ?? null;
        $candidates = $data['candidates'] ?? [];

        if ($status !== 'OK' || empty($candidates[0]['geometry']['location'])) {
            if (config('services.google_maps.debug')) {
                Log::info('GeoService places response (non-OK or empty)', ['query' => $query, 'status' => $status, 'candidates' => count($candidates)]);
            }
            return null;
        }

        $first = $candidates[0];
        $location = $first['geometry']['location'] ?? [];

        if (config('services.google_maps.debug')) {
            Log::info('GeoService places result', [
                'query' => $query,
                'formatted_address' => $first['formatted_address'] ?? null,
                'place_id' => $first['place_id'] ?? null,
                'lat' => $location['lat'] ?? null,
                'lng' => $location['lng'] ?? null,
                'types' => $first['types'] ?? [],
            ]);
        }

        return [
            'lat' => (float) ($location['lat'] ?? 0),
            'lng' => (float) ($location['lng'] ?? 0),
            'formatted_address' => $first['formatted_address'] ?? null,
            'location_type' => $first['geometry']['location_type'] ?? '',
            'place_id' => $first['place_id'] ?? null,
            'types' => $first['types'] ?? [],
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
