<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SyncEmployeesToUsers extends Command
{
    protected $signature = 'sync:employees-users';
    protected $description = 'Create users for employees if not exist and assign role';

    public function handle()
    {
        $employees = Employee::all();
        $createdCount = 0;
        $skippedCount = 0;

        foreach ($employees as $employee) {
            // Check if user exists using user_id or email
            $user = User::find($employee->user_id);

            if (!$user) {

                   $email = $employee->email;
    if (empty($email)) {
        $email = Str::slug($employee->fore_name . '.' . $employee->sur_name) . rand(1000, 9999) . '@example.com';
        $this->warn("Generated email {$email} for employee ID {$employee->id}");
    }
                // Check if a user with this email exists already
                $user = User::where('email', $employee->email)->first();

                if (!$user) {
                    // Create a new user
                    $user = User::create([
                        'name' => $employee->fore_name . ' ' . $employee->sur_name,
                        'first_name' => $employee->fore_name,
                        'last_name' => $employee->sur_name,
                        'email' => $email,
                        'phone_number' => $employee->contact ?? '',
                        'status' => $employee->status ?? 'active',
                        'username' => Str::slug($employee->fore_name . $employee->sur_name) . rand(100, 999),
                        'password' => Hash::make('defaultPassword123'), // You should notify or reset later
                    ]);

                    // Assign 'employee' role using Spatie
                    $user->assignRole('security_staff');

                    // Update employee with new user_id
                    $employee->user_id = $user->id;
                    $employee->email = $email;
                    $employee->save();

                    $this->info("Created user for employee ID {$employee->id}, user ID {$user->id}");
                    $createdCount++;
                } else {
                    $this->warn("User with email {$employee->email} already exists. Updating employee user_id.");
                    $employee->user_id = $user->id;
                    $employee->save();
                    $skippedCount++;
                }
            } else {
                $this->line("Employee ID {$employee->id} already linked to user.");
                $skippedCount++;
            }
        }

        $this->info("Sync complete. Created: $createdCount, Skipped/Updated: $skippedCount");
        return 0;
    }
}
