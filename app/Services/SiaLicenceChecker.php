<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Cookie\CookieJar;

class SiaLicenceChecker
{
    protected $searchUrl = 'https://services.sia.homeoffice.gov.uk/PublicRegister/SearchPublicRegisterByLicence';
    protected $timeout = 30;
    protected $debug = true;

    /**
     * Check an SIA licence by 16-digit licence number
     *
     * @param string $licenceNumber
     * @param bool $useCache
     * @return array
     */
    public function checkByLicenceNumber(string $licenceNumber, bool $useCache = true): array
    {
        $licenceNumber = $this->sanitizeLicenceNumber($licenceNumber);

        if (! $this->isValidLicenceNumberFormat($licenceNumber)) {
            return $this->errorResponse('Invalid SIA licence format', $licenceNumber);
        }

        $cacheKey = 'sia_check_' . $licenceNumber;
        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $result = $this->performCheck($licenceNumber);

        // cache successful or not-successful results for short period to avoid hammering site
        Cache::put($cacheKey, $result, now()->addMinutes(30));

        return $result;
    }

    /**
     * Perform the check
     *
     * @param string $licenceNumber
     * @return array
     */
    protected function performCheck(string $licenceNumber): array
    {
        try {
            // Browser-like headers to reduce bot blocking
            $commonHeaders = [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Connection' => 'keep-alive',
                'Accept-Language' => 'en-GB,en-US;q=0.9,en;q=0.8',
                'Referer' => 'https://services.sia.homeoffice.gov.uk/',
            ];

            // Try variants: GET with common param names, then POST fallback
            $attempts = [
                ['method' => 'get',  'params' => ['licence' => $licenceNumber]],
                ['method' => 'get',  'params' => ['licenceNumber' => $licenceNumber]],
                ['method' => 'post', 'params' => ['licence' => $licenceNumber]],
                ['method' => 'post', 'params' => ['LicenseNo' => $licenceNumber]],

            ];

            $this->timeout = max($this->timeout, 60); // increase default timeout for remote
            // prepare a cookie jar and try to prime a session / fetch hidden form tokens
            $cookieJar = new \GuzzleHttp\Cookie\CookieJar();
            $initialFormParams = [];
            try {
                $seedResp = Http::withHeaders($commonHeaders)
                    ->withOptions(['allow_redirects' => true, 'http_errors' => false, 'cookies' => $cookieJar])
                    ->timeout($this->timeout)
                    ->accept('text/html')
                    ->get('https://services.sia.homeoffice.gov.uk/PublicRegister');

                $seedBody = (string) ($seedResp->body() ?? '');
                // attempt to extract hidden inputs (anti-forgery tokens or other session fields)
                if (preg_match_all('/<input[^>]+type=["\']hidden["\'][^>]*>/i', $seedBody, $hiddenInputs)) {
                    foreach ($hiddenInputs[0] as $inputHtml) {
                        if (preg_match("/name=[\"']([^\"']+)[\"']/i", $inputHtml, $m)) {
                            $name = $m[1];
                            if (preg_match("/value=[\"']([^\"']*)[\"']/i", $inputHtml, $v)) {
                                $value = $v[1];
                            } else {
                                $value = '';
                            }
                            $initialFormParams[$name] = $value;
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::info('SIA initial seed request failed, continuing without initial tokens', ['exception' => $e->getMessage()]);
            }
            // ensure debug folder exists to avoid Storage failures
            try {
                Storage::makeDirectory('sia_debug');
            } catch (\Throwable $e) {
                // non-fatal: if we can't create the dir, we'll still try to proceed
                Log::warning('Unable to ensure sia_debug directory exists', ['exception' => $e->getMessage()]);
            }

            $lastResponse = null;
            $serverErrorDetected = false;
            $serverErrorStatuses = [];
            $savedDebugFiles = [];
            $serverErrorCount = 0;

foreach ($attempts as $idx => $attempt) {
                $method = $attempt['method'];
                $params = $attempt['params'];

                try {
                    // reuse cookie jar and include any initial hidden form params for POSTs
                    $opts = ['allow_redirects' => true, 'http_errors' => false, 'cookies' => $cookieJar];
                    // If an HTTP proxy is configured (for example a UK static IP proxy), pass it to Guzzle
                    $proxy = env('SIA_HTTP_PROXY');
                    if (!empty($proxy)) {
                        // Guzzle accepts either a string (applies to all schemes) or an array with 'http'/'https' keys.
                        $opts['proxy'] = $proxy;
                        Log::info('Using SIA HTTP proxy for requests', ['proxy' => $proxy]);
                    }
                    $response = Http::withHeaders($commonHeaders)
                        ->withOptions($opts)
                        ->timeout($this->timeout)
                        ->retry(3, 1500, null, false) // 3 retries, 1.5s backoff, no throwing
                        ->accept('text/html');

                    if ($method === 'get') {
                        $response = $response->get($this->searchUrl, $params);
                    } else {
                        // merge in any initial hidden form params we discovered
                        $postParams = array_merge($initialFormParams, $params);
                        $response = $response->asForm()->post($this->searchUrl, $postParams);
                    }

                    $status = $response->status();
                    $body = (string) $response->body();
                } catch (\Throwable $e) {
                    // Log and save debug info, then continue to next attempt instead of failing immediately
                    Log::warning('SIA request attempt exception', [
                        'licence' => $licenceNumber,
                        'attempt' => $idx,
                        'method' => $method,
                        'exception' => $e->getMessage(),
                    ]);
                    if ($this->debug) {
                        $errFile = "sia_{$licenceNumber}_attempt{$idx}_exception_" . date('Y-m-d_His') . '.log';
                        Storage::put('sia_debug/' . $errFile, $e->getMessage() . "\n\n" . $e->getTraceAsString());
                        Log::info('SIA exception saved', ['file' => 'storage/app/sia_debug/' . $errFile]);
                    }
                    // try next attempt variant
                    continue;
                }

                // Save debug html on each attempt if enabled
                    if ($this->debug) {
                        $file = "sia_{$licenceNumber}_attempt{$idx}_" . date('Y-m-d_His') . '.html';
                        Storage::put('sia_debug/' . $file, $body);
                        $savedDebugFiles[] = 'storage/app/sia_debug/' . $file;
                        Log::info('SIA HTML Response Saved', ['file' => 'storage/app/sia_debug/' . $file, 'licence' => $licenceNumber, 'attempt' => $idx]);
                    }

                // Log limited details for diagnostics (avoid dumping huge bodies)
                Log::info('SIA request attempt', [
                    'licence' => $licenceNumber,
                    'attempt' => $idx,
                    'method' => $method,
                    'status' => $status,
                    'body_sample' => Str::limit(strip_tags($body), 1000),
                ]);

                // If 200 -> parse
                if ($status === 200) {
                    // quick blocking detection
                    $bodyLower = strtolower($body);
                    $blockingIndicators = [
                        'please enable javascript',
                        'access denied',
                        'forbidden',
                        'captcha',
                        'service unavailable',
                        'temporarily unavailable',
                        'maintenance',
                        '<title>error</title>',
                        'request blocked'
                    ];
                    $blocked = false;
                    foreach ($blockingIndicators as $indicator) {
                        if (stripos($bodyLower, $indicator) !== false) {
                            $blocked = true;
                            Log::warning("SIA response appears to be a blocking or error page", ['licence' => $licenceNumber, 'indicator' => $indicator]);
                            break;
                        }
                    }
                    if ($blocked) {
                        return $this->errorResponse('SIA website returned an error/blocking page', $licenceNumber);
                    }

                    return $this->parseResponse($body, $licenceNumber);
                }

                // If 5xx, record it and try other variants (do not fail immediately so we can try alternate param names/methods)
                if ($status >= 500 && $status < 600) {
                    Log::error('SIA returned server error', [
                        'licence' => $licenceNumber,
                        'status' => $status,
                        'body_sample' => Str::limit(strip_tags($body), 2000),
                    ]);
                    $serverErrorDetected = true;
                    $serverErrorStatuses[] = $status;
                    $serverErrorCount++;
                    // save full body for diagnostics if debug enabled
                    if ($this->debug) {
                        try {
                            $file500 = "sia_{$licenceNumber}_attempt{$idx}_status{$status}_" . date('Y-m-d_His') . '.html';
                            Storage::put('sia_debug/' . $file500, $body);
                            $savedDebugFiles[] = 'storage/app/sia_debug/' . $file500;
                            Log::info('SIA 5xx HTML Response Saved', ['file' => 'storage/app/sia_debug/' . $file500, 'licence' => $licenceNumber, 'attempt' => $idx]);
                        } catch (\Throwable $e) {
                            Log::warning('Failed to save SIA 5xx debug file', ['exception' => $e->getMessage()]);
                        }
                    }
                    // exponential backoff with jitter before next attempt (ms)
                    $base = 300; // 300ms base
                    $backoffMs = (int) min(5000, $base * (2 ** ($serverErrorCount - 1)));
                    $jitter = rand(0, 300);
                    $delayMs = $backoffMs + $jitter;
                    Log::info('SIA server error backoff', ['licence' => $licenceNumber, 'attempt' => $idx, 'delay_ms' => $delayMs]);
                    usleep($delayMs * 1000);
                    // try next attempt variant
                    $lastResponse = $response;
                    continue;
                }

                // Keep last response for fallback after attempts
                $lastResponse = $response;
            }

            // After attempts, if we have a response but couldn't parse / determine -> return unable to verify
            if ($lastResponse) {
                if ($serverErrorDetected) {
                    return array_merge($this->errorResponse('SIA website returned server errors during attempts', $licenceNumber), [
                        'debug_files' => $savedDebugFiles,
                        'server_statuses' => $serverErrorStatuses,
                    ]);
                }
                return $this->errorResponse('Unable to verify licence status (no valid response parsed)', $licenceNumber);
            }

            return $this->errorResponse('SIA website unavailable. No response', $licenceNumber);
        } catch (\Throwable $e) {
            Log::error("Error while performing SIA check", ['licence' => $licenceNumber, 'exception' => $e->getMessage()]);
            return $this->errorResponse('SIA website unavailable. Exception during request', $licenceNumber);
        }
    }
    /**
     * Parse the HTML response
     *
     * @param string $html
     * @param string $licenceNumber
     * @return array
     */
    protected function parseResponse(string $html, string $licenceNumber): array
    {
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        $htmlLower = strtolower($html);

        // Check for "no results" first
        $noResultIndicators = [
            'no licence found',
            'no results found',
            'no matches found',
            'search returned no results',
            'no licences match your search',
            'there are no results'
        ];

        foreach ($noResultIndicators as $indicator) {
            if (stripos($htmlLower, $indicator) !== false) {
                return $this->errorResponse('Licence not found in SIA register', $licenceNumber);
            }
        }

        // Look for obvious licence number presence or multiple result indicators
        $foundLicence = stripos($html, $licenceNumber) !== false;

        $successIndicators = [
            'licence holder',
            'holder name',
            'licence status',
            'licence type',
            'licence sector',
            'expiry date'
        ];

        $indicatorCount = 0;
        foreach ($successIndicators as $indicator) {
            if (stripos($htmlLower, $indicator) !== false) {
                $indicatorCount++;
            }
        }

        // If neither licence nor multiple indicators present, we cannot verify
        if (! $foundLicence && $indicatorCount < 2) {
            return $this->errorResponse('Unable to verify licence status', $licenceNumber);
        }

        // Prepare base result and attempt to extract details
        $result = [
            'success' => true,
            'valid' => false, // default to false, set true only when we detect active
            'licence_number' => $licenceNumber,
            'holder_name' => null,
            'licence_sector' => null,
            'licence_status' => null,
            'expiry_date' => null,
            'issue_date' => null,
            'error' => null,
        ];

        $result = $this->extractDetails($html, $result);

        // Normalize parsed status and decide validity
        $statusRaw = strtolower((string) ($result['licence_status'] ?? ''));
        $statusRaw = trim(preg_replace('/\s+/', ' ', $statusRaw));

        $activeKeywords = ['active', 'valid', 'live', 'current'];
        $inactiveKeywords = ['inactive', 'invalid', 'suspended', 'revoked', 'expired', 'expired/expired'];

        $foundActive = false;
        $foundInactive = false;

        // If parser gave a status, use it
        if (!empty($statusRaw)) {
            foreach ($activeKeywords as $k) {
                if (stripos($statusRaw, $k) !== false) {
                    $foundActive = true;
                    break;
                }
            }
            foreach ($inactiveKeywords as $k) {
                if (stripos($statusRaw, $k) !== false) {
                    $foundInactive = true;
                    break;
                }
            }
        } else {
            // Fallback: search raw HTML for clear active/inactive words (avoid false positives)
            foreach ($activeKeywords as $k) {
                if (stripos($htmlLower, $k . ' licence') !== false || stripos($htmlLower, $k . ' status') !== false || stripos($htmlLower, 'status: ' . $k) !== false) {
                    $foundActive = true;
                }
            }
            foreach ($inactiveKeywords as $k) {
                if (stripos($htmlLower, $k . ' licence') !== false || stripos($htmlLower, $k . ' status') !== false || stripos($htmlLower, 'status: ' . $k) !== false) {
                    $foundInactive = true;
                }
            }
        }

        if ($foundActive && ! $foundInactive) {
            $result['valid'] = true;
            $result['success'] = true;
            return $result;
        }

        if ($foundInactive) {
            return array_merge($result, [
                'success' => true,
                'valid' => false,
                'error' => 'Licence found but not active (' . ($result['licence_status'] ?? 'unknown') . ')'
            ]);
        }

        // Unable to determine active/inactive reliably
        return $this->errorResponse('Unable to determine licence active status from SIA response', $licenceNumber);
    }

    /**
     * Extract licence details from HTML
     *
     * @param string $html
     * @param array $result
     * @return array
     */
    protected function extractDetails(string $html, array $result): array
    {
        // Remove script tags
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);

        // Try to find table with results
        if (preg_match('/<table[^>]*>(.*?)<\/table>/is', $html, $tableMatch)) {
            $tableHtml = $tableMatch[1];

            // Extract rows
            preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $tableHtml, $rows);

            foreach ($rows[1] as $row) {
                // Get cells from this row
                preg_match_all('/<t[dh][^>]*>(.*?)<\/t[dh]>/is', $row, $cells);

                if (count($cells[1]) >= 2) {
                    $label = strip_tags($cells[1][0]);
                    $value = strip_tags($cells[1][1]);

                    $label = trim($label);
                    $value = trim($value);

                    // Skip if value looks like code
                    if (
                        empty($value) || strlen($value) > 200 ||
                        stripos($value, 'function') !== false ||
                        stripos($value, 'option[') !== false
                    ) {
                        continue;
                    }

                    $this->mapField($result, $label, $value);
                }
            }
        }

        // Alternative: Try regex patterns (kept but slightly more tolerant for formats)
        $patterns = [
            'name' => '/(?:holder\s*name|name)[:\s]*(?:<[^>]*>)*([\p{L}\s\.\'\-]{2,100})/iu',
            'status' => '/(?:licence\s*)?status[:\s]*(?:<[^>]*>)*([A-Za-z\/\s]+)/i',
            'sector' => '/(?:licence\s*)?(?:sector|type)[:\s]*(?:<[^>]*>)*([A-Za-z\s]+)/i',
            'expiry' => '/(?:expiry|expires|expiry date)[:\s]*(?:<[^>]*>)*([\d\/\-]+)/i',
        ];

        foreach ($patterns as $field => $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $value = trim(strip_tags($matches[1]));
                if (!empty($value) && strlen($value) < 200) {
                    switch ($field) {
                        case 'name':
                            if (empty($result['holder_name'])) {
                                $result['holder_name'] = $value;
                            }
                            break;
                        case 'status':
                            if (empty($result['licence_status'])) {
                                $result['licence_status'] = $value;
                            }
                            break;
                        case 'sector':
                            if (empty($result['licence_sector'])) {
                                $result['licence_sector'] = $value;
                            }
                            break;
                        case 'expiry':
                            if (empty($result['expiry_date'])) {
                                $result['expiry_date'] = $value;
                            }
                            break;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Map field based on label
     *
     * @param array $result
     * @param string $label
     * @param string $value
     */
    protected function mapField(array &$result, string $label, string $value): void
    {
        $label = strtolower($label);

        if (preg_match('/name|holder/i', $label)) {
            $result['holder_name'] = $value;
        } elseif (preg_match('/status/i', $label)) {
            $result['licence_status'] = $value;
        } elseif (preg_match('/sector|type/i', $label)) {
            $result['licence_sector'] = $value;
        } elseif (preg_match('/expiry|expires/i', $label)) {
            $result['expiry_date'] = $value;
        } elseif (preg_match('/issue|issued/i', $label)) {
            $result['issue_date'] = $value;
        }
    }

    /**
     * Create error response
     *
     * @param string $message
     * @param string|null $licenceNumber
     * @return array
     */
    protected function errorResponse(string $message, ?string $licenceNumber = null): array
    {
        return [
            'success' => false,
            'valid' => false,
            'error' => $message,
            'licence_number' => $licenceNumber,
            'holder_name' => null,
            'licence_sector' => null,
            'licence_status' => null,
            'expiry_date' => null,
            'issue_date' => null,
        ];
    }

    /**
     * Validate licence number format
     *
     * @param string $licenceNumber
     * @return bool
     */
    protected function isValidLicenceNumberFormat(string $licenceNumber): bool
    {
        return preg_match('/^[0-9]{16}$/', $licenceNumber) === 1;
    }

    /**
     * Sanitize licence number
     *
     * @param string $licenceNumber
     * @return string
     */
    protected function sanitizeLicenceNumber(string $licenceNumber): string
    {
        return preg_replace('/[^0-9]/', '', $licenceNumber);
    }

    /**
     * Clear cached result
     *
     * @param string|null $licenceNumber
     */
    public function clearCache(string $licenceNumber = null): void
    {
        if ($licenceNumber) {
            Cache::forget('sia_check_' . $this->sanitizeLicenceNumber($licenceNumber));
        }
    }
}
