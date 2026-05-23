<?php

namespace App\Console\Commands;

use App\Models\Site;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BackfillSitePlusCodes extends Command
{
    protected $signature = 'sites:backfill-plus-codes
                            {--limit=0 : Max sites to process in one run (0 = all)}
                            {--dry-run : Show what would be updated without saving}
                            {--force : Re-fetch plus codes even if one is already set}';

    protected $description = 'Populate plus_code on sites that have none, using the Google Places API';

    public function handle(): int
    {
        @set_time_limit(0);
        ini_set('memory_limit', '512M');

        $apiKey = config('services.google_maps.key');
        if (!$apiKey) {
            $this->error('GOOGLE_MAPS_API_KEY is not configured.');
            return 1;
        }

        $query = Site::query()->whereNull('deleted_at');
        if (!$this->option('force')) {
            $query->whereNull('plus_code');
        }

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $query->limit($limit);
        }

        $sites = $query->get(['id', 'site_name', 'address', 'post_code', 'plus_code']);
        $total = $sites->count();

        if ($total === 0) {
            $this->info('No sites to process.');
            return 0;
        }

        $this->info("Processing {$total} site(s)" . ($this->option('dry-run') ? ' (dry run)' : '') . '…');
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $updated = 0;
        $failed  = 0;

        foreach ($sites as $site) {
            $address  = trim((string) ($site->address ?? ''));
            $postCode = trim((string) ($site->post_code ?? ''));

            if ($address === '' && $postCode === '') {
                $bar->advance();
                continue;
            }

            $input = $address !== '' && $postCode !== '' && strpos($address, $postCode) === false
                ? $address . ', ' . $postCode
                : ($address ?: $postCode);

            $plusCode = $this->fetchPlusCode($input, $apiKey);

            if ($plusCode === null) {
                $failed++;
                $bar->advance();
                continue;
            }

            if (!$this->option('dry-run')) {
                $site->plus_code = $plusCode;
                $site->save();
            } else {
                $this->newLine();
                $this->line("  [dry-run] Site #{$site->id} \"{$site->site_name}\": {$plusCode}");
            }

            $updated++;
            $bar->advance();

            // Respect Google's free-tier rate limit (~10 QPS)
            usleep(120000);
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done. Updated: {$updated}, skipped/failed: {$failed}.");

        return 0;
    }

    private function fetchPlusCode(string $input, string $apiKey): ?string
    {
        try {
            // Step 1 — find the place by text to get coordinates.
            $findResp = Http::timeout(8)->get(
                'https://maps.googleapis.com/maps/api/place/findplacefromtext/json',
                [
                    'input'     => $input,
                    'inputtype' => 'textquery',
                    'fields'    => 'plus_code,geometry',
                    'key'       => $apiKey,
                ]
            );

            if (!$findResp->successful()) {
                return null;
            }

            $findData = $findResp->json();
            if (($findData['status'] ?? '') !== 'OK') {
                return null;
            }

            $candidate = $findData['candidates'][0] ?? null;
            if (!$candidate) {
                return null;
            }

            // Sometimes Places returns plus_code directly — use it if present.
            if (!empty($candidate['plus_code']['compound_code'])) {
                return preg_replace('/\s.*$/', '', $candidate['plus_code']['compound_code']);
            }
            if (!empty($candidate['plus_code']['global_code'])) {
                return $candidate['plus_code']['global_code'];
            }

            // Step 2 — fall back to reverse-geocoding the coordinates.
            $lat = $candidate['geometry']['location']['lat'] ?? null;
            $lng = $candidate['geometry']['location']['lng'] ?? null;
            if ($lat === null || $lng === null) {
                return null;
            }

            $geoResp = Http::timeout(8)->get(
                'https://maps.googleapis.com/maps/api/geocode/json',
                [
                    'latlng' => "{$lat},{$lng}",
                    'key'    => $apiKey,
                ]
            );

            if (!$geoResp->successful()) {
                return null;
            }

            $geoData = $geoResp->json();
            if (($geoData['status'] ?? '') !== 'OK') {
                return null;
            }

            // The top-level plus_code on the geocode response is the most reliable source.
            if (!empty($geoData['plus_code']['compound_code'])) {
                return preg_replace('/\s.*$/', '', $geoData['plus_code']['compound_code']);
            }
            if (!empty($geoData['plus_code']['global_code'])) {
                return $geoData['plus_code']['global_code'];
            }

            // Last resort — scan individual results for a plus_code.
            foreach ($geoData['results'] ?? [] as $result) {
                if (!empty($result['plus_code']['compound_code'])) {
                    return preg_replace('/\s.*$/', '', $result['plus_code']['compound_code']);
                }
            }

            return null;

        } catch (\Throwable $e) {
            Log::warning('BackfillSitePlusCodes: API call failed', ['input' => $input, 'error' => $e->getMessage()]);
            return null;
        }
    }
}
