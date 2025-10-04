<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class SiaLicenceChecker
{
    protected $searchUrl = 'https://services.sia.homeoffice.gov.uk/rolh';
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
        try {
            $licenceNumber = $this->sanitizeLicenceNumber($licenceNumber);
            
            if (!$this->isValidLicenceNumberFormat($licenceNumber)) {
                return $this->errorResponse('Invalid licence number format. Must be 16 digits.', $licenceNumber);
            }
            
            $cacheKey = 'sia_licence_' . $licenceNumber;
            
            if ($useCache && Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }
            
            $result = $this->performCheck($licenceNumber);
            
            if ($result['success'] && $result['valid'] && $useCache) {
                Cache::put($cacheKey, $result, now()->addHours(24));
            }
            
            return $result;
            
        } catch (Exception $e) {
            Log::error('SIA Licence Check Exception', [
                'licence' => $licenceNumber ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse('System error: ' . $e->getMessage());
        }
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
            // Make request to SIA website
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept' => 'text/html,application/xhtml+xml',
                ])
                ->asForm()
                ->post($this->searchUrl, [
                    'licenceNumber' => $licenceNumber,
                    'searchType' => 'licenceNumber',
                    'Submit' => 'Search',
                ]);
            
            if (!$response->successful()) {
                return $this->errorResponse('SIA website unavailable. Status: ' . $response->status(), $licenceNumber);
            }
            
            $html = $response->body();
            
            // Debug: Save HTML
            if ($this->debug) {
                $filename = 'sia_' . $licenceNumber . '_' . date('Y-m-d_His') . '.html';
                Storage::put('sia_debug/' . $filename, $html);
                Log::info('SIA HTML Response Saved', [
                    'file' => 'storage/app/sia_debug/' . $filename,
                    'licence' => $licenceNumber
                ]);
            }
            
            // Parse the response
            return $this->parseResponse($html, $licenceNumber);
            
        } catch (Exception $e) {
            Log::error('SIA Check Error', [
                'licence' => $licenceNumber,
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
            
            return $this->errorResponse('Check failed: ' . $e->getMessage(), $licenceNumber);
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
        
        // Check for success indicators - the licence number appearing in results
        $foundLicence = false;
        
        // Look for the licence number in the response
        if (stripos($html, $licenceNumber) !== false) {
            $foundLicence = true;
        }
        
        // Look for common result indicators
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
        
        // If we have the licence number OR multiple success indicators, consider it found
        if ($foundLicence || $indicatorCount >= 2) {
            $result = [
                'success' => true,
                'valid' => true,
                'licence_number' => $licenceNumber,
                'holder_name' => null,
                'licence_sector' => null,
                'licence_status' => null,
                'expiry_date' => null,
                'issue_date' => null,
                'error' => null,
            ];
            
            // Try to extract details
            $result = $this->extractDetails($html, $result);
            
            return $result;
        }
        
        // Couldn't determine status
        return $this->errorResponse('Unable to verify licence status', $licenceNumber);
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
                    if (empty($value) || strlen($value) > 200 || 
                        stripos($value, 'function') !== false || 
                        stripos($value, 'option[') !== false) {
                        continue;
                    }
                    
                    $this->mapField($result, $label, $value);
                }
            }
        }
        
        // Alternative: Try regex patterns
        $patterns = [
            'name' => '/(?:holder\s*name|name)[:\s]*(?:<[^>]*>)*([A-Z][a-zA-Z]+(?:\s+[A-Z][a-zA-Z]+)+)/',
            'status' => '/(?:licence\s*)?status[:\s]*(?:<[^>]*>)*([A-Za-z]+)/',
            'sector' => '/(?:licence\s*)?(?:sector|type)[:\s]*(?:<[^>]*>)*([A-Za-z\s]+)/',
            'expiry' => '/(?:expiry|expires)[:\s]*(?:<[^>]*>)*([\d\/\-]+)/',
        ];
        
        foreach ($patterns as $field => $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $value = trim(strip_tags($matches[1]));
                if (!empty($value) && strlen($value) < 100) {
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
            Cache::forget('sia_licence_' . $this->sanitizeLicenceNumber($licenceNumber));
        }
    }
}