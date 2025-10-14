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
                $this->message = 'Unexpected response from SIA checker.';
                return false;
            }

            if (isset($result['success']) && isset($result['valid']) && $result['success'] && $result['valid']) {
                return true;
            }

            // provide remote error if available
            $this->message = $result['error'] ?? 'SIA licence could not be verified or is not found.';
            return false;
        } catch (Exception $e) {
            $this->message = 'SIA licence check failed: ' . $e->getMessage();
            return false;
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