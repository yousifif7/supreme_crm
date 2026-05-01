<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\User;
use App\Models\LoginActivity;

class ImportLoginActivitiesFromTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:login-activities {--months=6} {--dry-run} {--chunk=1000} {--debug} {--dedupe}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import login activities from personal_access_tokens (one-time)';

    public function handle(): int
    {
        $months = (int) $this->option('months');
        $dryRun = (bool) $this->option('dry-run');
        $chunk = (int) $this->option('chunk');

        $since = Carbon::now()->subMonths($months);

        $excludedRoles = ['security_staff', 'client', 'subcontractor', 'admin'];

        $this->info('Importing token usages since ' . $since->toDateTimeString() . ' (dry-run: ' . ($dryRun ? 'yes' : 'no') . ')');

        $query = DB::table('personal_access_tokens')
            ->where('tokenable_type', User::class)
            ->where(function ($q) use ($since) {
                $q->where('last_used_at', '>=', $since)
                  ->orWhere('created_at', '>=', $since);
            })
            ->orderByDesc('last_used_at');

        $total = 0;
        $imported = 0;
        $skipped = 0;
        $errors = 0;

        $skippedEmptyDate = 0;
        $skippedNoUser = 0;
        $skippedExcludedRole = 0;
        $skippedExists = 0;
        $skippedOther = 0;

        $createdEvenIfExists = 0;

        $samples = [];
        $createdSamples = [];
        $debug = (bool) $this->option('debug');

        $dedupe = (bool) $this->option('dedupe');

        $query->chunk($chunk, function ($tokens) use (&$total, &$imported, &$skipped, &$errors, &$skippedEmptyDate, &$skippedNoUser, &$skippedExcludedRole, &$skippedExists, &$skippedOther, &$samples, &$createdSamples, &$createdEvenIfExists, $excludedRoles, $dryRun, $debug, $dedupe) {
            foreach ($tokens as $t) {
                $total++;

                $loginAtRaw = $t->last_used_at ?? $t->created_at;
                if (empty($loginAtRaw)) {
                    $skipped++;
                    $skippedEmptyDate++;
                    if ($debug && count($samples) < 20) {
                        $samples[] = ['token' => $t, 'reason' => 'empty_date'];
                    }
                    continue;
                }

                try {
                    $loginAt = Carbon::parse($loginAtRaw);
                } catch (\Exception $e) {
                    $errors++;
                    continue;
                }

                $user = User::find($t->tokenable_id);
                if (!$user) {
                    $skipped++;
                    $skippedNoUser++;
                    if ($debug && count($samples) < 20) {
                        $samples[] = ['token' => $t, 'reason' => 'no_user'];
                    }
                    continue;
                }

                if ($user->hasAnyRole($excludedRoles) || in_array($user->role, $excludedRoles, true)) {
                    $skipped++;
                    $skippedExcludedRole++;
                    if ($debug && count($samples) < 20) {
                        $samples[] = ['token' => $t, 'user' => $user, 'reason' => 'excluded_role'];
                    }
                    continue;
                }

                $exists = LoginActivity::where('user_id', $user->id)
                    ->where('login_at', $loginAt)
                    ->exists();

                if ($exists && $dedupe) {
                    $skipped++;
                    $skippedExists++;
                    if ($debug && count($samples) < 20) {
                        $samples[] = ['token' => $t, 'user' => $user, 'reason' => 'already_exists'];
                    }
                    continue;
                }

                if ($dryRun) {
                    $imported++;
                    if ($exists) {
                        $createdEvenIfExists++;
                    }
                    if ($debug && count($createdSamples) < 20) {
                        $createdSamples[] = ['token' => $t, 'user' => $user, 'login_at' => $loginAt->toDateTimeString(), 'created_even_if_exists' => $exists];
                    }
                    continue;
                }

                LoginActivity::create([
                    'admin_id' => $user->admin_id,
                    'user_id' => $user->id,
                    'login_at' => $loginAt,
                    'ip_address' => null,
                    'user_agent' => null,
                ]);

                $imported++;
                if ($exists) {
                    $createdEvenIfExists++;
                }
                if ($debug && count($createdSamples) < 20) {
                    $createdSamples[] = ['token' => $t, 'user' => $user, 'login_at' => $loginAt->toDateTimeString(), 'created_even_if_exists' => $exists];
                }
            }
        });

        $this->info("Done. Total tokens scanned: $total. Imported: $imported. Skipped: $skipped. Errors: $errors.");

        $this->info('Skip breakdown: empty_date=' . $skippedEmptyDate . ', no_user=' . $skippedNoUser . ', excluded_role=' . $skippedExcludedRole . ', already_exists=' . $skippedExists . ', other=' . $skippedOther);
        $this->info('Created even if already existed: ' . $createdEvenIfExists . ' (dedupe=' . ($dedupe ? 'yes' : 'no') . ')');

        // Write a summary to the Laravel log so runs are recorded
        $logSummary = [
            'command' => 'import:login-activities',
            'months' => $months,
            'dry_run' => $dryRun,
            'dedupe' => $dedupe,
            'total' => $total,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'skip_breakdown' => [
                'empty_date' => $skippedEmptyDate,
                'no_user' => $skippedNoUser,
                'excluded_role' => $skippedExcludedRole,
                'already_exists' => $skippedExists,
                'other' => $skippedOther,
            ],
            'created_even_if_exists' => $createdEvenIfExists,
        ];

        Log::info('import:login-activities finished', $logSummary);

        if ($debug) {
            $this->info('Sample skipped tokens (up to 20):');
            foreach ($samples as $s) {
                $t = $s['token'];
                $line = sprintf("id=%s tokenable_id=%s tokenable_type=%s last_used_at=%s created_at=%s reason=%s", $t->id, $t->tokenable_id, $t->tokenable_type, $t->last_used_at, $t->created_at, $s['reason']);
                $this->line($line);
            }

            $this->info('Sample created/imported tokens (up to 20):');
            foreach ($createdSamples as $c) {
                $t = $c['token'];
                $line = sprintf("id=%s tokenable_id=%s tokenable_type=%s login_at=%s user_id=%s user_email=%s", $t->id, $t->tokenable_id, $t->tokenable_type, $c['login_at'], $c['user']->id, $c['user']->email ?? '');
                $this->line($line);
            }

            // also log the debug samples in structured form (sanitised)
            $logSamples = [];
            foreach ($samples as $s) {
                $t = $s['token'];
                $u = $s['user'] ?? null;
                $logSamples[] = [
                    'id' => $t->id ?? null,
                    'tokenable_id' => $t->tokenable_id ?? null,
                    'tokenable_type' => $t->tokenable_type ?? null,
                    'last_used_at' => $t->last_used_at ?? null,
                    'created_at' => $t->created_at ?? null,
                    'reason' => $s['reason'] ?? null,
                    'user_id' => $u->id ?? null,
                    'user_email' => $u->email ?? null,
                ];
            }

            $logCreated = [];
            foreach ($createdSamples as $c) {
                $t = $c['token'];
                $u = $c['user'] ?? null;
                $logCreated[] = [
                    'id' => $t->id ?? null,
                    'tokenable_id' => $t->tokenable_id ?? null,
                    'tokenable_type' => $t->tokenable_type ?? null,
                    'login_at' => $c['login_at'] ?? null,
                    'user_id' => $u->id ?? null,
                    'user_email' => $u->email ?? null,
                ];
            }

            Log::info('import:login-activities debug.samples', ['skipped_samples' => $logSamples, 'created_samples' => $logCreated]);
        }

        return 0;
    }
}
