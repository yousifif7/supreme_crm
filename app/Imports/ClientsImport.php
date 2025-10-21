<?php

namespace App\Imports;

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

class ClientsImport implements ToModel, WithHeadingRow, WithValidation, WithStartRow, SkipsOnError
{
    use SkipsErrors;

    public function startRow(): int
    {
        return 3; // Start from row 3, since row 2 is the header
    }

    public function headingRow(): int
    {
        return 2; // Header is in row 2
    }

    public function model(array $row)
    {
        // Skip empty rows or rows where name is empty
        if (empty($row['name']) || trim($row['name']) === '') {
            return null;
        }

        $userId = null;

        // Create user only if username is provided
        if (!empty($row['name'])) {
            $password = !empty($row['password']) ? $row['password'] : 'password123';

              // Validate contact_email or generate random one
    if (empty($row['contact_email']) || !filter_var($row['contact_email'], FILTER_VALIDATE_EMAIL)) {
        // Generate a random email using username or random string
        $random = Str::random(8);
        $row['contact_email'] = strtolower(preg_replace('/\s+/', '', $row['name'])) . "_{$random}@example.com";
    }

            $user = User::create([
                'name' => $row['name'],
                'first_name' => $row['name'],
                'last_name' => '',
                'username' => $row['name'],
                'email' => $row['contact_email'],
                'password' => Hash::make($password),
            ]);

            // Assign client role
            $role = Role::firstOrCreate(['name' => 'client']);
            $user->assignRole($role);

            $userId = $user->id;
        }

        return new Client([
            'user_id' => $userId,
            'client_name' => $row['name'],
            'address' => $row['address'] ?? null,
            'contact_number' => $row['contact_number'] ?? null,
            'contact_person' => $row['contact_person'] ?? null,
            'email' => $row['contact_email'] ?? null,
            'invoice_terms' => $row['invoice_terms'] ?? null,
            'payment_terms' => $row['payment_terms'] ?? null,
            'contract_start' => !empty($row['contract_start']) ? date('Y-m-d', strtotime($row['contract_start'])) : null,
            'contract_end' => !empty($row['contract_end']) ? date('Y-m-d', strtotime($row['contract_end'])) : null,
            'guard_rate' => !empty($row['guard_rate']) ? (float)$row['guard_rate'] : null,
            'office_rate' => !empty($row['office_rate']) ? (float)$row['office_rate'] : null,
            'vat' => $this->convertToBoolean($row['vat'] ?? 'No'),
        ]);
    }

    public function rules(): array
    {
        return [
            '#' => 'nullable', // Ignore the # column
            'name' => 'nullable|max:255',
            'address' => 'nullable|max:355',
            'contact_number' => [
                'nullable',
                // 'min:9',
                'max:255',
                // 'regex:/^(\+?\d{1,3})?[-.\s]?\(?\d+\)?([-.\s]?\d+)*$/'
            ],
            'contact_person' => 'nullable|max:255',
            'contact_email' => 'nullable|email|max:255',
            'username' => 'nullable|email|max:255|unique:users,email',
            'password' => 'nullable|min:6',
            'invoice_terms' => 'nullable|max:255',
            'payment_terms' => 'nullable|max:255',
            'contract_start' => 'nullable|date',
            'contract_end' => 'nullable|date|after_or_equal:contract_start',
            'guard_rate' => 'nullable|numeric|min:0',
            'office_rate' => 'nullable|numeric|min:0',
            'vat' => 'nullable',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'contact_number.regex' => 'The contact number format is invalid. It should be a valid phone number.',
            'username.unique' => 'This username (email) is already registered.',
            'username.email' => 'Username must be a valid email address.',
            'password.min' => 'Password must be at least 6 characters long.',
            'contact_email.email' => 'Contact email must be a valid email address.',
            'contract_end.after_or_equal' => 'Contract end date must be after or equal to contract start date.',
            'guard_rate.numeric' => 'Guard rate must be a valid number.',
            'office_rate.numeric' => 'Office rate must be a valid number.',
            'guard_rate.min' => 'Guard rate must be at least 0.',
            'office_rate.min' => 'Office rate must be at least 0.',
        ];
    }

    /**
     * Convert string values to boolean
     */
    private function convertToBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_null($value) || $value === '') {
            return false;
        }

        $value = strtolower(trim((string)$value));

        return in_array($value, ['yes', 'true', '1', 'active', 'on']);
    }
}
