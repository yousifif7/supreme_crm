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
            ->orderBy('shift_date');

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
                'note' => $note ? [
                    'id' => $note->id,
                    'note_type' => $note->note_type,
                    'note' => $note->note,
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
        $totalHours  = $totalDays * $hoursPerDay;

        $paid          = false;
        $sspPaidDays   = 0;
        $holidayHours  = 0;
        $unpaidHours   = 0;

        switch ($request->type) {
            case 'sick_leave':
                $weeklyPay = $employee->weekly_pay ?? 0;
                $sickPay   = $this->calculateSickPay($employee, $start, $end, $weeklyPay);

                $sspPaidDays = $sickPay['paid_days'];
                $unpaidHours = $sickPay['unpaid_days'] * $hoursPerDay;
                $paid        = $sspPaidDays > 0;
                break;

            case 'annual_leave':
                $holidayBalance = $employee->holiday_balance ?? 0; // in hours
                if ($totalHours > $holidayBalance) {
                    $holidayHours = $holidayBalance;
                    $unpaidHours  = $totalHours - $holidayBalance;
                    $paid = $holidayBalance > 0;
                } else {
                    $holidayHours = $totalHours;
                    $paid = $holidayHours > 0;
                }
                break;

            case 'unpaid_leave':
                $unpaidHours = $totalHours;
                $paid = false;
                break;

            case 'other_leave':
                $paid = $request->paid ?? false;
                if ($paid) {
                    $holidayHours = $totalHours;
                } else {
                    $unpaidHours = $totalHours;
                }
                break;
        }

        $leave = LeaveRequest::create([
            'user_id'          => $user->id,
            'employee_id'      => $employee->id,
            'shift_id'         => $request->shift_id,
            'start_date'       => $start, // datetime
            'end_date'         => $end,   // datetime
            'reason'           => $request->reason,
            'type'             => $request->type,
            'hours'            => $totalHours,
            'approved_hours'   => $totalHours - $unpaidHours,
            'paid'             => $paid,
            'ssp_paid_days'    => $sspPaidDays,
            'holiday_days_used' => $holidayHours,
            'unpaid_days'      => $unpaidHours / $hoursPerDay,
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
                'hours'            => $l->hours,
                'reject_reason'    => $l->reject_reason,
                'approved_hours'   => $l->approved_hours,
                'paid'             => $l->paid,
                'ssp_paid_days'    => $l->ssp_paid_days,
                'holiday_days_used' => $l->holiday_days_used,
                'unpaid_days'      => $l->unpaid_days,
                'amount_paid'      => $l->amount_paid,
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
                Storage::disk('public')->put("patrols/media/{$filename}", base64_decode($base64));
                $scan->media()->create(['file_path' => "patrols/media/{$filename}"]);
            }
        }

        return response()->json(['message' => 'Checkpoint scanned']);
    }

    public function startPatrol($patrol_id)
    {
        $patrol = Patrol::findOrFail($patrol_id);

        $now = Carbon::now(); // current server time
        $patrolStart = Carbon::parse($patrol->start_time);

        // Guard cannot start before scheduled time
        if ($now->lt($patrolStart)) {
            return response()->json([
                'message' => 'You cannot start the patrol before its scheduled start time at ' . $patrolStart->format('H:i')
            ], 403);
        }

        // Optional: prevent restarting an already started or completed patrol
        if (in_array($patrol->status, ['in_progress', 'completed'])) {
            return response()->json([
                'message' => 'Patrol has already started or completed.'
            ], 403);
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
            $patrol->status = 'missed';
            $patrol->save();
            return response()->json([
                'message' => 'Patrol completion time exceeded. You cannot complete after 50 minutes.'
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
            'patrol_id' => $patrol?->id ?? null,
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

        $weeklyLimit = 40;
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
        $usedHours = LeaveRequest::where('employee_id', $employee->id)
            ->whereIn('status', ['approved', 'completed'])
            ->sum('approved_hours');

        // Available hours cannot be negative
        $availableHours = max($totalHolidayHours - $usedHours, 0);

        // Unpaid hours (if a leave requested more than balance)
        $unpaidHours = LeaveRequest::where('employee_id', $employee->id)
            ->whereIn('status', ['approved', 'completed'])
            ->sum(\DB::raw('hours - approved_hours'));

        return response()->json([
            'user_id'     => $user->id,
            'total_hours'     => $totalHolidayHours,
            'used_hours'      => $usedHours,
            'available_hours' => $availableHours,
            'unpaid_hours'    => max($unpaidHours, 0),
        ]);
    }

    public function calendar()
    {
        $userId = Auth::id();

        // Fetch shifts for this user
        $shifts = ShiftDate::where('staff_id', $userId)
            ->orderBy('shift_date')
            ->get(['id', 'shift_date', 'start_time', 'end_time']);

        // Transform to calendar format
        $calendarShifts = $shifts->map(function ($shift) {
            $startDateTime = \Carbon\Carbon::parse($shift->shift_date . ' ' . $shift->start_time)->toIso8601String();
            $endDateTime   = \Carbon\Carbon::parse($shift->shift_date . ' ' . $shift->end_time)->toIso8601String();

            // Handle overnight shifts (end_time < start_time)
            if (\Carbon\Carbon::parse($shift->end_time)->lt(\Carbon\Carbon::parse($shift->start_time))) {
                $endDateTime = \Carbon\Carbon::parse($shift->shift_date)
                    ->addDay()
                    ->setTimeFromTimeString($shift->end_time)
                    ->toIso8601String();
            }

            return [
                'id'        => $shift->id,
                'startTime' => $startDateTime,
                'endTime'   => $endDateTime,
            ];
        });

        return response()->json($calendarShifts);
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
