<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Models\SiaCheckReport;
use App\Services\SiaLicenceChecker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RunSiaCheck implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Max attempts before the job is marked failed */
    public int $tries = 1;

    /** Seconds before the job is considered timed out */
    public int $timeout = 90;

    /**
     * A run_id groups all jobs dispatched in the same processSia call so
     * they all appear as one report entry. Each single-employee dispatch
     * (on create/update) gets its own unique run_id.
     */
    public string $runId;

    public function __construct(public int $employeeId, ?string $runId = null)
    {
        $this->runId = $runId ?? (string) Str::uuid();
    }

    public function handle(SiaLicenceChecker $checker): void
    {
        $employee = Employee::find($this->employeeId);

        if (!$employee || empty($employee->sia_licence)) {
            return;
        }

        $statusBefore = $employee->sia_status;
        $checkedAt    = now();

        try {
            $result    = $checker->checkByLicenceNumber($employee->sia_licence, true);
            $newStatus = (!empty($result['valid'])) ? 'Active' : 'Inactive';
            $changed   = $statusBefore !== $newStatus;

            if ($changed) {
                $employee->sia_status = $newStatus;
                $employee->save();
                Log::info('RunSiaCheck: status updated', [
                    'employee_id' => $this->employeeId,
                    'old'         => $statusBefore,
                    'new'         => $newStatus,
                ]);
            }

            SiaCheckReport::create([
                'run_id'        => $this->runId,
                'employee_id'   => $employee->id,
                'employee_name' => trim($employee->fore_name . ' ' . $employee->sur_name),
                'sia_licence'   => $employee->sia_licence,
                'status_before' => $statusBefore,
                'status_after'  => $newStatus,
                'changed'       => $changed,
                'error'         => null,
                'checked_at'    => $checkedAt,
            ]);
        } catch (\Throwable $e) {
            Log::error('RunSiaCheck job failed', [
                'employee_id' => $this->employeeId,
                'error'       => $e->getMessage(),
            ]);

            // Still record the failure so the report shows it
            SiaCheckReport::create([
                'run_id'        => $this->runId,
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
}
