<?php

namespace App\Http\Controllers\API;

use Notify;
use Carbon\Carbon;
use App\Models\Site;
use App\Models\Shift;
use App\Models\Patrol;
use App\Models\Employee;
use App\Models\ShiftDate;
use App\Models\BookingAlarm;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\ShiftBooking;
use Illuminate\Http\Request;
use App\Models\PatrolCheckpoint;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ShiftApiController extends Controller
{
    // 10. Get Upcoming Shifts
    public function getUpcomingShifts(Request $request)
    {
        // $employee = Employee::where('user_id', Auth::id())->first();

        $limit = $request->query('limit', 10);

        $shiftDates = ShiftDate::with('shift')
            ->where('staff_id', Auth::id())
            ->where(function ($query) {
                $query->where('shift_date', '>=', now()->toDateString())
                    ->orWhere('status', 'booked_on');
            })
            ->orderBy('shift_date')
            ->paginate($limit);


        $transformed = $shiftDates->getCollection()->transform(function ($shiftDate) {
            $shift = Shift::findOrFail($shiftDate->shift_id);
            $site = $shift ? Site::find($shift->site_id) : null;

            return [
                'id' => $shiftDate->id,
                'shift_id' => $shiftDate->shift_id,
                'site_id' => $shiftDate->shift->site_id,
                'site_name' => $site?->site_name,
                'site_address' => optional($shiftDate->shift->site)->address,
                'start_time' => $shiftDate->start_time,
                'end_time' => $shiftDate->end_time,
                'shift_date' => $shiftDate->shift_date,
                'duties' => optional($shiftDate->shift)->duties,
                'supervisor_name' => $shiftDate->shift->supervisor_name,
                'supervisor_contact' => $shiftDate->shift->supervisor_contact,
                'status' => $shiftDate->status,
                'briefing_pdf' => optional($shiftDate->shift)->briefing_pdf_url,
                'risk_assessment_pdf' => optional($shiftDate->shift)->risk_assessment_pdf_url,
            ];
        });

        return response()->json([
            'shift_dates' => $transformed,
            'pagination' => [
                'current_page' => $shiftDates->currentPage(),
                'total_pages' => $shiftDates->lastPage(),
                'total' => $shiftDates->total(),
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
            ->where('staff_id', $employee->id)
            ->first();

        if (!$shift) {
            return response()->json([
                'message' => 'Shift date (ID: ' . $shift_id . ') Not on your upcoming shifts list!',
            ]);
        }


        // check if shift status is dispatched
        if ($shift->is_assign == 1) {
            if ($request->response == 'accept') {
                $shift->status = 'accepted';
                $shift->is_assign = 2; //accept shift
                $shift->save();

                return response()->json([
                    'message' => 'Shift date Accepted successfully!',
                ]);
            } elseif ($request->response == 'decline') {
                $shift->status = 'declined';
                $shift->is_assign = 5; //reject shift
                // $shift->reason = $request->reason ?? null;
                $shift->save();
                return response()->json([
                    'message' => 'Shift date Declined successfully!',
                ]);
            }

            return response()->json([
                'message' => 'Unaccepted response (' . $request->response . ') You can only accept / decline',
            ]);
        }

        return response()->json([
            'message' => 'Could not change shift status ',
        ]);
    }

    // 12. Submit Leave Request
    public function submitLeaveRequest(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
            'type' => 'required|in:annual_leave,sick_leave,emergency',
        ]);

        $leave = LeaveRequest::create([
            'user_id' => Auth::id(),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
            'type' => $request->type,
            'status' => 'pending',
        ]);

        $employee = Employee::where('user_id', Auth::id())->first();
        Notify::toDashboard(
            auth::id(),
            'alert',
            'Leave Request',
            'Leave Request by ' . $employee->fore_name . ' ' . $employee->sur_name,
            "/leaves/$leave->id/view"
        );

        send_push_notification(
            $employee->user_id,
            'Leave request submitted',
            'You have submitted a leave request.',
            ['leave' => $leave],
        );


        return response()->json([
            'message' => 'Leave request submitted',
            'leave_id' => $leave->id,
        ]);
    }

    public function showLeaves()
    {
        $leaves = LeaveRequest::where('user_id', Auth::id())->get()->paginate(10);

        return response()->json([
            'leaves' => $leaves->map(fn($l) => [
                'id' => $l->id,
                'type' => $l->type,
                'status' => $l->status,
                'reason' => $l->reason,
                'start_date' => $l->start_date,
                'end_date' => $l->end_date,
                'location' => json_decode($l->location),
                'timestamp' => $l->timestamp,
                'created_at' => $l->created_at,
                'updated_at' => $l->updated_at,
            ]),
            'pagination' => [
                'current_page' => $leaves->currentPage(),
                'total_pages' => $leaves->lastPage(),
                'total' => $leaves->total(),
            ]
        ]);
    }

    // 13. Acknowledge Shift Documents
    public function acknowledgeDocuments(Request $request, $shift_id)
    {
        $request->validate([
            'risk_assessment_read' => 'required|boolean',
            'assignment_instructions_read' => 'required|boolean',
            'acknowledgment_timestamp' => 'required|date',
        ]);

        $employee = Employee::where('user_id', Auth::id())->first();

        $shift = Shift::where('id', $shift_id)
            ->where('staff_id', $employee->id)
            ->firstOrFail();

        $shift->update([
            'risk_assessment_read' => $request->risk_assessment_read,
            'assignment_instructions_read' => $request->assignment_instructions_read,
            'acknowledgment_timestamp' => $request->acknowledgment_timestamp,
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
            'timestamp' => 'required|date',
        ]);

        $user = Auth::user();
        $formattedTimestamp = Carbon::parse($request->timestamp)->format('Y-m-d H:i:s');

        // Correct: fetch by shiftdate primary key
        $shiftDate = ShiftDate::find($shiftDate_id);
        if (!$shiftDate) {
            return response()->json([
                'message' => 'Shift date (ID: ' . $shiftDate_id . ') Not on your upcoming shifts list!',
            ]);
        }

        if ($shiftDate->is_assign !== 2) {
            return response()->json([
                'message' => 'Shift date (ID: ' . $shiftDate_id . ') not accepted, You can not book on or off a shift untill it is accepted!',
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

        // Check if user already booked on
        $existingBooking = ShiftBooking::where('user_id', $user->id)
            ->where('type', 'book_on')
            ->first();

        if ($existingBooking) {
            return response()->json([
                'message' => 'You already have a booked on shift (ShiftDate ID: ' . $existingBooking->shift_id . ').'
            ], 409);
        }

        // Correct: find by ShiftDate ID
        $shiftDate = ShiftDate::find($shiftDate_id);
        if (!$shiftDate) {
            return response()->json([
                'message' => 'Trying to book on unavailable shift (ShiftDate ID: ' . $shiftDate_id . ').'
            ], 409);
        }

        if ($shiftDate->is_assign == 3) {
            return response()->json([
                'message' => 'Shift date (ID: ' . $shiftDate_id . ') has been already booked on',
            ]);
        }

        if ($shiftDate->is_assign !== 2) {
            return response()->json([
                'message' => 'Shift date (ID: ' . $shiftDate_id . ') not accepted, You can not book on or off a shift untill it is accepted!',
            ]);
        }

        if (!$shiftDate) {
            return response()->json([
                'message' => 'Shift date (ID: ' . $shiftDate_id . ') Not on your upcoming shifts list!',
            ]);
        }

        if ($shiftDate->is_assign == 2) {
            // Update status
            $shiftDate->status = 'booked_on';
            $shiftDate->is_assign = 3; //shift started
            $shiftDate->save();
        }


        // Notifications
        Notification::create([
            'user_id' => 1,
            'employee_id' => null,
            'type' => 'alert',
            'title' => 'Shift booked on',
            'message' => 'Guard ' . $user->first_name . ' ' . $user->last_name . ' Booked on shift (ID: ' . $shiftDate->id . ' starting at ' . $shiftDate->start_time,
            'read' => false,
            'action_url' => "/shift-dates/$shiftDate_id/view"
        ]);

        Notification::create([
            'user_id' => $user->id,
            'employee_id' => auth::id(),
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

        if ($shiftDate) {
            $shiftDate->status = 'booked_off';
            $shiftDate->is_assign = 4; //shift ended
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
        $shift = ShiftDate::with('patrols.checkpoints')->findOrFail($shift_id);

        $patrols = $shift->patrols->map(function ($patrol) {
            return [
                'id' => $patrol->id,
                'name' => $patrol->name,
                'checkpoints' => $patrol->checkpoints->map(function ($checkpoint) {
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

    public function completePatrol(Request $request, $patrol_id)
    {
        $request->validate([
            'summary' => 'required|string',
            'total_checkpoints' => 'required|integer',
            'completed_checkpoints' => 'required|integer',
            'issues_reported' => 'required|integer',
        ]);

        $patrol = Patrol::findOrFail($patrol_id);

        $patrol->update([
            'summary' => $request->summary,
            'total_checkpoints' => $request->total_checkpoints,
            'completed_checkpoints' => $request->completed_checkpoints,
            'issues_reported' => $request->issues_reported,
            'completed_at' => now(),
        ]);

        return response()->json(['message' => 'Patrol marked as completed']);
    }

    // check if guard is on duty
    public function checkDutyStatus(Request $request)
    {
        $user = Auth::user();

        // Get the latest shift bookings
        $latestBooking = ShiftBooking::where('user_id', $user->id)
            ->latest('created_at')
            ->first();

        if (!$latestBooking) {
            return response()->json([
                'status' => 'off-duty',
                'shift_date_id' => null,
                'shift_id' => null,
                'message' => 'No shift bookings found.'
            ]);
        }

        // Determine status based on latest booking type
        $status = $latestBooking->type === 'book_on' ? 'on-duty' : 'off-duty';

        // Fetch shift info if available
        $shiftDate = ShiftDate::find($latestBooking->shift_id);
        $shift = $shiftDate ? Shift::find($shiftDate->shift_id) : null;

        $response = [
            'status' => $status,
            'shift_date_id' => $shiftDate?->id,
            'shift_id' => $shift?->id,
            'booked_at' => $latestBooking->created_at,
        ];

        return response()->json($response);
    }
}
