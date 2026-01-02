<?php

namespace App\Imports;

use App\Models\Shift;
use App\Models\ShiftDate;
use App\Models\Client;
use App\Models\Site;
use App\Models\Employee;
use App\Models\User;
use App\Models\Patrol;
use App\Models\CheckCall;
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
class ShiftDateImport implements ToModel, WithHeadingRow, WithStartRow, SkipsOnError, SkipsOnFailure
{
    use SkipsErrors;

    private $failures = [];
    private $currentRow = 1; // Track current row number
    private $successCount = 0; // Track successful imports

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
        $this->currentRow++; // Increment row counter

        // Detect required columns robustly (headers may be malformed)
        $dateRaw = $this->findColumnValue($row, ['date', 'shift_date', 'shift date']);
        $clientRaw = $this->findColumnValue($row, ['Client']);
        $siteRaw = $this->findColumnValue($row, ['site']);
        $startRaw = $this->findColumnValue($row, ['start', 'start_time', 'start time']);
        $endRaw = $this->findColumnValue($row, ['end', 'end_time', 'end time']);

        // Normalize common placeholders like N/A, -, none
        $clientRaw = $this->normalizeCell($clientRaw);
        $siteRaw = $this->normalizeCell($siteRaw);
        $startRaw = $this->normalizeCell($startRaw);
        $endRaw = $this->normalizeCell($endRaw);

        // If date missing, try to detect by parsing any value in the row
        if (is_null($dateRaw)) {
            foreach ($row as $cell) {
                if ($this->parseDate($cell)) {
                    $dateRaw = $cell;
                    break;
                }
            }
        }

        if (is_null($startRaw)) {
            foreach ($row as $cell) {
                if ($this->parseTime($cell)) {
                    $startRaw = $cell;
                    break;
                }
            }
        }

        if (is_null($endRaw)) {
            foreach ($row as $cell) {
                if ($this->parseTime($cell)) {
                    $endRaw = $cell;
                    break;
                }
            }
        }

        // Basic required-field check
        if (empty($dateRaw) || empty($clientRaw) || empty($siteRaw) || empty($startRaw) || empty($endRaw)) {
            $this->failures[] = [
                'row' => $this->currentRow,
                'error' => 'Missing required field(s): date/client/site/start/end'
            ];
            return null;
        }

        try {
            // Parse and validate date
            $shiftDate = $this->parseDate($dateRaw);
            if (!$shiftDate) {
                $this->failures[] = [
                    'row' => $this->currentRow,
                    'error' => "Invalid date format: {$dateRaw}"
                ];
                return null;
            }

            // Find client by normalized-name matching in PHP (handles header differences/punctuation)
            // Prefer matching against User records with role 'client' (most of the app uses users as clients).
            $client = null;
            if (!empty($clientRaw)) {
                $search = $this->normalizeForCompare($clientRaw);

                // 1) Try to match against Users with role 'client'
                try {
                    $users = User::role('client')->get();
                    foreach ($users as $u) {
                        $full = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
                        if ($full) {
                            if (strpos($this->normalizeForCompare($full), $search) !== false) {
                                $client = $u; // user record
                                break;
                            }
                        }
                    }

                    // fuzzy fallback on user full names
                    if (!$client) {
                        $best = null; $bestDist = PHP_INT_MAX;
                        foreach ($users as $u) {
                            $full = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
                            $dist = levenshtein($search, $this->normalizeForCompare($full));
                            if ($dist < $bestDist) { $bestDist = $dist; $best = $u; }
                        }
                        if ($best && $bestDist <= 5) {
                            $client = $best;
                        }
                    }
                } catch (\Exception $e) {
                    // ignore role lookup issues and fall back to Client model below
                }

                // 2) Fallback: try legacy Client model matching
                if (!$client) {
                    $clients = Client::all();
                    foreach ($clients as $c) {
                        if (strpos($this->normalizeForCompare($c->client_name), $search) !== false) {
                            $client = $c;
                            break;
                        }
                    }

                    // fuzzy levenshtein fallback on normalized names
                    if (!$client) {
                        $best = null; $bestDist = PHP_INT_MAX;
                        foreach ($clients as $c) {
                            $dist = levenshtein($search, $this->normalizeForCompare($c->client_name));
                            if ($dist < $bestDist) { $bestDist = $dist; $best = $c; }
                        }
                        if ($best && $bestDist <= 5) {
                            $client = $best;
                        }
                    }
                }
            }

            if (!$client) {
                $this->failures[] = [
                    'row' => $this->currentRow,
                    'error' => "Client '{$clientRaw}' not found"
                ];
                return null;
            }

            // Find site by name (optionally filter by client)
            $site = Site::where('site_name', 'like', '%' . trim($siteRaw) . '%')
                       ->where('client_id', $client->id)
                       ->first();

            // If not found in client's sites, try normalized PHP match across all sites
            if (!$site && !empty($siteRaw)) {
                $searchSite = $this->normalizeForCompare($siteRaw);
                $sites = Site::all();
                foreach ($sites as $st) {
                    if (strpos($this->normalizeForCompare($st->site_name), $searchSite) !== false) {
                        $site = $st; break;
                    }
                }
                if (!$site) {
                    $best = null; $bestDist = PHP_INT_MAX;
                    foreach ($sites as $st) {
                        $dist = levenshtein($searchSite, $this->normalizeForCompare($st->site_name));
                        if ($dist < $bestDist) { $bestDist = $dist; $best = $st; }
                    }
                    if ($best && $bestDist <= 5) { $site = $best; }
                }
            }

            if (!$site) {
                $this->failures[] = [
                    'row' => $this->currentRow,
                    'error' => "Site '{$siteRaw}' not found"
                ];
                return null;
            }

            // Find officer/staff by name (optional)
            $staff = null;
            $staffUserId = null;
            $officerRaw = $this->findColumnValue($row, ['officer', 'officer name', 'staff', 'staff name', 'officername', 'staffname']);
            $officerRaw = $this->normalizeCell($officerRaw);
            if (!empty($officerRaw)) {
                $officerName = trim($officerRaw);
                $staff = Employee::where(function($query) use ($officerName) {
                    $query->where('fore_name', 'like', '%' . $officerName . '%')
                          ->orWhere('sur_name', 'like', '%' . $officerName . '%')
                          ->orWhereRaw("CONCAT(fore_name, ' ', sur_name) LIKE ?", ['%' . $officerName . '%']);
                })->first();

                // If not found, try fuzzy levenshtein on employee full names
                if (!$staff) {
                    $employees = Employee::select('id', 'fore_name', 'sur_name', 'user_id', 'sia_expiry')->get();
                    $best = null; $bestDist = PHP_INT_MAX;
                    foreach ($employees as $e) {
                        $full = trim($e->fore_name . ' ' . $e->sur_name);
                        $dist = levenshtein(strtolower($officerName), strtolower($full));
                        if ($dist < $bestDist) { $bestDist = $dist; $best = $e; }
                    }
                    if ($best && $bestDist <= 5) {
                        $staff = $best;
                    }
                }

                if ($staff) {
                    // Use the employee's user_id when storing staff_id on shifts
                    $staffUserId = $staff->user_id ?? $staff->id;

                    // Check if staff SIA license is expired
                    if (isset($staff->sia_expiry) && $staff->sia_expiry && Carbon::parse($staff->sia_expiry)->lt(now())) {
                        Log::warning("Staff {$officerName} has expired SIA licence for shift on {$shiftDate}");
                    }
                }
            }

            // Parse start and end times
            $startTime = $this->parseTime($startRaw);
            $endTime = $this->parseTime($endRaw);

            if (!$startTime || !$endTime) {
                $this->failures[] = [
                    'row' => $this->currentRow,
                    'error' => "Invalid time format - Start: {$startRaw}, End: {$endRaw}"
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
            if ($staffUserId) {
                $overlappingShiftDate = ShiftDate::where('staff_id', $staffUserId)
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
                'staff_id' => $staffUserId,
                'number_shift' => 1,
                'site_rate' => $site->guard_rate ?? $client->guard_rate ?? 0,
                'employee_rate' => $site->payable_rate ?? 0,
                'comments' => $row['comments'] ?? null,
                'days' => json_encode([$dayAbbr]),
                'lost_time' => $row['lost_time'] ?? null,
                'is_assign' => $staffUserId ? 1 : 0,
                'restrict_start_time' => 0,
                'enforce_picture_check' => 0,
                'restrict_location_check' => 0,
            ]);

            // Check if shift date already exists to avoid duplicates
            $existingShiftDate = ShiftDate::where('shift_id', $shift->id)
                ->where('shift_date', $shiftDate)
                ->where('start_time', $startTime)
                ->where('end_time', $endTime)
                ->where('staff_id', $staffUserId)
                ->first();

            if ($existingShiftDate) {
                // Skip duplicate shift date entry
                $this->failures[] = [
                    'row' => $this->currentRow,
                    'error' => 'Duplicate shift - already exists in database'
                ];
                return null;
            }

            // Create shift date entry
            $shiftDate = new ShiftDate([
                'shift_id' => $shift->id,
                        'staff_id' => $staffUserId,
                'shift_date' => $shiftDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'total_hours' => $totalHours,
                'is_assign' => $staffUserId ? 1 : 0,
                'break_time' => null, // Could be calculated or set from lost time
            ]);

            $shiftDate->save(); // Save the shift date first so we can use its ID

            // Create patrols and checkcalls like in ShiftController::store()
            $this->createPatrolsAndCheckCalls($shiftDate, $shift, $startTime, $endTime);

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

    /**
     * Create patrols and checkcalls for a shift date (same logic as ShiftController::store)
     */
    private function createPatrolsAndCheckCalls(ShiftDate $shiftDate, Shift $shift, string $startTime, string $endTime)
    {
        try {
            $start = Carbon::createFromFormat('H:i:s', $startTime);
            $end = Carbon::createFromFormat('H:i:s', $endTime);

            // Convert to minutes since midnight
            $startMinutes = $start->hour * 60 + $start->minute;
            $endMinutes = $end->hour * 60 + $end->minute;

            // Calculate duration in minutes, handle overnight automatically
            $durationMinutes = ($endMinutes - $startMinutes + 1440) % 1440;

            // if shift is exactly 24 hours, make it 1440
            if ($durationMinutes == 0) {
                $durationMinutes = 1440;
            }

            $numberOfCheckCalls = ceil($durationMinutes / 60);

            $shiftStart = Carbon::parse($shiftDate->shift_date . ' ' . $startTime);

            // Get site checkpoints for patrol
            $site = Site::with('checkpoints')->find($shift->site_id);
            $totalCheckpoints = $site->checkpoints->count() ?? 0;

            // Create auto patrols and checkcalls for each hour of the shift
            for ($n = 0; $n < (int) $numberOfCheckCalls; $n++) {
                $checkTime = $shiftStart->copy()->addHours($n);
                $patrolTime = $shiftStart->copy()->addHours($n);

                // Always create patrols
                Patrol::create([
                    'shift_id' => $shiftDate->id,
                    'name' => 'Auto Patrol ' . ($n + 1),
                    'summary' => 'Scheduled patrol at ' . $patrolTime->format('H:i'),
                    'start_time' => $patrolTime->format('Y-m-d H:i:s'),
                    'status' => 'pending',
                    'total_checkpoints' => $totalCheckpoints,
                    'completed_checkpoints' => 0,
                    'issues_reported' => 0,
                    'completed_at' => null,
                ]);

                // Create auto checkcalls (you can enable/disable this based on your needs)
                // For now, creating them by default for imported shifts
                CheckCall::create([
                    'shift_id' => $shiftDate->id,
                    'employee_id' => $shiftDate->staff_id ?? null,
                    'name' => 'Auto CheckCall ' . ($n + 1),
                    'scheduled_time' => $checkTime->format('Y-m-d H:i:s'),
                    'status' => 'pending',
                    'require_media' => $shiftDate->require_media ?? 0,
                ]);
            }

            Log::info("Created {$numberOfCheckCalls} patrols and checkcalls for imported shift date #{$shiftDate->id}");
        } catch (\Exception $e) {
            Log::error("Error creating patrols/checkcalls for shift date #{$shiftDate->id}: " . $e->getMessage());
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


            // handle excel formate like 45871 instead of 02-Aug-2025
            if (is_numeric($dateValue) && strlen((string)$dateValue) >= 1) {
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateValue);
                return Carbon::instance($dt)->format('Y-m-d');
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
            // If it's a DateTime object
            if ($timeValue instanceof \DateTimeInterface) {
                return Carbon::instance($timeValue)->format('H:i:s');
            }

            // If Excel returns a numeric (serial) value for time/date
            if (is_numeric($timeValue)) {
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($timeValue);
                return Carbon::instance($dt)->format('H:i:s');
            }

            $timeValue = trim((string)$timeValue);

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

    /**
     * Find a column value from the given row using possible header names.
     * Performs normalization on header keys and allows fuzzy matching.
     */
    private function findColumnValue(array $row, array $names)
    {
        // Normalize target names
        $targets = array_map(function ($n) {
            return preg_replace('/[^a-z0-9]/', '', strtolower($n));
        }, $names);

        // First try exact/normalized header key match
        foreach ($row as $key => $value) {
            $normKey = preg_replace('/[^a-z0-9]/', '', strtolower((string)$key));
            foreach ($targets as $t) {
                if ($t === $normKey) {
                    return $value;
                }
            }
        }

        // Then try partial match (substring)
        foreach ($row as $key => $value) {
            $normKey = preg_replace('/[^a-z0-9]/', '', strtolower((string)$key));
            foreach ($targets as $t) {
                if ($t !== '' && strpos($normKey, $t) !== false) {
                    return $value;
                }
            }
        }

        // As fallback return null — caller may attempt content-based detection
        return null;
    }

    /**
     * Normalize a string for comparison: lowercase, remove accents, strip non-alphanumerics.
     */
    private function normalizeForCompare(?string $value): string
    {
        if (is_null($value)) return '';
        $val = trim((string)$value);
        if ($val === '') return '';
        // remove BOM and non-printables
        $val = preg_replace('/[\x00-\x1F\x7F]/u', '', $val);
        // transliterate to ASCII
        $val = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $val) ?: $val;
        $val = strtolower($val);
        // keep only a-z0-9
        $val = preg_replace('/[^a-z0-9]/', '', $val);
        return $val;
    }

    /**
     * Normalize a cell value: trim and treat common placeholders as empty.
     */
    private function normalizeCell($value)
    {
        if (is_null($value)) return null;
        $val = trim((string)$value);
        if ($val === '') return null;
        $lower = strtolower($val);
        $placeholders = ['n/a', 'na', '-', 'none', 'n\a', 'tba'];
        if (in_array($lower, $placeholders, true)) return null;
        return $val;
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
