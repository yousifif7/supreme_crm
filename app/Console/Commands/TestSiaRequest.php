<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class TestSiaRequest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:sia {licence}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run GET and POST requests against SIA search endpoint and save raw responses to storage/app/sia_debug';

    public function handle()
    {
        $licence = $this->argument('licence');

        $url = 'https://services.sia.homeoffice.gov.uk/PublicRegister/SearchPublicRegisterByLicence';

        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'en-GB,en-US;q=0.9,en;q=0.8',
            'Referer' => 'https://services.sia.homeoffice.gov.uk/',
        ];

        // Ensure debug dir
        try {
            Storage::makeDirectory('sia_debug');
        } catch (\Throwable $e) {
            $this->warn('Could not create sia_debug directory: ' . $e->getMessage());
        }

        $this->info('Starting GET request...');
        try {
            $getResponse = Http::withHeaders($headers)
                ->withOptions(['allow_redirects' => true, 'http_errors' => false])
                ->timeout(60)
                ->accept('text/html')
                ->get($url, ['licence' => $licence]);

            $status = $getResponse->status();
            $body = (string)$getResponse->body();
        } catch (\Throwable $e) {
            $this->error('GET request failed: ' . $e->getMessage());
            Log::error('TestSiaRequest GET failed', ['exception' => $e->getMessage()]);
            $status = null;
            $body = $e->getMessage();
        }

    $fileGet = "sia_test_get_{$licence}_status{$status}_" . date('Y-m-d_His') . '.html';
    Storage::put('sia_debug/' . $fileGet, $body);
    $this->info('Saved GET response to: storage/app/sia_debug/' . $fileGet);
    $this->info('GET status: ' . ($status ?? 'null'));
    $this->info('GET body preview (first 2000 chars):');
    $this->line(mb_substr($body ?? '', 0, 2000));

        $this->info('Starting POST request...');
        try {
            $postResponse = Http::withHeaders($headers)
                ->withOptions(['allow_redirects' => true, 'http_errors' => false])
                ->timeout(60)
                ->accept('text/html')
                ->asForm()
                ->post($url, ['licence' => $licence]);

            $pstatus = $postResponse->status();
            $pbody = (string)$postResponse->body();
        } catch (\Throwable $e) {
            $this->error('POST request failed: ' . $e->getMessage());
            Log::error('TestSiaRequest POST failed', ['exception' => $e->getMessage()]);
            $pstatus = null;
            $pbody = $e->getMessage();
        }

    $filePost = "sia_test_post_{$licence}_status{$pstatus}_" . date('Y-m-d_His') . '.html';
    Storage::put('sia_debug/' . $filePost, $pbody);
    $this->info('Saved POST response to: storage/app/sia_debug/' . $filePost);
    $this->info('POST status: ' . ($pstatus ?? 'null'));
    $this->info('POST body preview (first 2000 chars):');
    $this->line(mb_substr($pbody ?? '', 0, 2000));

        $this->info('Done. Inspect the saved files and paste their contents here if you want me to analyze them.');

        return 0;
    }
}
