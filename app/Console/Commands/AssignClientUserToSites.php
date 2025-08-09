<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Site;
use App\Models\Client;

class AssignClientUserToSites extends Command
{
    protected $signature = 'sites:assign-client-user';
    protected $description = 'Assign site->user_id from related client->user_id based on client_id';

    public function handle()
    {
        $sites = Site::all();
        $updatedCount = 0;
        $skippedCount = 0;

        foreach ($sites as $site) {
            if (!$site->client_id) {
                $this->warn("Site ID {$site->id} has no client_id. Skipping.");
                $skippedCount++;
                continue;
            }

            $client = Client::find($site->client_id);

            if (!$client) {
                $this->warn("No client found for Site ID {$site->id} (client_id {$site->client_id}). Skipping.");
                $skippedCount++;
                continue;
            }

            if (!$client->user_id) {
                $this->warn("Client ID {$client->id} has no user_id. Skipping Site ID {$site->id}.");
                $skippedCount++;
                continue;
            }

            $site->client_id = $client->user_id;
            $site->save();

            $this->info("Assigned Site ID {$site->id} user_id = {$client->user_id} from Client ID {$client->id}");
            $updatedCount++;
        }

        $this->info("Update complete. Updated: {$updatedCount}, Skipped: {$skippedCount}");
        return 0;
    }
}
