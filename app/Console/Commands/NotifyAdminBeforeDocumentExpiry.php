<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;
use App\Helpers\Notify;
use Carbon\Carbon;

class NotifyAdminBeforeDocumentExpiry extends Command
{
    protected $signature = 'notify:document-expiry';
    protected $description = 'Notify admin before 60 days, 30 days, and on the day of document expiry';

    public function handle()
    {
        $today = Carbon::now()->toDateString();
        $days60 = Carbon::now()->addDays(60)->toDateString();
        $days30 = Carbon::now()->addDays(30)->toDateString();

        // 60 days before expiry
        $this->sendExpiryNotification($days60, 'First Reminder', 'Document Expiry Alert - 60 Days Remaining');

        // 30 days before expiry
        $this->sendExpiryNotification($days30, 'Second Reminder', 'Document Expiry Alert - 30 Days Remaining');

        // On the day of expiry
        $this->sendExpiryNotification($today, 'Final Reminder', 'Document Expiry Alert - Today');

        $this->info('Document expiry notifications sent for 60 days, 30 days, and today.');
    }

    private function sendExpiryNotification($date, $reminderStage, $title)
    {
        $documents = Document::whereNotNull('expiry_date')
            ->whereDate('expiry_date', '=', $date)
            ->where('status', 'approved')
            ->get();

        if ($documents->isEmpty()) {
            return;
        }

        foreach ($documents as $doc) {
            Notify::toDashboard(
                $doc->user_id,
                'document_expiry',
                $title,
                "{$reminderStage}: The document '{$doc->document_type}' will expire on {$doc->expiry_date}.",
                url('employees?staff_id='.$doc->id)
            );
        }

        $this->info($reminderStage . ': Notified for ' . $documents->count() . ' documents.');
    }
}
