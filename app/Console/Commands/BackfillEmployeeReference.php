<?php 
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;

class BackfillEmployeeReference extends Command
{
    protected $signature = 'employees:backfill-references';
    protected $description = 'Generate unique reference numbers for employees without one';

    public function handle()
    {
        $employees = Employee::whereNull('reference_number')->get();
        $count = 0;

        foreach ($employees as $employee) {
            $employee->reference_number = $this->generateUniqueReference();
            $employee->save();
            $count++;
        }

        $this->info("{$count} employees updated with reference numbers.");
    }

    private function generateUniqueReference()
    {
        do {
            $ref = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (Employee::where('reference_number', $ref)->exists());

        return $ref;
    }
}
