<?php

namespace Database\Seeders;

use App\Models\CheckCall;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Patrol;
use App\Models\PatrolCheckPoint;
use App\Models\Shift;
use App\Models\ShiftBooking;
use App\Models\ShiftDate;
use App\Models\Site;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

/**
 * Seeds a ready-to-demo client site with live map tracking, patrol path,
 * checkpoints, check-calls, and booked-on guards for "today".
 *
 * Re-run anytime to refresh today's shift window + fresh GPS breadcrumbs:
 *   php artisan db:seed --class=DemoTrackingSeeder --force
 */
class DemoTrackingSeeder extends Seeder
{
    public const SITE_CODE = 'FL-DEMO-TRACK';
    public const CLIENT_EMAIL = 'demo.client@fieldline.test';
    public const GUARD_EMAILS = [
        'demo.guard1@fieldline.test',
        'demo.guard2@fieldline.test',
    ];
    public const PASSWORD = 'Demo@12345';

    /** Canary Wharf / One Canada Square area (UK) */
    private float $siteLat = 51.504945;
    private float $siteLng = -0.019401;

    public function run(): void
    {
        $this->command?->info('Seeding FieldLine demo tracking data…');

        DB::transaction(function () {
            $roles = $this->ensureRoles();
            $client = $this->seedClient($roles['client']);
            $guards = $this->seedGuards($roles['security_staff']);
            $site = $this->seedSite($client);
            $this->seedCheckpoints($site);
            $shift = $this->seedShift($client, $site, $guards);
            $shiftDates = $this->seedShiftDates($shift, $guards);
            $patrol = $this->seedPatrol($shiftDates[0]);
            $this->seedCheckCalls($shiftDates[0], $guards[0]);
            $this->seedBookings($shiftDates, $guards);
            $this->seedLocations($shiftDates, $guards, $patrol);
        });

        $site = Site::withoutGlobalScopes()->where('site_code', self::SITE_CODE)->first();

        $this->command?->newLine();
        $this->command?->info('Demo tracking ready.');
        $this->command?->table(
            ['Item', 'Value'],
            [
                ['CRM login (superadmin)', 'use your existing admin account'],
                ['Demo client email', self::CLIENT_EMAIL],
                ['Demo guard emails', implode(', ', self::GUARD_EMAILS)],
                ['Password (client + guards)', self::PASSWORD],
                ['Site', $site?->site_name . ' (#' . $site?->id . ')'],
                ['Live map URL', $site ? url('/track/site/' . $site->id) : 'n/a'],
                ['Roster', url('/scheduling') . ' / ' . url('/today_rota')],
            ]
        );
    }

    private function ensureRoles(): array
    {
        return [
            'client' => Role::firstOrCreate(['name' => 'client', 'guard_name' => 'web']),
            'security_staff' => Role::firstOrCreate(['name' => 'security_staff', 'guard_name' => 'web']),
        ];
    }

    private function seedClient(Role $role): User
    {
        $user = User::withoutGlobalScopes()->firstOrNew(['email' => self::CLIENT_EMAIL]);
        $user->fill([
            'name' => 'Demo Client',
            'first_name' => 'Demo',
            'last_name' => 'Client',
            'username' => 'democlient',
            'password' => Hash::make(self::PASSWORD),
            'admin_id' => null,
        ]);
        $user->save();
        $user->syncRoles([$role]);

        // App expects both a users row (login) and a clients row (CRM profile).
        // sites.client_id stores the user's id (not clients.id).
        Client::withoutGlobalScopes()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'admin_id' => null,
                'client_name' => 'Demo Client Ltd',
                'email' => self::CLIENT_EMAIL,
                'contact_person' => 'Demo Client',
                'contact_number' => '020 0000 0000',
                'address' => '1 Canada Square, Canary Wharf, London',
                'is_active' => 1,
            ]
        );

        return $user;
    }

    /** @return array<int, User> */
    private function seedGuards(Role $role): array
    {
        $names = [
            ['Alex', 'Walker'],
            ['Jordan', 'Hayes'],
        ];
        $guards = [];

        foreach (self::GUARD_EMAILS as $i => $email) {
            $user = User::withoutGlobalScopes()->firstOrNew(['email' => $email]);
            $user->fill([
                'first_name' => $names[$i][0],
                'last_name' => $names[$i][1],
                'username' => 'demoguard' . ($i + 1),
                'password' => Hash::make(self::PASSWORD),
                'admin_id' => null,
            ]);
            $user->save();
            $user->syncRoles([$role]);

            Employee::withoutGlobalScopes()->updateOrCreate(
                ['email' => $email],
                [
                    'user_id' => $user->id,
                    'admin_id' => null,
                    'fore_name' => $names[$i][0],
                    'sur_name' => $names[$i][1],
                    'status' => 'active',
                    'sia_licence' => 'DEMO' . str_pad((string) ($i + 1), 8, '0', STR_PAD_LEFT),
                    'sia_status' => 'Active',
                    'sia_expiry' => now()->addYear()->toDateString(),
                    'job_title' => 'Security Officer',
                    'employee_type' => 'Static Guards',
                ]
            );

            $guards[] = $user;
        }

        return $guards;
    }

    private function seedSite(User $client): Site
    {
        return Site::withoutGlobalScopes()->updateOrCreate(
            ['site_code' => self::SITE_CODE],
            [
                'admin_id' => null,
                'client_id' => $client->id,
                'site_name' => 'FieldLine Demo — Canary Wharf HQ',
                'address' => '1 Canada Square, Canary Wharf, London',
                'post_code' => 'E14 5AB',
                'plus_code' => 'GX3J+X6 London',
                'contact_person' => 'Demo Client',
                'contact_number' => '020 0000 0000',
                'note' => 'DEMO SITE — seeded for map / patrol / tracking demos. Safe to re-seed.',
                'radius' => 150,
                'has_qr' => true,
                'start_time' => '06:00:00',
                'end_time' => '22:00:00',
                'break_time' => '00:30:00',
                'guard_rate' => 14.50,
                'office_rate' => 22.00,
            ]
        );
    }

    private function seedCheckpoints(Site $site): void
    {
        $points = [
            ['Main Reception', 0.00012, 0.00005],
            ['Loading Bay', -0.00018, 0.00010],
            ['Car Park Gate', 0.00005, -0.00022],
            ['Fire Exit East', 0.00020, -0.00008],
        ];

        foreach ($points as $i => [$name, $dLat, $dLng]) {
            PatrolCheckPoint::updateOrCreate(
                [
                    'site_id' => $site->id,
                    'name' => $name,
                ],
                [
                    'qr_code' => 'DEMO-CP-' . ($i + 1),
                    'nfc_tag' => 'DEMO-NFC-' . ($i + 1),
                    'latitude' => round($this->siteLat + $dLat, 7),
                    'longitude' => round($this->siteLng + $dLng, 7),
                    'required' => true,
                ]
            );
        }
    }

    /** @param array<int, User> $guards */
    private function seedShift(User $client, Site $site, array $guards): Shift
    {
        $shift = Shift::withoutGlobalScopes()
            ->where('site_id', $site->id)
            ->where('comments', 'FL-DEMO-TRACK-SHIFT')
            ->first();

        $payload = [
            'admin_id' => null,
            'site_id' => $site->id,
            'staff_id' => $guards[0]->id,
            'user_id' => $client->id,
            'from_shift' => now()->startOfDay(),
            'to_shift' => now()->endOfDay(),
            'start' => '06:00:00',
            'end' => '22:00:00',
            'comments' => 'FL-DEMO-TRACK-SHIFT',
            'restrict_location_check' => 0,
            'number_shift' => 2,
        ];

        if ($shift) {
            $shift->update($payload);
            return $shift;
        }

        return Shift::withoutGlobalScopes()->create($payload);
    }

    /**
     * @param array<int, User> $guards
     * @return array<int, ShiftDate>
     */
    private function seedShiftDates(Shift $shift, array $guards): array
    {
        $today = Carbon::today()->toDateString();
        $rows = [];

        // Wipe older demo dates for this shift so the live map always sees "today"
        ShiftDate::withoutGlobalScopes()
            ->where('shift_id', $shift->id)
            ->whereDate('shift_date', '<', $today)
            ->each(function (ShiftDate $sd) {
                Location::where('shiftdate_id', (string) $sd->id)->delete();
                CheckCall::where('shift_id', $sd->id)->delete();
                Patrol::where('shift_id', $sd->id)->delete();
                ShiftBooking::where('shift_id', $sd->id)->delete();
                $sd->forceDelete();
            });

        foreach ($guards as $guard) {
            $sd = ShiftDate::withoutGlobalScopes()->updateOrCreate(
                [
                    'shift_id' => $shift->id,
                    'staff_id' => $guard->id,
                    'shift_date' => $today,
                ],
                [
                    'admin_id' => null,
                    'start_time' => '00:00:00',
                    'end_time' => '23:59:00',
                    'total_hours' => '12',
                    'break_time' => '30',
                    'is_assign' => 3,
                    'status' => 'booked_on',
                    'absentee_start_time' => '06:00:00',
                    'require_media' => 0,
                    'guard_rate' => 14.50,
                    'site_rate' => 22.00,
                ]
            );
            $rows[] = $sd;
        }

        return $rows;
    }

    private function seedPatrol(ShiftDate $shiftDate): Patrol
    {
        // Map-demo patrol (already started) — kept for live tracking UI.
        $patrol = Patrol::withoutGlobalScopes()
            ->where('shift_id', $shiftDate->id)
            ->where('name', 'Demo Perimeter Patrol')
            ->first();

        $payload = [
            'admin_id' => null,
            'shift_id' => $shiftDate->id,
            'name' => 'Demo Perimeter Patrol',
            'summary' => 'Seeded patrol with GPS trail for demo heatmap.',
            'total_checkpoints' => 4,
            'completed_checkpoints' => 3,
            'issues_reported' => 0,
            'start_time' => now()->subHours(2),
            'started_at' => now()->subHours(2),
            'status' => 'in_progress',
            'approval_status' => 'pending',
        ];

        if ($patrol) {
            $patrol->update($payload);
        } else {
            $patrol = Patrol::withoutGlobalScopes()->create($payload);
        }

        // Overdue pending patrol so shifts:process-notifications can mark it missed.
        Patrol::withoutGlobalScopes()->updateOrCreate(
            [
                'shift_id' => $shiftDate->id,
                'name' => 'Demo Overdue Patrol',
            ],
            [
                'admin_id' => null,
                'summary' => 'Seeded pending patrol with start_time in the past (cron → missed).',
                'total_checkpoints' => 4,
                'completed_checkpoints' => 0,
                'issues_reported' => 0,
                'start_time' => now()->subMinutes(45),
                'started_at' => null,
                'completed_at' => null,
                'status' => 'pending',
                'approval_status' => 'pending',
            ]
        );

        return $patrol;
    }

    private function seedCheckCalls(ShiftDate $shiftDate, User $guard): void
    {
        CheckCall::where('shift_id', $shiftDate->id)->delete();

        $specs = [
            ['Early Check', now()->subHours(3), 'completed', 'approved'],
            ['Mid Shift Check', now()->subMinutes(40), 'completed', 'pending'],
            ['Next Check', now()->addHour(), 'pending', 'pending'],
        ];

        foreach ($specs as [$name, $when, $status, $approval]) {
            CheckCall::create([
                'shift_id' => $shiftDate->id,
                'name' => $name,
                'employee_id' => $status === 'completed' ? $guard->id : null,
                'scheduled_time' => $when,
                'status' => $status,
                'method' => 'app',
                'approval_status' => $approval,
                'require_media' => false,
            ]);
        }
    }

    /** @param array<int, ShiftDate> $shiftDates @param array<int, User> $guards */
    private function seedBookings(array $shiftDates, array $guards): void
    {
        foreach ($shiftDates as $i => $sd) {
            ShiftBooking::where('shift_id', $sd->id)->delete();
            ShiftBooking::create([
                'user_id' => $guards[$i]->id,
                'shift_id' => $sd->id,
                'type' => 'book_on',
                'latitude' => $this->siteLat + (($i - 0.5) * 0.00015),
                'longitude' => $this->siteLng + (($i - 0.5) * 0.00012),
                'address' => '1 Canada Square, Canary Wharf, London E14 5AB',
                'timestamp' => now()->subHours(4)->subMinutes($i * 7),
                'face_verification_result' => 'passed',
            ]);
        }
    }

    /**
     * @param array<int, ShiftDate> $shiftDates
     * @param array<int, User> $guards
     */
    private function seedLocations(array $shiftDates, array $guards, Patrol $patrol): void
    {
        foreach ($shiftDates as $sd) {
            Location::where('shiftdate_id', (string) $sd->id)->delete();
        }

        $hasPatrolId = Schema::hasColumn('locations', 'patrol_id');

        // Guard 1 — walking perimeter (also tagged to demo patrol for heatmap)
        $this->insertPath(
            userId: $guards[0]->id,
            shiftDateId: $shiftDates[0]->id,
            patrolId: $hasPatrolId ? $patrol->id : null,
            points: 48,
            minutesBack: 90,
            radiusDegrees: 0.00045
        );

        // Guard 2 — shorter nearby path (site live pins)
        $this->insertPath(
            userId: $guards[1]->id,
            shiftDateId: $shiftDates[1]->id,
            patrolId: null,
            points: 24,
            minutesBack: 45,
            radiusDegrees: 0.00025,
            phase: 1.2
        );
    }

    private function insertPath(
        int $userId,
        int $shiftDateId,
        ?int $patrolId,
        int $points,
        int $minutesBack,
        float $radiusDegrees,
        float $phase = 0.0
    ): void {
        $rows = [];
        $ts = now()->subMinutes($minutesBack);

        for ($i = 0; $i < $points; $i++) {
            $angle = $phase + ($i / max(1, $points - 1)) * 2 * M_PI;
            // Slight spiral so the trail looks like a real walk, not a perfect circle
            $r = $radiusDegrees * (0.55 + 0.45 * ($i / max(1, $points - 1)));
            $lat = $this->siteLat + ($r * cos($angle)) + (mt_rand(-8, 8) / 1000000);
            $lng = $this->siteLng + ($r * sin($angle) * 1.4) + (mt_rand(-8, 8) / 1000000);
            $ts = $ts->copy()->addSeconds(mt_rand(70, 110));

            $row = [
                'user_id' => $userId,
                'latitude' => round($lat, 6),
                'longitude' => round($lng, 6),
                'accuracy' => mt_rand(4, 18),
                'shiftdate_id' => (string) $shiftDateId,
                'timestamp' => $ts->toDateTimeString(),
                'on_duty' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('locations', 'patrol_id')) {
                $row['patrol_id'] = $patrolId;
            }

            $rows[] = $row;
        }

        Location::insert($rows);
    }
}
