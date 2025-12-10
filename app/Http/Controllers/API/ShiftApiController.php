<?php

namespace App\Http\Controllers\API;

use Notify;
use Carbon\Carbon;
use App\Models\Site;
use App\Models\User;
use App\Models\Shift;
use App\Models\Patrol;
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
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\PatrolMedia;
use Illuminate\Support\Facades\Log;
use App\Services\GeoService;
use App\Models\Location;

class ShiftApiController extends Controller
{
    // 10. Get Upcoming Shifts
    public function getShifts(Request $request)
    {
        $userId   = Auth::id();
        $limit    = $request->query('limit', 10);
        $category = $request->query('category'); // "past", "current", "upcoming"
        $today    = now()->toDateString();

        // Eager-load trainings and only the current user's acknowledgements for those trainings
        $query = ShiftDate::with([
            'shift.site',
            'trainings' => function ($q) use ($userId) {
                $q->with(['acknowledgedUsers' => function ($q2) use ($userId) {
                    $q2->where('user_id', $userId);
                }]);
            },
        ])
            ->where('staff_id', $userId)
            ->orderBy('shift_date', 'desc');

        // category filter
        if ($category) {
            if ($category === 'past') {
                $query->where('shift_date', '<', $today);
            } elseif ($category === 'current') {
                $query->where('shift_date', '=', $today);
            } elseif ($category === 'upcoming') {
                $query->where('shift_date', '>', $today);
            }
        }

        $shifts = $query->paginate($limit);

        $transformed = $shifts->getCollection()->transform(function ($shiftDate) use ($today) {
            $shift = $shiftDate->shift;
            $site  = $shift?->site;

            if ($shiftDate->shift_date < $today) {
                $category = 'past';
            } elseif ($shiftDate->shift_date == $today) {
                $category = 'current';
            } else {
                $category = 'upcoming';
            }

            // Fetch the note for this shift
            $note = ShiftNote::where('shift_date_id', $shiftDate->id)->first(); // assuming you have a relation: ShiftDate -> note

            $trainings = $shiftDate->trainings->map(function ($training) {
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
                // ✅ Add note info here
                'note' => ($note?->note_type === 'guard') ? [
                    'id'        => $note->id,
                    'note_type' => $note->note_type,
                    'note'      => $note->note,
                ] : null,
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

        return response()->json([
            'message' => 'Could not submit a respond, current shift status ' . $shift->status,
        ]);
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

        $leave = LeaveRequest::create([
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

        send_push_notification(
            $user->id,
            'Leave request submitted',
            'You have submitted a leave request.',
            ['leave' => $leave]
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
        $request->validate([
            'face_verification_result' => 'required|string',
            'location.latitude' => 'required|numeric',
            'location.longitude' => 'required|numeric',
            'location.address' => 'required|string',
            'timestamp' => 'date',
        ]);

        $user = Auth::user();
        $formattedTimestamp = Carbon::now()->format('Y-m-d H:i:s');

        // Correct: fetch by shiftdate primary key
        $shiftDate = ShiftDate::find($shiftDate_id);
        if (!$shiftDate) {
            return response()->json([
                'message' => 'Shift date (ID: ' . $shiftDate_id . ') Not on your upcoming shifts list!',
            ]);
        }

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
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return response()->json(['message' => 'No employee record linked to this user.'], 404);
        }

        // ✅ Check if user already booked on
        $existingBooking = ShiftBooking::where('user_id', $user->id)
            ->where('type', 'book_on')
            ->first();

        if ($existingBooking) {
            $shift = ShiftDate::find($existingBooking->shift_id);
            if ($shift?->is_assign != 4) {
                return response()->json([
                    'message' => 'You already have a booked on shift (ShiftDate ID: ' . $existingBooking->shift_id . ').'
                ], 409);
            }
        }

        // ✅ Correct: find by ShiftDate ID
        $shiftDate = ShiftDate::with('trainings.acknowledgedUsers')->find($shiftDate_id);

        if (!$shiftDate) {
            return response()->json([
                'message' => 'Trying to book on unavailable shift (ShiftDate ID: ' . $shiftDate_id . ').'
            ], 409);
        }

        if ($shiftDate->is_assign !== 2) {
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

        // ✅ Only allow booking on at or after shift start
        $now = Carbon::now();
        $shiftStart = Carbon::parse($shiftDate->shift_date . ' ' . $shiftDate->start_time);

        if ($now->lt($shiftStart)) {
            return response()->json(['message' => 'You can only book on when the shift is due at ' . $shiftDate->start_time], 422);
        }

        // ✅ Update status
        $shiftDate->status = 'booked_on';
        $shiftDate->is_assign = 3; // shift started
        $shiftDate->absentee_start_time = date('H:i', strtotime($request->timestamps));
        $shiftDate->save();

        // ✅ Notifications
        Notification::create([
            'user_id' => 1,
            'employee_id' => null,
            'type' => 'alert',
            'title' => 'Shift booked on',
            'message' => 'Guard ' . $user->first_name . ' ' . $user->last_name . ' booked on shift (ID: ' . $shiftDate->id . ') starting at ' . $shiftDate->start_time,
            'read' => false,
            'action_url' => "/shift-dates/$shiftDate_id/view"
        ]);

        Logger::log($shiftDate, 'Booked On', ' booked on shift (ID: ' . $shiftDate->id . ') starting at ' . $shiftDate->start_time);

        Notification::create([
            'user_id' => $user->id,
            'employee_id' => $employee->id,
            'type' => 'alert',
            'title' => 'Shift booked on',
            'message' => 'You have booked on shift (ID: ' . $shiftDate->id . ') ends at ' . $shiftDate->shift->end_shift,
            'read' => false,
        ]);

        send_push_notification(
            $user->id,
            'Shift booked on',
            'Your shift has been successfully booked on.',
            ['shift_date_id' => $shiftDate->id]
        );

        return $this->bookOnOff($request, $shiftDate_id, 'book_on');
    }


    public function bookOff(Request $request, $shiftDate_id)
    {
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

        if ($shiftDate) {
            $shiftDate->status = 'booked_off';
            $shiftDate->is_assign = 4; //shift ended
            $timeOnly = date('H:i', strtotime($request->timestamps));

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

        send_push_notification(
            $user->id,
            'Shift booked off',
            'Your shift has been successfully booked off.',
            ['shift_date_id' => $shiftDate_id]
        );

        // Remove the "book_on" record
        $existingBooking->delete();

        return response()->json([
            'message' => 'Shift booked off successfully.'
        ]);
    }

    public function getPatrolRoutes($shift_id)
    {
        $shift = ShiftDate::with('patrols')->findOrFail($shift_id);
        $patrols = $shift->patrols->map(function ($patrol) use ($shift) {
            $checkpoints = PatrolCheckPoint::where('site_id', $shift->shift->site_id)->get();
            return [
                'id' => $patrol->id,
                'name' => $patrol->name,
                'start_time' => $patrol->start_time,
                'started_at' => $patrol->started_at,
                'completed_at' => $patrol->completed_at,
                'status' => $patrol->status,
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

    public function scanCheckpoint(Request $request, $checkpoint_id)
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

        $checkpoint = PatrolCheckpoint::find($checkpoint_id);
        if (!$checkpoint) {
            return response()->json(['message' => 'Checkpoint not found.'], 404);
        }

        $scan = $checkpoint->scans()->create([
            'user_id' => Auth::id(),
            'scan_data' => $request->scan_data,
            'scan_method' => $request->scan_method,
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

        return response()->json(['message' => 'Checkpoint scanned']);
    }

    public function startPatrol($patrol_id)
    {
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
                Notification::create([
                    'user_id' => $otherUser->id,
                    'employee_id' => $otherShiftDate->staff_id,
                    'type' => 'alert',
                    'title' => 'Patrol auto-completed',
                    'message' => 'Your previous patrol was marked completed to allow starting a new patrol at ' . $now,
                    'read' => false,
                ]);

                send_push_notification(
                    $otherUser->id,
                    'Patrol auto-completed',
                    'Your previous patrol was marked completed at ' . $now,
                    ['shift_date_id' => $other->shift_id]
                );
            }
        }

        // Start requested patrol
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

        Notification::create([
            'user_id' => Auth::id(),
            'employee_id' => auth::id(),
            'type' => 'alert',
            'title' => 'Patrol Started',
            'message' => 'You have started your patrol',
            'read' => false,
        ]);

        send_push_notification(
            Auth::id(),
            'Patrol started on',
            'You have started your patrol successfully at ' . $now,
            ['shift_date_id' => $patrol->shift_id]
        );

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
        ]);

        $patrol->update([
            'summary' => $request->summary,
            'total_checkpoints' => $request->total_checkpoints,
            'completed_checkpoints' => $request->completed_checkpoints,
            'issues_reported' => $request->issues_reported,
            'completed_at' => $now,
            'status' => 'completed',
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

        Notification::create([
            'user_id' => Auth::id(),
            'employee_id' => auth::id(),
            'type' => 'alert',
            'title' => 'Patrol completed',
            'message' => 'You have completed your patrol successfully!',
            'read' => false,
        ]);

        send_push_notification(
            Auth::id(),
            'Patrol Completed',
            'You have Completed your patrol successfully at ' . $now,
            ['shift_date_id' => $patrol->shift_id]
        );

        return response()->json(['message' => 'Patrol marked as completed']);
    }

    // Upload media files for a patrol (accepts UploadedFile instances or base64 data URIs)
    public function uploadPatrolMedia(Request $request, $patrol_id)
    {
        $request->validate([
            'media_files' => 'nullable|array',
            'location.latitude' => 'nullable|numeric',
            'location.longitude' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        $patrol = Patrol::findOrFail($patrol_id);

        // Ensure the authenticated user is the assigned staff for this patrol's shift
        $shiftDate = ShiftDate::find($patrol->shift_id);
        if (!$shiftDate || $shiftDate->staff_id !== Auth::id()) {
            return response()->json(['message' => 'You are not assigned to this patrol.'], 403);
        }

        if($patrol->status !='in_progress'){
            return response()->json(['message' => 'The patrol is not in progress at the moment, you cannot submit media!']);
        }

        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        // Prepare timestamp data
        // Accept location sent as nested array (`location[latitude]`), dotted keys (`location.latitude`) or plain `latitude`/`longitude`
        $lat = $request->input('location.latitude');
        $lng = $request->input('location.longitude');

        // Fallbacks for different form-data naming conventions
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

        $geoService = new GeoService();
        $resolvedAddress = null;
        try {
            if ($lat && $lng) {
                $resolvedAddress = $geoService->getAddressFromCoordinates($lat, $lng);
            }
        } catch (\Exception $e) {
            Log::warning('GeoService failed: ' . $e->getMessage());
        }

        // Ensure location is an array with a `formatted_address` key so watermark code can read it
        $locationForStamp = is_array($resolvedAddress) ? $resolvedAddress : ['formatted_address' => ($resolvedAddress ?? ($shiftDate->shift->site->address ?? 'N/A'))];

        $timestampData = [
            'time' => Carbon::now()->format('Y-m-d H:i:s'),
            'employee' => $employee ? ($employee->fore_name . ' ' . $employee->sur_name) : ($user->first_name . ' ' . $user->last_name),
            'latitude' => $lat,
            'longitude' => $lng,
            'site' => $shiftDate->shift->site->site_name ?? 'N/A',
            'location' => $locationForStamp,
        ];

        // Build list of inputs: uploaded files (media_files[], file_path, file, etc.) and base64 strings
        $items = [];

        // Collected uploaded files under media_files[]
        $uploaded = $request->file('media_files');
        if ($uploaded) {
            if (is_array($uploaded)) {
                foreach ($uploaded as $up) $items[] = $up;
            } else {
                $items[] = $uploaded;
            }
        }

        // Single file field common name used in Postman
        if ($request->hasFile('file_path')) {
            $items[] = $request->file('file_path');
        }

        // Generic catch-all for any other file fields
        $allFiles = $request->allFiles();
        foreach ($allFiles as $key => $f) {
            // skip those we've already added via media_files
            if ($key === 'media_files' || $key === 'file_path') continue;
            if (is_array($f)) {
                foreach ($f as $sub) $items[] = $sub;
            } else {
                $items[] = $f;
            }
        }

        // Also accept base64 strings supplied in JSON body as media_files array
        $bodyMedia = $request->input('media_files');
        if (is_array($bodyMedia)) {
            foreach ($bodyMedia as $bm) {
                // If it's an uploaded file already, skip
                if ($bm instanceof \Illuminate\Http\UploadedFile) continue;
                $items[] = $bm; // base64 string or data URI
            }
        }

        if (empty($items)) {
            Log::warning('No media items found in request for patrol ' . $patrol->id, ['request_keys' => array_keys($request->all())]);
        }

        $created = [];

        // Process each collected item
        foreach ($items as $file) {
            $filePath = null;
            $originalName = null;

            if ($file instanceof \Illuminate\Http\UploadedFile) {
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension() ?: 'bin';
                $filename = time() . '_' . uniqid() . '.' . $extension;
                $dir = public_path('patrols/media');
                if (!file_exists($dir)) mkdir($dir, 0755, true);
                $file->move($dir, $filename);
                $filePath = 'patrols/media/' . $filename;
            } elseif (is_string($file) && preg_match('/^data:/', $file)) {
                $fileData = preg_replace('/^data:\w+\/\w+;base64,/', '', $file);
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
                    ];
                    $extension = $extMap[$mime] ?? 'bin';
                }
                $dir = public_path('patrols/media');
                if (!file_exists($dir)) mkdir($dir, 0755, true);
                $filename = time() . '_' . uniqid() . '.' . $extension;
                file_put_contents($dir . '/' . $filename, base64_decode($fileData));
                $filePath = 'patrols/media/' . $filename;
            } else {
                // unsupported type, skip
                Log::warning('Skipping unsupported media item type for patrol ' . $patrol->id);
                continue;
            }

            if (!$filePath) continue;

            $fullPath = public_path($filePath);
            $fileType = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

            // Compress file if needed
            $compressedPath = $this->compressFile($fullPath, $fileType);
            if ($compressedPath && $compressedPath != $fullPath) {
                if (file_exists($fullPath)) @unlink($fullPath);
                rename($compressedPath, $fullPath);
            }

            // Save DB record
            try {
                $pm = PatrolMedia::create([
                    'patrol_id' => $patrol->id,
                    'file_path' => $filePath,
                ]);
                $created[] = [
                    'id' => $pm->id,
                    'file_path' => $filePath,
                    'url' => asset($filePath),
                ];
            } catch (\Exception $e) {
                Log::error('Failed to record patrol media: ' . $e->getMessage());
            }
        }

        // Notify admin and the guard
        try {
            Notification::create([
                'user_id' => 1,
                'employee_id' => null,
                'type' => 'alert',
                'title' => 'Patrol media uploaded',
                'message' => 'Media uploaded for patrol ID ' . $patrol->id,
                'read' => false,
                'action_url' => "/shift-dates/{$patrol->shift_id}/view"
            ]);

            Notification::create([
                'user_id' => Auth::id(),
                'employee_id' => Auth::id(),
                'type' => 'alert',
                'title' => 'Patrol media uploaded',
                'message' => 'Your media has been uploaded for patrol ID ' . $patrol->id,
                'read' => false,
            ]);

            send_push_notification(
                Auth::id(),
                'Patrol media uploaded',
                'Your media has been uploaded successfully',
                ['patrol_id' => $patrol->id]
            );
        } catch (\Exception $e) {
            Log::error('Patrol media notification failed: ' . $e->getMessage());
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
        $maxSize = 5 * 1024 * 1024; // 5MB limit

        if ($originalSize <= $maxSize) {
            return $filePath; // No compression needed
        }

        switch ($fileType) {
            case 'jpg':
            case 'jpeg':
                return $this->compressImage($filePath, 60, 1920); // 60% quality, max width 1920px
            case 'png':
                return $this->compressImage($filePath, 8, 1920); // PNG compression level 8, max width 1920px
            case 'mp4':
            case 'mov':
            case 'avi':
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
        // Check if FFmpeg is available
        if (!function_exists('shell_exec') || !shell_exec('which ffmpeg')) {
            return $filePath;
        }

        $originalSize = filesize($filePath);
        $maxSize = 10 * 1024 * 1024; // 10MB target for videos
        $targetBitrate = '1000k'; // Adjust based on original size

        // Calculate target bitrate based on original file size
        if ($originalSize > 50 * 1024 * 1024) { // > 50MB
            $targetBitrate = '500k';
        } elseif ($originalSize > 20 * 1024 * 1024) { // > 20MB
            $targetBitrate = '800k';
        }

        $compressedPath = $filePath . '.compressed.mp4';
        $escapedInput = escapeshellarg($filePath);
        $escapedOutput = escapeshellarg($compressedPath);

        // FFmpeg command for compression
        $command = "ffmpeg -i {$escapedInput} " .
            "-c:v libx264 -crf 28 -preset medium -b:v {$targetBitrate} " .
            "-c:a aac -b:a 64k " .
            "-movflags +faststart " .
            "{$escapedOutput} 2>/dev/null";

        shell_exec($command);

        if (file_exists($compressedPath) && filesize($compressedPath) < $originalSize) {
            return $compressedPath;
        } else {
            if (file_exists($compressedPath)) {
                unlink($compressedPath);
            }
            return $filePath;
        }
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

    // Existing timestamp/watermark methods (adapted)
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

        $text = "Time: " . $timestampData['time'] .
            "\nEmployee: " . $timestampData['employee'] .
            "\nLat: " . $timestampData['latitude'] . "  " .
            "Lng: " . $timestampData['longitude'] .
            "\nSite: " . $timestampData['site'] .
            "\nLocation: " . ($timestampData['location']['formatted_address'] ?? 'Unknown');

        $lines = explode("\n", $text);
        $fontPath = public_path('fonts/Arial.ttf');

        if (!file_exists($fontPath)) {
            // Fallback to GD font if TTF not available
            $this->addWatermarkWithGDFont($img, $text, $imagePath, $ext);
            return;
        }

        $imgWidth = imagesx($img);
        $fontSize = max(30, intval($imgWidth * 0.025));
        $lineHeight = $fontSize + 30;
        $padding = 15;

        $rectWidth = 0;
        foreach ($lines as $line) {
            $bbox = imagettfbbox($fontSize, 0, $fontPath, $line);
            $lineWidth = abs($bbox[4] - $bbox[0]);
            if ($lineWidth > $rectWidth) {
                $rectWidth = $lineWidth;
            }
        }
        $rectHeight = count($lines) * $lineHeight + 2 * $padding;

        imagefilledrectangle($img, 0, 0, $rectWidth + 2 * $padding, $rectHeight, $blackTrans);

        $x = $padding;
        $y = $padding + $fontSize;
        foreach ($lines as $line) {
            imagettftext($img, $fontSize, 0, $x, $y, $white, $fontPath, $line);
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
        $ffmpegPath = base_path('ffmpeg-7.0.2-amd64-static/ffmpeg');
        $ffprobePath = base_path('ffmpeg-7.0.2-amd64-static/ffprobe');

        // Normalize input path
        $videoPath = str_replace(['\\', '/'], '/', $videoPath);

        // Temporary directory
        $tempDir = base_path('public/temp_videos');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $outputPath = $videoPath . '.tmp.mp4';

        $location = $timestampData['location']['formatted_address'] ?? '' . ' ' . ($timestampData['location']['street'] ?? '') . ' ' . ($timestampData['location']['city'] ?? '') . ' ' . ($timestampData['location']['country'] ?? '') . ' ' . ($timestampData['location']['postal_code'] ?? '');
        // Prepare overlay text
        $text = "Time: " . $timestampData['time'] .
            "\nEmployee: " . $timestampData['employee'] .
            "\nLat: " . $timestampData['latitude'] . "  " .
            "Lng: " . $timestampData['longitude'] .
            "\nSite: " . $timestampData['site'] .
            "\nLocation: " . $location;

        $text = str_replace([':', ','], '-', $text);

        // Generate text overlay PNG
        $textImage = $tempDir . '/text_overlay.png';
        $fontPath = base_path('ffmpeg/static/Roboto_Condensed-Black.ttf');
        $fontSize = 15;
        $im = imagecreatetruecolor(200, 300);
        imagesavealpha($im, true);
        $transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
        imagefill($im, 0, 0, $transparent);
        $white = imagecolorallocate($im, 255, 255, 255);
        imagettftext($im, $fontSize, 0, 10, 35, $white, $fontPath, $text);
        imagepng($im, $textImage);
        imagedestroy($im);

        // ✅ FIXED ffprobe command — NO spaces after `v:0`
        $cmdProbe = "\"$ffprobePath\" -v error -select_streams v:0 -show_entries stream=width,height -of csv=p=0 \"$videoPath\" 2>&1";
        $dimensions = trim(shell_exec($cmdProbe));

        $rotateNeeded = false;
        $width = 0;
        $height = 0;

        // Parse dimensions safely
        if (!empty($dimensions)) {
            $parts = explode(',', $dimensions);
            if (count($parts) >= 2) {
                $width = (int)$parts[0];
                $height = (int)$parts[1];
            }
        }

        // Determine if rotation is required
        if ($width === 0 || $height === 0) {
            // ffprobe failed to detect — rotate by default
            $rotateNeeded = true;
        } elseif ($height < $width) {
            // Portrait mode → rotate
            $rotateNeeded = true;
        }

        // FFmpeg command
        if ($rotateNeeded) {
            // Rotate 90° clockwise + overlay
            $cmd = "\"$ffmpegPath\" -i \"$videoPath\" -i \"$textImage\" -filter_complex \"transpose=1,overlay=10:10\" -c:a copy \"$outputPath\" -y";
        } else {
            // Normal overlay
            $cmd = "\"$ffmpegPath\" -i \"$videoPath\" -i \"$textImage\" -filter_complex \"overlay=10:10\" -c:a copy \"$outputPath\" -y";
        }

        // Execute FFmpeg
        exec($cmd . ' 2>&1', $outputLines, $returnVar);

        if ($returnVar === 0 && file_exists($outputPath)) {
            @unlink($videoPath);
            rename($outputPath, $videoPath);
            @unlink($textImage);
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

        // Get the latest shift booking
        $latestBooking = ShiftBooking::where('user_id', $user->id)
            ->latest('created_at')
            ->first();

        // If no booking exists, default to off-duty
        if (!$latestBooking) {
            return response()->json([
                'status' => 'off-duty',
                'shift_date_id' => null,
                'shift_id' => null,
                'message' => 'No shift bookings found.'
            ]);
        }

        // Fetch related shift info safely
        $shiftDate = ShiftDate::find($latestBooking->shift_id);
        $shift = $shiftDate ? Shift::find($shiftDate->shift_id) : null;

        // Determine duty status
        $status = $latestBooking->type === 'book_on' ? 'on-duty' : 'off-duty';

        $patrol = Patrol::where('shift_id', $shiftDate->id)->where('status', 'in_progress')->first();

        return response()->json([
            'status'        => $status,
            'shift_date_id' => $shiftDate?->id,
            'shift_id'      => $shift?->id,
            'patrol_id'     => $patrol?->id ?? null,
            'current_shift' => $shiftDate ?? null,
            'message'       => 'Latest booking retrieved successfully.'
        ]);
    }

    public function workHours(Request $request)
    {
        $user = Auth::user();

        // Get all ended shifts for this guard
        $shifts = ShiftDate::where('staff_id', $user->id)
            ->where('is_assign', 4) // only finished shifts
            ->get();

        $totalWorked = 0;

        foreach ($shifts as $shift) {
            // Prefer total_hours if stored
            if ($shift->total_hours) {
                $worked = $shift->total_hours;
            } else {
                // Calculate manually from times
                $start = Carbon::parse($shift->start_time);
                $end   = Carbon::parse($shift->end_time);

                $worked = $end->diffInMinutes($start) / 60;

                // subtract break if available
                if ($shift->break_time) {
                    $worked -= $shift->break_time;
                }
            }

            $totalWorked += max($worked, 0); // avoid negatives
        }

        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $weeklyLimit = $employee->visa_type ===  'Student' ? 20 : 40;

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

        $transformed = $shiftDates->transform(function ($shiftDate) use ($today) {
            $shift = $shiftDate->shift;
            $site  = $shift?->site;

            if ($shiftDate->shift_date < $today) {
                $category = 'past';
            } elseif ($shiftDate->shift_date == $today) {
                $category = 'current';
            } else {
                $category = 'upcoming';
            }

            $note = ShiftNote::where('shift_date_id', $shiftDate->id)->first();

            $trainings = $shiftDate->trainings->map(function ($training) {
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
                'note' => ($note?->note_type === 'guard') ? [
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
}
