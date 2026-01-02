<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupConnections extends Command
{
    protected $signature = 'db:cleanup-connections';
    protected $description = 'Force cleanup of all database connections';

    public function handle()
    {
        try {
            // Disconnect all connections
            DB::disconnect();
            
            $this->info('✅ Database connections cleaned up successfully.');
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to cleanup connections: ' . $e->getMessage());
            return 1;
        }
    }
}
