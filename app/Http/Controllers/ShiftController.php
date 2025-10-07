<?php

namespace App\Http\Controllers;

use Notify;
use Carbon\Carbon;
use App\Models\Site;
use App\Models\User;
use App\Models\Shift;
use App\Models\Client;
use App\Models\Patrol;
use App\Helpers\Logger;
use App\Models\Employee;
use App\Models\Location;
use Carbon\CarbonPeriod;
use App\Models\CheckCall;
use App\Models\ShiftDate;
use App\Models\ShiftNote;
use App\Models\EmployeeTerm;
use App\Models\EmployeeType;
use App\Models\LeaveRequest;
use App\Models\ShiftBooking;
use Illuminate\Http\Request;
use App\Models\Subcontractor;
use Illuminate\Support\Facades\DB;
use App\DataTables\ShiftsDataTable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ShiftController extends Controller
{


    public function unassign(Request $request, $id)
    {
        $shiftDate = ShiftDate::findOrFail($id);
        $shiftDate->staff_id = null; // Remove assigned staff
        $shiftDate->is_assign=0;
        $shiftDate->save();

        return response()->json(['success' => true]);
    }

   public function generateContinuousPath($userId = 3102, $patrolId = 1277, $shiftDateId = 331, $points = 10)
{
    // Starting point (random within bounding box)
    $latMin = 37.782;
    $latMax = 37.790;
    $lngMin = -122.447;
    $lngMax = -122.435;

    // Delete existing points for this patrol
    Location::where('patrol_id', $patrolId)->delete();

    $data = [];
    $timestamp = Carbon::now()->subMinutes($points); // start time

    // Starting coordinates
    $latitude = mt_rand($latMin * 1000000, $latMax * 1000000) / 1000000;
    $longitude = mt_rand($lngMin * 1000000, $lngMax * 1000000) / 1000000;

    for ($i = 0; $i < $points; $i++) {
        // Each next point is very close to previous (~10–50 meters)
        $latOffset = mt_rand(-50, 50) / 1000000;
        $lngOffset = mt_rand(-50, 50) / 1000000;

        $latitude += $latOffset;
        $longitude += $lngOffset;

        // Clamp coordinates within bounding box
        $latitude = max(min($latitude, $latMax), $latMin);
        $longitude = max(min($longitude, $lngMax), $lngMin);

        $accuracy = mt_rand(1, 20); // realistic accuracy
        $onDuty = 1;
        $timestamp->addSeconds(mt_rand(30, 60)); // gradual timestamp

        $data[] = [
            'user_id' => $userId,
            'patrol_id' => $patrolId,
            'shiftdate_id' => $shiftDateId,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
            'timestamp' => $timestamp->toDateTimeString(),
            'on_duty' => $onDuty,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    Location::insert($data);

    return "Inserted $points continuous heatmap points forming a smooth path!";
}

    public function index(ShiftsDataTable $dataTable)
    {
        $clients = User::role('client')->orderBy('first_name', 'asc')->get();
        $sites = Site::orderBy('site_name', 'asc')->get();
        $staffs = User::role('security_staff')->get();
        $subcontractors = User::role('subcontractor')->get();
        $users = User::all();
        $services = EmployeeType::all();
        return $dataTable->render('security_boards.shifts', compact('clients', 'sites', 'staffs', 'subcontractors', 'users', 'services'));
    }
    public function scheduling()
    {
        // $shifts = Shift::all();
        $clients = User::role('client')->get();
        $sites = Site::all();
        $staffs = User::role('security_staff')->get();
        $subcontractors = User::role('subcontractor')->get();
        // $users = User::all();
        $services = EmployeeType::all();
        return view('security_boards.scheduling', compact('sites', 'staffs', 'clients', 'services', 'subcontractors'));
    }
    public function worker_calendar()
    {
        $shifts = Shift::all();
        $clients = User::role('client')->get();
        $sites = Site::all();
        $staffs = User::role('security_staff')->get();
        $subcontractors = User::role('subcontractor')->get();
        $users = User::all();
        $services = EmployeeType::all();
        return view('security_boards.worker_calendar', compact('shifts', 'clients', 'sites', 'staffs', 'subcontractors', 'users', 'services'));
    }
    public function site_calendar()
    {
        $shifts = Shift::all();
        $clients = User::role('client')->get();
        $sites = Site::all();
        $staffs = User::role('security_staff')->get();
        $subcontractors = User::role('subcontractor')->get();
        $users = User::all();
        $services = EmployeeType::all();
        return view('security_boards.site_calendar', compact('shifts', 'clients', 'sites', 'staffs', 'subcontractors', 'users', 'services'));
    }
    public function today_rota()
    {
        $shifts = Shift::all();
        $clients = User::role('client')->get();
        $sites = Site::all();
        $staffs = User::role('security_staff')->get();
        $subcontractors = User::role('subcontractor')->get();
        $users = User::all();
        $services = EmployeeType::all();
        return view('security_boards.today_rota', compact('shifts', 'clients', 'sites', 'staffs', 'subcontractors', 'users', 'services'));
    }

    public function show(ShiftDate $shiftDate)
    {
        $shiftDate->load(['staff', 'shift.client', 'shift.site', 'shift.staff', 'logs', 'checkCalls']);
        return $this->sendRes('success', ['view_data' => view('security_boards.shift-detail-modal', compact('shiftDate'))->render()]);
    }

    public function store(Request $request)
    {
        $shiftCount = count($request->client_id);

        $documents = [
            'sia_licence_file'           => 'SIA Licence File',
            'passport_file'              => 'Passport File',
            'proof_of_address_file'      => 'Proof of Address File',
            'ni_letter_file'             => 'NI Letter File',
            'first_aid_certificate_file' => 'First Aid Certificate File',
            'act_certificate_file'       => 'ACT Certificate File',
        ];

        $shiftsWorkingHours = 0;
        for ($i = 0; $i < $shiftCount; $i++) {
            $validator = Validator::make([
                'client_id' => $request->client_id[$i],
                'site_id' => $request->site_id[$i],
                'company_id' => $request->company_id[$i] ?? null,
                'staff_id' => $request->staff_id[$i] ?? null,
                'training_id' => $request->training_id ?? [],
                'start_shift' => $request->start_shift[$i],
                'end_shift' => $request->end_shift[$i],
                'break-mins_shift' => $request->{'break-mins_shift'}[$i] ?? null,
                'number_shift' => $request->number_shift[$i] ?? null,
                'site_rate' => $request->site_rate[$i] ?? null,
                'service_type_1' => $request->service_type_1[$i] ?? null,
                'service_type_2' => $request->service_type_2[$i] ?? null,
                'subcontractor_id' => $request->subcontractor_id[$i] ?? null,
                'from_shift' => $request->from_shift[$i] ?? null,
                'to_shift' => $request->to_shift[$i] ?? null,
                'comments' => $request->comments[$i] ?? null,
                'days' => $request->days[$i] ?? "Mon,Tue,Wed,Thu,Fri,Sat,Sun",
                'employee_rate' => $request->employee_rate[$i] ?? null,
                'start' => $request->start[$i] ?? null,
                'end' => $request->end[$i] ?? null,
                'po_number' => $request->po_number[$i] ?? null,
                'lost_time' => $request->lost_time[$i] ?? null,
                'po_rate' => $request->po_rate[$i] ?? null,
                'manager_1_id' => $request->manager_1_id[$i] ?? null,
                'manager_2_id' => $request->manager_2_id[$i] ?? null,
                'restrict_start_time' => $request->restrict_start_time[$i] ?? null,
                'enforce_picture_check' => $request->enforce_picture_check[$i] ?? null,
                'restrict_location_check' => $request->restrict_location_check[$i] ?? null,
                'checkpoints' => $request->checkpoints[$i] ?? null,
            ], [
                'client_id' => 'required|integer',
                'site_id' => 'required|integer',
                'company_id' => 'nullable',
                'staff_id' => 'nullable|integer',
                'start_shift' => 'required|date_format:H:i',
                'end_shift' => 'required|date_format:H:i',
                'break-mins_shift' => 'nullable',
                'number_shift' => 'nullable|integer|min:0',
                'site_rate' => 'nullable|numeric',
                'service_type_1' => 'nullable',
                'service_type_2' => 'nullable',
                'subcontractor_id' => 'nullable',
                'from_shift' => 'required|date',
                'to_shift' => 'required|date|after_or_equal:from_shift',
                'comments' => 'nullable|string|max:1000',
                'days' => 'nullable|string',
                'employee_rate' => 'nullable|numeric',
                'start' => 'nullable|date_format:H:i',
                'end' => 'nullable|date_format:H:i',
                'po_number' => 'nullable',
                'lost_time' => 'nullable',
                'po_rate' => 'nullable|numeric',
                'manager_1_id' => 'nullable|integer',
                'manager_2_id' => 'nullable|integer',
                'restrict_start_time' => 'nullable',
                'enforce_picture_check' => 'nullable',
                'restrict_location_check' => 'nullable',
                'training_id' => 'nullable|array',
                'training_id.*' => 'exists:training_materials,id',
            ]);


            $validator->after(function ($validator) use ($request, $i, &$shiftsWorkingHours, $documents) {
                $start = $request->start_shift[$i] ?? null;
                $end = $request->end_shift[$i] ?? null;
                $from = $request->from_shift[$i] ?? null;
                $to = $request->to_shift[$i] ?? null;
                $breakMinutes = $request->{'break-mins_shift'}[$i] ?? null;
                $dayString = $request->days[$i] ?? 'Mon,Tue,Wed,Thu,Fri,Sat,Sun';

                // ✅ Validate time logic only if both times are present and correctly formatted
                if ($start && $end && preg_match('/^\d{2}:\d{2}$/', $start) && preg_match('/^\d{2}:\d{2}$/', $end)) {
                    $startTime = \Carbon\Carbon::createFromFormat('H:i', $start);
                    $endTime = \Carbon\Carbon::createFromFormat('H:i', $end);

                    if ($startTime->eq($endTime)) {
                        $validator->errors()->add("end_shift", "End time must not be the same as start time.");
                    } else {
                        // Calculate duration in minutes (handling overnight shifts)
                        $duration = $endTime->diffInMinutes($startTime, false);

                        if ($duration <= 0) {
                            // If negative or zero, assume next day
                            $duration += 1440; // Add 24 hours in minutes
                        }

                        if ($duration < 60) {
                            $validator->errors()->add("end_shift", "Shift duration must be at least 1 hour.");
                        }
                    }
                }


                // ✅ Check overlapping shift logic only if staff ID and dates exist
                $staffId = $request->staff_id[$i] ?? null;
                if ($staffId && $start && $end && $from && $to) {
                    try {
                        $startTime = \Carbon\Carbon::createFromFormat('H:i', $start);
                        $endTime = \Carbon\Carbon::createFromFormat('H:i', $end);
                        $fromDate = \Carbon\Carbon::parse($from);
                        $toDate = \Carbon\Carbon::parse($to);

                        $overlappingShift = \App\Models\Shift::where('staff_id', $staffId)
                            ->where(function ($query) use ($fromDate, $toDate) {
                                $query->whereBetween('from_shift', [$fromDate, $toDate])
                                    ->orWhereBetween('to_shift', [$fromDate, $toDate]);
                            })
                            ->where(function ($query) use ($startTime, $endTime) {
                                $query->where(function ($q) use ($startTime, $endTime) {
                                    $q->where('start_shift', '<', $endTime)
                                        ->where('end_shift', '>', $startTime);
                                });
                            })
                            ->exists();

                        if ($overlappingShift) {
                            $validator->errors()->add("staff_id", "This staff already has a shift during the selected time range.");
                        }
                    } catch (\Exception $e) {
                        // Optionally log or silently ignore if date parsing fails
                    }
                }

                // ✅ Check SIA license expiry only if staff exists
                if ($staffId) {
                    $staff = \App\Models\Employee::where('user_id', $staffId)->first();
                    $selectedDays = array_map('trim', explode(',', $dayString));
                    $newShiftHours = 0;

                    try {
                        $newShiftHours = $this->calculateTotalWorkingHours(
                            $staffId,
                            $from,
                            $to,
                            $start,
                            $end,
                            $breakMinutes,
                            $selectedDays
                        );
                    } catch (\Exception $e) {
                        $validator->errors()->add('staff_id', $e->getMessage());
                    }

                    // Ensure $from and $start are valid
                    if (!empty($from) && !empty($start)) {
                        $shiftDateCarbon = \Carbon\Carbon::parse($from); // just the date part
                        $newShiftStart   = \Carbon\Carbon::parse($from . ' ' . $start); // full datetime

                        // Get week start and end for that shift date
                        $weekStart = $shiftDateCarbon->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
                        $weekEnd   = $shiftDateCarbon->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);

                        // Fetch existing shifts for this staff in the same week
                        $totalWeekHours = \App\Models\ShiftDate::where('staff_id', $staffId)
                            ->whereBetween('shift_date', [$weekStart, $weekEnd])
                            ->sum('total_hours');

                        // Apply restrictions safely
                        if (!$staff) {
                            $validator->errors()->add('staff_id', 'Selected staff does not exist.');
                        } else {
                            applyRestrictions(
                                $staff,
                                $validator,
                                'staff_id',
                                $newShiftHours,
                                $shiftDateCarbon,
                                $newShiftStart
                            );
                        }
                    } else {
                        $validator->errors()->add('staff_id', 'Invalid shift date or start time for restriction check.');
                    }
                }
            });


            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors(), 'index' => $i], 422);
            }

            $data = $validator->validated();

            $data['restrict_start_time'] = $data['restrict_start_time'] ? 1 : 0;
            $data['enforce_picture_check'] = $data['enforce_picture_check'] ? 1 : 0;
            $data['restrict_location_check'] = $data['restrict_location_check'] ? 1 : 0;
            $data['days'] = json_encode([str_replace(['"', '[', ']'], '', $data['days'])]);

            if (!empty($data['staff_id'])) {
                $data['is_assign'] = 1;
            }

            $serviceType1 = DB::table('employee_types')->where('id', $request->service_type_1[$i])->first();
            $serviceType2 = DB::table('employee_types')->where('id', $request->service_type_2[$i])->first();

            $shift = Shift::create([
                'client_id'   => $request->client_id[$i],
                'site_id'     => $request->site_id[$i],
                'staff_id'    => $request->staff_id[$i] ?? null,
                'start_shift' => $request->start_shift[$i],
                'end_shift'   => $request->end_shift[$i],
                'service_type_1'   => $serviceType1?->name,
                'service_type_2'   => $serviceType2?->name,
            ]);

            $dayString = $request->days[$i] ?? 'Mon,Tue,Wed,Thu,Fri,Sat,Sun';
            $selectedDays = array_map('trim', explode(',', $dayString));

            // Convert full day names (Monday) → short (Mon)
            $selectedDays = array_map(function ($day) {
                return ucfirst(substr($day, 0, 3));
            }, $selectedDays);

            $fromDate = Carbon::parse($request->from_shift[$i]);
            $toDate   = Carbon::parse($request->to_shift[$i]);
            $period   = CarbonPeriod::create($fromDate, $toDate);

            foreach ($period as $date) {
                if (in_array($date->format('D'), $selectedDays)) {

                    if (!empty($data['staff_id'])) {
                        $shiftStart = Carbon::parse($date->format('Y-m-d') . ' ' . $data['start_shift']);
                        $shiftEnd   = Carbon::parse($date->format('Y-m-d') . ' ' . $data['end_shift']);

                        $leave = LeaveRequest::where('user_id', $data['staff_id'])
                            ->where('status', 'approved')
                            ->where(function ($query) use ($shiftStart, $shiftEnd) {
                                $query->where(function ($q) use ($shiftStart, $shiftEnd) {
                                    $q->where('start_date', '<=', $shiftEnd)
                                        ->where('end_date', '>=', $shiftStart);
                                });
                            })
                            ->first();

                        if ($leave) {
                            return response()->json([
                                'error' => "Staff has an approved leave from "
                                    . \Carbon\Carbon::parse($leave->start_date)->format('Y-m-d H:i')
                                    . " to "
                                    . \Carbon\Carbon::parse($leave->end_date)->format('Y-m-d H:i')
                                    . " on "
                                    . \Carbon\Carbon::parse($date)->format('Y-m-d') . "."
                            ], 422);
                        }
                    }

                    // Create ShiftDate
                    $shiftDate = ShiftDate::create([
                        'shift_id'    => $shift->id,
                        'staff_id'    => $shift->staff_id ?? null,
                        'shift_date'  => $date->format('Y-m-d'),
                        'start_time'  => $request->start_shift[$i],
                        'end_time'    => $request->end_shift[$i],
                        'is_assign'   => !empty($shift->staff_id) ? 1 : 0,
                        'break_time'  => $request->{'break-mins_shift'}[$i] ?? null,
                        'total_hours' => $this->calculateTotalHours(
                            $request->start_shift[$i],
                            $request->end_shift[$i]
                        ),
                    ]);

                    if ($shift->staff_id) {
                        $weekStart = now()->startOfWeek();
                        $weekEnd   = now()->endOfWeek();

                        $totalWeekHours = \App\Models\ShiftDate::where('staff_id', $shiftDate->staff_id)
                            ->whereBetween('shift_date', [$weekStart, $weekEnd])
                            ->sum('total_hours');

                        $minWeeklyHours = $entity->hour_per_week ?? 40;

                        $expectedHours = $totalWeekHours + $shiftDate->total_hours;

                        $staff = User::find($shift->staff_id);
                        if ($expectedHours < $minWeeklyHours) {
                            // 👇 Instead of blocking, just trigger a notification
                            Notify::toDashboard(
                                null,
                                'alert',
                                'Worked Hours',
                                "Guard {$staff->first_name} {$staff->last_name} has only {$expectedHours} hours scheduled this week. Minimum is {$minWeeklyHours}.",
                                "#"
                            );
                        }
                    }

                    if (!empty($data['training_id'])) {
                        $shiftDate->trainings()->sync($data['training_id']);
                    }

                    $startTime = Carbon::createFromFormat('H:i', $data['start_shift']);
                    $endTime   = Carbon::createFromFormat('H:i', $data['end_shift']);

                    // Convert to minutes since midnight
                    $startMinutes = $startTime->hour * 60 + $startTime->minute;
                    $endMinutes   = $endTime->hour * 60 + $endTime->minute;

                    // Calculate duration in minutes, handle overnight automatically
                    $durationMinutes = ($endMinutes - $startMinutes + 1440) % 1440;

                    // if shift is exactly 24 hours, make it 1440
                    if ($durationMinutes == 0) {
                        $durationMinutes = 1440;
                    }

                    $numberOfCheckCalls = ceil($durationMinutes / 60);
                    // dd($durationMinutes, $numberOfCheckCalls);

                    $start = Carbon::createFromFormat('H:i', $data['start_shift']); // string -> Carbon

                    $site = Site::with('checkpoints')->find($shift->site_id);

                    $totalCheckpoints = $site->checkpoints->count() ?? 0;

                    for ($n = 0; $n < (int) $numberOfCheckCalls; $n++) {
                        $checkTime  = $start->copy()->addHours($n);
                        $patrolTime = $start->copy()->addHours($n);

                        if (request()->has('auto_checkcall_enabled') && request('auto_checkcall_enabled')) {
                            CheckCall::create([
                                'shift_id'       => $shiftDate->id,
                                'employee_id'       => $shiftDate->staff_id ?? null,
                                'name'           => 'Auto CheckCall ' . ($n + 1),
                                'scheduled_time' => $checkTime->format('Y-m-d H:i'),
                                'status'         => 'pending',
                            ]);
                        }

                        Patrol::create([
                            'shift_id'              => $shiftDate->id,
                            'name'                  => 'Auto Patrol ' . ($n + 1),
                            'summary'               => 'Scheduled patrol at ' . $patrolTime->format('H:i'),
                            'start_time'            => $patrolTime->format('Y-m-d H:i'),
                            'status'                => 'pending',
                            'total_checkpoints'     => $totalCheckpoints,
                            'completed_checkpoints' => 0,
                            'issues_reported'       => 0,
                            'completed_at'          => null,
                        ]);
                    }

                    // Manully added checkcalls
                    if ($request->has('checkcalls') && is_array($request->checkcalls)) {
                        foreach ($request->checkcalls as $checkcall) {
                            if (!empty($checkcall['name']) && !empty($checkcall['scheduled_time'])) {
                                CheckCall::create([
                                    'shift_id'       => $shiftDate->id,
                                    'name'           => $checkcall['name'],
                                    'scheduled_time' => $date->format('Y-m-d') . ' ' . $checkcall['scheduled_time'],
                                    'status'         => 'pending',
                                ]);
                            }
                        }
                    }
                }
                Logger::log(Auth::user(), 'Create', 'A Shift for site ' . $shift->site->site_name . ' Starting at: ' . $shiftDate->start_time . ' On ' . $shiftDate->date);
            }
        }

        return response()->json([
            'message' => 'Shifts created successfully!',
            'redirect_url' => route('shiftDates.view', [
                'shiftDate' => $shiftDate->id,   // must match the {shiftDate} route param
            ])
        ]);
    }

    public function edit($id)
    {
        $shift = ShiftDate::find($id);
        // $shift = Shift::with('client', 'site', 'staff')->find($shiftDate->shift_id);

        return response()->json(['shift' => $shift]);
    }

    public function update(Request $request, $id)
    {
        $shift = ShiftDate::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status_id'   => 'nullable|integer',
            'staff_id'    => 'nullable|integer',
            'start_shift' => 'required',
            'end_shift'   => 'required',
            'book_on'     => 'nullable',
            'book_off'    => 'nullable',
            'shift_date'  => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['absentee_start_time'] = $data['book_on'] ?? null;
        $data['absentee_end_time']   = $data['book_off'] ?? null;
        $data['start_time']          = $data['start_shift'];
        $data['end_time']            = $data['end_shift'];

        // Normalize time format
        if (strlen($data['start_shift']) === 5) {
            $data['start_shift'] .= ':00';
        }
        if (strlen($data['end_shift']) === 5) {
            $data['end_shift'] .= ':00';
        }

        // Calculate total hours
        $data['total_hours'] = $this->calculateTotalHours(
            $data['start_shift'],
            $data['end_shift'],
            'H:i:s'
        );

        $data['is_assign'] = $data['status_id'];
        $shift->status     = 'pending';

        // ✅ Restrictions only if assigning to a staff member
        if (!empty($data['staff_id'])) {
            $staffUser = Employee::where('user_id', $data['staff_id'])->first();
            if ($staffUser) {
                $staff = Employee::findOrFail($staffUser->id);


                $shiftDateValue = $data['shift_date'] ?? $shift->shift_date;
                $leave = LeaveRequest::where('user_id', $staff->user_id)
                    ->where('status', 'approved')
                    ->where('start_date', '<=', $shift->shift_date) // full datetime comparison
                    ->where('end_date', '>=', $shift->shift_date)   // full datetime comparison
                    ->first();

                if ($leave) {
                    return response()->json([
                        'errors' => [
                            'leave' => ['leave' => "Staff has an approved leave from "
                                . \Carbon\Carbon::parse($leave->start_date)->format('Y-m-d H:i')
                                . " to "
                                . \Carbon\Carbon::parse($leave->end_date)->format('Y-m-d H:i')]
                        ]
                    ], 422);
                }

                $newShiftHours = $data['total_hours'];

                // Build new shift start/end
                $shiftDate    = $data['shift_date'] ?? $shift->shift_date;
                $newStartTime = \Carbon\Carbon::parse($shiftDate . ' ' . $data['start_shift']);
                $newEndTime   = \Carbon\Carbon::parse($shiftDate . ' ' . $data['end_shift']);


                if ($newEndTime->lte($newStartTime)) {
                    $newEndTime->addDay();
                }

                // Apply restrictions (40hr, 20hr, 12hr rest, etc.)
                applyRestrictions(
                    $staff,
                    $validator,
                    'staff_id',
                    $newShiftHours,
                    $shiftDate,     // string date
                    $newStartTime   // Carbon datetime
                );

                if ($validator->errors()->any()) {
                    return response()->json([
                        'errors' => $validator->errors()
                    ], 422);
                }

                // Overlapping check
                $overlap = ShiftDate::where('staff_id', $staff->user_id)
                    ->where('id', '!=', $shift->id) // ignore current shift
                    ->where(function ($query) use ($newStartTime, $newEndTime) {
                        $query->whereRaw('TIMESTAMP(shift_date, start_time) < ?', [$newEndTime])
                            ->whereRaw('TIMESTAMP(shift_date, end_time) > ?', [$newStartTime]);
                    })
                    ->exists();

                if ($overlap) {
                    return response()->json([
                        'error' => 'This staff already has a shift during this time.'
                    ], 422);
                }

                // Push notification for reassignment
                send_push_notification(
                    $staff->user_id,
                    'Shift reassigned',
                    'An admin reassigned a shift for you, You have to respond!',
                    ['shift' => $shift],
                );

                $weekStart = now()->startOfWeek();
                $weekEnd   = now()->endOfWeek();

                $totalWeekHours = \App\Models\ShiftDate::where('staff_id', $staff->id)
                    ->whereBetween('shift_date', [$weekStart, $weekEnd])
                    ->sum('total_hours');

                $minWeeklyHours = $entity->hour_per_week ?? 40;

                $expectedHours = $totalWeekHours + $newShiftHours;

                if ($expectedHours < $minWeeklyHours) {
                    // 👇 Instead of blocking, just trigger a notification
                    Notify::toDashboard(
                        null,
                        'alert',
                        'Worked Hours',
                        "Guard {$staff->fore_name} {$staff->sur_name} has only {$expectedHours} hours scheduled this week. Minimum is {$minWeeklyHours}.",
                        "#"
                    );
                }
            }
        }

        // ✅ Update shift
        $shift->update($data);

        return response()->json(['message' => 'Shift updated successfully']);
    }

    public function destroy($id)
    {
        $shiftDate = ShiftDate::findOrFail($id);
        $shiftDate->delete();

        return response()->json(['success' => true, 'message' => 'Shift deleted successfully']);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:shift_dates,id',
        ]);

        ShiftDate::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => 'Selected shifts deleted successfully.']);
    }

    public function storeBookon(Request $request)
    {
        $request->validate([
            'book_on_id' => 'required|exists:shift_dates,id',
            'absentee_start_time' => 'required|date_format:H:i',
        ]);

        $shiftDate = \App\Models\ShiftDate::find($request->input('book_on_id'));
        $time = $request->input('absentee_start_time') . ':00'; // append seconds

        if ($shiftDate->staff_id) {
            if ($shiftDate->is_assign == 2) {
                $shiftDate->absentee_start_time = $time;
                $shiftDate->status = 'booked_on';
                $shiftDate->is_assign = 3;
                $shiftDate->save();

                // Create shift booking
                ShiftBooking::create([
                    'user_id' => $shiftDate->staff_id,
                    'shift_id' => $shiftDate->id,
                    'type' => 'book_on',
                    'timestamp' => now(),
                    'face_verification_result' => 'not_required',
                ]);
                // Notify staff
                send_push_notification(
                    $shiftDate->staff_id,
                    'Shift assigned',
                    'An admin booked you ON for shift (ID: ' . $shiftDate->id . ') starting at ' . $shiftDate->start_time,
                    ['shiftDate' => $shiftDate],
                );
                return response()->json(['success' => 'Shift booked on successfully'], 200);
            }

            if ($shiftDate->is_assign == 3) {
                return response()->json([
                    'error' => 'The shift is already booked on!'
                ], 422);
            }
            if ($shiftDate->is_assign == 4) {
                return response()->json([
                    'error' => 'The shift is booked Off and ended! You cannot book it on!'
                ], 422);
            }

            if ($shiftDate->is_assign == 5) {
                return response()->json([
                    'error' => 'The shift is reject by guard, You cannot interact with it!'
                ], 422);
            }

            return response()->json([
                'error' => 'The shift is not accepted by staff, You cannot book it on.'
            ], 422);
        }

        if ($shiftDate->staff_id !== $request->book_on_id) {
            return response()->json([
                'error' => 'This staff is not assigned to the shift.'
            ], 422);
        }

        return response()->json([
            'error' => 'There is no staff assigned!.'
        ], 422);
    }

    public function storeBookoff(Request $request)
    {
        $request->validate([
            'book_off_id' => 'required|exists:shift_dates,id',
            'absentee_end_time' => 'required',
        ]);

        $shiftDate = \App\Models\ShiftDate::find($request->input('book_off_id'));

        if ($shiftDate->staff_id) {
            if ($shiftDate->is_assign == 3) {
                $shiftDate->absentee_end_time = $request->input('absentee_end_time');
                $shiftDate->status = 'booked_off';
                $shiftDate->is_assign = 4;
                $shiftDate->save();

                // Create shift booking
                ShiftBooking::create([
                    'user_id' => $shiftDate->staff_id,
                    'shift_id' => $shiftDate->id,
                    'type' => 'book_off',
                    'timestamp' => now(),
                    'face_verification_result' => 'not_required',
                ]);
                // Notify staff
                send_push_notification(
                    $shiftDate->staff_id,
                    'Shift assigned',
                    'An admin booked you OFF for shift (ID: ' . $shiftDate->id . ') ending at ' . $shiftDate->end_time,
                    ['shiftDate' => $shiftDate],
                );
                return response()->json(['success' => 'Shift booked off successfully'], 200);
            }
            if ($shiftDate->is_assign == 4) {
                return response()->json([
                    'error' => 'The shift is already booked Off!'
                ], 422);
            }
            if ($shiftDate->is_assign == 5) {
                return response()->json([
                    'error' => 'The shift is reject by guard, You cannot interact with it!'
                ], 422);
            }

            return response()->json([
                'error' => 'The shift is not Been booked ON by staff, You cannot book it off!'
            ], 422);
        }
        if ($shiftDate->staff_id !== $request->book_off_id) {
            return response()->json([
                'error' => 'This staff is not assigned to the shift.'
            ], 422);
        }

        return response()->json([
            'error' => 'There is no staff assigned!.'
        ], 422);
    }

    public function getShifts(Request $request)
    {
        $query = \App\Models\ShiftDate::with(['staff', 'shift.client', 'shift.site', 'shift.staff']);

        $from_shift = $request->from_shift;
        $to_shift = $request->to_shift;

        $query->whereHas('shift', function ($q) use ($request) {
            if ($request->filled('site')) {
                $q->where('site_id', $request->site);
            }

            if ($request->filled('staff')) {
                $q->where('staff_id', $request->staff);
            }

            if ($request->filled('client_id')) {
                $q->where('client_id', $request->client_id);
            }
        });

        if ($request->filled('status')) {
            $query->where('is_assign', $request->status);
        }

        if ($request->filled('start_time')) {
            $query->whereTime('start_time', '>=', $request->start_time);
        }

        if ($request->filled('end_time')) {
            $query->whereTime('end_time', '<=', $request->end_time);
        }

        if ($request->filled('created_at')) {
            $query->whereDate('created_at', $request->created_at);
        }

        if (!empty($from_shift) && !empty($to_shift)) {
            $query->whereBetween('shift_date', [$from_shift, $to_shift]);
        }

        $shiftDates = $query->get();

        // Format data for Gantt chart
        return $this->formatGanttData($shiftDates);
    }

    /**
     * Format data for Gantt chart view
     */
    private function formatGanttData($shiftDates)
    {
        $ganttData = [];

        $statusColorMap = [
            0 => 'bg-dark-blue',
            1 => 'bg-lighter',
            2 => 'bg-dark-green',
            3 => 'bg-light-yellow',
            4 => 'bg-light-blue',
            5 => 'bg-purple1',
            6 => 'bg-red',
            7 => 'bg-primary11',
            8 => 'bg-orange',
        ];

        foreach ($shiftDates as $sd) {
            $shift = $sd->shift;
            if (!$shift) continue;

            // Parse start/end times
            $startTime = \Carbon\Carbon::createFromFormat('H:i:s', $sd->start_time);
            $endTime = \Carbon\Carbon::createFromFormat('H:i:s', $sd->end_time);

            // Handle overnight shifts
            $startDate = \Carbon\Carbon::parse($sd->shift_date)->setTimeFrom($startTime);
            $endDate   = \Carbon\Carbon::parse($sd->shift_date)->setTimeFrom($endTime);
            if ($endTime->lessThanOrEqualTo($startTime)) {
                $endDate->addDay();
            }

            // Calculate duration
            $durationHours   = $startDate->diffInHours($endDate);
            $durationMinutes = $startDate->diffInMinutes($endDate) % 60;
            $durationFormatted = sprintf('%d hr %02d min', $durationHours, $durationMinutes);

            // Staff
            $staffName = $sd->staff ? $sd->staff->first_name . " " . $sd->staff->last_name : 'Not Assigned';

            // Client
            $client = User::find($shift->client_id);

            $note = ShiftNote::where('shift_date_id', $sd->id)->first(); // or $sd->shiftNote, depending on your relationship

            $ganttData[] = [
                'id' => $sd->id,
                'site_id' => $shift->site_id ?? null,
                'site_name' => $shift->site->site_name ?? 'Unknown Site',
                'title' => $shift->title ?? 'Shift',
                'start_date' => $sd->shift_date,
                'end_date' => $sd->shift_date,
                'start_time' => $sd->start_time,
                'end_time' => $sd->end_time,
                'service_type' => $sd->shift->service_type_2 ?? $sd->shift->service_type_1,
                'formatted_time' => "{$startTime->format('H:i')} - {$endTime->format('H:i')}",
                'duration' => "({$durationFormatted})",
                'staff_name' => $staffName,
                'staff_id' => $sd->staff_id ?? null,
                'client_id' => $client->id ?? null,
                'client_name' => $client->name ?? 'Unknown Client',
                'color_class' => $statusColorMap[$sd->is_assign] ?? 'bg-secondary',
                'status' => $sd->is_assign,
                'is_assigned' => $sd->is_assign != 0,
                'duration_hours' => $durationHours + ($durationMinutes / 60),
                'start_datetime' => $startDate->format('Y-m-d\TH:i:s'),
                'end_datetime' => $endDate->format('Y-m-d\TH:i:s'),
                'note' => $note?->note ?? null,          // ✅ include note text
                'note_type' => $note?->note_type ?? null // ✅ include note type
            ];
        }

        return response()->json(['data' => $ganttData]);
    }

    public function getShiftsWithStaff()
    {
        $shifts = Shift::with(['client', 'site'])->get();
        $events = [];
        $highlightDates = [];

        $statusColorMap = [
            0 => 'bg-dark-blue',
            1 => 'bg-lighter',
            2 => 'bg-dark-green',
            3 => 'bg-light-yellow',
            4 => 'bg-light-blue',
            5 => 'bg-purple1',
            6 => 'bg-red',
            7 => 'bg-primary11',
            8 => 'bg-orange',
        ];

        foreach ($shifts as $shift) {
            $dayList = json_decode($shift->days, true) ?: [];

            $sds = ShiftDate::where('shift_id', $shift->id)
                ->whereNotNull('staff_id')
                ->with('staff')
                ->get();

            foreach ($sds as $sd) {
                $dayName = (new \DateTime($sd->shift_date))->format('D');
                if (!empty($dayList) && !in_array($dayName, $dayList)) {
                    continue;
                }

                $startTime = \Carbon\Carbon::createFromFormat('H:i:s', $sd->start_time);
                $endTime   = \Carbon\Carbon::createFromFormat('H:i:s', $sd->end_time);

                // For FullCalendar display, keep start/end on same day
                $startDateTime = \Carbon\Carbon::parse($sd->shift_date)->setTimeFrom($startTime);
                $endDateTime   = \Carbon\Carbon::parse($sd->shift_date)->setTimeFrom($endTime);

                // For duration, handle overnight shifts
                $endForDuration = $endTime->copy();
                if ($endForDuration->lessThan($startTime)) {
                    $endForDuration->addDay();
                }

                $durationHours   = $startTime->diffInHours($endForDuration);
                $durationMinutes = $startTime->diffInMinutes($endForDuration) % 60;
                $durationFormatted = sprintf('%d hr %02d min', $durationHours, $durationMinutes);

                $events[] = [
                    'title' => $sd->staff ? $sd->staff->first_name . ' ' . $sd->staff->last_name : 'Unassigned',
                    'start' => $startDateTime->format('Y-m-d\TH:i:s'),
                    'end' => $endDateTime->format('Y-m-d\TH:i:s'),
                    'classNames' => [$statusColorMap[$sd->is_assign] ?? 'bg-secondary'],
                    'extendedProps' => [
                        'shift_id' => $shift->id,
                        'location' => $shift->site->site_name ?? 'Unknown Site',
                        'urgent' => rand(0, 1) === 1,
                        'sd_id' => $sd->id,
                        'duration' => $durationFormatted,
                        'start_time_str' => $startTime->format('H:i'),
                        'end_time_str' => $endTime->format('H:i'),
                    ]
                ];

                $highlightDates[] = $sd->shift_date;
            }
        }

        return response()->json([
            'events' => $events,
            'highlightDates' => array_values(array_unique($highlightDates))
        ]);
    }



    public function getShiftsBySite()
    {
        $shifts = Shift::with(['site'])->get();
        $events = [];
        $highlightDates = [];

        $statusColorMap = [
            0 => 'bg-dark-blue',
            1 => 'bg-lighter',
            2 => 'bg-dark-green',
            3 => 'bg-light-yellow',
            4 => 'bg-light-blue',
            5 => 'bg-purple',
            6 => 'bg-red',
            7 => 'bg-primary11',
            8 => 'bg-orange',
        ];

        foreach ($shifts as $shift) {
            $sds = ShiftDate::where('shift_id', $shift->id)->get();

            foreach ($sds as $sd) {
                $startTime = \Carbon\Carbon::createFromFormat('H:i:s', $sd->start_time);
                $endTime   = \Carbon\Carbon::createFromFormat('H:i:s', $sd->end_time);

                // Calculate duration
                if ($endTime->lessThanOrEqualTo($startTime)) {
                    $endTime->addDay();
                }
                $durationHours   = $startTime->diffInHours($endTime);
                $durationMinutes = $startTime->diffInMinutes($endTime) % 60;
                $durationFormatted = sprintf('%d hr %02d min', $durationHours, $durationMinutes);

                // Use same day for calendar event, even for overnight shifts
                $calendarStart = date('Y-m-d\TH:i:s', strtotime("$sd->shift_date $sd->start_time"));
                $calendarEnd   = date('Y-m-d\TH:i:s', strtotime("$sd->shift_date $sd->end_time")); // same day

                $events[] = [
                    'title' => $shift->site->site_name ?? 'Unknown Site',
                    'start' => $calendarStart,
                    'end' => $calendarEnd,
                    'allDay' => false,
                    'className' => [$statusColorMap[$sd->is_assign] ?? 'bg-secondary'],
                    'color' => '#3a87ad',
                    'extendedProps' => [
                        'duration' => $durationFormatted,
                        'startTime' => $startTime->format('H:i'),
                        'endTime' => $endTime->format('H:i'), // keep correct display even if overnight
                        'urgent' => rand(0, 1) === 1,
                        'sd_id' => $sd->id,
                    ]
                ];
            }
        }

        return response()->json([
            'events' => $events,
            'highlightDates' => array_values(array_unique($highlightDates)),
        ]);
    }

    public function getTodayShifts()
    {
        $today = now()->format('Y-m-d');
        $shifts = Shift::with(['client', 'site'])->get();
        $events = [];

        $statusColorMap = [
            0 => 'bg-dark-blue',
            1 => 'bg-lighter',
            2 => 'bg-dark-green',
            3 => 'bg-light-yellow',
            4 => 'bg-light-blue',
            5 => 'bg-purple',
            6 => 'bg-red',
            7 => 'bg-primary11',
            8 => 'bg-orange',
        ];

        $todayDay = now()->format('D'); // Mon, Tue, etc.

        foreach ($shifts as $shift) {
            $dayList = json_decode($shift->days, true) ?: [];
            if (!empty($dayList) && !in_array($todayDay, $dayList)) continue;

            // Fetch ShiftDates for today
            $shiftDates = ShiftDate::where('shift_id', $shift->id)
                ->where('shift_date', $today)
                ->with('staff')
                ->get();

            foreach ($shiftDates as $sd) {
                $startTime = \Carbon\Carbon::createFromFormat('H:i:s', $sd->start_time);
                $endTime   = \Carbon\Carbon::createFromFormat('H:i:s', $sd->end_time);

                // Duration calculation (handle overnight)
                $endForDuration = $endTime->copy();
                if ($endForDuration->lessThan($startTime)) {
                    $endForDuration->addDay();
                }

                $durationHours   = $startTime->diffInHours($endForDuration);
                $durationMinutes = $startTime->diffInMinutes($endForDuration) % 60;
                $durationFormatted = sprintf('%d hr %02d min', $durationHours, $durationMinutes);

                // Keep start/end on same day for display
                $startDateTime = \Carbon\Carbon::parse($sd->shift_date . ' ' . $sd->start_time);
                $endDateTime   = \Carbon\Carbon::parse($sd->shift_date . ' ' . $sd->end_time);

                $events[] = [
                    'title' => $shift->client->name ?? 'Unknown Client',
                    'start' => $startDateTime->format('Y-m-d\TH:i:s'),
                    'end'   => $endDateTime->format('Y-m-d\TH:i:s'),
                    'allDay' => false,
                    'color' => '#3a87ad',
                    'className' => $statusColorMap[$sd->is_assign] ?? 'bg-secondary',
                    'extendedProps' => [
                        'duration' => $durationFormatted,
                        'client'   => $shift->client->name ?? '',
                        'site'     => $shift->site->site_name ?? '',
                        'staff'    => $sd?->staff?->first_name . ' ' . $sd?->staff?->last_name,
                        'urgent'   => rand(0, 1) === 1,
                        'sd_id'    => $sd->id ?? null,
                        'start_time_str' => $startTime->format('H:i'),
                        'end_time_str'   => $endTime->format('H:i'), // this is correct end display
                    ]
                ];
            }
        }

        return response()->json($events);
    }

    private function calculateTotalHours($start, $end, $format = 'H:i')
    {
        $startTime = \Carbon\Carbon::createFromFormat($format, $start);
        $endTime = \Carbon\Carbon::createFromFormat($format, $end);

        // Handle overnight shifts (e.g. 22:00 to 06:00 next day)
        if ($endTime->lessThanOrEqualTo($startTime)) {
            $endTime->addDay();
        }

        $totalHours = $startTime->diffInMinutes($endTime) / 60;

        return number_format($totalHours, 2);
    }

    private function calculateTotalWorkingHours($staffId, $startDate, $endDate, $startTime, $endTime, $breakMinutes, $days, $format = 'H:i')
    {
        $validDays = array_map(function ($d) {
            return strtolower($d);
        }, $days);

        // Setup time and date
        $startTime = \Carbon\Carbon::createFromFormat($format, $startTime);
        $endTime = \Carbon\Carbon::createFromFormat($format, $endTime);

        // Handle overnight shifts (e.g. 22:00 to 06:00 next day)
        if ($endTime->lessThanOrEqualTo($startTime)) {
            $endTime->addDay();
        }

        $fromDate = Carbon::parse($startDate);
        $toDate = Carbon::parse($endDate);

        // Total working minutes per day
        $dailyMinutes = $newShiftMinutesPerDay = $endTime->diffInMinutes($startTime, true) - (int)$breakMinutes;

        $totalMinutes = 0;

        $period = CarbonPeriod::create($fromDate, $toDate);

        foreach ($period as $date) {
            // Fetch existing total minutes for that day
            $existingMinutes = \DB::table('shift_dates')
                ->where('staff_id', $staffId)
                ->where('is_assign', 0)
                ->where('shift_date', $date->format('Y-m-d'))
                ->sum(\DB::raw('TIME_TO_SEC(TIMEDIFF(end_time, start_time)) / 60'));

            $combinedMinutes = $existingMinutes + $newShiftMinutesPerDay;

            if ($combinedMinutes > 960) {
                throw new \Exception("Shift on " . $date->format('Y-m-d') . " exceeds 16 hours including existing shifts.");
            }

            if (in_array(strtolower($date->format('D')), $validDays)) {
                $totalMinutes += $dailyMinutes;
            }
        }

        // Final result in hours
        return $totalHours = round($totalMinutes / 60, 2);
    }

    public function getMonthlyShiftsStats()
    {
        $monthlyStats = ShiftDate::selectRaw('MONTH(shift_date) as month, COUNT(*) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month');

        // Ensure all months are represented (1-12)
        $data = [];
        for ($i = 1; $i <= 12; $i++) {
            $data[] = $monthlyStats[$i] ?? 0;
        }

        return response()->json([
            'shift' => $data,
        ]);
    }
    public function assign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shift_id' => 'required|exists:shift_dates,id',
            'staff_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $staffId   = $request->staff_id;
        $staffUser = Employee::where('user_id', $staffId)->firstOrFail();
        $shiftDate = ShiftDate::findOrFail($request->shift_id);
        $shift     = Shift::findOrFail($shiftDate->shift_id);

        // ====== 1️⃣ Check for approved leave ======
        $leave = LeaveRequest::where('user_id', $staffId)
            ->where('status', 'approved')
            ->where('start_date', '<=', $shiftDate->shift_date) // full datetime comparison
            ->where('end_date', '>=', $shiftDate->shift_date)   // full datetime comparison
            ->first();

        if ($leave) {
            return response()->json([
                'errors' => [
                    'leave' => "Staff has an approved leave from "
                        . \Carbon\Carbon::parse($leave->start_date)->format('Y-m-d H:i')
                        . " to "
                        . \Carbon\Carbon::parse($leave->end_date)->format('Y-m-d H:i')
                ]
            ], 422);
        }

        $start        = $shift->start_shift ?? null;
        $end          = $shift->end_shift ?? null;
        $from         = $shift->from_shift ?? null;
        $to           = $shift->to_shift ?? null;
        $breakMinutes = $shift->{'break-mins_shift'} ?? null;

        $selectedDays = explode(',', trim($shift->days, '[]"'));

        // ✅ Calculate new shift hours for this assignment
        $newShiftHours = $this->calculateTotalWorkingHours(
            $staffUser->id,
            $from,
            $to,
            $start,
            $end,
            $breakMinutes,
            $selectedDays,
            'H:i:s'
        );

        $staff = Employee::findOrFail($staffUser->id);

        // ✅ Apply all restrictions (including 40hr/20hr student visa rule)
        $newShiftStart = \Carbon\Carbon::parse($shiftDate->shift_date . ' ' . $shiftDate->start_time);

        // ✅ Apply all restrictions (including 40hr, 20hr student visa, and 12hr rest rule)
        applyRestrictions(
            $staff,
            $validator,
            'staff_id',
            $newShiftHours,
            $shiftDate->shift_date,
            $newShiftStart //pass new start datetime
        );

        if ($validator->errors()->any()) {
            \Log::info('Restrictions failed', $validator->errors()->toArray());

            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        // ✅ Overlapping check
        $newStart = \Carbon\Carbon::parse($shiftDate->shift_date . ' ' . $shiftDate->start_time);
        $newEnd   = \Carbon\Carbon::parse($shiftDate->shift_date . ' ' . $shiftDate->end_time);

        if ($newEnd->lte($newStart)) {
            $newEnd->addDay();
        }

        $overlap = ShiftDate::where('staff_id', $staff->user_id)
            ->where(function ($query) use ($newStart, $newEnd) {
                $query->whereRaw('TIMESTAMP(shift_date, start_time) < ?', [$newEnd])
                    ->whereRaw('TIMESTAMP(shift_date, end_time) > ?', [$newStart]);
            })
            ->exists();

        if ($overlap) {
            return response()->json([
                'error' => 'This staff already has a shift during this time.'
            ], 422);
        }

        // ✅ Assign shift if passes all checks
        $shiftDate->staff_id = $staff->user_id;
        $shiftDate->is_assign = 1;
        $shiftDate->status = 'pending';
        $shiftDate->save();

        send_push_notification(
            $staff->user_id,
            'Shift assigned',
            'An admin assigned a shift for you, You have to respond!',
            ['shiftDate' => $shiftDate],
        );

        $weekStart = now()->startOfWeek();
        $weekEnd   = now()->endOfWeek();

        $totalWeekHours = \App\Models\ShiftDate::where('staff_id', $shiftDate->staff_id)
            ->whereBetween('shift_date', [$weekStart, $weekEnd])
            ->sum('total_hours');

        $minWeeklyHours = $entity->hour_per_week ?? 40;

        $expectedHours = $totalWeekHours + $newShiftHours;

        if ($expectedHours < $minWeeklyHours) {
            // 👇 Instead of blocking, just trigger a notification
            Notify::toDashboard(
                null,
                'alert',
                'Worked Hours',
                "Guard {$staff->fore_name} {$staff->sur_name} has only {$expectedHours} hours scheduled this week. Minimum is {$minWeeklyHours}.",
                "#"
            );
        }

        return response()->json(['success' => 'Shift assigned successfully!'], 200);
    }


    public function getClient($id)
    {
        $client = User::role('client')->with('site')->findOrFail($id);

        return response()->json([
            'client' => $client,
            'sites' => $client->site ?? [],
        ]);
    }

    public function getStaff($id)
    {
        $employee = Employee::findOrFail($id);

        return response()->json([
            'employee' => $employee,
        ]);
    }

    public function filter(Request $request)
    {
        $query = ShiftDate::with(['shift', 'staff']);

        $from_shift = $request->from_shift;
        $to_shift = $request->to_shift;


        $query->whereHas('shift', function ($q) use ($request) {

            if ($request->filled('site')) {
                $q->where('site_id', $request->site);
            }

            if ($request->filled('staff')) {
                $q->where('staff_id', $request->staff);
            }

            if ($request->filled('client_id')) {
                $q->whereTime('client_id', '>=', $request->client_id);
            }
        });

        if ($request->filled('status')) {
            $query->where('is_assign', $request->status);
        }

        if ($request->filled('start_time')) {
            $query->whereTime('start_time', '>=', $request->start_time);
        }



        if ($request->filled('end_time')) {
            $query->whereTime('end_time', '<=', $request->end_time);
        }

        if ($request->filled('created_at')) {
            $query->whereDate('created_at', $request->created_at);
        }

        if ($request->filled('created_at')) {
            $query->whereDate('created_at', $request->created_at);
        }
        if (!empty($from_shift) && !empty($to_shift)) {
            $query->whereBetween('shift_date', [$from_shift, $to_shift]);
        }


        $shifts = $query->get();

        if ($request->ajax()) {
            $events = $shifts->map(function ($shift) {
                return [
                    'id' => $shift->shift->id,
                    'title' => optional($shift->shift->staff)->name ?? 'No Staff',
                    'start' => $shift->shift->start_time ?? $shift->shift->from_shift,
                    'end' => $shift->shift->end_time ?? null,
                    'className' => 'bg-dark-blue',
                    'location' => optional($shift->shift->site)->site_name ?? 'No Site',
                    'urgent' => false,
                    'sd_id' => $shift->shift->id
                ];
            });

            return response()->json(['events' => $events]);
        }

        // Regular page load fallback (not used for filtering)
        $sites = Site::all();
        $staffs = User::all();

        return back()->with(compact('shifts', 'sites', 'staffs'));
    }

    // Update the status of a check call
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,missed,completed', // adjust statuses as needed
        ]);

        $checkCall = CheckCall::findOrFail($id);
        $checkCall->status = $request->input('status');
        $checkCall->save();

        return response()->json([
            'success' => true,
            'message' => 'Check call status updated successfully.',
            'status' => $checkCall->status,
        ]);
    }

    // Add a comment to a check call
    public function addComment(Request $request, $id)
    {
        $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        $checkCall = CheckCall::findOrFail($id);

        // Assuming you have a comments relation or a comment column in check_calls table
        // If you don't have a dedicated comments table, you might want to create one.
        // For now, I'll assume a "comments" field (JSON or text) or a separate comments model.

        // Example: Append comment to existing comments in JSON format
        $comment = $checkCall->comment ? json_decode($checkCall->comment, true) : [];
        $comment[] = [
            'comment' => $request->input('comment'),
            'created_at' => now()->toDateTimeString(),
            // Optionally add user info if you track that
        ];
        $checkCall->comment = json_encode($comment);
        $checkCall->save();

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully.',
            'comment' => $comment,
        ]);
    }
    public function map($shiftId)
    {
        $shift = ShiftDate::findOrFail($shiftId);

        // staff_id is the user assigned to this shift
        $userId = $shift->staff_id;

        return view('map', compact('shift', 'userId'));
    }

    public function shiftLocations($shiftDateId)
    {
        $shiftDate = \App\Models\ShiftDate::findOrFail($shiftDateId);

        // Staff assigned to this shift
        $user = $shiftDate->staff;

        if (!$user) {
            return response()->json(['message' => 'No staff assigned to this shift.'], 404);
        }

        // Fetch locations directly by shiftdate_id
        $locations = \App\Models\Location::where('shiftdate_id', $shiftDate->id)
            ->orderBy('created_at')
            ->get(['latitude', 'longitude', 'created_at']);

        return response()->json([
            'shift'     => [
                'id'         => $shiftDate->id,
                'date'       => $shiftDate->shift_date,
                'start_time' => $shiftDate->start_time,
                'end_time'   => $shiftDate->end_time,
            ],
            'user'      => [
                'id'   => $user->id,
                'name' => $user->fore_name . ' ' . $user->sur_name,
            ],
            'locations' => $locations,
        ]);
    }

    public function view(ShiftDate $shiftDate)
    {
        $shiftDate->load([
            'staff',
            'shift.client',
            'shift.site',
            'shift.staff',
            'logs',
            'checkCalls'
        ]);

        return view('security_boards.shift-detail', compact('shiftDate'));
    }


    public function patrolUpdate(Request $request, $id)
    {
        $patrol = Patrol::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required',
            'status' => 'required|in:pending,in_progress,completed,missed',
        ]);

        // Fix start_time formatting
        $startTime = $request->input('start_time'); // e.g. "05:00"
        $patrolDate = $patrol->date ?? now()->toDateString(); // if you store date separately
        $fixedStartTime = $patrolDate . ' ' . $startTime . ':00';

        $patrol->update([
            'name' => $request->input('name'),
            'start_time' => $fixedStartTime,
            'status' => $request->input('status'),
        ]);

        $shift = ShiftDate::find($patrol->shift_id);
        send_push_notification(
            $shift?->user_id,
            'Patrol updated',
            'An admin has updated your patrol! check on your app now.',
            ['patrol' => $patrol],
        );

        return response()->json([
            'success' => true,
            'patrol' => $patrol
        ]);
    }

    public function patrolDestroy($id)
    {
        $patrol = Patrol::findOrFail($id);
        $patrol->delete();

        return response()->json(['success' => true]);
    }

    public function multiAssign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shift_ids'   => 'required|array',
            'shift_ids.*' => 'exists:shift_dates,id',
            'staff_id'    => 'nullable|exists:users,id',
            'start_times' => 'nullable|array', // optional keyed by shift ID
            'end_times'   => 'nullable|array', // optional keyed by shift ID
            'book_on'     => 'nullable|array', // optional keyed by shift ID
            'book_off'    => 'nullable|array', // optional keyed by shift ID
            'shift_dates'    => 'nullable|array', // optional keyed by shift ID
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $staffId   = $request->staff_id;
        $staffUser = Employee::where('user_id', $staffId)->firstOrFail();

        $updatedShifts = [];
        $errors = [];

        foreach ($request->shift_ids as $shiftId) {
            $shiftDate = ShiftDate::findOrFail($shiftId);
            $shift     = Shift::findOrFail($shiftDate->shift_id);

            // ====== 1️⃣ Check for approved leave ======
            if ($staffId) {
                $shiftStart = \Carbon\Carbon::parse($shiftDate->shift_date . ' ' . ($request->start_times[$shiftId] ?? $shiftDate->start_time));
                $shiftEnd   = \Carbon\Carbon::parse($shiftDate->shift_date . ' ' . ($request->end_times[$shiftId] ?? $shiftDate->end_time));

                // Handle overnight shift
                if ($shiftEnd->lte($shiftStart)) {
                    $shiftEnd->addDay();
                }

                $leave = LeaveRequest::where('user_id', $staffId)
                    ->where('status', 'approved')
                    ->where(function ($query) use ($shiftStart, $shiftEnd) {
                        $query->where(function ($q) use ($shiftStart, $shiftEnd) {
                            $q->where('start_date', '<=', $shiftEnd)
                                ->where('end_date', '>=', $shiftStart);
                        });
                    })
                    ->first();

                if ($leave) {
                    $errors["shift_{$shiftId}"] = ['leave' => "Staff has an approved leave from "
                        . \Carbon\Carbon::parse($leave->start_date)->format('Y-m-d H:i')
                        . " to "
                        . \Carbon\Carbon::parse($leave->end_date)->format('Y-m-d H:i')];
                    continue;
                }
            }

            // Use provided start/end times if available
            $newStart = $request->start_times[$shiftId] ?? $shiftDate->start_time;
            $newEnd   = $request->end_times[$shiftId] ?? $shiftDate->end_time;
            $newDate = $request->shift_dates[$shiftId] ?? $shiftDate->shift_date;

            // Use provided book_on/book_off if available
            $bookOn  = $request->book_on[$shiftId] ?? null;
            $bookOff = $request->book_off[$shiftId] ?? null;

            $newShiftStart = \Carbon\Carbon::parse($shiftDate->shift_date . ' ' . $newStart);
            $newShiftEnd   = \Carbon\Carbon::parse($shiftDate->shift_date . ' ' . $newEnd);
            if ($newShiftEnd->lte($newShiftStart)) {
                $newShiftEnd->addDay();
            }

            // Calculate total hours for restriction checks
            $selectedDays = explode(',', trim($shift->days, '[]"'));
            $newShiftHours = $this->calculateTotalWorkingHours(
                $staffUser->id,
                $shift->from_shift,
                $shift->to_shift,
                $shift->start_shift,
                $shift->end_shift,
                $shift->{'break-mins_shift'} ?? 0,
                $selectedDays,
                'H:i:s'
            );

            // Apply restrictions
            applyRestrictions(
                $staffUser,
                $validator,
                'staff_id',
                $newShiftHours,
                $shiftDate->shift_date,
                $newShiftStart
            );

            if ($validator->errors()->any()) {
                $errors[$shiftId] = $validator->errors()->toArray();
                continue;
            }

            // Check for overlapping shifts
            $overlap = ShiftDate::where('staff_id', $staffUser->user_id)
                ->where(function ($query) use ($newShiftStart, $newShiftEnd) {
                    $query->whereRaw('TIMESTAMP(shift_date, start_time) < ?', [$newShiftEnd])
                        ->whereRaw('TIMESTAMP(shift_date, end_time) > ?', [$newShiftStart]);
                })
                ->exists();

            if ($overlap) {
                $errors[$shiftId] = ['overlap' => 'This staff already has a shift during this time.'];
                continue;
            }

            // Assign shift and extra fields
            $shiftDate->staff_id            = $staffUser->user_id;
            $shiftDate->is_assign           = 1;
            $shiftDate->status              = 'pending';
            $shiftDate->start_time          = $newStart;
            $shiftDate->end_time            = $newEnd;
            $shiftDate->absentee_start_time = $bookOn;
            $shiftDate->absentee_end_time   = $bookOff;
            $shiftDate->shift_date          = $newDate;           // ✅ add this

            // Normalize time format for hours calculation
            $startCalc = strlen($newStart) === 5 ? $newStart . ':00' : $newStart;
            $endCalc   = strlen($newEnd) === 5 ? $newEnd . ':00' : $newEnd;
            $shiftDate->total_hours = $this->calculateTotalHours($startCalc, $endCalc, 'H:i:s');

            $shiftDate->save();

            // Push notification
            send_push_notification(
                $staffUser->user_id,
                'Shift assigned',
                'An admin assigned a shift for you, You have to respond!',
                ['shiftDate' => $shiftDate]
            );

            $updatedShifts[] = $shiftDate->id;
        }

        if (!empty($errors) && empty($updatedShifts)) {
            return response()->json(['errors' => $errors], 422);
        }

        return response()->json([
            'updated' => $updatedShifts,
            'errors'  => $errors
        ]);
    }


    public function multiEdit(Request $request)
    {
        $shiftIds = $request->shift_ids;

        if (!$shiftIds || !is_array($shiftIds)) {
            return response()->json([
                'error' => 'No shifts selected.'
            ], 422);
        }

        // Fetch shifts with staff and site details
        $shifts = ShiftDate::with(['staff', 'shift'])
            ->whereIn('id', $shiftIds)
            ->get();

        // Format data as needed for modal
        $shiftData = $shifts->map(function ($shift) {
            return [
                'id' => $shift->id,
                'start_time' => $shift->start_time,
                'end_time' => $shift->end_time,
                'staff_id' => $shift->staff_id,
                'site_id' => $shift->site_id,
                'shift_name' => $shift->shift->title ?? '',
            ];
        });

        return response()->json([
            'shifts' => $shiftData
        ]);
    }

    public function showNote($id)
    {
        $note = ShiftNote::where('shift_date_id', $id)->first();
        return response()->json($note);
    }

    public function storeNote(Request $request, $id)
    {
        $request->validate([
            'note_type' => 'required|in:guard,control,both',
            'note' => 'required|string',
        ]);

        $note = ShiftNote::updateOrCreate(
            ['shift_date_id' => $id],
            [
                'note_type' => $request->note_type,
                'note'      => $request->note,
                'user_id'   => Auth::id(),
            ]
        );

        return response()->json([
            'success' => true,
            'note' => $note
        ]);
    }

    public function deleteNote($noteId)
    {
        $note = ShiftNote::find($noteId);
        if ($note) {
            $note->delete();
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false]);
    }

    public function assignWithOverride(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shift_id' => 'required|exists:shift_dates,id',
            'staff_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $staffId   = $request->staff_id;
        $staffUser = Employee::where('user_id', $staffId)->firstOrFail();
        $shiftDate = ShiftDate::findOrFail($request->shift_id);
        $shift     = Shift::findOrFail($shiftDate->shift_id);

        // ⚠ Skip restrictions entirely
        // ✅ Still enforce overlap check to avoid double booking
        $newStart = \Carbon\Carbon::parse($shiftDate->shift_date . ' ' . $shiftDate->start_time);
        $newEnd   = \Carbon\Carbon::parse($shiftDate->shift_date . ' ' . $shiftDate->end_time);

        if ($newEnd->lte($newStart)) {
            $newEnd->addDay();
        }

        $overlap = ShiftDate::where('staff_id', $staffUser->user_id)
            ->where(function ($query) use ($newStart, $newEnd) {
                $query->whereRaw('TIMESTAMP(shift_date, start_time) < ?', [$newEnd])
                    ->whereRaw('TIMESTAMP(shift_date, end_time) > ?', [$newStart]);
            })
            ->exists();

        if ($overlap) {
            return response()->json([
                'error' => 'This staff already has a shift during this time.'
            ], 422);
        }

        // ✅ Force assign
        $shiftDate->staff_id = $staffUser->user_id;
        $shiftDate->is_assign = 1;
        $shiftDate->status = 'pending';
        $shiftDate->save();

        send_push_notification(
            $staffUser->user_id,
            'Shift assigned (override)',
            'An admin assigned a shift for you, overriding restrictions.',
            ['shiftDate' => $shiftDate],
        );

        return response()->json(['success' => 'Shift assigned with override!'], 200);
    }

    public function multiAssignWithOverride(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shift_ids'   => 'required|array',
            'shift_ids.*' => 'exists:shift_dates,id',
            'staff_id'    => 'required|exists:users,id',
            'start_times' => 'nullable|array',
            'end_times'   => 'nullable|array',
            'book_on'     => 'nullable|array',
            'book_off'    => 'nullable|array',
            'shift_dates' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $staffId   = $request->staff_id;
        $staffUser = Employee::where('user_id', $staffId)->firstOrFail();
        $updatedShifts = [];
        $errors = [];

        foreach ($request->shift_ids as $shiftId) {
            $shiftDate = ShiftDate::findOrFail($shiftId);
            $shift     = Shift::findOrFail($shiftDate->shift_id);

            $newStart = $request->start_times[$shiftId] ?? $shiftDate->start_time;
            $newEnd   = $request->end_times[$shiftId] ?? $shiftDate->end_time;
            $newDate  = $request->shift_dates[$shiftId] ?? $shiftDate->shift_date;
            $bookOn   = $request->book_on[$shiftId] ?? null;
            $bookOff  = $request->book_off[$shiftId] ?? null;

            $newShiftStart = \Carbon\Carbon::parse($newDate . ' ' . $newStart);
            $newShiftEnd   = \Carbon\Carbon::parse($newDate . ' ' . $newEnd);
            if ($newShiftEnd->lte($newShiftStart)) {
                $newShiftEnd->addDay();
            }

            // ✅ Overlap check only
            $overlap = ShiftDate::where('staff_id', $staffUser->user_id)
                ->where(function ($query) use ($newShiftStart, $newShiftEnd) {
                    $query->whereRaw('TIMESTAMP(shift_date, start_time) < ?', [$newShiftEnd])
                        ->whereRaw('TIMESTAMP(shift_date, end_time) > ?', [$newShiftStart]);
                })
                ->exists();

            if ($overlap) {
                $errors[$shiftId] = ['overlap' => 'This staff already has a shift during this time.'];
                continue;
            }

            // ✅ Assign without restriction checks
            $shiftDate->staff_id            = $staffUser->user_id;
            $shiftDate->is_assign           = 1;
            $shiftDate->status              = 'pending';
            $shiftDate->start_time          = $newStart;
            $shiftDate->end_time            = $newEnd;
            $shiftDate->absentee_start_time = $bookOn;
            $shiftDate->absentee_end_time   = $bookOff;
            $shiftDate->shift_date          = $newDate;

            // Recalculate total hours
            $startCalc = strlen($newStart) === 5 ? $newStart . ':00' : $newStart;
            $endCalc   = strlen($newEnd) === 5 ? $newEnd . ':00' : $newEnd;
            $shiftDate->total_hours = $this->calculateTotalHours($startCalc, $endCalc, 'H:i:s');

            $shiftDate->save();

            send_push_notification(
                $staffUser->user_id,
                'Shift assigned (override)',
                'An admin assigned a shift for you, overriding restrictions.',
                ['shiftDate' => $shiftDate]
            );

            $updatedShifts[] = $shiftDate->id;
        }

        return response()->json([
            'updated' => $updatedShifts,
            'errors'  => $errors
        ]);
    }

    public function updateWithOverride(Request $request, $id)
    {
        $shift = ShiftDate::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status_id'   => 'nullable|integer',
            'staff_id'    => 'nullable|integer',
            'start_shift' => 'required',
            'end_shift'   => 'required',
            'book_on'     => 'nullable',
            'book_off'    => 'nullable',
            'shift_date'  => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['absentee_start_time'] = $data['book_on'] ?? null;
        $data['absentee_end_time']   = $data['book_off'] ?? null;
        $data['start_time']          = $data['start_shift'];
        $data['end_time']            = $data['end_shift'];

        // Normalize time format
        if (strlen($data['start_shift']) === 5) $data['start_shift'] .= ':00';
        if (strlen($data['end_shift']) === 5)   $data['end_shift']   .= ':00';

        // Calculate total hours
        $data['total_hours'] = $this->calculateTotalHours($data['start_shift'], $data['end_shift'], 'H:i:s');
        $shift->status = 'pending';

        // ⚠ Skip restrictions completely
        if (!empty($data['staff_id'])) {
            $staffUser = Employee::where('user_id', $data['staff_id'])->firstOrFail();
            $shift->staff_id = $staffUser->user_id;
        }

        $shift->update($data);

        // Send notification
        if (!empty($data['staff_id'])) {
            send_push_notification(
                $staffUser->user_id,
                'Shift updated (override)',
                'An admin updated a shift for you, overriding restrictions.',
                ['shift' => $shift]
            );
        }

        return response()->json(['success' => 'Shift updated with override!']);
    }

    public function storeOverride(Request $request)
    {
        $shiftCount = count($request->client_id);

        for ($i = 0; $i < $shiftCount; $i++) {
            // Basic validation
            $validator = Validator::make([
                'client_id' => $request->client_id[$i],
                'site_id' => $request->site_id[$i],
                'company_id' => $request->company_id[$i] ?? null,
                'staff_id' => $request->staff_id[$i] ?? null,
                'training_id' => $request->training_id ?? [],
                'start_shift' => $request->start_shift[$i],
                'end_shift' => $request->end_shift[$i],
                'break-mins_shift' => $request->{'break-mins_shift'}[$i] ?? null,
                'from_shift' => $request->from_shift[$i] ?? null,
                'to_shift' => $request->to_shift[$i] ?? null,
                'days' => $request->days[$i] ?? "Mon,Tue,Wed,Thu,Fri,Sat,Sun",
            ], [
                'client_id' => 'required|integer',
                'site_id' => 'required|integer',
                'staff_id' => 'nullable|integer',
                'start_shift' => 'required|date_format:H:i',
                'end_shift' => 'required|date_format:H:i',
                'from_shift' => 'required|date',
                'to_shift' => 'required|date|after_or_equal:from_shift',
                'training_id' => 'nullable|array',
                'training_id.*' => 'exists:training_materials,id',
            ]);

            // Additional simple validations
            $validator->after(function ($validator) use ($request, $i) {
                $start = $request->start_shift[$i] ?? null;
                $end = $request->end_shift[$i] ?? null;

                if ($start && $end) {
                    $startTime = \Carbon\Carbon::createFromFormat('H:i', $start);
                    $endTime = \Carbon\Carbon::createFromFormat('H:i', $end);

                    if ($startTime->eq($endTime)) {
                        $validator->errors()->add("end_shift", "End time must not be the same as start time.");
                    }
                }
            });

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors(), 'index' => $i], 422);
            }

            $data = $validator->validated();

            $data['days'] = json_encode([str_replace(['"', '[', ']'], '', $data['days'])]);
            $data['is_assign'] = !empty($data['staff_id']) ? 1 : 0;

            $serviceType1 = DB::table('employee_types')->where('id', $request->service_type_1[$i])->first();
            $serviceType2 = DB::table('employee_types')->where('id', $request->service_type_2[$i])->first();

            // Create main Shift
            $shift = Shift::create([
                'client_id'   => $request->client_id[$i],
                'site_id'     => $request->site_id[$i],
                'staff_id'    => $request->staff_id[$i] ?? null,
                'start_shift' => $request->start_shift[$i],
                'end_shift'   => $request->end_shift[$i],
                'service_type_1' => $serviceType1?->name,
                'service_type_2' => $serviceType2?->name,
            ]);

            // Expand to ShiftDates
            $dayString = $request->days[$i] ?? 'Mon,Tue,Wed,Thu,Fri,Sat,Sun';
            $selectedDays = array_map(function ($day) {
                return ucfirst(substr(trim($day), 0, 3));
            }, explode(',', $dayString));

            $fromDate = Carbon::parse($request->from_shift[$i]);
            $toDate   = Carbon::parse($request->to_shift[$i]);
            $period   = CarbonPeriod::create($fromDate, $toDate);

            foreach ($period as $date) {
                if (!in_array($date->format('D'), $selectedDays)) continue;

                $shiftDate = ShiftDate::create([
                    'shift_id'    => $shift->id,
                    'staff_id'    => $shift->staff_id ?? null,
                    'shift_date'  => $date->format('Y-m-d'),
                    'start_time'  => $request->start_shift[$i],
                    'end_time'    => $request->end_shift[$i],
                    'is_assign'   => !empty($shift->staff_id) ? 1 : 0,
                    'break_time'  => $request->{'break-mins_shift'}[$i] ?? null,
                    'total_hours' => $this->calculateTotalHours(
                        $request->start_shift[$i],
                        $request->end_shift[$i]
                    ),
                ]);

                if (!empty($data['training_id'])) {
                    $trainingIds = is_array($data['training_id']) ? $data['training_id'] : [$data['training_id']];
                    $shiftDate->trainings()->sync($trainingIds);
                }

                // Auto CheckCalls / Patrols
                $startTime = Carbon::createFromFormat('H:i', $data['start_shift']);
                $endTime   = Carbon::createFromFormat('H:i', $data['end_shift']);
                $durationMinutes = ($endTime->diffInMinutes($startTime) + 1440) % 1440;
                $durationMinutes = $durationMinutes ?: 1440; // handle exact 24h
                $numberOfCheckCalls = ceil($durationMinutes / 60);

                $site = Site::with('checkpoints')->find($shift->site_id);
                $totalCheckpoints = $site->checkpoints->count() ?? 0;

                for ($n = 0; $n < $numberOfCheckCalls; $n++) {
                    $checkTime  = $startTime->copy()->addHours($n);
                    $patrolTime = $startTime->copy()->addHours($n);

                    if ($request->has('auto_checkcall_enabled') && $request->auto_checkcall_enabled) {
                        CheckCall::create([
                            'shift_id' => $shiftDate->id,
                            'name' => 'Auto CheckCall ' . ($n + 1),
                            'scheduled_time' => $checkTime->format('Y-m-d H:i'),
                            'status' => 'pending',
                        ]);
                    }

                    Patrol::create([
                        'shift_id' => $shiftDate->id,
                        'name' => 'Auto Patrol ' . ($n + 1),
                        'summary' => 'Scheduled patrol at ' . $patrolTime->format('H:i'),
                        'start_time' => $patrolTime->format('Y-m-d H:i'),
                        'status' => 'pending',
                        'total_checkpoints' => $totalCheckpoints,
                        'completed_checkpoints' => 0,
                        'issues_reported' => 0,
                        'completed_at' => null,
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Shifts overridden successfully!',
        ]);
    }
}
