<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\User;
use App\Models\Subcontractor;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

class EmployeesImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError
{
    use SkipsErrors;

    public function model(array $row)
    {
        // Skip empty rows or rows where full_name is empty
        if (empty($row['full_name']) || trim($row['full_name']) === '') {
            return null;
        }

        // Parse full name into first and last name
        $fullName = trim($row['full_name']);
        $nameParts = explode(' ', $fullName, 2);
        $firstName = $nameParts[0];
        $lastName = isset($nameParts[1]) ? $nameParts[1] : '';

        $userId = null;
        $subcontractorId = null;

        // Create user if username is provided (username will be used as email)
        if (!empty($row['username']) && trim($row['username']) !== '') {
            $password = 'password123'; // Default password

            $user = User::create([
                'name' => $fullName,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'username' => $row['username'],
                'email' => $row['username'], // Use username as email
                'password' => Hash::make($password),
            ]);

            // Assign security_staff role
            $role = Role::firstOrCreate(['name' => 'security_staff']);
            $user->assignRole($role);

            $userId = $user->id;
        }

        // Handle subcontractor
        $subcontractorValue = null;
        if (!empty($row['subcontractor']) && trim($row['subcontractor']) !== '') {
            $subcontractor = Subcontractor::where('company_name', trim($row['subcontractor']))->first();

            if (!$subcontractor) {
                // Create new subcontractor if doesn't exist
                $subcontractor = Subcontractor::create([
                    'company_name' => trim($row['subcontractor']),
                    'is_active' => true,
                ]);
            }

            $subcontractorValue = $subcontractor->id;
        }

        return new Employee([
            'user_id' => $userId,
            'entry_date' => $this->parseDate($row['date_of_registration'] ?? null),
            'fore_name' => $firstName,
            'sur_name' => $lastName,
            'subcontractor' => $subcontractorValue,
            'guard_rate' => !empty($row['pay_rate']) ? (float)$row['pay_rate'] : null,
            'contact' => $row['contact'] ?? null,
            'sia_licence' => $row['sia_number'] ?? null,
            'service_type' => $row['service_type'] ?? null,
            'sia_expiry' => $this->parseDate($row['sia_expiry'] ?? null),
            'dob' => $this->parseDate($row['dob'] ?? null),
            'email' => $row['email'] ?? null, // Use username as email, fallback to email field
            'address_group' => $row['address_with_post_code'] ?? $row['address_group'] ?? null,
            'account_name' => $row['account_name'] ?? null,
            'sort_code' => $row['sort_code'] ?? null,
            'account_number' => $row['account_number'] ?? null,
            'ni_number' => $row['ni_number'] ?? null,
            'visa_type' => $row['visa_status'] ?? null,
            'visa_expiry' => $this->parseDate($row['visa_expiry_date'] ?? null),
            'status' => 'active', // Default status
            'gender' => 'male', // Default gender (required field)
            'licence_type' => 'SIA', // Default licence type (required field)
            'place_work' => 'Various', // Default place of work (required field)
            'hour_per_week' => 40, // Default hours (required field)
            'passport_no' => null,
            'passport_expiry' => now()->addYears(10), // Default passport expiry (required field)
            'pin' => rand(1000, 9999), // Generate random PIN (required field)
            'share_code' => null, // Default share code (required field)
            'share_code_expiry' => now()->addYears(1), // Default share code expiry (required field)
        ]);
    }

    public function rules(): array
    {
        return [
            '#' => 'nullable', // Ignore the # column
            'date_of_registration' => 'nullable',
            'full_name' => 'required|max:255',
            'subcontractor' => 'nullable|max:255',
            'pay_rate' => 'nullable|min:0',
            'contact' => 'nullable|max:255',
            'sia_number' => 'nullable|max:255',
            'service_type' => 'nullable|max:255',
            'sia_expiry' => 'nullable',
            'dob' => 'nullable',
            'email' => 'nullable|email|max:255',
            'username' => 'nullable|email|max:255|unique:users,email',
            'address_with_post_code' => 'nullable|max:500',
            'address_group' => 'nullable|max:500',
            'account_name' => 'nullable|max:255',
            'sort_code' => 'nullable|max:255',
            'account_number' => 'nullable|max:255',
            'ni_number' => 'nullable|max:255',
            'visa_status' => 'nullable|max:255',
            'visa_expiry_date' => 'nullable',
            'remaining_sia_days' => 'nullable',
            'remaining_visa_days' => 'nullable',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'full_name.required' => 'Full name is required.',
            'username.unique' => 'This username (email) is already registered.',
            'username.email' => 'Username must be a valid email address.',
            'email.email' => 'Email must be a valid email address.',
            'pay_rate.numeric' => 'Pay rate must be a valid number.',
            'pay_rate.min' => 'Pay rate must be at least 0.',
        ];
    }

    /**
     * Parse date from various formats
     */
    private function parseDate($dateString): ?string
    {
        if (empty($dateString) || trim($dateString) === '') {
            return null;
        }

        try {
            // Handle format like "02-Aug-24"
            if (preg_match('/^\d{2}-[A-Za-z]{3}-\d{2}$/', $dateString)) {
                $date = Carbon::createFromFormat('d-M-y', $dateString);
            }
            // Handle format like "02-Aug-2024"
            elseif (preg_match('/^\d{2}-[A-Za-z]{3}-\d{4}$/', $dateString)) {
                $date = Carbon::createFromFormat('d-M-Y', $dateString);
            }
            // Handle standard date formats
            else {
                $date = Carbon::parse($dateString);
            }

            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning("Could not parse date: " . $dateString);
            return null;
        }
    }

    /**
     * Safely get column value with default
     */
    private function getColumnValue(array $row, string $column, $default = null)
    {
        return array_key_exists($column, $row) ? $row[$column] : $default;
    }
}
