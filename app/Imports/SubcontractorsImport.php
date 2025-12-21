<?php

namespace App\Imports;

use App\Models\Subcontractor;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Spatie\Permission\Models\Role;

class SubcontractorsImport implements ToModel, WithHeadingRow, WithStartRow, SkipsOnError
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
        // No validation: import whatever fields exist in the row.
        // Determine company name from possible keys or first non-empty cell
        $company = $this->getColumnValue($row, 'company_name');
        if (empty($company)) {
            $company = $this->getColumnValue($row, 'company');
        }
        if (empty($company)) {
            $company = $this->getColumnValue($row, 'company name');
        }
        if (empty($company)) {
            // pick best candidate from row: prefer textual values, not pure numbers/emails/phones
            $company = $this->detectCompanyFromRow($row);
        }

        // Persist subcontractor directly and attach/create user per provided snippet
        $s = Subcontractor::create([
            'company_name' => $company ?? null,
            'company_address' => $this->getColumnValue($row, 'company_address'),
            'contact_person' => $this->getColumnValue($row, 'contact_person'),
            'contact_number' => $this->getColumnValue($row, 'contact_number'),
            'email' => $this->getColumnValue($row, 'email'),
            'invoice_terms' => $this->getColumnValue($row, 'invoice_terms'),
            'payment_terms' => $this->getColumnValue($row, 'payment_terms'),
            'department' => $this->getColumnValue($row, 'department'),
            'vat_registered' => $this->convertToBoolean($this->getColumnValue($row, 'vat_registered') ?? 'No'),
            'vat_number' => $this->getColumnValue($row, 'vat_number'),
            'pay_rate' => is_numeric($this->getColumnValue($row, 'pay_rate')) ? (float)$this->getColumnValue($row, 'pay_rate') : null,
            'pmva_trained_officer' => $this->convertToBoolean($this->getColumnValue($row, 'pmva_trained_officer') ?? 'No'),
            'is_active' => $this->convertToBoolean($this->getColumnValue($row, 'is_active') ?? 'Active'),
        ]);

        $email = $s->email ?: Str::slug($s->company_name).'_'.$s->id.'@example.com';
        $username = Str::slug($s->company_name).'_'.$s->id;

        $user = User::where('email', $email)
                    ->orWhere('username', $username)
                    ->first();

        if (!$user) {
            $user = User::create([
                'name'       => $s->company_name,
                'first_name' => $s->company_name,
                'last_name'  => '',
                'username'   => $username,
                'email'      => $email,
                'password'   => Hash::make('password'),
            ]);

            $role = Role::firstOrCreate(['name' => 'subcontractor']);
            $user->assignRole($role);
        }

        $s->user_id = $user->id;
        $s->email   = $user->email;
        $s->save();

        // Return null because we already persisted the subcontractor
        return null;
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

    /**
     * Heuristic to pick the most likely company name from a row.
     */
    private function detectCompanyFromRow(array $row): ?string
    {
        $best = null;
        $bestScore = -INF;

        foreach ($row as $cell) {
            $val = trim((string)$cell);
            if ($val === '') {
                continue;
            }

            $score = 0;

            // Prefer values that contain letters
            if (preg_match('/[a-zA-Z]/', $val)) {
                $score += 5;
            }

            // Penalize pure numbers (likely an index column)
            if (preg_match('/^\d+$/', $val)) {
                $score -= 10;
            }

            // Penalize emails
            if (filter_var($val, FILTER_VALIDATE_EMAIL)) {
                $score -= 5;
            }

            // Penalize phone-like values (digits, spaces, +, -, parentheses)
            if (preg_match('/^[\d\+\-\s\(\)]+$/', $val)) {
                $score -= 6;
            }

            // Slight bonus for multi-word values (company names often contain spaces)
            if (str_word_count($val) > 1) {
                $score += 2;
            }

            // Slight bonus for longer strings
            if (strlen($val) > 8) {
                $score += 1;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $val;
            }
        }

        return $best;
    }
}
