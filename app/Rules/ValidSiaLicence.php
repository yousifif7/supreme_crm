<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Services\SiaLicenceChecker;
use Exception;

class ValidSiaLicence implements Rule
{
    protected $message = 'Unable to verify SIA licence.';
    protected $checkerResult = null;

    public function __construct()
    {
        //
    }

    public function passes($attribute, $value)
    {
        $value = is_string($value) ? trim(preg_replace('/\s+/', '', $value)) : $value;

        if (empty($value)) {
            // allow empty — use 'required' rule if needed
            return true;
        }

        try {
            /** @var SiaLicenceChecker $checker */
            $checker = app(SiaLicenceChecker::class);
            // perform live check; do not rely on stale cache for create/update
            $result = $checker->checkByLicenceNumber((string) $value, false);
            $this->checkerResult = $result;

            if (!is_array($result)) {
                // Allow the operation to proceed if service is unavailable
                \Log::warning('SIA checker returned unexpected response, allowing operation to proceed', ['value' => $value]);
                return true;
            }

            // If the SIA service successfully responded
            if (isset($result['success']) && $result['success'] === true) {
                // Check if the licence is valid
                if (isset($result['valid']) && $result['valid'] === true) {
                    // Licence is valid - allow to proceed
                    \Log::info('SIA licence verified successfully', ['value' => $value]);
                    return true;
                } else {
                    // Service responded successfully but licence is invalid
                    $this->message = 'SIA licence not found in register or is not valid.';
                    \Log::warning('SIA licence is invalid', ['value' => $value, 'error' => $result['error'] ?? 'Not found']);
                    return false;
                }
            }

            // If the SIA service is unavailable or returns an error, allow it to pass
            // This prevents blocking employee creation/update when the service is down
            \Log::warning('SIA licence check failed (service unavailable), allowing operation to proceed', ['error' => $result['error'] ?? 'Unknown error', 'value' => $value]);
            return true;

        } catch (Exception $e) {
            // Allow the operation to proceed if there's an exception
            \Log::warning('SIA licence check exception, allowing operation to proceed', ['error' => $e->getMessage(), 'value' => $value]);
            return true;
        }
    }

    public function message()
    {
        return $this->message;
    }

    public function getCheckerResult()
    {
        return $this->checkerResult;
    }
}