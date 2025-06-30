<?php

namespace App\Imports;

use App\Models\Site;
use App\Models\Client;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;

class SitesImport implements ToModel, WithHeadingRow, WithValidation, WithStartRow, SkipsOnError
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
        // Skip empty rows or rows where site_name is empty
        if (empty($row['site_name']) || trim($row['site_name']) === '') {
            return null;
        }

        // Find client by name only if client_name is provided
        $clientId = null;
        if (!empty($row['client_name']) && trim($row['client_name']) !== '') {
            $client = Client::where('client_name', trim($row['client_name']))->first();

            if (!$client) {
                // Log error but skip this row
                Log::warning("Client not found: " . $row['client_name']);
                return null;
            }

            $clientId = $client->id;
        }

        return new Site([
            'client_id' => $clientId,
            'site_name' => $row['site_name'],
            'address' => $row['address'] ?? null,
            'site_code' => $row['site_code'] ?? null,
            'post_code' => $row['post_code'] ?? null,
            'guard_names' => $row['guard_names'] ?? null,
            'contact_number' => $row['contact_number'] ?? null,
            'contact_person' => $row['contact_person'] ?? null,
            'note' => $row['note'] ?? null,
            'start_time' => $row['start_time'] ?? null,
            'end_time' => $row['end_time'] ?? null,
            'break_time' => $row['break_time'] ?? null,
            'guard_rate' => !empty($row['guard_rate']) ? (float)$row['guard_rate'] : null,
            'office_rate' => !empty($row['office_rate']) ? (float)$row['office_rate'] : null,
            'billable_rate' => !empty($row['billable_rate']) ? (float)$row['billable_rate'] : null,
            'payable_rate' => !empty($row['payable_rate']) ? (float)$row['payable_rate'] : null,
        ]);
    }

    public function rules(): array
    {
        return [
            '#' => 'nullable', // Ignore the # column
            'client_name' => 'nullable|max:255',
            'site_name' => 'required|max:255',
            'address' => 'nullable|max:255',
            'site_code' => 'nullable|max:50',
            'post_code' => 'nullable|max:50',
            'guard_names' => 'nullable|max:255',
            'contact_number' => 'nullable|max:50',
            'contact_person' => 'nullable|max:255',
            'note' => 'nullable|max:1000',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'break_time' => 'nullable',
            'guard_rate' => 'nullable|numeric|min:0',
            'office_rate' => 'nullable|numeric|min:0',
            'billable_rate' => 'nullable|numeric|min:0',
            'payable_rate' => 'nullable|numeric|min:0',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'site_name.required' => 'Site Name is required.',
            'guard_rate.numeric' => 'Guard rate must be a valid number.',
            'office_rate.numeric' => 'Office rate must be a valid number.',
            'billable_rate.numeric' => 'Billable rate must be a valid number.',
            'payable_rate.numeric' => 'Payable rate must be a valid number.',
            'guard_rate.min' => 'Guard rate must be at least 0.',
            'office_rate.min' => 'Office rate must be at least 0.',
            'billable_rate.min' => 'Billable rate must be at least 0.',
            'payable_rate.min' => 'Payable rate must be at least 0.',
        ];
    }

    /**
     * Safely get column value with default
     */
    private function getColumnValue(array $row, string $column, $default = null)
    {
        return array_key_exists($column, $row) ? $row[$column] : $default;
    }
}
