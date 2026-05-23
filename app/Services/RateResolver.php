<?php

namespace App\Services;

use App\Models\Site;
use App\Models\ShiftDate;
use App\Models\SiteStaffRate;
use App\Models\SiteHolidayRate;
use Carbon\Carbon;

class RateResolver
{
    /**
     * Resolve the pair of rates (guard_rate for pay, site_rate for billing) for a single
     * shift_date, using the current Site + SiteStaffRate + SiteHolidayRate configuration.
     *
     * Priority (per the client's spec):
     *   1. SiteHolidayRate matching the shift's calendar date (calendar-anchored, ignores effective_from)
     *   2. SiteStaffRate for (site_id, user_id) — only for guard_rate
     *   3. Site.guard_rate / Site.office_rate
     *
     * Why centralised: shift creation (ShiftController) and retroactive site-rate edits
     * (SiteController::update -> propagateRateChange) both need identical resolution,
     * otherwise the stored rates drift from what the site config implies.
     */
    public function resolveForShiftDate(ShiftDate $shiftDate, ?Site $site = null): array
    {
        $site = $site ?: optional($shiftDate->shift)->site;
        if (!$site) {
            return [
                'guard_rate' => $shiftDate->guard_rate,
                'site_rate'  => $shiftDate->site_rate,
            ];
        }

        $shiftDay = Carbon::parse($shiftDate->shift_date)->format('Y-m-d');

        $guardRate = $site->guard_rate;
        $siteRate  = $site->office_rate;

        $holiday = SiteHolidayRate::where('site_id', $site->id)
            ->whereDate('holiday_date', $shiftDay)
            ->first();
        if ($holiday) {
            if (!is_null($holiday->guard_rate)) $guardRate = $holiday->guard_rate;
            if (!is_null($holiday->site_rate))  $siteRate  = $holiday->site_rate;
        }

        if (!empty($shiftDate->staff_id)) {
            $staffOverride = SiteStaffRate::where('site_id', $site->id)
                ->where('user_id', $shiftDate->staff_id)
                ->value('guard_rate');
            if (!is_null($staffOverride)) {
                $guardRate = $staffOverride;
            }
        }

        return [
            'guard_rate' => $guardRate,
            'site_rate'  => $siteRate,
        ];
    }

    /**
     * Recompute and persist rates for every shift_date attached (via shifts.site_id) to
     * the given site, where shift_date >= $effectiveFrom. Past shift_dates are intentionally
     * left untouched — they keep whatever guard_rate/site_rate was snapshot at the time so
     * historical invoices remain stable, even when holiday or site rates are edited later.
     *
     * Returns the count of shift_dates that had at least one rate column changed.
     */
    public function propagateForSite(Site $site, Carbon $effectiveFrom): int
    {
        $shiftIds = $site->shifts()->pluck('id');
        if ($shiftIds->isEmpty()) {
            return 0;
        }

        $query = ShiftDate::whereIn('shift_id', $shiftIds)
            ->whereDate('shift_date', '>=', $effectiveFrom->format('Y-m-d'));

        $changed = 0;
        $query->with('shift.site')->chunkById(500, function ($shiftDates) use ($site, &$changed) {
            foreach ($shiftDates as $shiftDate) {
                $resolved = $this->resolveForShiftDate($shiftDate, $site);

                $dirty = false;
                if ((string) $shiftDate->guard_rate !== (string) $resolved['guard_rate']) {
                    $shiftDate->guard_rate = $resolved['guard_rate'];
                    $dirty = true;
                }
                if ((string) $shiftDate->site_rate !== (string) $resolved['site_rate']) {
                    $shiftDate->site_rate = $resolved['site_rate'];
                    $dirty = true;
                }
                if ($dirty) {
                    $shiftDate->saveQuietly();
                    $changed++;
                }
            }
        });

        return $changed;
    }
}
