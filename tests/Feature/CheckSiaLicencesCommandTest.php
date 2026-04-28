<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Services\SiaLicenceChecker;
use Illuminate\Database\Schema\Blueprint;
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

    public function test_it_processes_bulk_sia_checks_directly_for_all_employees(): void
    {
        Schema::dropIfExists('sia_check_reports');
        Schema::dropIfExists('employees');

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('fore_name')->nullable();
            $table->string('sur_name')->nullable();
            $table->string('sia_licence')->nullable();
            $table->string('sia_status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sia_check_reports', function (Blueprint $table) {
            $table->id();
            $table->string('run_id');
            $table->unsignedBigInteger('employee_id');
            $table->string('employee_name')->nullable();
            $table->string('sia_licence')->nullable();
            $table->string('status_before')->nullable();
            $table->string('status_after')->nullable();
            $table->boolean('changed')->default(false);
            $table->text('error')->nullable();
            $table->dateTime('checked_at')->nullable();
            $table->timestamps();
        });

        DB::table('employees')->insert([
            ['id' => 1, 'fore_name' => 'A', 'sur_name' => 'One', 'sia_licence' => '1011 0015 4420 8079', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'fore_name' => 'B', 'sur_name' => 'Two', 'sia_licence' => '1013464710444055', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'fore_name' => 'C', 'sur_name' => 'Three', 'sia_licence' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $checker = Mockery::mock(SiaLicenceChecker::class);
        $checker->shouldReceive('checkByLicenceNumber')
            ->once()
            ->with('1011001544208079', true)
            ->andReturn(['success' => true, 'valid' => true, 'error' => null]);
        $checker->shouldReceive('checkByLicenceNumber')
            ->once()
            ->with('1013464710444055', true)
            ->andReturn(['success' => true, 'valid' => false, 'error' => null]);

        $this->app->instance(SiaLicenceChecker::class, $checker);

        try {
            $this->artisan('sia:check')
                ->expectsOutput('Starting SIA licence check...')
                ->expectsOutputToContain('Processed 2 SIA licences. Run ID:')
                ->expectsOutput('SIA licence check completed.')
                ->assertExitCode(0);

            $this->assertSame(2, DB::table('sia_check_reports')->count());
            $this->assertSame('1011001544208079', DB::table('employees')->where('id', 1)->value('sia_licence'));
            $this->assertSame('Active', DB::table('employees')->where('id', 1)->value('sia_status'));
            $this->assertSame('Inactive', DB::table('employees')->where('id', 2)->value('sia_status'));
        } finally {
            Schema::dropIfExists('sia_check_reports');
            Schema::dropIfExists('employees');
        }
    }
}