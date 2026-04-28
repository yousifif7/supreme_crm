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
    protected $timeout = 15;
    // Set to true only when manually debugging a single licence — never leave on for bulk runs
    protected $debug = false;
    // public storage disk and folder for debug files (storage/app/public/<folder>)
    protected $publicDebugDisk = 'public';
    protected $publicDebugPath = 'sia_debug';

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
                'Connection' => 'close',
                'Accept-Language' => 'en-GB,en-US;q=0.9,en;q=0.8',
                'Referer' => 'https://services.sia.homeoffice.gov.uk/',
            ];

            // Transport hardening for flaky proxy/TLS paths.
            $transportRetries = max(1, (int) env('SIA_TRANSPORT_RETRIES', 4));
            $transportBackoffMs = max(100, (int) env('SIA_TRANSPORT_BACKOFF_MS', 700));
            $curlOptions = [];
            if (defined('CURLOPT_HTTP_VERSION') && defined('CURL_HTTP_VERSION_1_1')) {
                $curlOptions[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;
            }
            if (defined('CURLOPT_FRESH_CONNECT')) {
                $curlOptions[CURLOPT_FRESH_CONNECT] = true;
            }
            if (defined('CURLOPT_FORBID_REUSE')) {
                $curlOptions[CURLOPT_FORBID_REUSE] = true;
            }

            // Two attempts only: one GET, one POST fallback. The extra variants
            // previously here made 12+ HTTP calls per employee, causing CPU spikes.
            // The site's form uses the field name `LicenseNo` (case-sensitive),
            // so send that key to receive real results instead of the search page.
            $attempts = [
                ['method' => 'post', 'params' => ['LicenseNo' => $licenceNumber]],
                ['method' => 'get',  'params' => ['LicenseNo' => $licenceNumber]],
            ];

            // Keep timeout from class property — do not override it here
            // prepare a cookie jar and try to prime a session / fetch hidden form tokens
            $cookieJar = new \GuzzleHttp\Cookie\CookieJar();
            $initialFormParams = [];
            try {
                $seedOptions = [
                    'allow_redirects' => true,
                    'http_errors' => false,
                    'cookies' => $cookieJar,
                    'connect_timeout' => min(10, $this->timeout),
                ];
                if (!empty($curlOptions)) {
                    $seedOptions['curl'] = $curlOptions;
                }
                $seedProxy = env('SIA_HTTP_PROXY');
                if (!empty($seedProxy)) {
                    $seedOptions['proxy'] = is_string($seedProxy)
                        ? ['http' => $seedProxy, 'https' => $seedProxy]
                        : $seedProxy;
                }
                if (env('SIA_DISABLE_CURL_VERIFY', false)) {
                    $seedOptions['verify'] = false;
                }

                $seedResp = Http::withHeaders($commonHeaders)
                    ->withOptions($seedOptions)
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
            // ensure public debug folder exists to avoid failures (create public/sia_debug)
            try {
                if (env('SIA_PUBLIC_DEBUG_DIRECT', false)) {
                    $fullDir = public_path($this->publicDebugPath);
                    if (!is_dir($fullDir)) {
                        mkdir($fullDir, 0755, true);
                    }
                } else {
                    Storage::disk($this->publicDebugDisk)->makeDirectory($this->publicDebugPath);
                }
            } catch (\Throwable $e) {
                // non-fatal: if we can't create the dir, we'll still try to proceed
                Log::warning('Unable to ensure public sia_debug directory exists', ['exception' => $e->getMessage()]);
            }

            $lastResponse = null;
            $serverErrorDetected = false;
            $serverErrorStatuses = [];
            $savedDebugFiles = [];
            $serverErrorCount = 0;

foreach ($attempts as $idx => $attempt) {
                $method = $attempt['method'];
                $params = $attempt['params'];
                // Create a temp stream to capture Guzzle / cURL verbose output for debugging
                $debugStream = fopen('php://temp', 'w+');

                try {
                    // reuse cookie jar and include any initial hidden form params for POSTs
                    // Attach a debug stream so Guzzle/cURL verbose output can be captured
                    $opts = ['allow_redirects' => true, 'http_errors' => false, 'cookies' => $cookieJar, 'debug' => $debugStream];
                    $opts['connect_timeout'] = min(10, $this->timeout);
                    if (!empty($curlOptions)) {
                        $opts['curl'] = $curlOptions;
                    }
                    $configuredProxy = env('SIA_HTTP_PROXY');
                    // Optional: allow disabling peer verification for temporary debugging only
                    if (env('SIA_DISABLE_CURL_VERIFY', false)) {
                        $opts['verify'] = false;
                        Log::warning('SIA cURL peer verification disabled via SIA_DISABLE_CURL_VERIFY', ['licence' => $licenceNumber]);
                    }
                    $response = $this->sendSiaRequestWithFailover(
                        $method,
                        $params,
                        $initialFormParams,
                        $commonHeaders,
                        $opts,
                        $configuredProxy,
                        $transportRetries,
                        $transportBackoffMs
                    );

                    $status = $response->status();
                    $body = (string) $response->body();

                    // Capture and save the verbose cURL/Guzzle debug output for this attempt
                    if (($this->debug || env('SIA_PUBLIC_DEBUG', false)) && is_resource($debugStream)) {
                        rewind($debugStream);
                        $verbose = stream_get_contents($debugStream);
                        fclose($debugStream);
                        if (!empty(trim($verbose))) {
                            try {
                                $dbgFile = "sia_{$licenceNumber}_attempt{$idx}_curl_verbose_" . date('Y-m-d_His') . '.log';
                                $dbgPath = $this->savePublicDebugFile($dbgFile, $verbose);
                                if ($dbgPath) {
                                    $savedDebugFiles[] = $dbgPath;
                                    Log::info('SIA curl verbose saved', ['file' => $dbgPath, 'licence' => $licenceNumber, 'attempt' => $idx]);
                                }
                            } catch (\Throwable $e) {
                                Log::warning('Failed to save SIA curl verbose debug', ['exception' => $e->getMessage()]);
                            }
                        }
                    } else {
                        if (is_resource($debugStream)) {
                            fclose($debugStream);
                        }
                    }
                } catch (\Throwable $e) {
                    // Log and save debug info, then continue to next attempt instead of failing immediately
                    Log::warning('SIA request attempt exception', [
                        'licence' => $licenceNumber,
                        'attempt' => $idx,
                        'method' => $method,
                        'exception' => $e->getMessage(),
                    ]);
                    if ($this->debug || env('SIA_PUBLIC_DEBUG', false)) {
                        $errFile = "sia_{$licenceNumber}_attempt{$idx}_exception_" . date('Y-m-d_His') . '.log';
                        $path = $this->savePublicDebugFile($errFile, $e->getMessage() . "\n\n" . $e->getTraceAsString());
                        if ($path) {
                            $savedDebugFiles[] = $path;
                            Log::info('SIA exception saved', ['file' => $path]);
                        }
                    }
                    // try next attempt variant
                    continue;
                }

                // Save debug html on each attempt if enabled
                    if ($this->debug || env('SIA_PUBLIC_DEBUG', false)) {
                        $file = "sia_{$licenceNumber}_attempt{$idx}_" . date('Y-m-d_His') . '.html';
                        $path = $this->savePublicDebugFile($file, $body);
                        if ($path) {
                            $savedDebugFiles[] = $path;
                            Log::info('SIA HTML Response Saved', ['file' => $path, 'licence' => $licenceNumber, 'attempt' => $idx]);
                        }
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

                    $parsedResponse = $this->parseResponse($body, $licenceNumber);

                    // A 200 search page is not a valid result. Try the next attempt before failing.
                    if ($parsedResponse['success'] === true) {
                        return $parsedResponse;
                    }

                    $retryableErrors = [
                        'Unable to verify licence status',
                        'Unable to verify licence status (no valid response parsed)',
                        'Unable to determine licence active status from SIA response',
                    ];

                    if (in_array($parsedResponse['error'] ?? null, $retryableErrors, true)) {
                        $lastResponse = $response;
                        continue;
                    }

                    return $parsedResponse;
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
                        if ($this->debug || env('SIA_PUBLIC_DEBUG', false)) {
                            try {
                                $file500 = "sia_{$licenceNumber}_attempt{$idx}_status{$status}_" . date('Y-m-d_His') . '.html';
                                $path = $this->savePublicDebugFile($file500, $body);
                                if ($path) {
                                    $savedDebugFiles[] = $path;
                                    Log::info('SIA 5xx HTML Response Saved', ['file' => $path, 'licence' => $licenceNumber, 'attempt' => $idx]);
                                }
                            } catch (\Throwable $e) {
                                Log::warning('Failed to save SIA 5xx debug file', ['exception' => $e->getMessage()]);
                            }
                        }
                    // exponential backoff with jitter before next attempt (ms)
                    // Small fixed backoff — the job will be retried later if needed
                    usleep(300_000); // 300ms
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
        // Replace deprecated mb_convert_encoding(..., 'HTML-ENTITIES', ...)
        // Convert non-ASCII characters to numeric HTML entities while leaving tags intact
        $convmap = [0x80, 0xFFFF, 0, 0xFFFF];
        $html = mb_encode_numericentity($html, $convmap, 'UTF-8');
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

        $cardFields = [];
        if (preg_match_all('/<span[^>]*class="ax_paragraph"[^>]*>\s*(.*?)\s*<\/span>\s*<div[^>]*class="form-group"[^>]*>\s*(?:<div|<span)[^>]*class="(ax_h4(?:_green)?|ax_h5)"[^>]*>\s*(.*?)\s*<\/(?:div|span)>/is', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $label = trim(html_entity_decode(strip_tags($match[1])));
                $value = trim(html_entity_decode(strip_tags($match[3])));

                if ($label === '' || $value === '') {
                    continue;
                }

                $cardFields[$label] = preg_replace('/\s+/', ' ', $value);
                $this->mapField($result, $label, $cardFields[$label]);
            }
        }

        $firstName = $cardFields['First name'] ?? null;
        $surname = $cardFields['Surname'] ?? null;

        if ($firstName || $surname) {
            $result['holder_name'] = trim(implode(' ', array_filter([$firstName, $surname])));
        }

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
            'expiry' => '/(?:expiry|expires|expiry date)[:\s]*(?:<[^>]*>)*([A-Za-z0-9\/,\-\s]+)/i',
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

    /**
     * Save a debug file to the public disk and return a web-accessible path.
     *
     * @param string $filename
     * @param string $contents
     * @return string|null
     */
    protected function savePublicDebugFile(string $filename, string $contents): ?string
    {
        try {
            $path = trim($this->publicDebugPath, '/') . '/' . $filename;
            // If configured to write directly into the project's public/ folder, do that.
            if (env('SIA_PUBLIC_DEBUG_DIRECT', false)) {
                $full = public_path($path);
                $dir = dirname($full);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                file_put_contents($full, $contents);
                // Return web-accessible path relative to site root
                return $path;
            }

            // Default: write to storage/app/public and return storage/ path (requires storage:link)
            Storage::disk($this->publicDebugDisk)->put($path, $contents);
            return 'storage/' . $path;
        } catch (\Throwable $e) {
            Log::warning('Failed to save public debug file', ['exception' => $e->getMessage(), 'file' => $filename]);
            return null;
        }
    }

    /**
     * Send one SIA request with proxy failover candidates.
     */
    protected function sendSiaRequestWithFailover(
        string $method,
        array $params,
        array $initialFormParams,
        array $headers,
        array $baseOptions,
        $configuredProxy,
        int $transportRetries,
        int $transportBackoffMs
    ) {
        $proxyCandidates = $this->buildProxyCandidates($configuredProxy);
        $lastException = null;

        foreach ($proxyCandidates as $candidate) {
            try {
                $opts = $baseOptions;
                if ($candidate !== null) {
                    $opts['proxy'] = is_string($candidate)
                        ? ['http' => $candidate, 'https' => $candidate]
                        : $candidate;
                    Log::info('Using SIA HTTP proxy candidate', ['proxy' => $this->maskProxyForLog((string) $candidate)]);
                } else {
                    unset($opts['proxy']);
                    Log::warning('Using direct SIA connection fallback (no proxy)');
                }

                $candidateInitialFormParams = $initialFormParams;

                // Up to two tries per candidate: initial try + one token/session refresh retry on 5xx.
                for ($candidateTry = 0; $candidateTry < 2; $candidateTry++) {
                    if ($method === 'post' && empty($candidateInitialFormParams)) {
                        try {
                            $seedResponse = Http::withHeaders($headers)
                                ->withOptions($opts)
                                ->timeout($this->timeout)
                                ->accept('text/html')
                                ->get('https://services.sia.homeoffice.gov.uk/PublicRegister');
                            $seedBody = (string) ($seedResponse->body() ?? '');
                            $candidateInitialFormParams = $this->extractHiddenFormInputs($seedBody);
                            Log::info('SIA candidate seed completed', [
                                'candidate' => $candidate === null ? 'direct' : $this->maskProxyForLog((string) $candidate),
                                'hidden_fields' => count($candidateInitialFormParams),
                            ]);
                        } catch (\Throwable $seedException) {
                            Log::warning('SIA candidate seed failed', [
                                'candidate' => $candidate === null ? 'direct' : $this->maskProxyForLog((string) $candidate),
                                'exception' => $seedException->getMessage(),
                            ]);
                        }
                    }

                    $request = Http::withHeaders($headers)
                        ->withOptions($opts)
                        ->timeout($this->timeout)
                        ->retry(max(0, $transportRetries - 1), $transportBackoffMs, function ($exception) {
                            return $this->isRetryableTransportException($exception);
                        }, false)
                        ->accept('text/html');

                    if ($method === 'get') {
                        $response = $request->get($this->searchUrl, $params);
                    } else {
                        $postParams = array_merge($candidateInitialFormParams, $params);
                        $response = $request->asForm()->post($this->searchUrl, $postParams);
                    }

                    if ($response->status() >= 500 && $candidateTry === 0) {
                        // Force a fresh seed before second try on same route.
                        $candidateInitialFormParams = [];
                        continue;
                    }

                    return $response;
                }
            } catch (\Throwable $e) {
                $lastException = $e;
                Log::warning('SIA proxy candidate failed', [
                    'candidate' => $candidate === null ? 'direct' : $this->maskProxyForLog((string) $candidate),
                    'exception' => $e->getMessage(),
                ]);
                continue;
            }
        }

        if ($lastException) {
            throw $lastException;
        }

        throw new \RuntimeException('No SIA proxy candidate available');
    }

    /**
     * Build proxy candidates from configured proxy and optional pool.
     */
    protected function buildProxyCandidates($configuredProxy): array
    {
        $candidates = [];

        if (!empty($configuredProxy) && is_string($configuredProxy)) {
            $candidates[] = trim($configuredProxy);
        }

        $poolRaw = (string) env('SIA_HTTP_PROXY_POOL', '');
        if ($poolRaw !== '') {
            $items = preg_split('/[\r\n,;]+/', $poolRaw) ?: [];
            foreach ($items as $item) {
                $item = trim($item);
                if ($item !== '') {
                    $candidates[] = $item;
                }
            }
        }

        $candidates = array_values(array_unique($candidates));
        $allowDirectFallback = (bool) env('SIA_ALLOW_DIRECT_FALLBACK', true);

        if ($allowDirectFallback || empty($candidates)) {
            $candidates[] = null;
        }

        return $candidates;
    }

    /**
     * Mask proxy credentials before logging.
     */
    protected function maskProxyForLog(string $proxy): string
    {
        return preg_replace('/:\/\/([^:@\/]+):([^@\/]+)@/', '://$1:***@', $proxy) ?: $proxy;
    }

    /**
     * Retry only transient network/proxy/TLS transport errors.
     */
    protected function isRetryableTransportException($exception): bool
    {
        if (! $exception instanceof \Throwable) {
            return false;
        }

        $message = strtolower($exception->getMessage());
        $retryIndicators = [
            'curl error 35',
            'ssl_error_syscall',
            'unexpected eof',
            'connection timeout',
            'operation timed out',
            'timed out',
            'could not resolve host',
            'failed to connect',
            'connection reset',
            'recv failure',
            'empty reply from server',
        ];

        foreach ($retryIndicators as $indicator) {
            if (strpos($message, $indicator) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract hidden input fields from the SIA page for form submits.
     */
    protected function extractHiddenFormInputs(string $html): array
    {
        $fields = [];

        if (preg_match_all('/<input[^>]+type=["\']hidden["\'][^>]*>/i', $html, $hiddenInputs)) {
            foreach ($hiddenInputs[0] as $inputHtml) {
                if (preg_match('/name=["\']([^"\']+)["\']/i', $inputHtml, $m)) {
                    $name = $m[1];
                    $value = '';
                    if (preg_match('/value=["\']([^"\']*)["\']/i', $inputHtml, $v)) {
                        $value = $v[1];
                    }
                    $fields[$name] = $value;
                }
            }
        }

        return $fields;
    }
}
