<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\User;
use App\Models\Subcontractor;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;
use Exception;

class EmployeesImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        $employeesData = [];
        $usersData = [];
        $processedEmails = [];
        $now = now();
        $password = Hash::make('password123');
        $role = Role::firstOrCreate(['name' => 'security_staff']);

        /** ------------------------
         *  1️⃣ PROCESS USERS
         *  ------------------------ */
        foreach ($rows as $row) {
            try {
                $fullName = trim($row['full_name'] ?? '');
                if ($fullName === '') continue;

                $nameParts = explode(' ', $fullName, 2);
                $firstName = $nameParts[0];
                $lastName = $nameParts[1] ?? '';

                // Generate/validate email
                $email = trim($row['email'] ?? '');
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $base = strtolower(preg_replace('/\s+/', '', $fullName));
                    do {
                        $random = Str::random(6);
                        $email = "{$base}_{$random}@example.com";
                    } while (
                        in_array($email, $processedEmails) ||
                        User::where('email', $email)->exists()
                    );
                }

                // Skip duplicates (file + DB)
                if (in_array(strtolower($email), $processedEmails)) continue;
                if (User::where('email', $email)->exists()) continue;

                $processedEmails[] = strtolower($email);

                $usersData[] = [
                    'name' => $fullName,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'username' => $fullName,
                    'email' => $email,
                    'password' => $password,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            } catch (Exception $e) {
                Log::error('Error processing user row: ' . $e->getMessage(), ['row' => $row]);
            }
        }

        /** ------------------------
         *  2️⃣ BULK INSERT USERS (IN CHUNKS)
         *  ------------------------ */
        if (!empty($usersData)) {
            collect($usersData)->chunk(500)->each(function ($chunk) {
                User::insert($chunk->toArray());
            });
        }

        $insertedEmails = array_column($usersData, 'email');
        $userMap = User::whereIn('email', $insertedEmails)->pluck('id', 'email');

        /** ------------------------
         *  3️⃣ BUILD EMPLOYEE RECORDS
         *  ------------------------ */
        foreach ($rows as $row) {
            try {
                $fullName = trim($row['full_name'] ?? '');
                if ($fullName === '') continue;

                $nameParts = explode(' ', $fullName, 2);
                $firstName = $nameParts[0];
                $lastName = $nameParts[1] ?? '';

                // Match or regenerate same email
                $email = trim($row['email'] ?? '');
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $base = strtolower(preg_replace('/\s+/', '', $fullName));
                    foreach ($userMap as $userEmail => $id) {
                        if (Str::startsWith($userEmail, $base . "_")) {
                            $email = $userEmail;
                            break;
                        }
                    }
                }

                if (!isset($userMap[$email])) continue;

                // Subcontractor
                $subcontractorId = null;
                if (!empty($row['subcontractor']) && trim($row['subcontractor']) !== '') {
                    $sub = Subcontractor::firstOrCreate(
                        ['company_name' => trim($row['subcontractor'])],
                        ['is_active' => true]
                    );
                    $subcontractorId = $sub->id;
                }

                $employeesData[] = [
                    'user_id' => $userMap[$email],
                    'entry_date' => $this->parseDate($row['date_of_registration'] ?? null),
                    'fore_name' => $firstName,
                    'sur_name' => $lastName,
                    'subcontractor' => $subcontractorId,
                    'guard_rate' => !empty($row['pay_rate']) ? (float)$row['pay_rate'] : null,
                    'contact' => $row['contact'] ?? null,
                    'sia_licence' => $row['sia_number'] ?? null,
                    'service_type' => $row['service_type'] ?? null,
                    'sia_expiry' => $this->parseDate($row['sia_expiry'] ?? null),
                    'dob' => $this->parseDate($row['dob'] ?? null),
                    'email' => $email,
                    'address_group' => $row['address_with_post_code'] ?? $row['address_group'] ?? null,
                    'account_name' => $row['account_name'] ?? null,
                    'sort_code' => $row['sort_code'] ?? null,
                    'account_number' => $row['account_number'] ?? null,
                    'ni_number' => $row['ni_number'] ?? null,
                    'visa_type' => $row['visa_status'] ?? null,
                    'visa_expiry' => $this->parseDate($row['visa_expiry_date'] ?? null),
                    'status' => 'active',
                    'gender' => 'male',
                    'licence_type' => 'SIA',
                    'place_work' => 'Various',
                    'hour_per_week' => 40,
                    'passport_no' => null,
                    'passport_expiry' => now()->addYears(10)->format('Y-m-d'),
                    'pin' => rand(1000, 9999),
                    'share_code' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            } catch (Exception $e) {
                Log::error('Error building employee record: ' . $e->getMessage(), ['row' => $row]);
            }
        }

        /** ------------------------
         *  4️⃣ BULK INSERT EMPLOYEES (IN CHUNKS)
         *  ------------------------ */
        if (!empty($employeesData)) {
            collect($employeesData)->chunk(500)->each(function ($chunk) {
                Employee::insert($chunk->toArray());
            });
            Log::info(count($employeesData) . ' employees imported successfully (chunked).');
        }
    }

    private function parseDate($dateString): ?string
    {
        if (empty($dateString) || trim($dateString) === '') return null;

        try {
            if (preg_match('/^\d{2}-[A-Za-z]{3}-\d{2}$/', $dateString)) {
                return Carbon::createFromFormat('d-M-y', $dateString)->format('Y-m-d');
            } elseif (preg_match('/^\d{2}-[A-Za-z]{3}-\d{4}$/', $dateString)) {
                return Carbon::createFromFormat('d-M-Y', $dateString)->format('Y-m-d');
            } elseif (is_numeric($dateString) && strlen($dateString) >= 4) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateString))->format('Y-m-d');
            }
            return Carbon::parse($dateString)->format('Y-m-d');
        } catch (Exception $e) {
            Log::warning("Date parse failed: {$dateString} | " . $e->getMessage());
            return null;
        }
    }
}
