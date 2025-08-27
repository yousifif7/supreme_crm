<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\Subcontractor;

class AssignSubcontractorToEmployee extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:assign-subcontractor-to-employee';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
     public function handle()
    {
        $sites = Employee::all();
        $updatedCount = 0;
        $skippedCount = 0;

        foreach ($sites as $site) {
            if (!$site->subcontractor) {
                $this->warn("Site ID {$site->id} has no client_id. Skipping.");
                $skippedCount++;
                continue;
            }

            $client = Subcontractor::find($site->subcontractor);

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

            $site->subcontractor = $client->user_id;
            $site->save();

            $this->info("Assigned Site ID {$site->id} user_id = {$client->user_id} from Client ID {$client->id}");
            $updatedCount++;
        }

        $this->info("Update complete. Updated: {$updatedCount}, Skipped: {$skippedCount}");
        return 0;
    }
}
