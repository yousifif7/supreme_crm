<?php

namespace App\Http\Controllers\API;

use Notify;
use Carbon\Carbon;
use App\Models\Site;
use App\Models\User;
use App\Models\Shift;
use App\Helpers\Logger;
use App\Models\Employee;
use App\Models\ShiftDate;
use App\Models\ShiftNote;
use App\Models\BookingAlarm;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\ShiftBooking;
use Illuminate\Http\Request;
use App\Models\PatrolCheckPoint;
use App\Models\CheckpointScan;
use App\Models\Patrol;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\PatrolMedia;
use App\Models\BookingMedia;
use Illuminate\Support\Facades\Log;
use App\Services\GeoService;
use App\Models\Location;

class ShiftApiController extends Controller
{
 /**
 * Parse a UK-based timestamp string and return a Carbon instance
 * Supports multiple formats (timestamp already in UK time):
 * - "2024-01-15T14:30:45Z" (ISO 8601 UTC)
 * - "2024-01-15T14:30:45+00:00" (ISO 8601 with offset)
 * - "2024-01-15 14:30:45" (ISO with time)
 * - "15/01/2024 14:30:45" (UK format with time)
 * - "15/01/2024 2:30 PM" (UK format with 12h time)
 * 
 * @param string $timestamp
 * @return \Carbon\Carbon
 */
private function parseUKTimestamp($timestamp)
{
    try {
        // If the timestamp has timezone info (Z, +00:00, etc), parse it as-is
        // Carbon will handle the conversion automatically
        if (preg_match('/[Z\+\-]\d{2}:?\d{2}$/', $timestamp) || str_ends_with($timestamp, 'Z')) {
            // Parse as timezone-aware string (will be in UTC/offset)
            $carbon = Carbon::parse($timestamp);
            // Convert to London time for storage/display
            return $carbon->setTimezone('Europe/London');
        }

        // Try ISO format first (no timezone info)
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $timestamp)) {
            return Carbon::parse($timestamp);
        }

        // Try DD/MM/YYYY format (UK standard, assume it's already in UK time)
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})/', $timestamp)) {
            return Carbon::createFromFormat('d/m/Y H:i:s', $timestamp)
                ?? Carbon::createFromFormat('d/m/Y H:i', $timestamp)
                ?? Carbon::createFromFormat('d/m/Y', $timestamp)
                ?? Carbon::parse($timestamp);
        }

        // Fallback to generic parsing (assumes server timezone)
        return Carbon::parse($timestamp);
    } catch (\Exception $e) {
        Log::warning('Failed to parse timestamp: ' . $timestamp . ' - ' . $e->getMessage());
        return Carbon::now('Europe/London');
    }
}
	
    // 10. Get Upcoming Shifts
    public function getShifts(Request $request)
    {
        $userId   = Auth::id();
        $limit    = $request->query('limit', 50);
        $category = $request->query('category'); // "past", "current", "upcoming"
        $today    = now()->toDateString();

        $orderParam = strtolower($request->query('order', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query = ShiftDate::with([
            'shift.site',
            'trainings' => function ($q) use ($userId) {
                $q->with(['acknowledgedUsers' => function ($q2) use ($userId) {
                    $q2->where('user_id', $userId);
                }]);
            },
            ])
            ->where('staff_id', $userId)
            ->orderBy('shift_date', $orderParam)
            ->orderBy('start_time', $orderParam);

        // category filter — make time-aware so "current" excludes future shifts later today
        if ($category) {
            if ($category === 'past') {
                // Exclude very old historical shifts (2025 and earlier) from past results
                // Also include any shifts explicitly marked as ended/booked-off (is_assign == 4)
                $cutoff = '2025-12-15';
                $query->where(function($q) use ($today, $cutoff) {
                    $q->where(function($q2) use ($today, $cutoff) {
                        $q2->where('shift_date', '<', $today)->where('shift_date', '>=', $cutoff);
                    })->orWhere('is_assign', 4);
                });
            } elseif ($category === 'current') {
                $nowStr = Carbon::now()->format('Y-m-d H:i:s');
                $query->where(function ($q) use ($today, $nowStr) {
                    // include explicitly booked-on shifts OR shifts that have already started (today)
                    $q->where('is_assign', 3)
                      ->orWhere(function ($q2) use ($today, $nowStr) {
                          // only include shifts that have started and are not already marked ended (is_assign != 4)
                          $q2->where('shift_date', $today)
                             ->whereRaw("CONCAT(shift_date,' ',start_time) <= ?", [$nowStr])
                             ->where('is_assign', '!=', 4);
                      });
                });
            } elseif ($category === 'upcoming') {
                $nowStr = Carbon::now()->format('Y-m-d H:i:s');
                $query->where(function ($q) use ($today, $nowStr) {
                    // future-dated shifts or later-today shifts whose start time is still in the future
                    $q->where('shift_date', '>', $today)
                      ->orWhere(function ($q2) use ($today, $nowStr) {
                          $q2->where('shift_date', $today)
                             ->whereRaw("CONCAT(shift_date,' ',start_time) > ?", [$nowStr])
                             ->where('is_assign', '!=', 4);
                      });
                });
            }
        }

        $shifts = $query->paginate($limit);

        $transformed = $shifts->getCollection()->transform(function ($shiftDate) use ($today, $userId) {
            $shift = $shiftDate->shift;
            $site  = $shift?->site;

            // Determine category using precise datetimes so overnight shifts and booked-on state are handled.
            $nowDt = Carbon::now();
            $shiftStart = Carbon::parse($shiftDate->shift_date . ' ' . $shiftDate->start_time);
            $shiftEnd = Carbon::parse($shiftDate->shift_date . ' ' . $shiftDate->end_time);
            if ($shiftEnd->lte($shiftStart)) {
                // Overnight shift, end on following day
                $shiftEnd->addDay();
            }

            // Determine if the shift is currently in progress (handles overnight)
            $inProgress = $shiftStart->lte($nowDt) && $shiftEnd->gt($nowDt);

            // Ended shifts (explicitly booked off or scheduled end passed)
            if ($shiftDate->is_assign == 4 || $shiftEnd->lte($nowDt)) {
                $category = 'past';
            }
            // Current when explicitly booked-on OR when the shift is in progress
            elseif ($shiftDate->is_assign == 3 || $inProgress) {
                $category = 'current';
            }
            // All other cases (not booked on and not started yet) are upcoming
            else {
                $category = 'upcoming';
            }

            // Fetch the note for this shift
            // Latest guard-facing note only (note_type 'guard' or 'both'); control-only notes are hidden from the guard.
            $note = ShiftNote::where('shift_date_id', $shiftDate->id)
                ->whereIn('note_type', ['guard', 'both'])
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->first();

                // Load trainings from the site (materials belong to site), not the shift
                $siteTrainings = collect();
                if ($site) {
                    $siteTrainings = $site->trainings()->with(['acknowledgedUsers' => function ($q) use ($userId) {
                        $q->where('user_id', $userId);
                    }])->get();
                }

                $trainings = $siteTrainings->map(function ($training) {
                $ack = $training->acknowledgedUsers->first();
                $acknowledged = false;
                $acknowledgedAt = null;
                $completionSeconds = null;

                if ($ack) {
                    $acknowledged = !empty($ack->acknowledged_at);
                    $acknowledgedAt = $ack->acknowledged_at ? (string) $ack->acknowledged_at : null;
                    $completionSeconds = $ack->completion_time_seconds !== null
                        ? (int) $ack->completion_time_seconds
                        : null;
                }

                return [
                    'id' => $training->id,
                    'title' => $training->title,
                    'description' => $training->description,
                    'pdf_url' => $training->pdf_url,
                    'content_url' => $training->content_url ?? null,
                    'required' => (bool) ($training->required ?? false),
                    'acknowledged' => $acknowledged,
                    'acknowledged_at' => $acknowledgedAt,
                    'completion_time_seconds' => $completionSeconds,
                    'implementation_date' => $training->implementation_date,
                    'complete_by_date' => $training->deadline,
                    'acknowledge_by_date' => $training->acknowledge_by_date,
                    'created_at' => $training->created_at,
                    'updated_at' => $training->updated_at,
                ];
            });

            return [
                'id' => $shiftDate->id,
                'shift_id' => $shiftDate->shift_id,
                'site_id' => $site?->id,
                'site_name' => $site?->site_name,
                'site_address' => $site?->address,
                'start_time' => $shiftDate->start_time,
                'end_time' => $shiftDate->end_time,
                'shift_date' => $shiftDate->shift_date,
                'duties' => $shift?->duties,
                'supervisor_name' => $shift?->supervisor_name,
                'supervisor_contact' => $shift?->supervisor_contact,
                'status' => $shiftDate->status,
                'started_at' => $shiftDate->absentee_start_time,
                'ended_at' => $shiftDate->absentee_end_time,
                'briefing_pdf' => $shift?->briefing_pdf_url,
                'risk_assessment_pdf' => $shift?->risk_assessment_pdf_url,
                'category' => $category,
                'trainings' => $trainings,
                'note' => $note ? [
                    'id'        => $note->id,
                    'note_type' => $note->note_type,
                    'note'      => $note->note,
                ] : null,

                'requires_booking_media_for_book_on' => (function() use ($shiftDate, $userId) {
                    try {
                        $hasPatrols = $shiftDate->patrols()->exists();
                        $hasCheckCalls = $shiftDate->checkCalls()->exists();
                        if ($hasPatrols || $hasCheckCalls) return false;
                        return !\App\Models\BookingMedia::where('shift_date_id', $shiftDate->id)
                            ->where('user_id', $userId)
                            ->where('type', 'book_on')
                            ->exists();
                    } catch (\Exception $e) {
                        // On error default to false to avoid forcing uploads unexpectedly
                        return false;
                    }
                })(),
                'requires_booking_media_for_book_off' => (function() use ($shiftDate, $userId) {
                    try {
                        $hasPatrols = $shiftDate->patrols()->exists();
                        $hasCheckCalls = $shiftDate->checkCalls()->exists();
                        if ($hasPatrols || $hasCheckCalls) return false;
                        return !\App\Models\BookingMedia::where('shift_date_id', $shiftDate->id)
                            ->where('user_id', $userId)
                            ->where('type', 'book_off')
                            ->exists();
                    } catch (\Exception $e) {
                        return false;
                    }
                })(),
            ];
        });

        return response()->json([
            'shift_dates' => $transformed,
            'pagination' => [
                'current_page' => $shifts->currentPage(),
                'total_pages'  => $shifts->lastPage(),
                'total'        => $shifts->total(),
            ],
        ]);
    }

    /**
     * Return a count of shifts for the authenticated user matching an optional category.
     * Query params: ?category=past|current|upcoming
     */
    public function countShifts(Request $request)
    {
        $userId = Auth::id();
        $category = $request->query('category');
        $today = now()->toDateString();
        $nowStr = Carbon::now()->format('Y-m-d H:i:s');

        $query = ShiftDate::where('staff_id', $userId);

        if ($category) {
            if ($category === 'past') {
                $cutoff = '2025-12-15';
                $query->where(function($q) use ($today, $cutoff) {
                    $q->where(function($q2) use ($today, $cutoff) {
                        $q2->where('shift_date', '<', $today)->where('shift_date', '>=', $cutoff);
                    })->orWhere('is_assign', 4);
                });
            } elseif ($category === 'current') {
                $query->where(function ($q) use ($today, $nowStr) {
                    $q->where('is_assign', 3)
                      ->orWhere(function ($q2) use ($today, $nowStr) {
                          $q2->where('shift_date', $today)
                             ->whereRaw("CONCAT(shift_date,' ',start_time) <= ?", [$nowStr])
                             ->where('is_assign', '!=', 4);
                      });
                });
            } elseif ($category === 'upcoming') {
                $query->where(function ($q) use ($today, $nowStr) {
                    $q->where('shift_date', '>', $today)
                      ->orWhere(function ($q2) use ($today, $nowStr) {
                          $q2->where('shift_date', $today)
                             ->whereRaw("CONCAT(shift_date,' ',start_time) > ?", [$nowStr])
                             ->where('is_assign', '!=', 4);
                      });
                });
            }
        }

        $count = $query->count();

        return response()->json([
            'category' => $category ?? 'all',
            'count' => $count,
        ]);
    }

    /**
     * GET /api/shifts/monthly-hours
     * Returns aggregated monthly hours for the authenticated guard.
     */
    public function monthlyHours(Request $request)
    {
        $userId = Auth::id();
        $tz = 'Europe/London';
        $now = Carbon::now($tz);

        $startMonth = $request->query('start_month'); // YYYY-MM
        $endMonth = $request->query('end_month');     // YYYY-MM
        $year = $request->query('year');

        try {
            if ($startMonth && $endMonth) {
                $start = Carbon::createFromFormat('Y-m', $startMonth, $tz)->startOfMonth();
                $end = Carbon::createFromFormat('Y-m', $endMonth, $tz)->endOfMonth();
            } elseif ($year) {
                $start = Carbon::createFromFormat('Y', $year, $tz)->startOfYear();
                $end = Carbon::createFromFormat('Y', $year, $tz)->endOfYear();
            } else {
                $start = $now->copy()->startOfYear();
                $end = $now->copy()->endOfYear();
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Invalid date format'], 400);
        }

        // Enforce max range: last 5 years
        $earliest = $now->copy()->subYears(5)->startOfYear();
        if ($start->lt($earliest)) $start = $earliest;

        if ($end->lt($start)) {
            return response()->json(['success' => false, 'message' => 'Invalid date range: end_month must be after start_month'], 400);
        }

        // Build month list (oldest first)
        $months = [];
        $cursor = $start->copy()->startOfMonth();
        while ($cursor->lte($end)) {
            $months[] = ['month_key' => $cursor->format('Y-m'), 'month' => $cursor->format('F Y')];
            $cursor->addMonth();
        }

        // Aggregate in DB: sum minutes and count shifts grouped by shift_date month
        // Note: this assumes shift_date/start_time/end_time are stored in local (Europe/London) values.
        $agg = DB::table('shift_dates')
            ->selectRaw("DATE_FORMAT(shift_date,'%Y-%m') as month_key, DATE_FORMAT(shift_date,'%M %Y') as month_name, SUM(TIMESTAMPDIFF(MINUTE, CONCAT(shift_date,' ',start_time), CONCAT(CASE WHEN end_time <= start_time THEN DATE_ADD(shift_date, INTERVAL 1 DAY) ELSE shift_date END, ' ', end_time))) as minutes, COUNT(*) as shifts_count")
            ->where('staff_id', $userId)
            ->whereIn('status', ['completed', 'booked_off'])
            ->whereBetween('shift_date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('month_key', 'month_name')
            ->orderBy('month_key', 'asc')
            ->get();

        $map = $agg->keyBy('month_key');

        $monthly_breakdown = [];
        $totalMinutes = 0;
        foreach ($months as $m) {
            $r = $map->get($m['month_key']);
            $minutes = $r ? (int) $r->minutes : 0;
            $shifts_count = $r ? (int) $r->shifts_count : 0;
            $hours = round($minutes / 60, 1);
            $monthly_breakdown[] = [
                'month_key' => $m['month_key'],
                'month' => $m['month'],
                'total_hours' => $hours,
                'shifts_count' => $shifts_count,
            ];
            $totalMinutes += $minutes;
        }

        $total_hours = round($totalMinutes / 60, 1);

        // Current month based on server UK time
        $currentMonthKey = $now->format('Y-m');
        $currentAgg = $map->get($currentMonthKey);
        $current_minutes = $currentAgg ? (int) $currentAgg->minutes : 0;
        $current_hours = round($current_minutes / 60, 1);
        $current_shifts = $currentAgg ? (int) $currentAgg->shifts_count : 0;

        // Determine reported year value when appropriate
        $yearField = null;
        if (!($startMonth && $endMonth)) {
            $yearField = (int) $start->format('Y');
        } else {
            if ($start->format('Y') === $end->format('Y')) $yearField = (int) $start->format('Y');
        }

        return response()->json([
            'success' => true,
            'data' => [
                'current_month' => [
                    'month_key' => $currentMonthKey,
                    'month' => $now->format('F Y'),
                    'total_hours' => $current_hours,
                    'shifts_count' => $current_shifts,
                ],
                'year_to_date' => [
                    'year' => $yearField,
                    'total_hours' => $total_hours,
                    'monthly_breakdown' => $monthly_breakdown,
                ],
            ],
        ]);
    }

    // 11. Accept/Decline Shift
    public function respondToShift(Request $request, $shift_id)
    {
        $request->validate([
            'response' => 'required|in:accept,decline',
            'reason' => 'required_if:response,decline|string|nullable',
        ]);

        $employee = Employee::where('user_id', Auth::id())->first();

        $shift = ShiftDate::where('id', $shift_id)
            ->where('staff_id', Auth::id())
            ->first();

        if (!$shift) {
            return response()->json([
                'message' => 'Shift date (ID: ' . $shift_id . ') Not on your upcoming shifts list!',
            ]);
        }

        $user = User::find(Auth::id());

        // check if shift status is dispatched
        if ($shift->is_assign == 1) {
            if ($request->response == 'accept') {
                $shift->status = 'accepted';
                $shift->is_assign = 2; //accept shift
                $shift->save();

                Notification::create([
                    'user_id' => 1,
                    'employee_id' => null,
                    'type' => 'alert',
                    'title' => 'Shift Accepted ',
                    'message' => 'Guard ' . $user->first_name . ' ' . $user->last_name . ' has Accepted shift (ID: ' . $shift->id . ' starting at ' . $shift->start_time,
                    'read' => false,
                    'action_url' => "/shift-dates/$shift->id/view"
                ]);

                return response()->json([
                    'message' => 'Shift date Accepted successfully!',
                ]);
            } elseif ($request->response == 'decline') {
                $shift->status = 'declined';
                $shift->is_assign = 5; //reject shift
                // $shift->reason = $request->reason ?? null;
                $shift->staff_id = null;
                $shift->save();

                Notification::create([
                    'user_id' => 1,
                    'employee_id' => null,
                    'type' => 'alert',
                    'title' => 'Shift Declined ',
                    'message' => 'Guard ' . $user->first_name . ' ' . $user->last_name . ' has Declined shift (ID: ' . $shift->id . ' starting at ' . $shift->start_time,
                    'read' => false,
                    'action_url' => "/shift-dates/$shift->id/view"
                ]);

                return response()->json([
                    'message' => 'Shift date Declined successfully!',
                ]);
            }

            return response()->json([
                'message' => 'Unaccepted response (' . $request->response . ') You can only accept / decline',
            ]);
        }

        // Provide clearer reasons when respond is not allowed
        if ($shift->is_assign == 2) {
            return response()->json(['message' => 'Shift already accepted.'], 409);
        }

        if ($shift->is_assign == 3) {
            return response()->json(['message' => 'Cannot accept/decline: shift already booked on.'], 409);
        }

        if ($shift->is_assign == 4) {
            return response()->json(['message' => 'Cannot accept/decline: shift has already ended.'], 409);
        }

        return response()->json([
            'message' => 'Could not submit a respond, current shift state (is_assign=' . $shift->is_assign . ', status=' . $shift->status . ')'
        ], 422);
    }

    public function submitLeaveRequest(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date|after_or_equal:now',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'required|string',
            'type'       => 'required|in:annual_leave,sick_leave,unpaid_leave,other_leave',
            'hours'      => 'nullable|numeric|min:0',
            'shift_id'   => 'nullable',
        ]);

        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        // Parse full datetime instead of date only
        $start = Carbon::parse($request->start_date);
        $end   = Carbon::parse($request->end_date);

        $totalDays   = $end->diffInDays($start) + 1;
        $hoursPerDay = $request->hours ?? 8;
        $totalHours  = max(0, $totalDays * $hoursPerDay);

        $paid          = false;
        $sspPaidDays   = 0;
        $holidayHours  = 0;
        $unpaidHours   = 0;

        switch ($request->type) {
                case 'sick_leave':
                $weeklyPay = $employee->weekly_pay ?? 0;
                $sickPay   = $this->calculateSickPay($employee, $start, $end, $weeklyPay);

                $sspPaidDays = $sickPay['paid_days'];
                    $unpaidHours = max(0, ($sickPay['unpaid_days'] ?? 0) * $hoursPerDay);
                $paid        = $sspPaidDays > 0;
                break;

                case 'annual_leave':
                    $holidayBalance = $employee->holiday_balance ?? 0; // in hours
                    if ($totalHours > $holidayBalance) {
                        $holidayHours = max(0, $holidayBalance);
                        $unpaidHours  = max(0, $totalHours - $holidayBalance);
                        $paid = $holidayBalance > 0;
                    } else {
                        $holidayHours = max(0, $totalHours);
                        $paid = $holidayHours > 0;
                    }
                break;

                case 'unpaid_leave':
                    $unpaidHours = max(0, $totalHours);
                    $paid = false;
                break;

                case 'other_leave':
                    $paid = $request->paid ?? false;
                    if ($paid) {
                        $holidayHours = max(0, $totalHours);
                    } else {
                        $unpaidHours = max(0, $totalHours);
                    }
                break;
        }
        // Normalize and clamp values before storing
        $unpaidHours = max(0, $unpaidHours ?? 0);
        $holidayHours = max(0, $holidayHours ?? 0);
        $sspPaidDays = max(0, $sspPaidDays ?? 0);

        // Derive admin_id from the guard's user record so the owning admin can see this request
        $adminId = \App\Models\User::withoutGlobalScope('admin_scope')
            ->where('id', $user->id)
            ->value('admin_id');

        $leave = LeaveRequest::create([
            'admin_id'         => $adminId,
            'user_id'          => $user->id,
            'employee_id'      => $employee->id,
            'shift_id'         => $request->shift_id,
            'start_date'       => $start, // datetime
            'end_date'         => $end,   // datetime
            'reason'           => $request->reason,
            'type'             => $request->type,
            'hours'            => $totalHours,
            'approved_hours'   => max(0, $totalHours - $unpaidHours),
            'paid'             => (bool) $paid,
            'ssp_paid_days'    => $sspPaidDays,
            'holiday_days_used' => $holidayHours,
            'unpaid_days'      => max(0, $unpaidHours / $hoursPerDay),
            'amount_paid'      => $sspPaidDays * 23.75,
            'status'           => 'pending',
        ]);

        // Notifications
        Notify::toDashboard(
            null,
            'alert',
            'Leave Request',
            'Leave Request by ' . $employee->fore_name . ' ' . $employee->sur_name,
            "/leaves"
        );

        return response()->json([
            'message'  => 'Leave request submitted',
            'leave_id' => $leave->id,
        ]);
    }

    public function showLeaves()
    {
        $leaves = LeaveRequest::where('user_id', Auth::id())
            ->latest('created_at')
            ->paginate(10);

        $items = $leaves->getCollection()->map(function ($l) {
            return [
                'id'               => $l->id,
                'shift_id'               => $l->shift_id,
                'type'             => $l->type,
                'status'           => $l->status,
                'reason'           => $l->reason,
                'start_date'       => $l->start_date,
                'end_date'         => $l->end_date,
                'hours'            => max(0, $l->hours ?? 0),
                'reject_reason'    => $l->reject_reason,
                'approved_hours'   => max(0, $l->approved_hours ?? 0),
                'paid'             => (bool) $l->paid,
                'ssp_paid_days'    => max(0, $l->ssp_paid_days ?? 0),
                'holiday_days_used' => max(0, $l->holiday_days_used ?? 0),
                'unpaid_days'      => max(0, $l->unpaid_days ?? 0),
                'amount_paid'      => max(0, $l->amount_paid ?? 0),
                'created_at'       => $l->created_at,
                'updated_at'       => $l->updated_at,
            ];
        })->values();

        return response()->json([
            'leaves' => $items,
            'pagination' => [
                'current_page' => $leaves->currentPage(),
                'per_page'     => $leaves->perPage(),
                'total_pages'  => $leaves->lastPage(),
                'total'        => $leaves->total(),
                'from'         => $leaves->firstItem(),
                'to'           => $leaves->lastItem(),
            ],
        ]);
    }

    /**
     * Get a single leave request by ID
     */
    public function showLeaveRequest($id)
    {
        $leave = LeaveRequest::where('user_id', Auth::id())
            ->where('id', $id)
            ->first();

        if (!$leave) {
            return response()->json(['message' => 'Leave request not found'], 404);
        }

        return response()->json([
            'leave' => [
                'id'                => $leave->id,
                'shift_id'          => $leave->shift_id,
                'type'              => $leave->type,
                'status'            => $leave->status,
                'reason'            => $leave->reason,
                'start_date'        => $leave->start_date,
                'end_date'          => $leave->end_date,
                'hours'             => max(0, $leave->hours ?? 0),
                'reject_reason'     => $leave->reject_reason,
                'approved_hours'    => max(0, $leave->approved_hours ?? 0),
                'paid'              => (bool) $leave->paid,
                'ssp_paid_days'     => max(0, $leave->ssp_paid_days ?? 0),
                'holiday_days_used' => max(0, $leave->holiday_days_used ?? 0),
                'unpaid_days'       => max(0, $leave->unpaid_days ?? 0),
                'amount_paid'       => max(0, $leave->amount_paid ?? 0),
                'created_at'        => $leave->created_at,
                'updated_at'        => $leave->updated_at,
            ]
        ]);
    }


    // 13. Acknowledge Shift Documents
    public function acknowledgeDocuments(Request $request, $shift_id)
    {
        $request->validate([
            'risk_assessment_read' => 'required|boolean',
            'assignment_instructions_read' => 'required|boolean',
            'acknowledgment_timestamp' => 'nullable|date',
        ]);

        $employee = Employee::where('user_id', Auth::id())->first();

        $shift = ShiftDate::where('id', $shift_id)
            ->where('staff_id', $employee->id)
            ->firstOrFail();

        $shift->update([
            'risk_assessment_read' => $request->risk_assessment_read,
            'assignment_instructions_read' => $request->assignment_instructions_read,
            'acknowledgment_timestamp' => Carbon::now(),
        ]);

        return response()->json(['message' => 'Documents acknowledged']);
    }

    public function bookOnOff(Request $request, $shiftDate_id, $type)
    {

        // If a booking already exists for this user+shift+type, return it (avoid duplicates)
        $user = Auth::user();
        $existing = ShiftBooking::where('user_id', $user->id)
            ->where('shift_id', $shiftDate_id)
            ->where('type', $type)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'booking_id' => $existing->id,
                'message' => 'Booking already exists.'
            ]);
        }

        $request->validate([
            'face_verification_result' => 'required|string',
            'location.latitude' => 'required|numeric',
            'location.longitude' => 'required|numeric',
            'location.address' => 'required|string',
            'timestamp' => 'date',
        ]);

        $formattedTimestamp = Carbon::now()->format('Y-m-d H:i:s');

        // Correct: fetch by shiftdate primary key
        $shiftDate = ShiftDate::find($shiftDate_id);
        if (!$shiftDate) {
            return response()->json([
                'message' => 'Shift date (ID: ' . $shiftDate_id . ') Not on your upcoming shifts list!',
            ]);
        }
        
/*
        $geoFenceError = $this->ensureWithinShiftSiteRadius(
            $shiftDate,
            $request->input('location.latitude'),
            $request->input('location.longitude'),
            'book ' . str_replace('_', ' ', $type)
        );
        if ($geoFenceError) {
            return $geoFenceError;
        }
    */    

        $booking = ShiftBooking::create([
            'user_id' => $user->id,
            'shift_id' => $shiftDate->id, // store shift_date_id, not main shift_id
            'type' => $type,
            'face_verification_result' => $request->face_verification_result,
            'latitude' => $request->location['latitude'],
            'longitude' => $request->location['longitude'],
            'address' => $request->location['address'],
            'timestamp' => $formattedTimestamp,
        ]);

        return response()->json([
            'success' => true,
            'booking_id' => $booking->id,
            'message' => 'Successfully booked ' . str_replace('_', ' ', $type),
        ]);
    }

    public function getBookingAlarms()
    {
        $user = Auth::user();

        $alarms = BookingAlarm::where('user_id', $user->id)
            ->where('acknowledged', false)
            ->get();

        return response()->json([
            'upcoming_alarms' => $alarms->map(function ($alarm) {
                return [
                    'shift_id' => $alarm->shift_id,
                    'type' => $alarm->type,
                    'scheduled_time' => $alarm->scheduled_time,
                    'alarm_time' => $alarm->alarm_time,
                    'acknowledged' => $alarm->acknowledged,
                ];
            }),
        ]);
    }

    public function acknowledgeAlarm($alarm_id)
    {
        $alarm = BookingAlarm::where('id', $alarm_id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $alarm->update(['acknowledged' => true]);

        return response()->json(['message' => 'Alarm acknowledged']);
    }

    public function bookOn(Request $request, $shiftDate_id)
    {
        $validated = $request->validate([
            'location.latitude' => 'required|numeric',
            'location.longitude' => 'required|numeric',
        ]);

        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return response()->json(['message' => 'No employee record linked to this user.'], 404);
        }

        // Check if user already has an active book_on (not yet booked off)
        $existingBooking = ShiftBooking::where('user_id', $user->id)
            ->where('type', 'book_on')
            // only consider bookings whose ShiftDate is not marked as ended (is_assign != 4)
            ->whereIn('shift_id', function ($q) {
                $q->select('id')->from('shift_dates')->where('is_assign', '!=', 4);
            })->first();

        if ($existingBooking) {
            return response()->json([
                'message' => 'You already have an active booked on shift (ShiftDate ID: ' . $existingBooking->shift_id . ').'
            ], 409);
        }

        //  Correct: find by ShiftDate ID
        $shiftDate = ShiftDate::with('trainings.acknowledgedUsers')->find($shiftDate_id);

        if (!$shiftDate) {
            return response()->json([
                'message' => 'Trying to book on unavailable shift (ShiftDate ID: ' . $shiftDate_id . ').'
            ], 409);
        }

/*     
       $geoFenceError = $this->ensureWithinShiftSiteRadius(
            $shiftDate,
            $validated['location']['latitude'],
            $validated['location']['longitude'],
            'book on'
        );
        if ($geoFenceError) {
            return $geoFenceError;
        }
*/
		
        if ($shiftDate->is_assign !== 2) {
            // Provide more detailed guidance to the client about why booking on is blocked
            if ($shiftDate->is_assign == 1) {
                return response()->json([
                    'message' => 'Shift is dispatched; you must accept the shift before booking on.'
                ], 422);
            }

            if ($shiftDate->is_assign == 3) {
                return response()->json([
                    'message' => 'Shift already booked on.'
                ], 409);
            }

            if ($shiftDate->is_assign == 4) {
                return response()->json([
                    'message' => 'Shift has already ended; you cannot book on.'
                ], 422);
            }

            return response()->json([
                'message' => 'Shift date (ID: ' . $shiftDate_id . ') not accepted. You cannot book on/off until it is accepted!',
            ], 422);
        }

        // Block booking if trainings not acknowledged
        foreach ($shiftDate->trainings as $training) {
            // Check if THIS user acknowledged THIS training
            $ack = $training->acknowledgedUsers->firstWhere('id', $user->id);

            if (!$ack || !$ack->pivot->acknowledged_at) {
                return response()->json([
                    'message' => "You must acknowledge all training/policies before booking on. Pending: {$training->title}"
                ], 422);
            }
        }

        
        $now = Carbon::now();
        $shiftStart = Carbon::parse($shiftDate->shift_date . ' ' . $shiftDate->start_time);

        // 15 minutes before the shift starts
        $bookingOpensAt = $shiftStart->copy()->subMinutes(15);

        if ($now->lt($bookingOpensAt)) {
            return response()->json([
                'message' => 'You can only book on within 15 minutes of the shift start time (' . $shiftDate->start_time . ')'
            ], 422);
        }

        // Prevent booking on after the scheduled shift end (handle overnight shifts)
        $shiftEnd = Carbon::parse($shiftDate->shift_date . ' ' . $shiftDate->end_time);
        if ($shiftEnd->lte($shiftStart)) {
            // overnight shift ending next day
            $shiftEnd->addDay();
        }

        if ($now->gt($shiftEnd)) {
            return response()->json([
                'message' => 'This shift time has already been passed out; you cannot book on.'
            ], 422);
        }


        // If there are no patrols AND no checkcalls for this shift, require booking media upload
        $hasPatrols = $shiftDate->patrols()->exists();
        $hasCheckCalls = $shiftDate->checkCalls()->exists();

        if (!$hasPatrols && !$hasCheckCalls) {
            $mediaExists = BookingMedia::where('shift_date_id', $shiftDate->id)
                ->where('type', 'book_on')
                ->where('user_id', $user->id)
                ->exists();

            if (!$mediaExists) {
                return response()->json([
                    'message' => 'This shift requires a media upload before booking on (no patrols or checkcalls).',
                    'required_action' => 'upload_media',
                    'upload_payload' => [
                        'shift_date_id' => $shiftDate->id,
                        'type' => 'book_on',
                        'media_files' => 'array of files or base64 data URLs'
                    ]
                ], 422);
            }
        }

        // Defensive state checks: prevent booking on if shift already started/ended
        if ($shiftDate->is_assign === 3) {
            $already = ShiftBooking::where('shift_id', $shiftDate->id)
                ->where('type', 'book_on')
                ->where('user_id', $user->id)
                ->exists();

            if ($already) {
                return response()->json([
                    'message' => 'You have already booked on for this shift (ShiftDate ID: ' . $shiftDate->id . ').'
                ], 409);
            }

            return response()->json([
                'message' => 'This shift has already been booked on.'
            ], 409);
        }

        if ($shiftDate->is_assign === 4) {
            return response()->json([
                'message' => 'This shift has already ended; you cannot book on.'
            ], 422);
        }

        // Update status
        $shiftDate->status = 'booked_on';
        $shiftDate->is_assign = 3; // shift started
        $shiftDate->absentee_start_time = date('H:i', strtotime($now));
        $shiftDate->save();

        // notifications
        Notification::create([
            'user_id' => 1,
            'employee_id' => null,
            'type' => 'alert',
            'title' => 'Shift booked on',
            'message' => 'Guard ' . $user->first_name . ' ' . $user->last_name . ' booked on shift (ID: ' . $shiftDate->id . ') starting at ' . $shiftDate->start_time,
            'read' => false,
            'action_url' => "/shift-dates/$shiftDate_id/view"
        ]);

        Logger::log($shiftDate, 'Booked On', ' booked on shift at ' . $shiftDate->shift->site->site_name . ' starting at ' . $shiftDate->start_time);

        // If request includes face/location data, delegate to bookOnOff to record booking with full metadata.
        if ($request->has('face_verification_result') && $request->has('location') && is_array($request->input('location'))) {
            return $this->bookOnOff($request, $shiftDate_id, 'book_on');
        }

        // Otherwise create a minimal booking record so DB accurately reflects booked_on state
        $created = ShiftBooking::create([
            'user_id' => $user->id,
            'shift_id' => $shiftDate_id,
            'type' => 'book_on',
            'timestamp' => now(),
            'face_verification_result' => 'not_required',
        ]);

        return response()->json([
            'success' => true,
            'booking_id' => $created->id,
            'message' => 'Successfully booked on.'
        ]);
    }


    public function bookOff(Request $request, $shiftDate_id)
    {
        $validated = $request->validate([
            'location.latitude' => 'required|numeric',
            'location.longitude' => 'required|numeric',
        ]);

        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return response()->json(['message' => 'No employee record linked to this user.'], 404);
        }

        // Check if booked ON for this ShiftDate
        $existingBooking = ShiftBooking::where('user_id', $user->id)
            ->where('shift_id', $shiftDate_id)
            ->where('type', 'book_on')
            ->first();

        $shiftDate = ShiftDate::find($shiftDate_id);
        if (!$shiftDate) {
            return response()->json([
                'message' => 'Trying to book off unavailable shift (ShiftDate ID: ' . $shiftDate_id . ').'
            ], 409);
        }

        if (!$existingBooking) {
            return response()->json([
                'message' => 'You have not booked on for this shift, so you cannot book off.'
            ], 400);
        }

/*
        $geoFenceError = $this->ensureWithinShiftSiteRadius(
            $shiftDate,
            $validated['location']['latitude'],
            $validated['location']['longitude'],
            'book off'
        );
        if ($geoFenceError) {
            return $geoFenceError;
        }
*/
		
        // Prevent booking off if the shift is not in a started state or already ended
        if ($shiftDate->is_assign === 4) {
            return response()->json([
                'message' => 'This shift has already been booked off.'
            ], 409);
        }

        if ($shiftDate->is_assign !== 3) {
            return response()->json([
                'message' => 'This shift is not in a started state; you cannot book off.'
            ], 422);
        }

        // Prevent duplicate book_off entries for this user and shift
        $alreadyOff = ShiftBooking::where('user_id', $user->id)
            ->where('shift_id', $shiftDate_id)
            ->where('type', 'book_off')
            ->exists();

        if ($alreadyOff) {
            return response()->json([
                'message' => 'You have already booked off for this shift.'
            ], 409);
        }

        //  Correct: update ShiftDate by ID

        // ================== TIME VALIDATION ==================
        $now = \Carbon\Carbon::now();
        $shiftEnd = \Carbon\Carbon::parse($shiftDate->shift_date . ' ' . $shiftDate->end_time);

        // Handle overnight shifts (end_time earlier than start_time)
        $shiftStart = \Carbon\Carbon::parse($shiftDate->shift_date . ' ' . $shiftDate->start_time);
        if ($shiftEnd->lte($shiftStart)) {
            $shiftEnd->addDay(); // push to next day
        }

        if ($now->lt($shiftEnd)) {
            return response()->json([
                'message' => 'You can only book off when the shift has ended (after ' . $shiftEnd->format('H:i') . ').',
            ], 422);
        }

        // If shift has no patrols AND no checkcalls, require booking media for book_off
        $hasPatrols = $shiftDate->patrols()->exists();
        $hasCheckCalls = $shiftDate->checkCalls()->exists();

        if (!$hasPatrols && !$hasCheckCalls) {
            $mediaExists = BookingMedia::where('shift_date_id', $shiftDate->id)
                ->where('type', 'book_off')
                ->where('user_id', $user->id)
                ->exists();

            if (!$mediaExists) {
                return response()->json([
                    'message' => 'This shift requires a media upload before booking off (no patrols or checkcalls).',
                    'required_action' => 'upload_media',
                    'upload_payload' => [
                        'shift_date_id' => $shiftDate->id,
                        'type' => 'book_off',
                        'media_files' => 'array of files or base64 data URLs'
                    ]
                ], 422);
            }
        }

        if ($shiftDate) {
            $shiftDate->status = 'booked_off';
            $shiftDate->is_assign = 4; //shift ended
            $timeOnly = date('H:i', strtotime($now));

            $shiftDate->absentee_end_time = $timeOnly;
            $shiftDate->save();
        }

        // Book off
        ShiftBooking::create([
            'user_id' => $user->id,
            'shift_id' => $shiftDate_id,
            'type' => 'book_off',
            'timestamp' => now(),
            'face_verification_result' => 'not_required',
        ]);

        Notification::create([
            'user_id' => 1,
            'employee_id' => null,
            'type' => 'alert',
            'title' => 'Shift booked off',
            'message' => 'Guard ' . $user->first_name . ' ' . $user->last_name . ' Booked off shift (ID: ' . $shiftDate->id . ' ending at ' . $shiftDate->end_time,
            'read' => false,
            'action_url' => "/shift-dates/$shiftDate_id/view"
        ]);

        // Remove any lingering "book_on" records for this user and shift
        ShiftBooking::where('user_id', $user->id)
            ->where('shift_id', $shiftDate_id)
            ->where('type', 'book_on')
            ->delete();

        try {
            Logger::log($shiftDate, 'Booked Off', 'Booked off at ' . $shiftDate->shift->site->site_name . ' ending at ' . $shiftDate->end_time);
        } catch (\Exception $e) {
            Log::error('Logger failed for bookOff: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Shift booked off successfully.'
        ]);
    }

    public function getPatrolRoutes($shift_id)
    {
        $shift = ShiftDate::with('patrols')->findOrFail($shift_id);
        $site = $shift->shift->site ?? null;
        $patrols = $shift->patrols->map(function ($patrol) use ($shift, $site) {
            $checkpoints = PatrolCheckPoint::where('site_id', $site?->id ?? $shift->shift->site_id)->get();
            return [
                'id' => $patrol->id,
                'name' => $patrol->name,
                'start_time' => $patrol->start_time,
                'started_at' => $patrol->started_at,
                'completed_at' => $patrol->completed_at,
                'status' => $patrol->status,
                'qr_image' => ($site && file_exists(public_path('qrForSites/site_' . $site->id . '.png'))) ? asset('qrForSites/site_' . $site->id . '.png') : null,
                'checkpoints' => $checkpoints->map(function ($checkpoint) {
                    return [
                        'id' => $checkpoint->id,
                        'name' => $checkpoint->name,
                        'location' => [
                            'latitude' => $checkpoint->latitude,
                            'longitude' => $checkpoint->longitude,
                        ],
                        'required' => (bool) $checkpoint->required,
                    ];
                }),
            ];
        });

        return response()->json(['patrols' => $patrols]);
    }

    public function patrolDetails($id)
    {
        $patrol = Patrol::with('shift')->find($id);

        if (!$patrol) {
            return response()->json(['message' => 'Patrol not found'], 404);
        }

        // Try to resolve site_id from the related ShiftDate -> Shift -> site_id
        $siteId = null;
        if ($patrol->shift) {
            // $patrol->shift is the ShiftDate relation; try to get the Shift -> site_id
            $site = $patrol->shift->shift ?? null;
            $siteId = $site->site_id ?? null;
        }

        if (!$siteId && isset($patrol->site_id)) {
            $siteId = $patrol->site_id;
        }

        $checkpoints = [];
        if ($siteId) {
            $checkpoints = PatrolCheckPoint::where('site_id', $siteId)->get();
        }

        $patrolData = [
            'id' => $patrol->id,
            'shift_id' => $patrol->shift_id,
            'name' => $patrol->name,
            'summary' => $patrol->summary ?? null,
            'total_checkpoints' => (int) ($patrol->total_checkpoints ?? 0),
            'completed_checkpoints' => (int) ($patrol->completed_checkpoints ?? 0),
            'issues_reported' => (int) ($patrol->issues_reported ?? 0),
            'completed_at' => $patrol->completed_at,
            'start_time' => $patrol->start_time,
            'status' => $patrol->status,
            'started_at' => $patrol->started_at,
            'created_at' => $patrol->created_at,
            'updated_at' => $patrol->updated_at,
            'checkpoints' => $checkpoints->map(function ($checkpoint) {
                return [
                    'id' => $checkpoint->id,
                    'name' => $checkpoint->name,
                    'qr_code' => $checkpoint->qr_code,
                    'nfc_tag' => $checkpoint->nfc_tag,
                    'location' => [
                        'latitude' => $checkpoint->latitude,
                        'longitude' => $checkpoint->longitude,
                    ],
                    'required' => (bool) $checkpoint->required,
                ];
            })->values(),
        ];

        return response()->json([
            'patrol' => $patrolData,
            'checkpoints' => $patrolData['checkpoints'],
        ]);
    }

    public function scanCode(Request $request, $patrol_id)
    {
        $request->validate([
            'scan_data' => 'required|string',
            'scan_method' => 'required|in:qr,nfc',
            'location.latitude' => 'required|numeric',
            'location.longitude' => 'required|numeric',
            'media_files' => 'array',
            'notes' => 'nullable|string',
            'issues_found' => 'nullable|string',
        ]);

        // Find patrol
        $patrol = Patrol::find($patrol_id);
        if (!$patrol) {
            return response()->json(['message' => 'Patrol not found.'], 404);
        }

        // Ensure authenticated user is the assigned guard for this patrol
        $shiftDate = ShiftDate::find($patrol->shift_id);
        if (!$shiftDate || $shiftDate->staff_id !== Auth::id()) {
            return response()->json(['message' => 'You are not assigned to this patrol.'], 403);
        }

        // Get the site from the shift
        $shift = $shiftDate->shift;
        if (!$shift) {
            return response()->json(['message' => 'Shift not found for this patrol.'], 404);
        }

        $site = $shift->site;
        if (!$site) {
            return response()->json(['message' => 'Site not found for this shift.'], 404);
        }

        $scanData = trim($request->scan_data);
        $method = $request->scan_method;

        // Verify scan data matches site's QR code or NFC tag
        if ($method === 'qr') {
            // Check if QR code image exists for this site
            if (!$site->has_qr || !file_exists(public_path('qrForSites/site_' . $site->id . '.png'))) {
                return response()->json(['message' => 'This site does not have a QR code configured.'], 422);
            }
            
            // The QR code content is the site URL
            $expectedQrContent = config('app.url') . '/sites/' . $site->id;
            if ($scanData !== $expectedQrContent) {
                return response()->json(['message' => 'Invalid QR code. Please scan the correct QR code for this site.'], 422);
            }
        } elseif ($method === 'nfc') {
            $matched = false;

            // 1. Check against site's NFC tag (DB — primary, single tag per site)
            if (!empty($site->nfc_tag) && trim($site->nfc_tag) === $scanData) {
                $matched = true;
            }

            // 2. Backward-compatibility: check legacy filesystem tags
            if (!$matched) {
                $nfcDir = public_path('nfcForSites');
                if (file_exists($nfcDir)) {
                    $pattern = $nfcDir . DIRECTORY_SEPARATOR . 'site_' . $site->id . '_*.txt';
                    foreach (glob($pattern) as $path) {
                        try {
                            $tag = trim(file_get_contents($path));
                            if ($tag === $scanData) {
                                $matched = true;
                                break;
                            }
                        } catch (\Exception $e) {
                            // ignore
                        }
                    }
                }
            }

            // 3. If still no match, check each checkpoint's NFC tag.
            //    The scanned data must match a checkpoint NFC tag AND the guard must
            //    be within proximity of that checkpoint's coordinates.
            if (!$matched) {
                $guardLat = (float) $request->location['latitude'];
                $guardLng = (float) $request->location['longitude'];
                // Use the site's configured radius (minimum 50 m) for checkpoint proximity.
                $proximityRadius = max(50, (float) ($site->radius ?? 50));

                $geoService = app(GeoService::class);
                $checkpoints = $site->checkpoints;

                foreach ($checkpoints as $cp) {
                    if (empty($cp->nfc_tag) || trim($cp->nfc_tag) !== $scanData) {
                        continue;
                    }

                    // Accept if checkpoint has no stored coordinates (cannot validate proximity)
                    if ($cp->latitude === null || $cp->longitude === null) {
                        $matched = true;
                        break;
                    }

                    $dist = $geoService->distanceInMeters(
                        $guardLat, $guardLng,
                        (float) $cp->latitude, (float) $cp->longitude
                    );

                    if ($dist <= $proximityRadius) {
                        $matched = true;
                        break;
                    }
                }
            }

            if (!$matched) {
                return response()->json(['message' => 'Invalid NFC tag. Please scan the correct NFC tag for this site.'], 422);
            }
        }

        // Create scan tied to the patrol (checkpoint matching verified)
        $scan = CheckpointScan::create([
            'patrol_id' => $patrol->id,
            'user_id' => Auth::id(),
            'scan_data' => $scanData,
            'scan_method' => $method,
            'latitude' => $request->location['latitude'],
            'longitude' => $request->location['longitude'],
            'notes' => $request->notes,
            'issues_found' => $request->issues_found,
            'timestamp' => now(),
        ]);

        // Save media files if provided
        if ($request->has('media_files')) {
            foreach ($request->media_files as $base64) {
                $filename = 'scan_' . uniqid() . '.jpg';
                $dir = public_path('patrols/media');
                if (!file_exists($dir)) mkdir($dir, 0755, true);
                file_put_contents($dir . '/' . $filename, base64_decode($base64));
                $scan->media()->create(['file_path' => "patrols/media/{$filename}"]);
            }
        }

        return response()->json(['message' => 'Scan recorded for patrol', 'patrol_id' => $patrol->id]);
    }

    public function startPatrol(Request $request, $patrol_id)
    {
        $validated = $request->validate([
            'location.latitude' => 'required|numeric',
            'location.longitude' => 'required|numeric',
        ]);

        $patrol = Patrol::findOrFail($patrol_id);

        $now = Carbon::now();
        $patrolStart = Carbon::parse($patrol->start_time);

        // Guard cannot start before scheduled time
        if ($now->lt($patrolStart)) {
            return response()->json([
                'message' => 'You cannot start the patrol before its scheduled start time at ' . $patrolStart->format('H:i')
            ], 403);
        }

        // If this specific patrol is already completed, block
        if ($patrol->status === 'completed') {
            return response()->json([
                'message' => 'Patrol has already been completed.'
            ], 403);
        }

        // If this specific patrol is already in progress, block (can't start same patrol twice)
        if ($patrol->status === 'in_progress') {
            return response()->json([
                'message' => 'Patrol is already in progress.'
            ], 403);
        }

        if($patrol->status == 'missed'){
            return response()->json(['message' => 'This Patrol has already been missed, You cannot submit unless an Admin gave permission to.'], 422);
        }

        $shiftDateForGeo = ShiftDate::find($patrol->shift_id);
        if (!$shiftDateForGeo) {
            return response()->json(['message' => 'Shift not found for this patrol.'], 404);
        }

/*
        $geoFenceError = $this->ensureWithinShiftSiteRadius(
            $shiftDateForGeo,
            $validated['location']['latitude'],
            $validated['location']['longitude'],
            'start patrol'
        );
        if ($geoFenceError) {
            return $geoFenceError;
        }
*/
		
        // If the guard currently has a different patrol in progress, mark that one completed
        $staffShiftIds = ShiftDate::where('staff_id', Auth::id())->pluck('id')->toArray();
        $other = Patrol::where('status', 'in_progress')
            ->where('id', '!=', $patrol->id)
            ->whereIn('shift_id', $staffShiftIds)
            ->first();

        if ($other) {
            $other->update([
                'status' => 'completed',
                'completed_at' => $now,
            ]);

            // notify admin and the guard about the forced completion
            $otherShiftDate = ShiftDate::find($other->shift_id);
            $otherUser = $otherShiftDate ? User::find($otherShiftDate->staff_id) : null;

            Notification::create([
                'user_id' => 1,
                'employee_id' => null,
                'type' => 'alert',
                'title' => 'Patrol auto-completed',
                'message' => 'Guard ' . ($otherUser?->first_name ?? 'Guard') . ' ' . ($otherUser?->last_name ?? '') . ' patrol was auto-completed at ' . $now,
                'read' => false,
                'action_url' => "/shift-dates/{$other->shift_id}/view"
            ]);

            if ($otherShiftDate && $otherUser) {

                send_push_notification(
                    $otherUser->id,
                    'Patrol auto-completed',
                    'Your previous patrol was marked completed at ' . $now,
                    ['type' => 'shift', 'shiftId' => $other->shift_id]
                );
            }
        }

        // Start requested patrol
        // If more than 15 minutes have passed since scheduled start, mark missed
        try {
            $gracePeriodEnd = Carbon::parse($patrol->start_time)->addMinutes(15);
            if ($now->gt($gracePeriodEnd)) {
                $patrol->status = 'missed';
                $patrol->save();

                // notify admin
                Notification::create([
                    'user_id' => 1,
                    'employee_id' => null,
                    'type' => 'alert',
                    'title' => 'Patrol missed',
                    'message' => 'Patrol ID ' . $patrol->id . ' was marked as missed (started after 15 minute grace period).',
                    'read' => false,
                    'action_url' => "/shift-dates/{$patrol->shift_id}/view"
                ]);

                return response()->json([
                    'message' => 'Patrol missed: more than 15 minutes have passed since scheduled start.'
                ], 422);
            }
        } catch (\Exception $e) {
            // If any parsing error occurs, continue to attempt to start the patrol
            Log::warning('Failed to evaluate patrol grace period: ' . $e->getMessage());
        }

        $patrol->update([
            'status' => 'in_progress',
            'started_at' => $now
        ]);

        $shiftDate = ShiftDate::find($patrol->shift_id);
        $user = User::find($shiftDate->staff_id);
        Notification::create([
            'user_id' => 1,
            'employee_id' => null,
            'type' => 'alert',
            'title' => 'Patrol started',
            'message' => 'Guard ' . $user->first_name . ' ' . $user->last_name . ' Started his patrol at ' . $now,
            'read' => false,
            'action_url' => "/shift-dates/$patrol->shift_id/view"
        ]);

        // Notification to guard removed - only admin notification kept

        return response()->json([
            'message' => 'Patrol started at ' . $now->format('H:i')
        ]);
    }

    public function completePatrol(Request $request, $patrol_id)
    {
        $patrol = Patrol::with('shift')->findOrFail($patrol_id);
        $now = Carbon::now();
        $patrolStart = Carbon::parse($patrol->start_time);

        // Guard can complete patrol only up to 50 mins after start
        if ($now->gt($patrolStart->copy()->addMinutes(50))) {
            $patrol->status = 'completed';
            $patrol->approval_status = 'pending';
            $patrol->save();
            return response()->json([
                'message' => 'Patrol completion time exceeded. However it is considered as completed.'
            ], 403);
        }

        $request->validate([
            'summary' => 'required|string',
            'total_checkpoints' => 'required|integer',
            'completed_checkpoints' => 'required|integer',
            'issues_reported' => 'required|integer',
            'location.latitude' => 'required|numeric',
            'location.longitude' => 'required|numeric',
            'location.address' => 'nullable|string',
        ]);

        $shiftDateForGeo = ShiftDate::find($patrol->shift_id);
        if (!$shiftDateForGeo) {
            return response()->json(['message' => 'Shift not found for this patrol.'], 404);
        }

        if ($patrol->shift->shift->enforce_picture_check) {
            $hasMedia = PatrolMedia::where('patrol_id', $patrol->id)->exists();
            if (!$hasMedia) {
                return response()->json(['message' => 'This site requires a picture check for patrols. Please upload patrol media before completing.'], 422);
            }
        }

 /*      
        $geoFenceError = $this->ensureWithinShiftSiteRadius(
            $shiftDateForGeo,
            $request->input('location.latitude'),
            $request->input('location.longitude'),
            'complete patrol'
        );
        if ($geoFenceError) {
            return $geoFenceError;
        }
            
*/
        $patrol->update([
            'summary' => $request->summary,
            'total_checkpoints' => $request->total_checkpoints,
            'completed_checkpoints' => $request->completed_checkpoints,
            'issues_reported' => $request->issues_reported,
            'completed_at' => $now,
            'status' => 'completed',
            'approval_status' => 'pending',
        ]);

        $shiftDate = ShiftDate::find($patrol->shift_id);
        $user = User::find($shiftDate->staff_id);
        Notification::create([
            'user_id' => 1,
            'employee_id' => null,
            'type' => 'alert',
            'title' => 'Patrol Completed',
            'message' => 'Guard ' . $user->first_name . ' ' . $user->last_name . ' completed his patrol at ' . $now,
            'read' => false,
            'action_url' => "/shift-dates/$patrol->shift_id/view"
        ]);

        // Notification to guard removed - only admin notification kept

        return response()->json(['message' => 'Patrol marked as completed']);
    }

    // Upload media files for a patrol (accepts UploadedFile instances or base64 data URIs)
public function uploadPatrolMedia(Request $request, $patrol_id)
{
    $request->validate([
        'media' => 'nullable|array',
        'media.*.media_file' => 'nullable',
        'media.*.timestamp' => 'nullable|string',
        'location.latitude' => 'required|numeric',
        'location.longitude' => 'required|numeric',
        'notes' => 'nullable|string',
    ]);

    Log::info('Incoming patrol media request', [
        'method' => $request->method(),
        'url' => $request->fullUrl(),
        'headers' => $request->headers->all(),
        'body' => $request->all(),
        'ip' => $request->ip(),
    ]);

    $patrol = Patrol::findOrFail($patrol_id);

    // Ensure the authenticated user is the assigned staff for this patrol's shift
    $shiftDate = ShiftDate::find($patrol->shift_id);

    if (!$shiftDate || $shiftDate->staff_id !== Auth::id()) {
        return response()->json([
            'message' => 'You are not assigned to this patrol.'
        ], 403);
    }

    if ($patrol->status != 'in_progress') {
        return response()->json([
            'message' => 'The patrol is not in progress at the moment, you cannot submit media!'
        ]);
    }

    $user = Auth::user();

    // Accept location sent as nested array or flat keys
    $lat = $request->input('location.latitude');
    $lng = $request->input('location.longitude');

    if (is_null($lat) || is_null($lng)) {

        $all = $request->all();

        if (isset($all['location']) && is_array($all['location'])) {

            $lat = $lat ?? ($all['location']['latitude'] ?? $all['location']['lat'] ?? null);

            $lng = $lng ?? ($all['location']['longitude'] ?? $all['location']['lng'] ?? null);

        } else {

            $lat = $lat ?? ($all['location.latitude'] ?? $all['latitude'] ?? $all['lat'] ?? null);

            $lng = $lng ?? ($all['location.longitude'] ?? $all['longitude'] ?? $all['lng'] ?? null);
        }
    }

    /*
    $geoFenceError = $this->ensureWithinShiftSiteRadius(
        $shiftDate,
        $lat,
        $lng,
        'submit patrol media'
    );

    if ($geoFenceError) {
        return $geoFenceError;
    }
    */

    $geoService = new GeoService();

    $resolvedAddress = null;

    try {

        if ($lat && $lng) {
            $resolvedAddress = $geoService->getAddressFromCoordinates($lat, $lng);
        }

    } catch (\Exception $e) {

        Log::warning('GeoService failed: ' . $e->getMessage());
    }

    $locationForStamp = is_array($resolvedAddress)
        ? $resolvedAddress
        : [
            'formatted_address' => (
                $resolvedAddress
                ?? ($shiftDate->shift->site->address ?? 'N/A')
            )
        ];

    $userName = trim(
        ($user->first_name ?? '') . ' ' . ($user->last_name ?? '')
    );

    // Base timestamp data
    $baseTimestampData = [
        'employee' => $userName,
        'latitude' => $lat,
        'longitude' => $lng,
        'site' => $shiftDate->shift->site->site_name ?? 'N/A',
        'location' => $locationForStamp,
    ];

    // Media array from request
$items = [];

// New app format
if ($request->has('media')) {
    $items = $request->all()['media'] ?? [];
}

// Old/mobile multipart format
elseif ($request->hasFile('media_files')) {

    foreach ($request->file('media_files') as $file) {

        $items[] = [
            'media_file' => $file,
            'timestamp' => now()->toISOString(),
        ];
    }
}
    if (empty($items)) {

        Log::warning(
            'No media items found in request for patrol ' . $patrol->id,
            ['request_keys' => array_keys($request->all())]
        );
    }

    @set_time_limit(0);

    $created = [];

    foreach ($items as $index => $mediaItem) {

        $file = $mediaItem['media_file'] ?? null;

        $captureTimestamp = $mediaItem['timestamp'] ?? null;

        if (!$file) {
            continue;
        }

        $filePath = null;

        $originalName = null;

        /*
        |--------------------------------------------------------------------------
        | Uploaded File
        |--------------------------------------------------------------------------
        */

        if ($file instanceof \Illuminate\Http\UploadedFile) {

            $originalName = $file->getClientOriginalName();

            $extension = $file->getClientOriginalExtension() ?: 'bin';

            $filename = time() . '_' . uniqid() . '.' . $extension;

            $dir = public_path('patrols/media');

            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }

            $file->move($dir, $filename);

            $filePath = 'patrols/media/' . $filename;
        }

        /*
        |--------------------------------------------------------------------------
        | Base64 File
        |--------------------------------------------------------------------------
        */

        elseif (is_string($file) && preg_match('/^data:/', $file)) {

            $fileData = preg_replace(
                '/^data:\w+\/\w+;base64,/',
                '',
                $file
            );

            $extension = 'png';

            if (preg_match('/^data:(\w+\/\w+);base64,/', $file, $matches)) {

                $mime = $matches[1];

                $extMap = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'video/mp4' => 'mp4',
                    'video/quicktime' => 'mov',
                    'application/pdf' => 'pdf',
                    'application/msword' => 'doc',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                ];

                $extension = $extMap[$mime] ?? 'bin';
            }

            $dir = public_path('patrols/media');

            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }

            $filename = time() . '_' . uniqid() . '.' . $extension;

            file_put_contents(
                $dir . '/' . $filename,
                base64_decode($fileData)
            );

            $filePath = 'patrols/media/' . $filename;
        }

        else {

            Log::warning(
                'Skipping unsupported media item type for patrol ' . $patrol->id
            );

            continue;
        }

        if (!$filePath) {
            continue;
        }

        $fullPath = public_path($filePath);

        $fileType = strtolower(
            pathinfo($fullPath, PATHINFO_EXTENSION)
        );

        /*
        |--------------------------------------------------------------------------
        | Timestamp Data Per Media Item
        |--------------------------------------------------------------------------
        */

        $timestampData = $baseTimestampData;

        if ($captureTimestamp) {

            $captureTime = $this->parseUKTimestamp($captureTimestamp);

            $timestampData['time'] =
                $captureTime->format('d/m/Y H:i:s');

            $timestampData['capture_time_unix'] =
                $captureTime->timestamp;

        } else {

            $timestampData['time'] =
                Carbon::now('Europe/London')
                    ->format('d/m/Y H:i:s');
        }

        /*
        |--------------------------------------------------------------------------
        | Process Media
        |--------------------------------------------------------------------------
        */

        if (in_array($fileType, ['mp4', 'mov', 'avi', 'mkv'])) {

            $this->processVideo($fullPath, $timestampData);

        } else {

            $compressedPath = $this->compressFile(
                $fullPath,
                $fileType
            );

            if ($compressedPath && $compressedPath != $fullPath) {

                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }

                rename($compressedPath, $fullPath);

                @chmod($fullPath, 0644);
            }

            switch ($fileType) {

                case 'jpg':
                case 'jpeg':
                case 'png':

                    $this->addWatermarkToImage(
                        $fullPath,
                        $timestampData
                    );

                    break;

                case 'pdf':

                    $this->addTimestampToPdf(
                        $fullPath,
                        $timestampData
                    );

                    break;

                case 'doc':
                case 'docx':

                    $this->addTimestampToDocument(
                        $fullPath,
                        $timestampData
                    );

                    break;

                default:

                    $this->createMetadataFile(
                        $fullPath,
                        $timestampData
                    );

                    break;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Save DB Record
        |--------------------------------------------------------------------------
        */

        try {

            $pm = PatrolMedia::create([
                'patrol_id' => $patrol->id,
                'file_path' => $filePath,
                'file_type' => $fileType,
                'original_name' => $originalName ?? "media_" . ($index + 1),
                'file_size' => filesize($fullPath),
                'captured_at' => isset($timestampData['capture_time_unix'])
                    ? Carbon::createFromTimestamp(
                        $timestampData['capture_time_unix']
                    )
                    : Carbon::now(),
            ]);

            $created[] = [
                'id' => $pm->id,
                'file_path' => $filePath,
                'url' => asset($filePath),
            ];

        } catch (\Exception $e) {

            Log::error(
                'Failed to record patrol media: ' . $e->getMessage()
            );
        }
    }

    // Notify admin
    try {

        Notification::create([
            'user_id' => 1,
            'employee_id' => null,
            'type' => 'alert',
            'title' => 'Patrol media uploaded',
            'message' => $userName .
                ' uploaded media for patrol (' .
                $patrol->name .
                ' )',
            'read' => false,
            'action_url' => "/shift-dates/{$patrol->shift_id}/view"
        ]);

    } catch (\Exception $e) {

        Log::error(
            'Patrol media notification failed: ' . $e->getMessage()
        );
    }

    return response()->json([
        'message' => 'Patrol media uploaded successfully',
        'media' => $created,
    ]);
}


    // Compression and timestamp/watermark helpers (copied/adapted from CheckCallController)
    private function compressFile($filePath, $fileType)
    {
        if (!file_exists($filePath)) return $filePath;
        $originalSize = filesize($filePath);
        $videoSizeLimit = 5 * 1024 * 1024; // 5MB — only skip compression for small videos

        switch ($fileType) {
            case 'jpg':
            case 'jpeg':
                return $this->compressImage($filePath, 60, 1920); // always compress images
            case 'png':
                return $this->compressImage($filePath, 8, 1920);
            case 'mp4':
            case 'mov':
            case 'avi':
                if ($originalSize <= $videoSizeLimit) return $filePath; // small videos need no compression
                return $this->compressVideo($filePath);
            case 'pdf':
                return $this->compressPdf($filePath);
            default:
                return $filePath; // No compression for other types
        }
    }

    private function compressImage($filePath, $quality, $maxWidth = 1920)
    {
        $img = null;
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($ext === 'jpg' || $ext === 'jpeg') {
            $img = @imagecreatefromjpeg($filePath);
        } elseif ($ext === 'png') {
            $img = @imagecreatefrompng($filePath);
        } else {
            return $filePath;
        }

        if (!$img) return $filePath;

        // Get original dimensions
        $originalWidth = imagesx($img);
        $originalHeight = imagesy($img);

        // Calculate new dimensions if needed
        if ($originalWidth > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = intval($originalHeight * $maxWidth / $originalWidth);
        } else {
            $newWidth = $originalWidth;
            $newHeight = $originalHeight;
        }

        // Create new image with new dimensions
        $newImg = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG
        if ($ext === 'png') {
            imagealphablending($newImg, false);
            imagesavealpha($newImg, true);
            $transparent = imagecolorallocatealpha($newImg, 255, 255, 255, 127);
            imagefilledrectangle($newImg, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Resize image
        imagecopyresampled($newImg, $img, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

        // Create compressed file path
        $compressedPath = $filePath . '.compressed.' . $ext;

        // Save compressed image
        if ($ext === 'jpg' || $ext === 'jpeg') {
            imagejpeg($newImg, $compressedPath, $quality);
        } elseif ($ext === 'png') {
            imagepng($newImg, $compressedPath, $quality); // PNG quality is 0-9
        }

        // Free memory
        imagedestroy($img);
        imagedestroy($newImg);

        // Check if compression was successful and reduced size
        if (file_exists($compressedPath) && filesize($compressedPath) < filesize($filePath)) {
            return $compressedPath;
        } else {
            // If compression failed or didn't reduce size, use original
            if (file_exists($compressedPath)) {
                unlink($compressedPath);
            }
            return $filePath;
        }
    }

    private function compressVideo($filePath)
    {
        // Detect system ffmpeg dynamically (works on Linux/macOS; avoids 'which' brittleness)
        $ffmpegBin = null;
        foreach (['/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg', '/bin/ffmpeg'] as $candidate) {
            if (file_exists($candidate) && is_executable($candidate)) {
                $ffmpegBin = $candidate;
                break;
            }
        }
        if (!$ffmpegBin && function_exists('shell_exec')) {
            $found = trim((string) @shell_exec('which ffmpeg 2>/dev/null'));
            if ($found && is_executable($found)) $ffmpegBin = $found;
        }
        if (!$ffmpegBin) {
            Log::info('compressVideo: ffmpeg not found, skipping compression', ['path' => $filePath]);
            return $filePath;
        }

        $originalSize = filesize($filePath);
        $targetBitrate = '1000k';
        if ($originalSize > 50 * 1024 * 1024) {
            $targetBitrate = '500k';
        } elseif ($originalSize > 20 * 1024 * 1024) {
            $targetBitrate = '800k';
        }

        $compressedPath = $filePath . '.compressed.mp4';
        $cmd = escapeshellcmd($ffmpegBin)
            . ' -i ' . escapeshellarg($filePath)
            . ' -c:v libx264 -crf 28 -preset medium -b:v ' . $targetBitrate
            . ' -c:a aac -b:a 64k -movflags +faststart '
            . escapeshellarg($compressedPath) . ' -y';

        $out = [];
        $ret = 0;
        exec($cmd . ' 2>/dev/null', $out, $ret);

        if ($ret === 0 && file_exists($compressedPath) && filesize($compressedPath) < $originalSize) {
            return $compressedPath;
        }
        if (file_exists($compressedPath)) @unlink($compressedPath);
        return $filePath;
    }

    private function compressPdf($filePath)
    {
        // Check if Ghostscript is available for PDF compression
        if (!function_exists('shell_exec') || !shell_exec('which gs')) {
            return $filePath;
        }

        $compressedPath = $filePath . '.compressed.pdf';
        $escapedInput = escapeshellarg($filePath);
        $escapedOutput = escapeshellarg($compressedPath);

        // Ghostscript command for PDF compression
        $command = "gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 " .
            "-dPDFSETTINGS=/screen -dNOPAUSE -dQUIET -dBATCH " .
            "-sOutputFile={$escapedOutput} {$escapedInput} 2>/dev/null";

        shell_exec($command);

        if (file_exists($compressedPath) && filesize($compressedPath) < filesize($filePath)) {
            return $compressedPath;
        } else {
            if (file_exists($compressedPath)) {
                unlink($compressedPath);
            }
            return $filePath;
        }
    }

  
    // Existing timestamp methods (keep these from previous implementation)
    private function addWatermarkToImage($imagePath, $timestampData)
{
    $img = null;
    $ext = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));

    if ($ext === 'jpg' || $ext === 'jpeg') {
        $img = @imagecreatefromjpeg($imagePath);
    } elseif ($ext === 'png') {
        $img = @imagecreatefrompng($imagePath);
    }

    if (!$img) return;

    $white = imagecolorallocate($img, 255, 255, 255);
    $blackTrans = imagecolorallocatealpha($img, 0, 0, 0, 80);

    // Format location text
    $locationText = 'Unknown';
    if (is_array($timestampData['location'] ?? null)) {
        $locationText = $timestampData['location']['formatted_address'] ?? json_encode($timestampData['location']);
    } else {
        $locationText = $timestampData['location'] ?? 'Unknown';
    }

    // Build watermark text with UK timestamp
    // The 'time' field should already be in UK format (d/m/Y H:i:s) from parseUKTimestamp()
    $text = "Time: " . ($timestampData['time'] ?? '') .
        "\nEmployee: " . ($timestampData['employee'] ?? '') .
        "\nLat: " . ($timestampData['latitude'] ?? '') . "  " .
        "Lng: " . ($timestampData['longitude'] ?? '') .
        "\nSite: " . ($timestampData['site'] ?? '') .
        "\nLocation: " . $locationText;

    $lines = explode("\n", $text);
    $fontPath = public_path('fonts/Arial.ttf');

    if (!file_exists($fontPath)) {
        // Fallback to GD font if TTF not available
        $this->addWatermarkWithGDFont($img, $text, $imagePath, $ext);
        return;
    }

    $imgWidth = imagesx($img);
    $imgHeight = imagesy($img);

    $padding = max(12, intval($imgWidth * 0.02));
    $maxRectWidth = max(100, intval($imgWidth * 0.9) - 2 * $padding);

    // Start font size relative to image width
    $fontSize = max(14, intval($imgWidth * 0.03));
    $minFontSize = 10;

    // Helper: split a very long 'word' into chunks that fit
    $splitLongWord = function ($word, $fontSizeLocal) use ($fontPath, $maxRectWidth) {
        $pieces = [];
        $len = mb_strlen($word);
        $start = 0;
        while ($start < $len) {
            $part = '';
            // Build char-by-char until it no longer fits
            for ($i = $start; $i < $len; $i++) {
                $test = $part . mb_substr($word, $i, 1);
                $bb = imagettfbbox($fontSizeLocal, 0, $fontPath, $test);
                $w = abs($bb[4] - $bb[0]);
                if ($w > $maxRectWidth) break;
                $part = $test;
            }
            if ($part === '') {
                // single character too wide? force at least one char
                $part = mb_substr($word, $start, 1);
                $start++;
            } else {
                $start += mb_strlen($part);
            }
            $pieces[] = $part;
        }
        return $pieces;
    };

    // Wrap lines and reduce font size if the block is too tall
    while (true) {
        $lineHeight = max(12, intval($fontSize * 1.18));
        $wrapped = [];

        foreach ($lines as $line) {
            $words = preg_split('/\s+/', trim($line));
            $current = '';
            foreach ($words as $w) {
                $test = $current === '' ? $w : $current . ' ' . $w;
                $bb = imagettfbbox($fontSize, 0, $fontPath, $test);
                $wWidth = abs($bb[4] - $bb[0]);
                if ($wWidth > $maxRectWidth) {
                    if ($current === '') {
                        // single very long word -> split it
                        $pieces = $splitLongWord($w, $fontSize);
                        foreach ($pieces as $p) $wrapped[] = $p;
                        $current = '';
                    } else {
                        $wrapped[] = $current;
                        $current = $w;
                    }
                } else {
                    $current = $test;
                }
            }
            if (strlen($current)) $wrapped[] = $current;
        }

        $rectWidth = 0;
        foreach ($wrapped as $rl) {
            $bb = imagettfbbox($fontSize, 0, $fontPath, $rl);
            $w = abs($bb[4] - $bb[0]);
            if ($w > $rectWidth) $rectWidth = $w;
        }
        $rectWidth = min($rectWidth, $maxRectWidth);
        $rectHeight = count($wrapped) * $lineHeight + 2 * $padding;

        // If the watermark block uses too much vertical space, reduce font
        if ($rectHeight > intval($imgHeight * 0.5) && $fontSize > $minFontSize) {
            $fontSize = max($minFontSize, $fontSize - 2);
            continue; // recalc wrapping with smaller font
        }
        break;
    }

    // Draw background rectangle and text
    imagefilledrectangle($img, 0, 0, $rectWidth + 2 * $padding, $rectHeight, $blackTrans);

    $x = $padding;
    $y = $padding + $fontSize;
    foreach ($wrapped as $rl) {
        imagettftext($img, $fontSize, 0, $x, $y, $white, $fontPath, $rl);
        $y += $lineHeight;
    }

    if ($ext === 'jpg' || $ext === 'jpeg') {
        imagejpeg($img, $imagePath, 90);
    } else {
        imagepng($img, $imagePath);
    }

    imagedestroy($img);
}


    private function addWatermarkWithGDFont($img, $text, $imagePath, $ext)
    {
        // Simple fallback: draw text block at top-left using built-in font
        $lines = explode("\n", $text);
        $x = 5;
        $y = 5;
        $font = 3; // built-in font size
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        // Draw semi-transparent rectangle background
        $rectHeight = count($lines) * 12 + 10;
        imagefilledrectangle($img, 0, 0, imagesx($img), $rectHeight, $black);
        $y += 10;
        foreach ($lines as $line) {
            imagestring($img, $font, $x, $y, $line, $white);
            $y += 12;
        }
        if ($ext === 'jpg' || $ext === 'jpeg') {
            imagejpeg($img, $imagePath, 90);
        } else {
            imagepng($img, $imagePath);
        }
    }

    private function addTimestampToVideo($videoPath, $timestampData)
    {
        // Detect system ffmpeg dynamically (the old bundled path is not reliable on all servers)
        $ffmpegBin = null;
        foreach (['/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg', '/bin/ffmpeg'] as $candidate) {
            if (file_exists($candidate) && is_executable($candidate)) {
                $ffmpegBin = $candidate;
                break;
            }
        }
        if (!$ffmpegBin && function_exists('shell_exec')) {
            $found = trim((string) @shell_exec('which ffmpeg 2>/dev/null'));
            if ($found && is_executable($found)) $ffmpegBin = $found;
        }
        if (!$ffmpegBin) {
            Log::info('addTimestampToVideo: ffmpeg not found, skipping video watermark', ['path' => $videoPath]);
            return;
        }

        // Build overlay text
        $locationText = 'Unknown';
        if (is_array($timestampData['location'] ?? null)) {
            $locationText = $timestampData['location']['formatted_address'] ?? json_encode($timestampData['location']);
        } else {
            $locationText = $timestampData['location'] ?? 'Unknown';
        }
        $text = "Time: " . ($timestampData['time'] ?? '') .
            "\nEmployee: " . ($timestampData['employee'] ?? '') .
            "\nLat: " . ($timestampData['latitude'] ?? '') . "  Lng: " . ($timestampData['longitude'] ?? '') .
            "\nSite: " . ($timestampData['site'] ?? '') .
            "\nLocation: " . $locationText;

        // Generate overlay PNG using GD (same font as image watermarks)
        $fontPath = public_path('fonts/Arial.ttf');
        $fontSize = 14;
        $im = imagecreatetruecolor(500, 120);
        imagesavealpha($im, true);
        $bgColor = imagecolorallocatealpha($im, 0, 0, 0, 60);
        imagefill($im, 0, 0, $bgColor);
        $white = imagecolorallocate($im, 255, 255, 255);
        if (file_exists($fontPath)) {
            $y = 18;
            foreach (explode("\n", $text) as $line) {
                imagettftext($im, $fontSize, 0, 8, $y, $white, $fontPath, $line);
                $y += $fontSize + 4;
            }
        } else {
            // Fallback: GD built-in font (no TTF required)
            $y = 5;
            foreach (explode("\n", $text) as $line) {
                imagestring($im, 3, 5, $y, $line, $white);
                $y += 14;
            }
        }
        // Use a unique temp filename to avoid race conditions
        $textImage = sys_get_temp_dir() . '/patrol_overlay_' . uniqid() . '.png';
        imagepng($im, $textImage);
        imagedestroy($im);

        $outputPath = $videoPath . '.wm_' . uniqid() . '.mp4';
        $cmd = escapeshellcmd($ffmpegBin)
            . ' -i ' . escapeshellarg($videoPath)
            . ' -i ' . escapeshellarg($textImage)
            . ' -filter_complex "overlay=10:10"'
            . ' -c:a copy '
            . escapeshellarg($outputPath) . ' -y';

        $out = [];
        $ret = 0;
        exec($cmd . ' 2>&1', $out, $ret);
        @unlink($textImage);

        if ($ret === 0 && file_exists($outputPath)) {
            @unlink($videoPath);
            if (!@rename($outputPath, $videoPath)) {
                @copy($outputPath, $videoPath);
                @unlink($outputPath);
            }
            @chmod($videoPath, 0644);
        } else {
            Log::warning('addTimestampToVideo: ffmpeg watermark failed', [
                'path'   => $videoPath,
                'return' => $ret,
                'output' => implode("\n", $out),
            ]);
            if (file_exists($outputPath)) @unlink($outputPath);
        }
    }

    /**
     * Compress and watermark a video in a single ffmpeg pass.
     * Uses -preset fast for significantly faster encoding than -preset medium.
     */
    private function processVideo(string $filePath, array $timestampData): void
    {
        // Enhanced ffmpeg detection including Windows paths
        $ffmpegBin = null;
        $candidates = [
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            '/bin/ffmpeg',
            'C:/ffmpeg/bin/ffmpeg.exe',
            'C:/Program Files/ffmpeg/bin/ffmpeg.exe',
            'ffmpeg' // Will use PATH
        ];
        
        foreach ($candidates as $candidate) {
            if (file_exists($candidate) && is_executable($candidate)) {
                $ffmpegBin = $candidate;
                break;
            }
        }
        
        if (!$ffmpegBin && function_exists('shell_exec')) {
            // Try Unix which command
            $found = trim((string) @shell_exec('which ffmpeg 2>/dev/null'));
            if ($found && is_executable($found)) {
                $ffmpegBin = $found;
            } else {
                // Try Windows where command
                $found = trim((string) @shell_exec('where ffmpeg 2>NUL'));
                if ($found) {
                    $lines = explode("\n", $found);
                    $ffmpegBin = trim($lines[0]);
                }
            }
        }
        
        if (!$ffmpegBin) {
            // Try just 'ffmpeg' - might be in PATH
            $ffmpegBin = 'ffmpeg';
        }

        $originalSize = @filesize($filePath) ?: 0;
        
        // AGGRESSIVE compression settings - compress regardless of size
        // Higher CRF = more compression (range: 0-51, 23 is default, we use 30-32 for aggressive compression)
        $crf = 30;
        $targetBitrate = '800k';
        
        if ($originalSize > 100 * 1024 * 1024) {
            // Very large files (>100MB): maximum compression
            $crf = 32;
            $targetBitrate = '400k';
        } elseif ($originalSize > 50 * 1024 * 1024) {
            // Large files (>50MB): high compression
            $crf = 31;
            $targetBitrate = '500k';
        } elseif ($originalSize > 20 * 1024 * 1024) {
            // Medium files (>20MB): moderate-high compression
            $crf = 30;
            $targetBitrate = '600k';
        }
        // Small files still get compressed with CRF 30 and 800k bitrate

        $locationText = 'Unknown';
        if (is_array($timestampData['location'] ?? null)) {
            $locationText = $timestampData['location']['formatted_address'] ?? $timestampData['location'];
        } else {
            $locationText = (string) ($timestampData['location'] ?? 'Unknown');
        }
        $text = "Time: " . ($timestampData['time'] ?? '') .
            "\nEmployee: " . ($timestampData['employee'] ?? '') .
            "\nLat: " . ($timestampData['latitude'] ?? '') . "  Lng: " . ($timestampData['longitude'] ?? '') .
            "\nSite: " . ($timestampData['site'] ?? '') .
            "\nLocation: " . $locationText;

        $fontPath = public_path('fonts/Arial.ttf');
        $im = imagecreatetruecolor(500, 120);
        imagesavealpha($im, true);
        imagefill($im, 0, 0, imagecolorallocatealpha($im, 0, 0, 0, 60));
        $white = imagecolorallocate($im, 255, 255, 255);
        if (file_exists($fontPath)) {
            $y = 18;
            foreach (explode("\n", $text) as $line) { imagettftext($im, 14, 0, 8, $y, $white, $fontPath, $line); $y += 18; }
        } else {
            $y = 5;
            foreach (explode("\n", $text) as $line) { imagestring($im, 3, 5, $y, $line, $white); $y += 14; }
        }
        $textImage = sys_get_temp_dir() . '/patrol_proc_' . uniqid() . '.png';
        imagepng($im, $textImage);
        imagedestroy($im);

        $outputPath = $filePath . '.proc_' . uniqid() . '.mp4';
        
        // Compute bufsize correctly from target bitrate (avoid multiplying '800k' by 2 which causes non-numeric errors)
        $bufsize = null;
        if (is_string($targetBitrate) && preg_match('/^(\d+)\s*k$/i', trim($targetBitrate), $m)) {
            $kb = (int)$m[1];
            $bufsize = ($kb * 2) . 'k';
        } else {
            // fallback: assume numeric value and double it, or default to 1600k
            if (is_numeric($targetBitrate)) {
                $bufsize = ((int)$targetBitrate * 2) . 'k';
            } else {
                $bufsize = '1600k';
            }
        }

        // Enhanced ffmpeg command with aggressive compression + scaling for large resolutions + watermark
        // Scale down videos wider than 1920px to save space, maintain aspect ratio
        $cmd = escapeshellcmd($ffmpegBin)
            . ' -i ' . escapeshellarg($filePath)
            . ' -i ' . escapeshellarg($textImage)
            . ' -filter_complex "[0:v]scale=\'min(1920,iw)\':-2[scaled];[scaled][1:v]overlay=10:10[out]"'
            . ' -map "[out]" -map 0:a?'
            . ' -c:v libx264 -crf ' . $crf . ' -preset medium -b:v ' . $targetBitrate . ' -maxrate ' . $targetBitrate . ' -bufsize ' . $bufsize
            . ' -c:a aac -b:a 64k -movflags +faststart '
            . escapeshellarg($outputPath) . ' -y';

        $out = []; $ret = 0;
        exec($cmd . ' 2>&1', $out, $ret);
        @unlink($textImage);

        if ($ret === 0 && file_exists($outputPath)) {
            $outputSize = @filesize($outputPath) ?: 0;
            Log::info('processVideo: success', [
                'path' => $filePath,
                'original_size' => $originalSize,
                'output_size' => $outputSize,
                'compression_ratio' => $originalSize > 0 ? round(($outputSize / $originalSize) * 100, 2) . '%' : 'N/A'
            ]);
            @unlink($filePath);
            if (!@rename($outputPath, $filePath)) { @copy($outputPath, $filePath); @unlink($outputPath); }
            @chmod($filePath, 0644);
        } else {
            Log::error('processVideo: ffmpeg failed', [
                'path' => $filePath,
                'ffmpeg_bin' => $ffmpegBin,
                'return_code' => $ret,
                'output' => implode("\n", $out),
            ]);
            if (file_exists($outputPath)) @unlink($outputPath);
        }
    }

    private function addTimestampToPdf($pdfPath, $timestampData)
    {
        $this->createMetadataFile($pdfPath, $timestampData);
    }

    private function addTimestampToDocument($docPath, $timestampData)
    {
        $this->createMetadataFile($docPath, $timestampData);
    }

    private function createMetadataFile($filePath, $timestampData)
    {
        $metadataPath = $filePath . '.metadata.txt';
        $content = "PATROL MEDIA METADATA\n";
        $content .= "==================\n";
        $content .= "Time: " . $timestampData['time'] . "\n";
        $content .= "Employee: " . $timestampData['employee'] . "\n";
        $content .= "Latitude: " . $timestampData['latitude'] . "\n";
        $content .= "Longitude: " . $timestampData['longitude'] . "\n";
        $content .= "Site: " . $timestampData['site'] . "\n";
        $content .= "Location: " . $timestampData['location'] . "\n";
        $content .= "Original File: " . basename($filePath) . "\n";

        file_put_contents($metadataPath, $content);
    }

    // check if guard is on duty
    public function checkDutyStatus(Request $request)
    {
        $user = Auth::user();
        $userId = $user->id;

        // Find the latest "book_on" booking for the user (this represents the booked-on shift)
        $latestBooking = ShiftBooking::where('user_id', $userId)
            ->where('type', 'book_on')
            ->latest('created_at')
            ->first();

        // If no booking exists, default to off-duty
        if (!$latestBooking) {
            return response()->json([
                'status' => 'off-duty',
                'shift_date' => null,
                'patrol_id' => null,
                'message' => 'No booked-on shifts found.'
            ]);
        }

        // Eager-load same relations as getShifts so response shape matches
        $shiftDate = ShiftDate::with([
            'shift.site',
            'trainings' => function ($q) use ($userId) {
                $q->with(['acknowledgedUsers' => function ($q2) use ($userId) {
                    $q2->where('user_id', $userId);
                }]);
            },
        ])->find($latestBooking->shift_id);

        if (!$shiftDate) {
            return response()->json([
                'status' => 'off-duty',
                'shift_date' => null,
                'patrol_id' => null,
                'message' => 'Booked-on shift record not found.'
            ]);
        }

        // Determine duty status
        $status = 'on-duty';

        // find any in-progress patrol for this ShiftDate
        $patrol = Patrol::where('shift_id', $shiftDate->id)->where('status', 'in_progress')->first();

        $today = now()->toDateString();

        // Transform single shiftDate to the same structure used by getShifts
        $shift = $shiftDate->shift;
        $site = $shift?->site;

        if ($shiftDate->shift_date < $today) {
            $category = 'past';
        } elseif ($shiftDate->shift_date == $today) {
            $category = 'current';
        } else {
            $category = 'upcoming';
        }

        // Latest guard-facing note only (note_type 'guard' or 'both'); control-only notes are hidden from the guard.
        $note = ShiftNote::where('shift_date_id', $shiftDate->id)
            ->whereIn('note_type', ['guard', 'both'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        // Load trainings from the site (materials belong to site), not the shift
        $siteTrainings = collect();
        if ($site) {
            $siteTrainings = $site->trainings()->with(['acknowledgedUsers' => function ($q) use ($userId) {
                $q->where('user_id', $userId);
            }])->get();
        }

        $trainings = $siteTrainings->map(function ($training) {
            $ack = $training->acknowledgedUsers->first();
            $acknowledged = false;
            $acknowledgedAt = null;
            $completionSeconds = null;

            if ($ack) {
                $acknowledged = !empty($ack->pivot->acknowledged_at);
                $acknowledgedAt = $ack->pivot->acknowledged_at ? (string) $ack->pivot->acknowledged_at : null;
                $completionSeconds = $ack->pivot->completion_time_seconds !== null ? (int) $ack->pivot->completion_time_seconds : null;
            }

            return [
                'id' => $training->id,
                'title' => $training->title,
                'description' => $training->description,
                'pdf_url' => $training->pdf_url,
                'content_url' => $training->content_url ?? null,
                'required' => (bool) ($training->required ?? false),
                'acknowledged' => $acknowledged,
                'acknowledged_at' => $acknowledgedAt,
                'status' => $acknowledgedAt ? 'completed' : 'pending',
                'completion_time_seconds' => $completionSeconds,
                'implementation_date' => $training->implementation_date,
                'complete_by_date' => $training->deadline,
                'acknowledge_by_date' => $training->acknowledge_by_date,
                'created_at' => $training->created_at,
                'updated_at' => $training->updated_at,
            ];
        });

        $transformed = [
            'id' => $shiftDate->id,
            'shift_id' => $shiftDate->shift_id,
            'site_id' => $site?->id,
            'site_name' => $site?->site_name,
            'site_address' => $site?->address,
            'start_time' => $shiftDate->start_time,
            'end_time' => $shiftDate->end_time,
            'shift_date' => $shiftDate->shift_date,
            'duties' => $shift?->duties,
            'supervisor_name' => $shift?->supervisor_name,
            'supervisor_contact' => $shift?->supervisor_contact,
            'status' => $shiftDate->status,
            'started_at' => $shiftDate->absentee_start_time,
            'ended_at' => $shiftDate->absentee_end_time,
            'briefing_pdf' => $shift?->briefing_pdf_url,
            'risk_assessment_pdf' => $shift?->risk_assessment_pdf_url,
            'category' => $category,
            'trainings' => $trainings,
            'note' => $note ? [
                'id' => $note->id,
                'note_type' => $note->note_type,
                'note' => $note->note,
            ] : null,
            'requires_booking_media_for_book_on' => (function() use ($shiftDate, $userId) {
                try {
                    $hasPatrols = $shiftDate->patrols()->exists();
                    $hasCheckCalls = $shiftDate->checkCalls()->exists();
                    if ($hasPatrols || $hasCheckCalls) return false;
                    return !BookingMedia::where('shift_date_id', $shiftDate->id)
                        ->where('user_id', $userId)
                        ->where('type', 'book_on')
                        ->exists();
                } catch (\Exception $e) {
                    return false;
                }
            })(),
            'requires_booking_media_for_book_off' => (function() use ($shiftDate, $userId) {
                try {
                    $hasPatrols = $shiftDate->patrols()->exists();
                    $hasCheckCalls = $shiftDate->checkCalls()->exists();
                    if ($hasPatrols || $hasCheckCalls) return false;
                    return !BookingMedia::where('shift_date_id', $shiftDate->id)
                        ->where('user_id', $userId)
                        ->where('type', 'book_off')
                        ->exists();
                } catch (\Exception $e) {
                    return false;
                }
            })(),
        ];

        return response()->json([
            'status' => $status,
            'patrol_id' => $patrol?->id ?? null,
            'shift_date' => $transformed,
            'message' => 'Booked-on shift retrieved successfully.'
        ]);
    }

    public function shiftDetails($id)
    {
        $user = Auth::user();
        $userId = $user->id;

        // Eager-load same relations as getShifts so response shape matches
        $shiftDate = ShiftDate::with([
            'shift.site',
            'trainings' => function ($q) use ($userId) {
                $q->with(['acknowledgedUsers' => function ($q2) use ($userId) {
                    $q2->where('user_id', $userId);
                }]);
            },
        ])->find($id);

        if (!$shiftDate) {
            return response()->json([
                'message' => 'No shift found with this ID. #'.$id
            ]);
        }

        // find any in-progress patrol for this ShiftDate
        $patrol = Patrol::where('shift_id', $shiftDate->id)->where('status', 'in_progress')->first();

        $today = now()->toDateString();

        // Transform single shiftDate to the same structure used by getShifts
        $shift = $shiftDate->shift;
        $site = $shift?->site;

        if ($shiftDate->shift_date < $today) {
            $category = 'past';
        } elseif ($shiftDate->shift_date == $today) {
            $category = 'current';
        } else {
            $category = 'upcoming';
        }

        // Latest guard-facing note only (note_type 'guard' or 'both'); control-only notes are hidden from the guard.
        $note = ShiftNote::where('shift_date_id', $shiftDate->id)
            ->whereIn('note_type', ['guard', 'both'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        // Load trainings from the site (materials belong to site), not the shift
        $siteTrainings = collect();
        if ($site) {
            $siteTrainings = $site->trainings()->with(['acknowledgedUsers' => function ($q) use ($userId) {
                $q->where('user_id', $userId);
            }])->get();
        }

        $trainings = $siteTrainings->map(function ($training) {
            $ack = $training->acknowledgedUsers->first();
            $acknowledged = false;
            $acknowledgedAt = null;
            $completionSeconds = null;

            if ($ack) {
                $acknowledged = !empty($ack->pivot->acknowledged_at);
                $acknowledgedAt = $ack->pivot->acknowledged_at ? (string) $ack->pivot->acknowledged_at : null;
                $completionSeconds = $ack->pivot->completion_time_seconds !== null ? (int) $ack->pivot->completion_time_seconds : null;
            }

            return [
                'id' => $training->id,
                'title' => $training->title,
                'description' => $training->description,
                'pdf_url' => $training->pdf_url,
                'content_url' => $training->content_url ?? null,
                'required' => (bool) ($training->required ?? false),
                'acknowledged' => $acknowledged,
                'acknowledged_at' => $acknowledgedAt,
                'status' => $acknowledgedAt ? 'completed' : 'pending',
                'completion_time_seconds' => $completionSeconds,
                'implementation_date' => $training->implementation_date,
                'complete_by_date' => $training->deadline,
                'acknowledge_by_date' => $training->acknowledge_by_date,
                'created_at' => $training->created_at,
                'updated_at' => $training->updated_at,
            ];
        });

        $transformed = [
            'id' => $shiftDate->id,
            'shift_id' => $shiftDate->shift_id,
            'site_id' => $site?->id,
            'site_name' => $site?->site_name,
            'site_address' => $site?->address,
            'start_time' => $shiftDate->start_time,
            'end_time' => $shiftDate->end_time,
            'shift_date' => $shiftDate->shift_date,
            'duties' => $shift?->duties,
            'supervisor_name' => $shift?->supervisor_name,
            'supervisor_contact' => $shift?->supervisor_contact,
            'status' => $shiftDate->status,
            'started_at' => $shiftDate->absentee_start_time,
            'ended_at' => $shiftDate->absentee_end_time,
            'briefing_pdf' => $shift?->briefing_pdf_url,
            'risk_assessment_pdf' => $shift?->risk_assessment_pdf_url,
            'category' => $category,
            'trainings' => $trainings,
            'note' => $note ? [
                'id' => $note->id,
                'note_type' => $note->note_type,
                'note' => $note->note,
            ] : null,
            'requires_booking_media_for_book_on' => (function() use ($shiftDate, $userId) {
                try {
                    $hasPatrols = $shiftDate->patrols()->exists();
                    $hasCheckCalls = $shiftDate->checkCalls()->exists();
                    if ($hasPatrols || $hasCheckCalls) return false;
                    return !BookingMedia::where('shift_date_id', $shiftDate->id)
                        ->where('user_id', $userId)
                        ->where('type', 'book_on')
                        ->exists();
                } catch (\Exception $e) {
                    return false;
                }
            })(),
            'requires_booking_media_for_book_off' => (function() use ($shiftDate, $userId) {
                try {
                    $hasPatrols = $shiftDate->patrols()->exists();
                    $hasCheckCalls = $shiftDate->checkCalls()->exists();
                    if ($hasPatrols || $hasCheckCalls) return false;
                    return !BookingMedia::where('shift_date_id', $shiftDate->id)
                        ->where('user_id', $userId)
                        ->where('type', 'book_off')
                        ->exists();
                } catch (\Exception $e) {
                    return false;
                }
            })(),
        ];

        return response()->json([
            'shift_date' => $transformed,
            'message' => 'Shift retrieved successfully.'
        ]);
    }

public function workHours(Request $request)
{
    $user = Auth::user();

    $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();
    $endOfWeek   = Carbon::now()->endOfWeek(Carbon::SUNDAY)->toDateString();

    // Get ended shifts for this guard in the current week (Mon–Sun)
    $shifts = ShiftDate::where('staff_id', $user->id)
        ->where('is_assign', 4)
        ->whereBetween('shift_date', [$startOfWeek, $endOfWeek])
        ->get();

    $totalWorked = 0;

    foreach ($shifts as $shift) {
        if ($shift->total_hours) {
            $worked = $shift->total_hours;
        } else {
            $start = Carbon::parse($shift->start_time);
            $end   = Carbon::parse($shift->end_time);

            $worked = $end->diffInMinutes($start) / 60;

            if ($shift->break_time) {
                $worked -= $shift->break_time;
            }
        }

        $totalWorked += max($worked, 0);
    }

    $employee = Employee::where('user_id', $user->id)->firstOrFail();

    $weeklyLimit = $employee->visa_type === 'Student' ? 20 : 40;
    $remaining = max($weeklyLimit - $totalWorked, 0);

    return response()->json([
        'total_worked_hours' => round($totalWorked, 2),
        'remaining_hours'    => round($remaining, 2),
        'weekly_limit'       => $weeklyLimit,
    ]);
}



    public function holidayBalances()
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $totalHolidayHours = $employee->holiday_balance ?? 0;

        // Sum of approved hours for all annual leaves
        $leaves = LeaveRequest::where('employee_id', $employee->id)
            ->whereIn('status', ['approved', 'completed'])
            ->get(['hours', 'approved_hours']);

        $usedHours = $leaves->sum(function ($l) { return max(0, $l->approved_hours ?? 0); });

        // Available hours cannot be negative
        $availableHours = max(0, $totalHolidayHours - $usedHours);

        // Unpaid hours (per-leave max(0, hours - approved_hours))
        $unpaidHours = $leaves->sum(function ($l) {
            return max(0, ($l->hours ?? 0) - ($l->approved_hours ?? 0));
        });

        return response()->json([
            'user_id'     => $user->id,
            'total_hours'     => $totalHolidayHours,
            'used_hours'      => $usedHours,
            'available_hours' => $availableHours,
            'unpaid_hours'    => max(0, $unpaidHours),
        ]);
    }

    public function calendar(Request $request)
    {
        $userId = Auth::id();
        $today  = now()->toDateString();

        // Debug: Check if user ID is valid
        Log::info('Calendar request', ['user_id' => $userId]);

        // Eager-load trainings and site similar to getShifts but return ALL results (no pagination, no filters)
        $shiftDates = ShiftDate::with([
            'shift.site',
            'trainings' => function ($q) use ($userId) {
                $q->with(['acknowledgedUsers' => function ($q2) use ($userId) {
                    $q2->where('user_id', $userId);
                }]);
            },
        ])
            ->where('staff_id', $userId)
            ->orderBy('shift_date', 'desc')
            ->get();

        Log::info('Calendar shifts found', ['count' => $shiftDates->count()]);

        $transformed = $shiftDates->transform(function ($shiftDate) use ($today,$userId) {
            $shift = $shiftDate->shift;
            $site  = $shift?->site;

            if ($shiftDate->shift_date < $today) {
                $category = 'past';
            } elseif ($shiftDate->shift_date == $today) {
                $category = 'current';
            } else {
                $category = 'upcoming';
            }

            // Latest guard-facing note only (note_type 'guard' or 'both'); control-only notes are hidden from the guard.
        $note = ShiftNote::where('shift_date_id', $shiftDate->id)
            ->whereIn('note_type', ['guard', 'both'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

            // Load trainings from the site (materials belong to site), not the shift
            $siteTrainings = collect();
            if ($site) {
                $siteTrainings = $site->trainings()->with(['acknowledgedUsers' => function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                }])->get();
            }

            $trainings = $siteTrainings->map(function ($training) {
                $ack = $training->acknowledgedUsers->first();
                $acknowledged = false;
                $acknowledgedAt = null;
                $completionSeconds = null;

                if ($ack) {
                    $acknowledged = !empty($ack->pivot->acknowledged_at);
                    $acknowledgedAt = $ack->pivot->acknowledged_at ? (string) $ack->pivot->acknowledged_at : null;
                    $completionSeconds = $ack->pivot->completion_time_seconds !== null
                        ? (int) $ack->pivot->completion_time_seconds
                        : null;
                }

                return [
                    'id' => $training->id,
                    'title' => $training->title,
                    'description' => $training->description,
                    'pdf_url' => $training->pdf_url,
                    'content_url' => $training->content_url ?? null,
                    'required' => (bool) ($training->required ?? false),
                    'acknowledged' => $acknowledged,
                    'acknowledged_at' => $acknowledgedAt,
                    'status' => $acknowledgedAt ? 'completed' : 'pending',
                    'completion_time_seconds' => $completionSeconds,
                    'implementation_date' => $training->implementation_date,
                    'complete_by_date' => $training->deadline,
                    'acknowledge_by_date' => $training->acknowledge_by_date,
                    'created_at' => $training->created_at,
                    'updated_at' => $training->updated_at,
                ];
            });

            return [
                'id' => $shiftDate->id,
                'shift_id' => $shiftDate->shift_id,
                'site_id' => $site?->id,
                'site_name' => $site?->site_name,
                'site_address' => $site?->address,
                'start_time' => $shiftDate->start_time,
                'end_time' => $shiftDate->end_time,
                'shift_date' => $shiftDate->shift_date,
                'duties' => $shift?->duties,
                'supervisor_name' => $shift?->supervisor_name,
                'supervisor_contact' => $shift?->supervisor_contact,
                'status' => $shiftDate->status,
                'started_at' => $shiftDate->absentee_start_time,
                'ended_at' => $shiftDate->absentee_end_time,
                'briefing_pdf' => $shift?->briefing_pdf_url,
                'risk_assessment_pdf' => $shift?->risk_assessment_pdf_url,
                'category' => $category,
                'trainings' => $trainings,
                'note' => $note ? [
                    'id'        => $note->id,
                    'note_type' => $note->note_type,
                    'note'      => $note->note,
                ] : null,
            ];
        });

        return response()->json([
            'shift_dates' => $transformed,
        ]);
    }

    public function calculateSickPay(Employee $staff, Carbon $sickStart, Carbon $sickEnd, int $weeklyPay)
    {
        if ($weeklyPay < 123) {
            return ['eligible' => false, 'paid_days' => 0, 'unpaid_days' => $sickStart->diffInDays($sickEnd) + 1, 'amount' => 0];
        }

        $totalDays = $sickStart->diffInDays($sickEnd) + 1;
        $unpaid = min(3, $totalDays);
        $paid = max(0, $totalDays - 3);
        $paid = min($paid, 196); // 28 weeks

        return [
            'eligible' => true,
            'total_days' => $totalDays,
            'unpaid_days' => $unpaid,
            'paid_days' => $paid,
            'amount' => $paid * 23.75,
        ];
    }

    /**
     * Holiday entitlement
     */
    public function calculateHoliday(Employee $staff, float $workedHours, string $type = 'accrual')
    {
        if ($type === 'accrual') {
            return ['holiday_hours' => round($workedHours * 0.1207, 2)];
        }

        $startDate = Carbon::parse($staff->start_date ?? now());
        $daysWorked = $startDate->diffInDays(now());
        $holidayDays = (28 / 365) * $daysWorked;

        return ['holiday_hours' => round($holidayDays * 8, 2)];
    }

    private function ensureWithinShiftSiteRadius(ShiftDate $shiftDate, $guardLat, $guardLng, string $activity)
    {
        if ((int) ($shiftDate->shift?->restrict_location_check ?? 0) !== 1) {
            return null;
        }

        $site = $shiftDate->shift?->site;

        if (!$site) {
            return response()->json([
                'message' => 'Site information is missing for this shift. Cannot verify your location.',
            ], 422);
        }

        $geoService = app(GeoService::class);
        $address  = trim((string) ($site->address ?? ''));
        $postCode = trim((string) ($site->post_code ?? ''));

        if ($address === '' && $postCode === '') {
            Log::warning('Site address and postcode both missing for geofence', [
                'shift_date_id' => $shiftDate->id,
                'site_id' => $site->id,
            ]);

            return response()->json([
                'message' => 'Site address is missing. Cannot verify your location.',
            ], 422);
        }

        Log::info('Using site address for geocoding', [
            'shift_date_id' => $shiftDate->id,
            'site_id' => $site->id,
            'site_address' => $address,
            'site_postcode' => $postCode,
        ]);

        $plusCode = trim((string) ($site->plus_code ?? ''));
        $siteCoords = $geoService->getCoordinatesFromAddress($address, $postCode ?: null, $plusCode ?: null);

        if (!$siteCoords || !isset($siteCoords['lat'], $siteCoords['lng'])) {
            Log::warning('Address geocoding failed for site', [
                'shift_date_id' => $shiftDate->id,
                'site_id' => $site->id,
                'site_address' => $address,
            ]);

            return response()->json([
                'message' => 'Unable to verify site location right now. Please try again shortly.',
            ], 422);
        }

        $distanceMeters = $geoService->distanceInMeters($guardLat, $guardLng, $siteCoords['lat'], $siteCoords['lng']);

        $allowedMeters = 1000;

        if ($distanceMeters > $allowedMeters) {
            return response()->json([
                'message' => 'You are outside the allowed site radius and cannot ' . $activity . '.',
                'distance_meters' => round($distanceMeters, 1),
                'allowed_radius_meters' => round($allowedMeters, 1),
                'site' => [
                    'id' => $site->id,
                    'name' => $site->site_name,
                ],
            ], 422);
        }

        return null;
    }
}