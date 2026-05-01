<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\SiaCheckReport;
use App\Services\SiaLicenceChecker;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CheckSiaLicences extends Command
{
    protected $signature = 'sia:check {--no-cache : Do not use cached results} {--licence= : Check a single SIA licence number instead of all employees}';
    protected $description = 'Check all SIA licences for employees';

    public function handle(SiaLicenceChecker $siaChecker): int
    {
        $this->info('Starting SIA licence check...');

        $useCache  = !$this->option('no-cache');
        $licence   = $this->option('licence');
        $runId     = (string) Str::uuid();

        if (is_string($licence) && trim($licence) !== '') {
            return $this->handleSingleLicenceCheck($siaChecker, $licence, $useCache);
        }

        $total = Employee::whereNotNull('sia_licence')
            ->whereNotNull('sia_expiry')
            ->whereDate('sia_expiry', '>=', Carbon::today()->toDateString())
            ->count();

        if ($total === 0) {
            $this->warn('No employees with SIA licences found.');

            return self::SUCCESS;
        }

        $processed = 0;

        Employee::whereNotNull('sia_licence')
            ->whereNotNull('sia_expiry')
            ->whereDate('sia_expiry', '>=', Carbon::today()->toDateString())
            ->orderBy('id')
            ->chunkById(100, function ($employees) use ($siaChecker, $useCache, $runId, &$processed): void {
                foreach ($employees as $employee) {
                    $statusBefore = $employee->sia_status;
                    $checkedAt = now();
                    $normalisedLicence = $this->normaliseLicenceNumber((string) $employee->sia_licence);

                    if ($normalisedLicence === '') {
                        continue;
                    }

                    if ($normalisedLicence !== (string) $employee->sia_licence) {
                        $employee->sia_licence = $normalisedLicence;
                    }

                    try {
                        $result    = $siaChecker->checkByLicenceNumber($normalisedLicence, $useCache);
                        $newStatus = (!empty($result['valid'])) ? 'Active' : 'Inactive';
                        $changed   = $statusBefore !== $newStatus;

                        if ($changed) {
                            $employee->sia_status = $newStatus;
                        }

                        if ($employee->isDirty(['sia_licence', 'sia_status'])) {
                            $employee->save();
                        }

                        SiaCheckReport::create([
                            'admin_id'      => $employee->admin_id,
                            'run_id'        => $runId,
                            'employee_id'   => $employee->id,
                            'employee_name' => trim($employee->fore_name . ' ' . $employee->sur_name),
                            'sia_licence'   => $normalisedLicence,
                            'status_before' => $statusBefore,
                            'status_after'  => $newStatus,
                            'changed'       => $changed,
                            'error'         => !empty($result['success']) ? null : ($result['error'] ?? null),
                            'checked_at'    => $checkedAt,
                        ]);
                    } catch (\Throwable $e) {
                        Log::error("Error checking SIA licence for employee {$employee->id}: " . $e->getMessage());

                        SiaCheckReport::create([
                            'admin_id'      => $employee->admin_id,
                            'run_id'        => $runId,
                            'employee_id'   => $employee->id,
                            'employee_name' => trim($employee->fore_name . ' ' . $employee->sur_name),
                            'sia_licence'   => $normalisedLicence,
                            'status_before' => $statusBefore,
                            'status_after'  => null,
                            'changed'       => false,
                            'error'         => $e->getMessage(),
                            'checked_at'    => $checkedAt,
                        ]);
                    }

                    $processed++;
                }
            });

        $this->info("Processed {$processed} SIA licences. Run ID: {$runId}");
        $this->info('SIA licence check completed.');

        return self::SUCCESS;
    }

    protected function handleSingleLicenceCheck(SiaLicenceChecker $siaChecker, string $licence, bool $useCache): int
    {
        $normalisedLicence = $this->normaliseLicenceNumber($licence);

        $this->info('Checking single SIA licence: ' . $normalisedLicence);

        $result = $siaChecker->checkByLicenceNumber($normalisedLicence, $useCache);

        $this->line('Result success: ' . (!empty($result['success']) ? 'yes' : 'no'));
        $this->line('Result valid: ' . (!empty($result['valid']) ? 'yes' : 'no'));
        $this->line('Result error: ' . ($result['error'] ?? 'none'));
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}');
        $this->info('SIA licence check completed.');

        return !empty($result['success']) ? self::SUCCESS : self::FAILURE;
    }

    protected function normaliseLicenceNumber(string $licence): string
    {
        return preg_replace('/[^0-9]/', '', $licence);
    }
}
