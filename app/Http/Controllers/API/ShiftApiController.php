<?php

namespace App\Http\Controllers\API;

use Notify;
use App\Models\Shift;
use App\Models\Patrol;
use App\Models\Employee;
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
        $user = Auth::user();

        $limit = $request->query('limit', 10);

        $shifts = Shift::with('site')
            ->where('staff_id', $user->id)
            ->where('start_shift', '>=', now()->toDateString())
            ->orderBy('start_shift')
            ->paginate($limit);

        $transformed = $shifts->getCollection()->transform(function ($shift) {
            return [
                'id' => $shift->id,
                'site_id' => $shift->site_id,
                'site_name' => optional($shift->site)->name,
                'site_address' => optional($shift->site)->address,
                'start_time' => $shift->start_time,
                'end_time' => $shift->end_time,
                'duties' => $shift->duties,
                'supervisor_name' => $shift->supervisor_name,
                'supervisor_contact' => $shift->supervisor_contact,
                'status' => $shift->status,
                'briefing_pdf' => $shift->briefing_pdf_url,
                'risk_assessment_pdf' => $shift->risk_assessment_pdf_url,
            ];
        });

        return response()->json([
            'shifts' => $transformed,
            'pagination' => [
                'current_page' => $shifts->currentPage(),
                'total_pages' => $shifts->lastPage(),
                'total' => $shifts->total(),
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

        $shift = Shift::where('id', $shift_id)
            ->where('staff_id', Auth::id())
            ->firstOrFail();

        $shift->status = $request->response === 'accept' ? 'accepted' : 'declined';
        $shift->decline_reason = $request->reason ?? null;
        $shift->save();

        return response()->json([
            'message' => 'Shift ' . $shift->status,
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

        $employee = Employee::find(Auth::id());
        Notify::toDashboard(
            $employee->id,
            'alert',
            'Leave Request',
            'Leave Request by ' . $employee->fore_name . ' ' . $employee->sur_name,
        );


        return response()->json([
            'message' => 'Leave request submitted',
            'leave_id' => $leave->id,
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

        $shift = Shift::where('id', $shift_id)
            ->where('staff_id', Auth::id())
            ->firstOrFail();

        $shift->update([
            'risk_assessment_read' => $request->risk_assessment_read,
            'assignment_instructions_read' => $request->assignment_instructions_read,
            'acknowledgment_timestamp' => $request->acknowledgment_timestamp,
        ]);

        return response()->json(['message' => 'Documents acknowledged']);
    }

    public function bookOnOff(Request $request, $shift_id, $type)
    {
        $request->validate([
            'face_verification_result' => 'required|string',
            'location.latitude' => 'required|numeric',
            'location.longitude' => 'required|numeric',
            'location.address' => 'required|string',
            'timestamp' => 'required|date',
        ]);

        $user = Auth::user();

        $booking = ShiftBooking::create([
            'user_id' => $user->id,
            'shift_id' => $shift_id,
            'type' => $type,
            'face_verification_result' => $request->face_verification_result,
            'latitude' => $request->location['latitude'],
            'longitude' => $request->location['longitude'],
            'address' => $request->location['address'],
            'timestamp' => $request->timestamp,
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

    public function bookOn(Request $request, $shift_id)
    {
        Notification::create([
            'user_id' => Auth::id(),
            'employee_id' => $request->user()->id,
            'type' => 'alert',
            'title' => 'Shift booked on',
            'message' => 'by ' . Auth::user()->first_name . ' ' . Auth::user()->last_name,
            'read' => false,
        ]);
        return $this->bookOnOff($request, $shift_id, 'book_on');
    }

    public function bookOff(Request $request, $shift_id)
    {
        return $this->bookOnOff($request, $shift_id, 'book_off');
    }

    public function getPatrolRoutes($shift_id)
    {
        $shift = Shift::with('patrols.checkpoints')->findOrFail($shift_id);

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
        $checkpoint = PatrolCheckpoint::findOrFail($checkpoint_id);

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
}
