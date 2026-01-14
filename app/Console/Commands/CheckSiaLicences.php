<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Services\SiaLicenceChecker;
use Illuminate\Support\Facades\Log;

class CheckSiaLicences extends Command
{
    protected $signature = 'sia:check {--no-cache : Do not use cached results}';
    protected $description = 'Check all SIA licences for employees';

    public function handle()
    {
        $this->info('Starting SIA licence check...');

        $useCache = !$this->option('no-cache');

        $employees = Employee::whereNotNull('sia_licence')->get();
        /** @var SiaLicenceChecker $siaChecker */
        $siaChecker = app(SiaLicenceChecker::class);

        foreach ($employees as $employee) {
            try {
                $result = $siaChecker->checkByLicenceNumber($employee->sia_licence, $useCache);

                $newStatus = (!empty($result) && !empty($result['valid'])) ? 'Active' : 'Inactive';

                if ($employee->sia_status !== $newStatus) {
                    $employee->sia_status = $newStatus;
                    $employee->save();
                }

                if ($newStatus === 'Active') {
                    Log::info("SIA licence valid for employee {$employee->id}");
                } else {
                    $err = $result['error'] ?? 'Licence not valid or not found';
                    Log::warning("SIA licence inactive for employee {$employee->id}: {$err}");
                }
            } catch (\Exception $e) {
                Log::error("Error checking SIA licence for employee {$employee->id}: " . $e->getMessage());
            }
        }

        $this->info('SIA licence check completed.');
    }
}
