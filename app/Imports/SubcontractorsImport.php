<?php

namespace App\Imports;

use App\Models\Subcontractor;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Spatie\Permission\Models\Role;

class SubcontractorsImport implements ToModel, WithHeadingRow, WithValidation, WithStartRow, SkipsOnError
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
        // Skip empty rows or rows where company_name is empty
        if (empty($row['company_name']) || trim($row['company_name']) === '') {
            return null;
        }

        $userId = null;

        // Create user only if username is provided
        if (!empty($row['username'])) {
            $password = !empty($row['password']) ? $row['password'] : 'password123';

            $user = User::create([
                'name' => $row['company_name'],
                'first_name' => $row['company_name'],
                'last_name' => '',
                'username' => $row['username'],
                'email' => $row['username'],
                'password' => Hash::make($password),
            ]);

            // Assign subcontractor role
            $role = Role::firstOrCreate(['name' => 'subcontractor']);
            $user->assignRole($role);

            $userId = $user->id;
        }

        return new Subcontractor([
            'user_id' => $userId,
            'company_name' => $row['company_name'],
            'company_address' => $row['company_address'] ?? null,
            'contact_person' => $row['contact_person'] ?? null,
            'contact_number' => $row['contact_number'] ?? null,
            'email' => $row['email'] ?? null,
            'invoice_terms' => $row['invoice_terms'] ?? null,
            'payment_terms' => $row['payment_terms'] ?? null,
            'department' => $row['department'] ?? null,
            'vat_registered' => $this->convertToBoolean($row['vat_registered'] ?? 'No'),
            'vat_number' => $row['vat_number'] ?? null,
            'pay_rate' => !empty($row['pay_rate']) ? (float)$row['pay_rate'] : null,
            'pmva_trained_officer' => $this->convertToBoolean($row['pmva_trained_officer'] ?? 'No'),
            'is_active' => $this->convertToBoolean($row['is_active'] ?? 'Active'),
        ]);
    }

    public function rules(): array
    {
        return [
            '#' => 'nullable', // Ignore the # column
            'company_name' => 'required|string|max:255',
            'company_address' => 'nullable|max:355',
            'contact_person' => 'nullable|max:255',
            'contact_number' => [
                'nullable',
                'min:9',
                'max:255',
                // 'regex:/^(\+?\d{1,3})?[-.\s]?\(?\d+\)?([-.\s]?\d+)*$/'
            ],
            'email' => 'nullable|email|max:255',
            'username' => 'nullable|email|max:255|unique:users,email',
            'password' => 'nullable|min:6',
            'invoice_terms' => 'nullable|max:255',
            'payment_terms' => 'nullable|max:255',
            'department' => 'nullable|max:255',
            'vat_registered' => 'nullable',
            'vat_number' => 'nullable|max:255',
            'pay_rate' => 'nullable',
            'pmva_trained_officer' => 'nullable|string',
            'is_active' => 'nullable|string',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'contact_number.regex' => 'The contact number format is invalid. It should be a valid phone number.',
            'username.unique' => 'This username (email) is already registered.',
            'username.email' => 'Username must be a valid email address.',
            'password.min' => 'Password must be at least 6 characters long.',
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

    /**
     * Safely get column value with default
     */
    private function getColumnValue(array $row, string $column, $default = null)
    {
        return array_key_exists($column, $row) ? $row[$column] : $default;
    }
}
