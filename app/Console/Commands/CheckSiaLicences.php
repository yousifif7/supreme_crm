<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\SiaCheckReport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CheckSiaLicences extends Command
{
    protected $signature = 'sia:check {--no-cache : Do not use cached results}';
    protected $description = 'Check all SIA licences for employees';

    public function handle()
    {
        $this->info('Starting SIA licence check...');

        $useCache  = !$this->option('no-cache');
        $runId     = (string) Str::uuid();
        $checkedAt = now();
        $today     = now()->startOfDay();

        $employees = Employee::whereNotNull('sia_licence')->get();
        // Temporarily disabled: external SIA checker service.
        // /** @var SiaLicenceChecker $siaChecker */
        // $siaChecker = app(SiaLicenceChecker::class);

        foreach ($employees as $employee) {
            $statusBefore = $employee->sia_status;

            try {
                // Temporarily disabled: external SIA checker service.
                // $result    = $siaChecker->checkByLicenceNumber($employee->sia_licence, $useCache);
                // $newStatus = (!empty($result) && !empty($result['valid'])) ? 'Active' : 'Inactive';

                $result = [
                    'source' => 'sia_expiry',
                    'expiry' => $employee->sia_expiry,
                ];

                $hasExpiry = !empty($employee->sia_expiry);
                $newStatus = ($hasExpiry && $employee->sia_expiry->copy()->startOfDay()->gte($today)) ? 'Active' : 'Inactive';
                $changed   = $statusBefore !== $newStatus;

                if ($changed) {
                    $employee->sia_status = $newStatus;
                    $employee->save();
                }

                if ($newStatus === 'Active') {
                    Log::info("SIA licence active by expiry date for employee {$employee->id}", [
                        'sia_expiry' => optional($employee->sia_expiry)->toDateString(),
                    ]);
                } else {
                    $err = $hasExpiry
                        ? 'SIA licence expired'
                        : 'SIA expiry date missing';
                    Log::warning("SIA licence inactive for employee {$employee->id}: {$err}");
                }

                SiaCheckReport::create([
                    'run_id'        => $runId,
                    'employee_id'   => $employee->id,
                    'employee_name' => trim($employee->fore_name . ' ' . $employee->sur_name),
                    'sia_licence'   => $employee->sia_licence,
                    'status_before' => $statusBefore,
                    'status_after'  => $newStatus,
                    'changed'       => $changed,
                    'error'         => null,
                    'checked_at'    => $checkedAt,
                ]);
            } catch (\Exception $e) {
                Log::error("Error checking SIA licence for employee {$employee->id}: " . $e->getMessage());

                SiaCheckReport::create([
                    'run_id'        => $runId,
                    'employee_id'   => $employee->id,
                    'employee_name' => trim($employee->fore_name . ' ' . $employee->sur_name),
                    'sia_licence'   => $employee->sia_licence,
                    'status_before' => $statusBefore,
                    'status_after'  => null,
                    'changed'       => false,
                    'error'         => $e->getMessage(),
                    'checked_at'    => $checkedAt,
                ]);
            }
        }

        $this->info('SIA licence check completed.');
    }
}
