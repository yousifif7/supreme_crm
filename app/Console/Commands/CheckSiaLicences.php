<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Services\SiaLicenceChecker;
use Illuminate\Support\Facades\Log;

class CheckSiaLicences extends Command
{
    protected $signature = 'sia:check';
    protected $description = 'Check all SIA licences for employees';

    public function handle()
    {
        $this->info('Starting SIA licence check...');

        $employees = Employee::whereNotNull('sia_licence')->get();
        $siaChecker = new SiaLicenceChecker();

        foreach ($employees as $employee) {
            try {
                $result = $siaChecker->checkByLicenceNumber($employee->sia_licence);

                if ($result['valid']) {
                    $employee->sia_status = 'valid';
                    $employee->save();
                    Log::info("SIA licence valid for employee {$employee->id}");
                } else {
                    $employee->sia_status = 'invalid';
                    $employee->save();
                    Log::warning("SIA licence invalid for employee {$employee->id}: {$result['error']}");
                }
            } catch (\Exception $e) {
                Log::error("Error checking SIA licence for employee {$employee->id}: " . $e->getMessage());
            }
        }

        $this->info('SIA licence check completed.');
    }
}
