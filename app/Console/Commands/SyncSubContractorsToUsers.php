<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\SubContractor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SyncSubContractorsToUsers extends Command
{
    protected $signature = 'sync:subcontractors-users';
    protected $description = 'Create users for sub-contractors if not exist and assign role';

    public function handle()
    {
        $subContractors = SubContractor::all();
        $createdCount = 0;
        $skippedCount = 0;

        foreach ($subContractors as $subContractor) {
            // Check if already linked
            $user = User::find($subContractor->user_id);

            if (!$user) {

                $email = $subContractor->email;
                if (empty($email)) {
                    $email = Str::slug($subContractor->company_name ?: $subContractor->contact_person) . rand(1000, 9999) . '@example.com';
                    $this->warn("Generated email {$email} for sub-contractor ID {$subContractor->id}");
                }

                // Check if email already exists
                $user = User::where('email', $email)->first();

                if (!$user) {
                    // Create new user
                    $user = User::create([
                        'name' => $subContractor->company_name ?: $subContractor->contact_person,
                        'first_name' => $subContractor->contact_person ?? '',
                        'last_name' => $subContractor->contact_person,
                        'email' => $email,
                        'phone_number' => $subContractor->contact_number ?? '',
                        'status' => $subContractor->is_active ? 'active' : 'inactive',
                        'username' => Str::slug($subContractor->company_name ?: $subContractor->contact_person) . rand(100, 999),
                        'password' => Hash::make('defaultPassword123'), // reset later
                    ]);

                    // Assign 'sub_contractor' role using Spatie
                    $user->assignRole('subcontractor');

                    // Update sub-contractor
                    $subContractor->user_id = $user->id;
                    $subContractor->email = $email;
                    $subContractor->save();

                    $this->info("Created user for sub-contractor ID {$subContractor->id}, user ID {$user->id}");
                    $createdCount++;
                } else {
                    $this->warn("User with email {$email} already exists. Updating sub-contractor user_id.");
                    $subContractor->user_id = $user->id;
                    $subContractor->save();
                    $skippedCount++;
                }
            } else {
                $this->line("Sub-contractor ID {$subContractor->id} already linked to user.");
                $skippedCount++;
            }
        }

        $this->info("Sync complete. Created: $createdCount, Skipped/Updated: $skippedCount");
        return 0;
    }
}
