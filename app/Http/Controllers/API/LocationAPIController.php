<?php

namespace App\Http\Controllers\API;

use Notify;
use Carbon\Carbon;
use App\Models\Patrol;
use App\Models\Location;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Shift;
use App\Models\ShiftDate;
use App\Models\User;
use App\Models\Site;
use App\Services\GeoService;
use Illuminate\Support\Facades\Log;


class LocationAPIController extends Controller
{
    //
    public function update(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy' => 'required|numeric',
            'timestamp' => 'date',
            'on_duty' => 'required|boolean',
            'shiftdate_id' => 'nullable',
            'patrol_id' => 'nullable',
        ]);

        $location = Location::create([
            'user_id' => Auth::id(),
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'accuracy' => $validated['accuracy'],
            'timestamp' => Carbon::now(),
            'on_duty' => $validated['on_duty'],
            'shiftdate_id' => $validated['shiftdate_id'],
            'patrol_id' => $validated['patrol_id'],
        ]);

        $outsideInfo = $this->notifyIfOutsideShiftSite(Auth::user(), $validated);

        return response()->json([
            'status' => 'success',
            'location_id' => $location->id,
            'outside_site' => (bool) ($outsideInfo['outside_site'] ?? false),
            'outside_message' => $outsideInfo['message'] ?? null,
        ]);
    }

    private function notifyIfOutsideShiftSite($user, array $validated): array
    {
        if (!$user || empty($validated['on_duty'])) {
            return ['outside_site' => false];
        }

        $shiftDate = $this->resolveShiftDateForLocation($validated);
        if (!$shiftDate || (int) ($shiftDate->staff_id ?? 0) !== (int) $user->id) {
            return ['outside_site' => false];
        }

        if (!(bool) ($shiftDate->shift?->restrict_location_check ?? false)) {
            return ['outside_site' => false];
        }

        $site = $shiftDate->shift?->site;
        if (!$site) {
            return ['outside_site' => false];
        }

        $geoService = app(GeoService::class);

        $address  = trim((string) ($site->address ?? ''));
        $postCode = trim((string) ($site->post_code ?? ''));
        if ($address === '' && $postCode === '') {
            Log::warning('Site address and postcode both missing for location geofence', [
                'shift_date_id' => $shiftDate->id ?? null,
                'site_id' => $site->id ?? null,
            ]);
            return ['outside_site' => false];
        }

        // Log::info('Using site address for geocoding (location API)', [
        //     'shift_date_id' => $shiftDate->id ?? null,
        //     'site_id' => $site->id ?? null,
        //     'site_address' => $address,
        //     'site_postcode' => $postCode,
        // ]);

        $siteCoords = $geoService->getCoordinatesFromAddress($address, $postCode ?: null);
        if (!$siteCoords || !isset($siteCoords['lat'], $siteCoords['lng'])) {
            Log::warning('Address geocoding failed for site (location API)', [
                'shift_date_id' => $shiftDate->id ?? null,
                'site_id' => $site->id ?? null,
                'site_address' => $address,
            ]);
            return ['outside_site' => false];
        }

        $distanceMeters = $geoService->distanceInMeters(
            $validated['latitude'],
            $validated['longitude'],
            $siteCoords['lat'],
            $siteCoords['lng']
        );

        // Prefer a per-site radius if configured on the site record; otherwise fall back to global config.
        $siteRadius = null;
        if (isset($site->radius) && is_numeric($site->radius) && (float) $site->radius > 0) {
            $siteRadius = (float) $site->radius + (float) config('services.site_geofence.radius_meters', 200);
        }

        $baseRadius = $siteRadius ?? (float) config('services.site_geofence.radius_meters', 500);

        // Always use the configured global margin; per-site margin is not supported here.
        $margin = (float) config('services.site_geofence.margin_meters', 200);
        $allowedMeters = $baseRadius + $margin;

        // Debug log to aid troubleshooting of geofence decisions
        // Log::debug('GeoFence radii (location API)', [
        //     'site_id' => $site->id ?? null,
        //     'site_radius' => $siteRadius,
        //     'base_radius' => $baseRadius,
        //     'margin' => $margin,
        //     'allowed_meters' => $allowedMeters,
        //     'distance_meters' => $distanceMeters,
        // ]);

        if ($distanceMeters <= $allowedMeters) {
            return ['outside_site' => false];
        }

        $cacheKey = 'outside_site_notified:' . $user->id . ':' . $shiftDate->id;
        if (Cache::has($cacheKey)) {
            return [
                'outside_site' => true,
                'message' => 'Outside shift site radius.',
            ];
        }

        $guardMessage = 'You are outside your shift site radius for ' . ($site->site_name ?? 'this site')
            . '. Please return to site. Distance: ' . round($distanceMeters, 1)
            . 'm (allowed: ' . round($allowedMeters, 1) . 'm).';

        $displayName = trim(($user->first_name ?? $user->name ?? 'Guard') . ' ' . ($user->last_name ?? ''));
        $dashboardMessage = $displayName . ' is outside the shift site radius at '
            . now()->format('Y-m-d H:i:s') . '. Site: ' . ($site->site_name ?? 'N/A')
            . ', Distance: ' . round($distanceMeters, 1) . 'm, Allowed: ' . round($allowedMeters, 1) . 'm.';

        Notify::toDashboard(
            null,
            'alert',
            'Guard Outside Site Radius',
            $dashboardMessage,
            '/shift-dates/' . $shiftDate->id . '/view'
        );

        send_push_notification(
            $user->id,
            'Outside Site Radius',
            $guardMessage,
            ['type' => 'location', 'shiftDateId' => $shiftDate->id]
        );

        $shiftEnd = Carbon::parse($shiftDate->shift_date . ' ' . $shiftDate->end_time);
        $shiftStart = Carbon::parse($shiftDate->shift_date . ' ' . $shiftDate->start_time);
        if ($shiftEnd->lte($shiftStart)) {
            $shiftEnd->addDay();
        }
        $cacheUntil = $shiftEnd->copy()->addHour();
        if ($cacheUntil->lte(now())) {
            $cacheUntil = now()->addHours(6);
        }

        Cache::put($cacheKey, true, $cacheUntil);

        return [
            'outside_site' => true,
            'message' => 'Outside shift site radius. Guard and control notified.',
        ];
    }

    private function resolveShiftDateForLocation(array $validated): ?ShiftDate
    {
        if (!empty($validated['shiftdate_id'])) {
            return ShiftDate::with('shift.site')->find($validated['shiftdate_id']);
        }

        if (!empty($validated['patrol_id'])) {
            $patrol = Patrol::find($validated['patrol_id']);
            if ($patrol) {
                return ShiftDate::with('shift.site')->find($patrol->shift_id);
            }
        }

        return null;
    }

    public function history(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'shift_id' => 'nullable|string', // Handle shifts if needed
        ]);

        $locations = Location::where('user_id', Auth::id())
            ->whereBetween('timestamp', [$request->date_from, $request->date_to])
            ->where('accuracy', '<=', 200)
            ->orderBy('timestamp', 'asc')
            ->get(['latitude', 'longitude', 'timestamp', 'accuracy']);

        return response()->json([
            'locations' => $locations
        ]);
    }

    public function locations(Patrol $patrol, Request $request)
    {
        $shiftDateId = $request->query('shiftDateId');

        if (!$shiftDateId) {
            return response()->json([
                'error' => 'shiftDateId is required'
            ], 400);
        }

        $locations = Location::where('patrol_id', $patrol->id)
            ->where('shiftdate_id', $shiftDateId)
            ->orderBy('created_at') // optional: order by timestamp
            ->get(['latitude', 'longitude', 'created_at']);

        return response()->json([
            'locations' => $locations
        ]);
    }

    public function disabled(Request $request)
    {
        $user = Auth::user();

        // Throttle notifications so admins don't get spammed
        $cacheKey = "location_disabled:{$user->id}";
        if (Cache::has($cacheKey)) {
            return response()->json(['status' => 'ok', 'message' => 'Notification already sent recently.']);
        }

        $displayName = trim(($user->first_name ?? $user->name) . ' ' . ($user->last_name ?? ''));
        $message = 'Location services disabled by ' . $displayName . ' at: ' . now();

        // Use your existing helper exactly as you showed
        \Notify::toDashboard(
            null,
            'alert',
            'Location Services Disabled',
            $message,
            ""
        );

        // keep cooldown (adjust minutes as you prefer)
        Cache::put($cacheKey, true, now()->addMinutes(30));

        return response()->json(['status' => 'success', 'message' => 'Control has been notified.']);
    }

    public function checkIdle(Request $request)
    {
        $user = $request->user();

        // Get last location
        $lastLocation = Location::where('user_id', $user->id)
            ->orderByDesc('timestamp')
            ->first();

        if (!$lastLocation) {
            return response()->json([
                'message' => 'No location recorded yet',
                'idle_status' => 'unknown'
            ]);
        }

        $now = now(); // current time
        $diffMinutes = $lastLocation->timestamp->diffInMinutes($now); // positive number

        $alerts = [];

        // 15-min idle -> notify guard
        if ($diffMinutes >= 15 && $diffMinutes < 30) {
            send_push_notification(
                $user->id,
                'Idle Alert',
                'You have been idle for 15 minutes.',
                ['type' => 'alert']
            );
            $alerts[] = 'guard_notified';
        }

        // 30-min idle -> notify control
        if ($diffMinutes >= 30) {
            Notify::toDashboard(
                null,
                'alert',
                'Idle Guard Alert',
                'Guard ' . $user->first_name.' '.$user->last_name .' has been idle for 30 minutes.',
                ""
            );
            $alerts[] = 'control_notified';
        }

        return response()->json([
            'idle_minutes' => $diffMinutes,
            'alerts_sent' => $alerts
        ]);
    }

    public function latestForSite(Request $request, $siteId)
    {
        $now = Carbon::now();

        // 1) Find the most relevant shift_date for this site:
        //    - active (start_time <= now <= end_time)
        //    - else most recent past (end_time < now) by end_time desc
        //    - else nearest future (start_time > now) by start_time asc
        $shiftDate = ShiftDate::whereHas('shift', function ($q) use ($siteId) {
                $q->where('site_id', $siteId);
            })
            ->whereNotNull('start_time') // guard against missing data
            ->whereNotNull('end_time')
            ->whereColumn('start_time', '<=', 'end_time') // sanity
            ->where(function ($q) use ($now) {
                $q->where(function ($q2) use ($now) {
                    // active
                    $q2->where('start_time', '<=', $now)->where('end_time', '>=', $now);
                });
            })
            ->orderByDesc('start_time')
            ->first();

        if (! $shiftDate) {
            // most recent past
            $shiftDate = ShiftDate::whereHas('shift', function ($q) use ($siteId) {
                    $q->where('site_id', $siteId);
                })
                ->whereNotNull('end_time')
                ->where('end_time', '<', $now)
                ->orderByDesc('end_time')
                ->first();
        }

        if (! $shiftDate) {
            // next future
            $shiftDate = ShiftDate::whereHas('shift', function ($q) use ($siteId) {
                    $q->where('site_id', $siteId);
                })
                ->where('start_time', '>', $now)
                ->orderBy('start_time')
                ->first();
        }

        // If no shift_date found at all, fall back to original behavior (all staff assigned to site shifts)
        if (! $shiftDate) {
            // original behavior: find distinct staff_ids across shift_dates for the site
            $staffIds = \DB::table('shift_dates')
                ->join('shifts', 'shift_dates.shift_id', '=', 'shifts.id')
                ->where('shifts.site_id', $siteId)
                ->whereNotNull('shift_dates.staff_id')
                ->distinct()
                ->pluck('shift_dates.staff_id')
                ->toArray();

            $windowStart = null;
            $windowEnd = null;
        } else {
            // Determine the staff IDs for the same shift occurrence.
            // This assumes shift_dates for the same shift occurrence share the same shift_id and start_time date/time.
            // Adjust logic if your model represents occurrences differently.
            $sdStart = Carbon::parse($shiftDate->start_time);
            $sdEnd = Carbon::parse($shiftDate->end_time);

            // Normalize end time for overnight shifts (end <= start -> add one day to end)
            $sdEndNormalized = $sdEnd->copy();
            if ($sdEndNormalized->lte($sdStart)) {
                $sdEndNormalized->addDay();
            }

            // Only return guard locations when the shift is currently active (between start and end)
            $nowCheck = Carbon::now();
            if (!($nowCheck->between($sdStart, $sdEndNormalized) || $nowCheck->equalTo($sdStart))) {
                // Shift is not in progress (either past or future) — don't expose staff locations
                $site = Site::find($siteId);
                $sitePayload = null;
                if ($site) {
                    $sitePayload = [
                        'id' => $site->id,
                        'site_name' => $site->site_name ?? '',
                        'address' => $site->address ?? '',
                        'post_code' => $site->post_code ?? $site->postcode ?? '',
                    ];
                }

                return response()->json([
                    'site' => $sitePayload,
                    'shift_date' => [
                        'id' => $shiftDate->id,
                        'shift_id' => $shiftDate->shift_id,
                        'start_time' => $shiftDate->start_time,
                        'end_time' => $shiftDate->end_time,
                    ],
                    'locations' => [],
                ]);
            }

            // small buffer in seconds to allow for clock skew (optional)
            $bufferSeconds = 60; // e.g. allow +/- 60s
            $windowStart = $sdStart->copy()->subSeconds($bufferSeconds);
            $windowEnd = $sdEndNormalized->copy()->addSeconds($bufferSeconds);

            // Get all staff assigned to the same shift occurrence
            $staffIds = ShiftDate::where('shift_id', $shiftDate->shift_id)
                ->whereDate('start_time', $sdStart->toDateString())
                ->whereNotNull('staff_id')
                ->pluck('staff_id')
                ->unique()
                ->filter()
                ->values()
                ->toArray();
        }

        $results = [];

        foreach ($staffIds as $sid) {
            // Prefer latest location inside the shift window (if window defined)
            $query = Location::where('user_id', $sid)
                ->whereNotNull('accuracy')
                ->where('accuracy', '<=', 100);

            if ($windowStart && $windowEnd) {
                // if timestamps are stored as datetimes
                $query->whereBetween('timestamp', [$windowStart->toDateTimeString(), $windowEnd->toDateTimeString()]);
            }

            $loc = (clone $query)->orderByDesc('timestamp')->first();

            // If no location in the window, fallback to the latest location for the user (optional)
            if (! $loc) {
                $loc = Location::where('user_id', $sid)
                    ->whereNotNull('accuracy')
                    ->where('accuracy', '<=', 100)
                    ->orderByDesc('timestamp')
                    ->first();
            }

            if ($loc) {
                $user = User::find($sid);
                $results[] = [
                    'user_id' => $sid,
                    'name' => $user ? trim(($user->first_name ?? $user->name) . ' ' . ($user->last_name ?? '')) : null,
                    'latitude' => (string) $loc->latitude,
                    'longitude' => (string) $loc->longitude,
                    'accuracy' => $loc->accuracy,
                    'timestamp' => $loc->timestamp,
                ];
            }
        }

        // If still empty, optionally try a site-wide location (locations referencing shiftdate_id for site shifts)
        if (empty($results)) {
            $loc = Location::whereIn('shiftdate_id', function ($q) use ($siteId) {
                    $q->select('shift_dates.id')
                        ->from('shift_dates')
                        ->join('shifts', 'shift_dates.shift_id', '=', 'shifts.id')
                        ->where('shifts.site_id', $siteId);
                })
                ->whereNotNull('accuracy')
                ->where('accuracy', '<=', 100)
                ->orderByDesc('timestamp')
                ->first();

            if ($loc) {
                $results[] = [
                    'user_id' => $loc->user_id,
                    'name' => null,
                    'latitude' => (string) $loc->latitude,
                    'longitude' => (string) $loc->longitude,
                    'accuracy' => $loc->accuracy,
                    'timestamp' => $loc->timestamp,
                ];
            }
        }

        // include site metadata so clients can geocode by address/postcode when needed
        $site = Site::find($siteId);
        $sitePayload = null;
        if ($site) {
            $sitePayload = [
                'id' => $site->id,
                'site_name' => $site->site_name ?? '',
                'address' => $site->address ?? '',
                'post_code' => $site->post_code ?? $site->postcode ?? '',
            ];
        }

        return response()->json([
            'site' => $sitePayload,
            'shift_date' => $shiftDate ? [
                'id' => $shiftDate->id,
                'shift_id' => $shiftDate->shift_id,
                'start_time' => $shiftDate->start_time,
                'end_time' => $shiftDate->end_time,
            ] : null,
            'locations' => $results,
        ]);
    }

}
