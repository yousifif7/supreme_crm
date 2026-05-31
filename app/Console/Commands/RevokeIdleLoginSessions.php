<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\LoginActivity;
use App\Models\User;

class RevokeIdleLoginSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:revoke-idle-sessions {--minutes=30} {--dry-run} {--fix-existing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revoke sessions that have been idle for longer than the threshold and stamp logout_at on the matching LoginActivity rows.';

    public function handle(): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $dryRun  = (bool) $this->option('dry-run');

        // One-time repair: earlier sweeps stamped logout_at from the session's
        // UTC last_activity, which produced logout times *before* login_at. The
        // real destroy time can't be recovered for past rows, so clamp those
        // broken logouts up to their login_at (duration reads ~0 instead of
        // negative). Run with --fix-existing on the affected environment.
        if ($this->option('fix-existing')) {
            $brokenQuery = LoginActivity::whereNotNull('logout_at')
                ->whereColumn('logout_at', '<', 'login_at');
            $brokenCount = (clone $brokenQuery)->count();

            if (!$dryRun && $brokenCount > 0) {
                LoginActivity::whereNotNull('logout_at')
                    ->whereColumn('logout_at', '<', 'login_at')
                    ->update(['logout_at' => DB::raw('login_at')]);
            }

            $this->info(sprintf(
                'fix-existing: %d row(s) with logout_at before login_at %s.',
                $brokenCount,
                $dryRun ? 'would be clamped (dry-run)' : 'clamped to login_at'
            ));
            Log::info('auth:revoke-idle-sessions fix-existing', [
                'broken_rows' => $brokenCount,
                'dry_run' => $dryRun,
            ]);
        }

        $threshold = Carbon::now()->subMinutes($minutes);
        $thresholdTs = $threshold->getTimestamp();

        $closed = 0;
        $sessionsKilled = 0;
        $orphansClosed = 0;
        $skippedExcludedRole = 0;

        // Mirror ImportLoginActivitiesFromTokens: skip these roles. They use the
        // mobile/API surface (Sanctum tokens) rather than the web session, so a
        // sweep based on the sessions table doesn't apply to them.
        $excludedRoles = ['security_staff', 'client', 'subcontractor'];

        $userIds = LoginActivity::whereNull('logout_at')
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if (!$user) {
                continue;
            }
            if ($user->hasAnyRole($excludedRoles) || in_array($user->role, $excludedRoles, true)) {
                $skippedExcludedRole++;
                continue;
            }

            $openLogins = LoginActivity::where('user_id', $userId)
                ->whereNull('logout_at')
                ->orderBy('login_at')
                ->get();

            // Close any older open rows using the next row's login_at as a
            // best-guess logout time (they were superseded by a newer login).
            for ($i = 0; $i < $openLogins->count() - 1; $i++) {
                $prev = $openLogins[$i];
                $next = $openLogins[$i + 1];
                $logoutAt = $next->login_at ?? Carbon::now();
                if (!$dryRun) {
                    $prev->update(['logout_at' => $logoutAt]);
                }
                $closed++;
            }

            $latest = $openLogins->last();
            if (!$latest) {
                continue;
            }

            $lastActivityTs = DB::table('sessions')
                ->where('user_id', $userId)
                ->max('last_activity');

            if ($lastActivityTs === null) {
                // No active session at all. Only close if the login itself is
                // already older than the idle window — otherwise the row may
                // belong to a session that simply hasn't been written yet.
                if ($latest->login_at && $latest->login_at->lt($threshold)) {
                    if (!$dryRun) {
                        $latest->update(['logout_at' => Carbon::now()]);
                    }
                    $closed++;
                    $orphansClosed++;
                }
                continue;
            }

            if ($lastActivityTs < $thresholdTs) {
                // Stamp logout_at with the moment the session is actually destroyed
                // (now), not the session's stale last_activity. Using last_activity
                // produced logout times *before* login because (a) it is the last
                // request time, which can predate this LoginActivity row, and
                // (b) Carbon::createFromTimestamp() returns UTC, so on a non-UTC app
                // timezone (Europe/London) it was shifted an hour behind login_at.
                $logoutAt = Carbon::now();

                if (!$dryRun) {
                    $latest->update(['logout_at' => $logoutAt]);
                    $deleted = DB::table('sessions')
                        ->where('user_id', $userId)
                        ->delete();
                    $sessionsKilled += $deleted;
                }
                $closed++;
            }
        }

        $summary = [
            'idle_minutes'           => $minutes,
            'dry_run'                => $dryRun,
            'rows_closed'            => $closed,
            'sessions_killed'        => $sessionsKilled,
            'orphans_closed'         => $orphansClosed,
            'skipped_excluded_role'  => $skippedExcludedRole,
            'excluded_roles'         => $excludedRoles,
        ];

        $this->info(sprintf(
            'auth:revoke-idle-sessions done. closed=%d sessions_killed=%d orphans_closed=%d skipped_role=%d (idle=%d min%s)',
            $closed,
            $sessionsKilled,
            $orphansClosed,
            $skippedExcludedRole,
            $minutes,
            $dryRun ? ', dry-run' : ''
        ));

        Log::info('auth:revoke-idle-sessions finished', $summary);

        return self::SUCCESS;
    }
}
