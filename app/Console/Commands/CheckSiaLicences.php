<?php

namespace App\Console\Commands;

use App\Jobs\RunSiaCheck;
use Illuminate\Console\Command;
use App\Models\Employee;
use App\Services\SiaLicenceChecker;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
        $checkedAt = now();

        if (is_string($licence) && trim($licence) !== '') {
            return $this->handleSingleLicenceCheck($siaChecker, $licence, $useCache);
        }

        $employeeIds = Employee::whereNotNull('sia_licence')->pluck('id');
        $total = $employeeIds->count();

        if ($total === 0) {
            $this->warn('No employees with SIA licences found.');

            return self::SUCCESS;
        }

        foreach ($employeeIds as $index => $id) {
            RunSiaCheck::dispatch($id, $runId)->delay(now()->addSeconds($index * 3));
        }

        Log::info('Queued SIA checks from artisan command', [
            'count' => $total,
            'run_id' => $runId,
            'use_cache' => $useCache,
        ]);

        $this->info("Queued SIA checks for {$total} employees. Run ID: {$runId}");
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
