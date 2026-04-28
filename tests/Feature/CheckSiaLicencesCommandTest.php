<?php

namespace Tests\Feature;

use App\Jobs\RunSiaCheck;
use App\Models\Employee;
use App\Services\SiaLicenceChecker;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class CheckSiaLicencesCommandTest extends TestCase
{
    public function test_it_normalises_sia_licence_values_on_the_employee_model(): void
    {
        $employee = new Employee();
        $employee->sia_licence = '2012 1121 2213 4541';

        $this->assertSame('2012112122134541', $employee->sia_licence);
    }

    public function test_it_checks_a_single_sia_licence_from_the_command_line(): void
    {
        $checker = Mockery::mock(SiaLicenceChecker::class);
        $checker->shouldReceive('checkByLicenceNumber')
            ->once()
            ->with('2012112122134541', false)
            ->andReturn([
                'success' => true,
                'valid' => true,
                'error' => null,
                'licence_number' => '2012112122134541',
                'licence_status' => 'Active',
            ]);

        $this->app->instance(SiaLicenceChecker::class, $checker);

        $this->artisan('sia:check', [
            '--licence' => '2012 1121 2213 4541',
            '--no-cache' => true,
        ])
            ->expectsOutput('Starting SIA licence check...')
            ->expectsOutput('Checking single SIA licence: 2012112122134541')
            ->expectsOutput('Result success: yes')
            ->expectsOutput('Result valid: yes')
            ->expectsOutput('Result error: none')
            ->expectsOutputToContain('"licence_number": "2012112122134541"')
            ->expectsOutput('SIA licence check completed.')
            ->assertExitCode(0);
    }

    public function test_it_queues_bulk_sia_checks_for_all_employees(): void
    {
        Bus::fake();

        Schema::dropIfExists('employees');
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('sia_licence')->nullable();
            $table->softDeletes();
        });

        DB::table('employees')->insert([
            ['id' => 1, 'sia_licence' => '1011 0015 4420 8079'],
            ['id' => 2, 'sia_licence' => '1013464710444055'],
            ['id' => 3, 'sia_licence' => null],
        ]);

        try {
            $this->artisan('sia:check')
                ->expectsOutput('Starting SIA licence check...')
                ->expectsOutputToContain('Queued SIA checks for 2 employees. Run ID:')
                ->expectsOutput('SIA licence check completed.')
                ->assertExitCode(0);

            Bus::assertDispatchedTimes(RunSiaCheck::class, 2);
        } finally {
            Schema::dropIfExists('employees');
        }
    }
}