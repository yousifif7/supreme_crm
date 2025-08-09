<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SyncClientsToUsers extends Command
{
    protected $signature = 'sync:clients-users';
    protected $description = 'Create users for clients if not exist and assign role';

    public function handle()
    {
        $clients = Client::all();
        $createdCount = 0;
        $skippedCount = 0;

        foreach ($clients as $client) {
            // Check if user exists via user_id
            $user = User::find($client->user_id);

            if (!$user) {

                $email = $client->email;
                if (empty($email)) {
                    $email = Str::slug($client->client_name) . rand(1000, 9999) . '@example.com';
                    $this->warn("Generated email {$email} for client ID {$client->id}");
                }

                // Check if user with this email exists
                $user = User::where('email', $email)->first();

                if (!$user) {
                    // Create a new user
                    $user = User::create([
                        'name' => $client->client_name,
                        'first_name' => $client->client_name ?? '',
                        'last_name' => '',
                        'email' => $email,
                        'phone_number' => $client->contact_number ?? '',
                        'status' => $client->is_active ? 'active' : 'inactive',
                        'username' => Str::slug($client->client_name) . rand(100, 999),
                        'password' => Hash::make('defaultPassword123'), // change later
                    ]);

                    // Assign 'client' role using Spatie
                    $user->assignRole('client');

                    // Update client record
                    $client->user_id = $user->id;
                    $client->email = $email;
                    $client->save();

                    $this->info("Created user for client ID {$client->id}, user ID {$user->id}");
                    $createdCount++;
                } else {
                    $this->warn("User with email {$email} already exists. Updating client user_id.");
                    $client->user_id = $user->id;
                    $client->save();
                    $skippedCount++;
                }
            } else {
                $this->line("Client ID {$client->id} already linked to user.");
                $skippedCount++;
            }
        }

        $this->info("Sync complete. Created: $createdCount, Skipped/Updated: $skippedCount");
        return 0;
    }
}
