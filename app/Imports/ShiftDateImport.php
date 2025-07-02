<?php

namespace App\Imports;

use App\Models\Shift;
use App\Models\ShiftDate;
use App\Models\Client;
use App\Models\Site;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;

/**
 * ShiftDateImport Class
 *
 * Imports shift data from Excel files with the following expected columns:
 * - # (Column A): Row number (optional)
 * - Date (Column B): Shift date in format like "01-May-2025" (required)
 * - Day (Column C): Day of week like "Thursday" (optional)
 * - Officer (Column D): Staff/Officer name (optional)
 * - Client (Column E): Client name (required - must exist in database)
 * - Site (Column F): Site name (required - must exist in database)
 * - Phone (Column G): Contact phone (optional)
 * - Start (Column H): Start time like "06:00" (required)
 * - End (Column I): End time like "18:00" (required)
 * - Lost Time (Column J): Lost time amount (optional)
 * - Hours (Column K): Total hours like "12" (optional - calculated if not provided)
 * - Comments (Column L): Shift comments (optional)
 *
 * Expected Excel format:
 * - Header row should be in row 1
 * - Data starts from row 2
 * - Client and Site names should match existing records in the database
 * - Officer names are matched against fore_name, sur_name, or full name in employees table
 */
class ShiftDateImport implements ToModel, WithHeadingRow, WithValidation, WithStartRow, SkipsOnError, SkipsOnFailure
{
    use SkipsErrors;

    private $failures = [];
    private $currentRow = 1; // Track current row number
    private $successCount = 0; // Track successful imports

    public function startRow(): int
    {
        return 2; // Start from row 2, since row 1 is the header
    }

    public function headingRow(): int
    {
        return 1; // Header is in row 1
    }

    public function model(array $row)
    {
        $this->currentRow++; // Increment row counter
        
        // Skip empty rows or rows where required fields are empty
        if (empty($row['date']) || empty($row['client']) || empty($row['site']) || empty($row['start']) || empty($row['end'])) {
            return null;
        }

        try {
            // Parse and validate date
            $shiftDate = $this->parseDate($row['date']);
            if (!$shiftDate) {
                $this->failures[] = [
                    'row' => $this->currentRow,
                    'error' => "Invalid date format: {$row['date']}"
                ];
                return null;
            }

            // Find client by name
            $client = Client::where('client_name', 'like', '%' . trim($row['client']) . '%')->first();
            if (!$client) {
                $this->failures[] = [
                    'row' => $this->currentRow,
                    'error' => "Client '{$row['client']}' not found"
                ];
                return null;
            }

            // Find site by name (optionally filter by client)
            $site = Site::where('site_name', 'like', '%' . trim($row['site']) . '%')
                       ->where('client_id', $client->id)
                       ->first();

            // If not found in client's sites, try any site with that name
            if (!$site) {
                $site = Site::where('site_name', 'like', '%' . trim($row['site']) . '%')->first();
            }

            if (!$site) {
                $this->failures[] = [
                    'row' => $this->currentRow,
                    'error' => "Site '{$row['site']}' not found"
                ];
                return null;
            }

            // Find officer/staff by name (optional)
            $staff = null;
            $staffId = null;
            if (!empty($row['officer'])) {
                $officerName = trim($row['officer']);
                $staff = Employee::where(function($query) use ($officerName) {
                    $query->where('fore_name', 'like', '%' . $officerName . '%')
                          ->orWhere('sur_name', 'like', '%' . $officerName . '%')
                          ->orWhereRaw("CONCAT(fore_name, ' ', sur_name) LIKE ?", ['%' . $officerName . '%']);
                })->first();

                if ($staff) {
                    $staffId = $staff->id;

                    // Check if staff SIA license is expired
                    if ($staff->sia_expiry && Carbon::parse($staff->sia_expiry)->lt(now())) {
                        Log::warning("Staff {$officerName} has expired SIA license for shift on {$shiftDate}");
                    }
                }
            }

            // Parse start and end times
            $startTime = $this->parseTime($row['start']);
            $endTime = $this->parseTime($row['end']);

            if (!$startTime || !$endTime) {
                $this->failures[] = [
                    'row' => $this->currentRow,
                    'error' => "Invalid time format - Start: {$row['start']}, End: {$row['end']}"
                ];
                return null;
            }

            // Validate time logic
            $startCarbon = Carbon::createFromFormat('H:i:s', $startTime);
            $endCarbon = Carbon::createFromFormat('H:i:s', $endTime);

            if ($startCarbon->eq($endCarbon)) {
                $this->failures[] = [
                    'row' => $this->currentRow,
                    'error' => "Start time and end time cannot be the same"
                ];
                return null;
            }

            // Calculate total hours
            $totalHours = $this->calculateTotalHours($startTime, $endTime);

            // Override with provided hours if available and valid
            if (!empty($row['hours']) && is_numeric($row['hours']) && $row['hours'] > 0) {
                $totalHours = (float)$row['hours'];
            }

            // Get day abbreviation from date
            $dayAbbr = Carbon::parse($shiftDate)->format('D');

            // Check for overlapping shifts if staff is assigned
            if ($staffId) {
                $overlappingShiftDate = ShiftDate::where('staff_id', $staffId)
                    ->where('shift_date', $shiftDate)
                    ->where(function($query) use ($startTime, $endTime) {
                        $query->where(function($q) use ($startTime, $endTime) {
                            $q->where('start_time', '<', $endTime)
                              ->where('end_time', '>', $startTime);
                        });
                    })
                    ->exists();

                if ($overlappingShiftDate) {
                    Log::warning("Staff {$row['officer']} already has an overlapping shift on {$shiftDate}");
                }
            }

            // Create or find existing shift
            $shift = Shift::firstOrCreate([
                'client_id' => $client->id,
                'site_id' => $site->id,
                'start_shift' => $startTime,
                'end_shift' => $endTime,
                'from_shift' => $shiftDate,
                'to_shift' => $shiftDate,
            ], [
                'staff_id' => $staffId,
                'number_shift' => 1,
                'site_rate' => $site->guard_rate ?? $client->guard_rate ?? 0,
                'employee_rate' => $site->payable_rate ?? 0,
                'comments' => $row['comments'] ?? null,
                'days' => json_encode([$dayAbbr]),
                'lost_time' => $row['lost_time'] ?? null,
                'is_assign' => $staffId ? 1 : 0,
                'restrict_start_time' => 0,
                'enforce_picture_check' => 0,
                'restrict_location_check' => 0,
            ]);

            // Create shift date entry
            $shiftDate = new ShiftDate([
                'shift_id' => $shift->id,
                'staff_id' => $staffId,
                'shift_date' => $shiftDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'total_hours' => $totalHours,
                'is_assign' => $staffId ? 1 : 0,
                'break_time' => null, // Could be calculated or set from lost time
            ]);

            $this->successCount++; // Increment success counter
            return $shiftDate;

        } catch (\Exception $e) {
            // Add to failures array instead of logging
            $this->failures[] = [
                'row' => $this->currentRow,
                'error' => $e->getMessage()
            ];
            return null;
        }
    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->failures[] = [
                'row' => $failure->row(),
                'error' => implode(', ', $failure->errors())
            ];
        }
    }

    public function getFailures()
    {
        return $this->failures;
    }

    public function getSuccessCount()
    {
        return $this->successCount;
    }

    public function getFailureCount()
    {
        return count($this->failures);
    }

    public function rules(): array
    {
        return [
            '#' => 'nullable', // Ignore the # column
            'date' => 'required',
            'day' => 'nullable',
            'officer' => 'nullable|max:255',
            'client' => 'required|max:255',
            'site' => 'required|max:255',
            'phone' => 'nullable|max:255',
            'start' => 'required',
            'end' => 'required',
            'lost_time' => 'nullable',
            'hours' => 'nullable|numeric|min:0',
            'comments' => 'nullable|max:1000',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'date.required' => 'Date is required.',
            'client.required' => 'Client name is required.',
            'site.required' => 'Site name is required.',
            'start.required' => 'Start time is required.',
            'end.required' => 'End time is required.',
            'hours.numeric' => 'Hours must be a valid number.',
            'hours.min' => 'Hours must be at least 0.',
            'comments.max' => 'Comments cannot exceed 1000 characters.',
        ];
    }

    /**
     * Parse date from various formats
     */
    private function parseDate($dateValue): ?string
    {
        if (empty($dateValue)) {
            return null;
        }

        try {
            // Handle various date formats
            $formats = ['d-M-Y', 'Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y'];

            foreach ($formats as $format) {
                try {
                    $date = Carbon::createFromFormat($format, trim($dateValue));
                    return $date->format('Y-m-d');
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Try Carbon's flexible parsing as last resort
            $date = Carbon::parse(trim($dateValue));
            return $date->format('Y-m-d');

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse time from various formats
     */
    private function parseTime($timeValue): ?string
    {
        if (empty($timeValue)) {
            return null;
        }

        try {
            $timeValue = trim($timeValue);

            // Handle formats like "06:00", "6:00", "06.00", "6"
            if (preg_match('/^(\d{1,2})[:.h]?(\d{2})?$/', $timeValue, $matches)) {
                $hour = (int)$matches[1];
                $minute = isset($matches[2]) ? (int)$matches[2] : 0;

                if ($hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59) {
                    return sprintf('%02d:%02d:00', $hour, $minute);
                }
            }

            // Try Carbon's flexible parsing
            $time = Carbon::parse($timeValue);
            return $time->format('H:i:s');

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Calculate total hours between start and end time
     */
    private function calculateTotalHours($start, $end): float
    {
        try {
            $startTime = Carbon::createFromFormat('H:i:s', $start);
            $endTime = Carbon::createFromFormat('H:i:s', $end);

            // Handle overnight shifts (e.g. 22:00 to 06:00 next day)
            if ($endTime->lessThanOrEqualTo($startTime)) {
                $endTime->addDay();
            }

            $totalMinutes = $startTime->diffInMinutes($endTime);
            return round($totalMinutes / 60, 2);

        } catch (\Exception $e) {
            return 0;
        }
    }
}

/*
 * Implementation Summary:
 *
 * This ShiftDateImport class provides comprehensive Excel import functionality for shifts with the following features:
 *
 * 1. **Excel Format Support**:
 *    - Headers in row 1, data starting from row 2
 *    - Supports columns: #, Date, Day, Officer, Client, Site, Phone, Start, End, Lost Time, Hours, Comments
 *    - Required fields: Date, Client, Site, Start, End
 *
 * 2. **Data Validation**:
 *    - Validates required fields and data formats
 *    - Checks for existing clients and sites in database
 *    - Validates time formats and logic
 *    - Warns about SIA license expiry and overlapping shifts
 *
 * 3. **Smart Matching**:
 *    - Fuzzy matching for client names using LIKE search
 *    - Sites matched by name, preferring client-specific sites
 *    - Officer names matched against fore_name, sur_name, or full name
 *
 * 4. **Date/Time Parsing**:
 *    - Supports multiple date formats (d-M-Y, Y-m-d, d/m/Y, etc.)
 *    - Flexible time parsing (06:00, 6:00, 06.00, 6, etc.)
 *    - Handles overnight shifts correctly
 *
 * 5. **Error Handling**:
 *    - Row-specific error tracking with line numbers
 *    - Collects all failures for user feedback instead of logging
 *    - Skips invalid rows without stopping import
 *    - Provides detailed error messages with exact row numbers
 *    - Shows import summary with success/failure counts
 *
 * 6. **Integration**:
 *    - Creates both Shift and ShiftDate records
 *    - Maintains relationship integrity
 *    - Calculates total hours automatically
 *    - Sets appropriate flags and rates from site/client data
 *    - Tracks successful imports and provides detailed feedback
 *
 * 7. **User Feedback**:
 *    - Shows specific row numbers that failed to import
 *    - Provides clear error messages for each failed row
 *    - Displays import summary (e.g., "15 successful, 3 failed")
 *    - Uses different alert types based on import results
 *
 * Usage: Already integrated in ExportController::importShiftExcel()
 */
