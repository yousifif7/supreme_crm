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
use App\Models\EmployeeBan;
use App\Models\Location;
use Carbon\CarbonPeriod;
use App\Models\CheckCall;
use App\Models\ShiftDate;
use App\Models\ShiftNote;
use App\Models\PatrolMedia;
use App\Models\EmployeeTerm;
use App\Models\EmployeeType;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\ShiftBooking;
use App\Services\GeoService;
use Illuminate\Http\Request;
use App\Models\Subcontractor;
use App\Exports\PatrolsExport;
use App\Models\CheckpointScan;
use App\Models\PatrolCheckPoint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\DataTables\ShiftsDataTable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\Validator;

class ShiftController extends Controller
{

    /**
     * Combine a date-like value with a time string into a Carbon instance.
     * Ensures the date part is reduced to a YYYY-MM-DD string so concatenating
     * a time does not accidentally produce double-time strings like
     * "2025-11-26 00:00:00 00:00".
     *
     * @param  mixed  $dateVal
     * @param  string $timeVal
     * @return \Carbon\Carbon
     */
    protected function combineDateTime($dateVal, $timeVal)
    {
        try {
            $dateOnly = Carbon::parse($dateVal)->toDateString();
        } catch (\Exception $e) {
            $dateOnly = (string) $dateVal;
        }

        return Carbon::parse(trim($dateOnly . ' ' . $timeVal));
    }

    /**
     * Return a small, cached list of subcontractor users.
     * Caches minimal columns for fast responses in UI endpoints.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getSubcontractors()
    {
        return Cache::remember('subcontractors_min_list', 300, function () {
            return User::role('subcontractor')
                ->orderBy('first_name', 'asc')
                ->get(['id', 'first_name', 'last_name', 'email']);
        });
    }

    protected function resolveSubcontractorUserId($subcontractorId): ?int
    {
        if (empty($subcontractorId)) {
            return null;
        }

        $subcontractor = Subcontractor::find($subcontractorId);
        if ($subcontractor && !empty($subcontractor->user_id)) {
            return (int) $subcontractor->user_id;
        }

        $byUserId = Subcontractor::where('user_id', $subcontractorId)->first();
        if ($byUserId && !empty($byUserId->user_id)) {
            return (int) $byUserId->user_id;
        }

        return null;
    }

    protected function findSubcontractorByStoredId($storedValue): ?Subcontractor
    {
        if (empty($storedValue)) {
            return null;
        }

        $subcontractor = Subcontractor::with('user')->find($storedValue);
        if ($subcontractor) {
            return $subcontractor;
        }

        return Subcontractor::with('user')->where('user_id', $storedValue)->first();
    }


    public function unassign(Request $request, $id)
    {
        $shiftDate = ShiftDate::findOrFail($id);

        send_push_notification(
            $shiftDate->staff->id,
            'Shift Unassigned',
            'An admin unassigned a shift for you, check your schedule.',
            ['type' => 'shift', 'shiftId' => $shiftDate->id],
        );

        ShiftBooking::where('shift_id', $shiftDate->id)->delete();


        $bookings = $shiftDate->bookings;
        foreach ($bookings as $booking) {
            $booking->delete();
        }

        $shiftDate->staff_id = null; // Remove assigned staff
        $shiftDate->is_assign=0;
        $shiftDate->status="pending";


        $shiftDate->save();

        try { $this->rescheduleShiftEvents($shiftDate); } catch (\Throwable $_) {}

        $checkcalls = $shiftDate->checkCalls;
        foreach ($checkcalls as $checkcall) {
            if($checkcall->status !=='completed'){
                $checkcall->employee_id = null;
                $checkcall->save();
            }
        }

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
            $subcontractors = $this->getSubcontractors();
        $users = User::all();
        $services = EmployeeType::all();
        return $dataTable->render('security_boards.shifts', compact('clients', 'sites', 'staffs', 'subcontractors', 'users', 'services'));
    }
    public function scheduling()
    {
        // Default window: 2 months before today through 1 month after today
        $startDefault = now()->subMonths(2)->startOfDay()->format('Y-m-d');
        $endDefault = now()->addMonths(1)->endOfDay()->format('Y-m-d');

        $clients = User::role('client')->get();
        $sites = Site::all();
        $staffs = User::role('security_staff')->get();
            $subcontractors = $this->getSubcontractors();
        $services = EmployeeType::all();
        return view('security_boards.scheduling', compact('sites', 'staffs', 'clients', 'services', 'subcontractors'));
    }
    public function worker_calendar()
    {
        $startDefault = now()->subMonths(2)->startOfDay()->format('Y-m-d');
        $endDefault = now()->addMonths(1)->endOfDay()->format('Y-m-d');

        $shifts = Shift::whereHas('shiftDates', function ($q) use ($startDefault, $endDefault) {
            $q->whereBetween('shift_date', [$startDefault, $endDefault]);
        })->with(['client', 'site'])->get();

        $clients = User::role('client')->get();
        $sites = Site::all();
        $staffs = User::role('security_staff')->get();
        $subcontractors = $this->getSubcontractors();
        $users = User::all();
        $services = EmployeeType::all();
        return view('security_boards.worker_calendar', compact('shifts', 'clients', 'sites', 'staffs', 'subcontractors', 'users', 'services'));
    }
    public function site_calendar()
    {
        $startDefault = now()->subMonths(2)->startOfDay()->format('Y-m-d');
        $endDefault = now()->addMonths(1)->endOfDay()->format('Y-m-d');

        $shifts = Shift::whereHas('shiftDates', function ($q) use ($startDefault, $endDefault) {
            $q->whereBetween('shift_date', [$startDefault, $endDefault]);
        })->with(['client', 'site'])->get();

        $clients = User::role('client')->get();
        $sites = Site::all();
        $staffs = User::role('security_staff')->get();
            $subcontractors = $this->getSubcontractors();
        $users = User::all();
        $services = EmployeeType::all();
        return view('security_boards.site_calendar', compact('shifts', 'clients', 'sites', 'staffs', 'subcontractors', 'users', 'services'));
    }
    public function today_rota()
    {
        $startDefault = now()->subMonths(2)->startOfDay()->format('Y-m-d');
        $endDefault = now()->addMonths(1)->endOfDay()->format('Y-m-d');

        $shifts = Shift::whereHas('shiftDates', function ($q) use ($startDefault, $endDefault) {
            $q->whereBetween('shift_date', [$startDefault, $endDefault]);
        })->with(['client', 'site'])->get();

        $clients = User::role('client')->get();
        $sites = Site::all();
        $staffs = User::role('security_staff')->get();
            $subcontractors = $this->getSubcontractors();
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
        try {


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
        $shiftDatesCreated = 0;
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
                // Accept either HH:MM or HH:MM:SS from client (some browsers/input types include seconds)
                'start_shift' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
                'end_shift' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
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
                'start' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
                'end' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
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

                // ✅ Prevent accidental multi-year shift ranges
                if ($from && $to) {
                    try {
                        $fromDate = \Carbon\Carbon::parse($from);
                        $toDate   = \Carbon\Carbon::parse($to);
                        if ($toDate->diffInDays($fromDate) > 365) {
                            $validator->errors()->add('to_shift', 'The shift date range cannot exceed 1 year. Please split large ranges into separate shifts.');
                        }
                    } catch (\Exception $e) {
                        // let the existing date validation handle parse errors
                    }
                }

                // ✅ Validate time logic only if both times are present and in an acceptable format
                if ($start && $end && preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $start) && preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $end)) {
                    // Accept either H:i or H:i:s
                    try {
                        $startTime = \Carbon\Carbon::createFromFormat('H:i', $start);
                    } catch (\Exception $e) {
                        $startTime = \Carbon\Carbon::createFromFormat('H:i:s', $start);
                    }

                    try {
                        $endTime = \Carbon\Carbon::createFromFormat('H:i', $end);
                    } catch (\Exception $e) {
                        $endTime = \Carbon\Carbon::createFromFormat('H:i:s', $end);
                    }

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
                        // Ensure $from is treated as a date (strip any time component) before appending start time
                        try {
                            $fromDateOnly = \Carbon\Carbon::parse($from)->toDateString();
                            $newShiftStart   = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $fromDateOnly . ' ' . $start);
                        } catch (\Exception $e) {
                            // Fallback: try a naive parse but let caller know if it fails later
                            $newShiftStart   = \Carbon\Carbon::parse($from . ' ' . $start);
                        }

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
                            // Ban restriction: cannot assign banned staff to site/client
                            $siteId = $request->site_id[$i] ?? null;
                            $clientId = $request->client_id[$i] ?? null;
                            if (EmployeeBan::isBannedFor($staff->id, $siteId, $clientId)) {
                                $validator->errors()->add('ban_forbidden', 'Selected staff is banned for the chosen site/client and cannot be assigned.');
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
                        }
                    } else {
                        $validator->errors()->add('staff_id', 'Invalid shift date or start time for restriction check.');
                    }
                }
            });


            if ($validator->fails()) {
                $errors = $validator->errors();
                $response = ['errors' => $errors, 'index' => $i];

                // If the current user is an admin/superadmin, include an override message
                // so the frontend can present an override confirmation to privileged users.
                try {
                    $user = auth()->user();
                    if ($user && method_exists($user, 'getRoleNames')) {
                        $roles = $user->getRoleNames();
                        if ($roles->contains('superadmin') || $roles->contains('admin')) {
                            // Do not provide an override message when the failure is due to a ban.
                            if (!$errors->has('ban_forbidden')) {
                                // Prefer staff-specific restriction message if present, otherwise first message
                                $firstMsg = $errors->has('staff_id') ? $errors->first('staff_id') : $errors->first();
                                if ($firstMsg) $response['override_message'] = $firstMsg;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // ignore any role-checking errors and continue returning validation errors
                }

                return response()->json($response, 422);
            }

            // Normalize time inputs: strip optional seconds (HH:MM:SS -> HH:MM)
            $startArr = $request->input('start_shift', []);
            $endArr = $request->input('end_shift', []);
            if (isset($startArr[$i]) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $startArr[$i])) {
                $startArr[$i] = substr($startArr[$i], 0, 5);
            }
            if (isset($endArr[$i]) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $endArr[$i])) {
                $endArr[$i] = substr($endArr[$i], 0, 5);
            }
            // Also normalize lightweight start/end inputs if present
            $startArr2 = $request->input('start', []);
            $endArr2 = $request->input('end', []);
            if (isset($startArr2[$i]) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $startArr2[$i])) {
                $startArr2[$i] = substr($startArr2[$i], 0, 5);
            }
            if (isset($endArr2[$i]) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $endArr2[$i])) {
                $endArr2[$i] = substr($endArr2[$i], 0, 5);
            }

            $request->merge([
                'start_shift' => $startArr,
                'end_shift' => $endArr,
                'start' => $startArr2,
                'end' => $endArr2,
            ]);

            $data = $validator->validated();

            // Ensure validated times are normalized to HH:MM (strip seconds if present)
            if (!empty($data['start_shift']) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $data['start_shift'])) {
                $data['start_shift'] = substr($data['start_shift'], 0, 5);
            }
            if (!empty($data['end_shift']) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $data['end_shift'])) {
                $data['end_shift'] = substr($data['end_shift'], 0, 5);
            }
            if (!empty($data['start']) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $data['start'])) {
                $data['start'] = substr($data['start'], 0, 5);
            }
            if (!empty($data['end']) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $data['end'])) {
                $data['end'] = substr($data['end'], 0, 5);
            }

            $data['restrict_start_time'] = !empty($data['restrict_start_time']) ? 1 : 0;
            $data['enforce_picture_check'] = !empty($data['enforce_picture_check']) ? 1 : 0;
            $data['restrict_location_check'] = !empty($data['restrict_location_check']) ? 1 : 0;
            $data['days'] = json_encode([str_replace(['"', '[', ']'], '', $data['days'])]);

            if (!empty($data['staff_id'])) {
                $data['is_assign'] = 1;
            }

            $serviceType1 = DB::table('employee_types')->where('id', $request->service_type_1[$i])->first();
            $serviceType2 = DB::table('employee_types')->where('id', $request->service_type_2[$i])->first();
            $resolvedSubcontractorUserId = $this->resolveSubcontractorUserId($request->subcontractor_id[$i] ?? null);

            $shift = Shift::create([
                'client_id'   => $request->client_id[$i],
                'site_id'     => $request->site_id[$i],
                'staff_id'    => $request->staff_id[$i] ?? null,
                'start_shift' => $request->start_shift[$i],
                'end_shift'   => $request->end_shift[$i],
                'service_type_1'   => $serviceType1?->name,
                'service_type_2'   => $serviceType2?->name,
                'subcontractor_id'   => $resolvedSubcontractorUserId,
                'restrict_start_time' => $data['restrict_start_time'],
                'enforce_picture_check' => $data['enforce_picture_check'],
                'restrict_location_check' => $data['restrict_location_check'],
                // Persist site-level rate on the parent Shift record so it is
                // available for later edits and reports.
                'site_rate'        => $request->site_rate[$i] ?? null,
                'employee_rate'        => $request->employee_rate[$i] ?? null,
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

                    // Quantity: how many identical shift instances to create for this date
                    $quantity = isset($request->number_shift[$i]) ? max(1, (int)$request->number_shift[$i]) : 1;

                    $lastCreated = null;
                    for ($q = 0; $q < $quantity; $q++) {
                        $shiftDate = ShiftDate::create([
                            'shift_id'    => $shift->id,
                            'staff_id'    => $shift->staff_id ?? null,
                            'shift_date'  => $date->format('Y-m-d'),
                            'start_time'  => $request->start_shift[$i],
                            'end_time'    => $request->end_shift[$i],
                            'subcontractor_id'    => $shift->subcontractor_id ?? null,
                            'is_assign'   => !empty($shift->staff_id) ? 1 : 0,
                            'break_time'  => $request->{'break-mins_shift'}[$i] ?? null,
                            'total_hours' => $this->calculateTotalHours(
                                $request->start_shift[$i],
                                $request->end_shift[$i]
                            ),
                            'guard_rate'  => $request->employee_rate[$i] ?? $request->site_rate[$i] ?? 0,
                            'require_media' => !empty($request->require_media_upload[$i]) ? 1 : 0,
                        ]);

                        $lastCreated = $shiftDate;
                        $shiftDatesCreated++;

                        // if ($shift->staff_id) {
                        //     $weekStart = now()->startOfWeek();
                        //     $weekEnd   = now()->endOfWeek();

                        //     $totalWeekHours = \App\Models\ShiftDate::where('staff_id', $shiftDate->staff_id)
                        //         ->whereBetween('shift_date', [$weekStart, $weekEnd])
                        //         ->sum('total_hours');

                        //     $minWeeklyHours = $entity->hour_per_week ?? 40;

                        //     $expectedHours = $totalWeekHours + $shiftDate->total_hours;

                        //     $staff = User::find($shift->staff_id);
                        //     if ($expectedHours < $minWeeklyHours) {
                        //         Notify::toDashboard(
                        //             null,
                        //             'alert',
                        //             'Worked Hours',
                        //             "Guard {$staff->first_name} {$staff->last_name} has only {$expectedHours} hours scheduled this week. Minimum is {$minWeeklyHours}.",
                        //             "#"
                        //         );
                        //     }
                        // }

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

                        $start = $this->combineDateTime($shiftDate->shift_date, $shiftDate->start_time);

                        $site = Site::with('checkpoints')->find($shift->site_id);
                        $totalCheckpoints = $site->checkpoints->count() ?? 0;

                        for ($n = 0; $n < (int) $numberOfCheckCalls; $n++) {
                            $checkTime  = $start->copy()->addHours($n);
                            $patrolTime = $start->copy()->addHours($n);

                            // Only create checkcalls if toggle is ON (value '1')
                            if (isset($request->auto_checkcall_enabled[$i]) && $request->auto_checkcall_enabled[$i] == '1') {
                                CheckCall::create([
                                    'shift_id'       => $shiftDate->id,
                                    'employee_id'    => $shiftDate->staff_id ?? null,
                                    'name'           => 'Auto CheckCall ' . ($n + 1),
                                    'scheduled_time' => $checkTime->format('Y-m-d H:i:s'),
                                    'status'         => 'pending',
                                    'require_media'  => $shiftDate->require_media ?? 0,
                                ]);
                            }
                            
                            
                            if (isset($request->auto_patrol_enabled[$i]) && $request->auto_patrol_enabled[$i] == '1') {
                                Patrol::create([
                                    'shift_id'              => $shiftDate->id,
                                    'name'                  => 'Auto Patrol ' . ($n + 1),
                                    'summary'               => 'Scheduled patrol at ' . $patrolTime->format('H:i'),
                                    'start_time'            => $patrolTime->format('Y-m-d H:i:s'),
                                    'status'                => 'pending',
                                    'total_checkpoints'     => $totalCheckpoints,
                                    'completed_checkpoints' => 0,
                                    'issues_reported'       => 0,
                                    'completed_at'          => null,
                                ]);
                            }
                        }

                        // Manually added checkcalls (form-level) — create per ShiftDate
                        if ($request->has('checkcalls') && is_array($request->checkcalls)) {
                            foreach ($request->checkcalls as $checkcall) {
                                if (!empty($checkcall['name']) && !empty($checkcall['scheduled_time'])) {
                                    CheckCall::create([
                                        'shift_id'       => $shiftDate->id,
                                        'name'           => $checkcall['name'],
                                        'scheduled_time' => $date->format('Y-m-d') . ' ' . $checkcall['scheduled_time'],
                                        'status'         => 'pending',
                                        'require_media'  => $shiftDate->require_media ?? 0,
                                    ]);
                                }
                            }
                        }


                        if ($request->has('patrols') && is_array($request->patrols)) {
                            foreach ($request->patrols as $patrol) {
                                if (!is_array($patrol)) continue;
                                if (empty($patrol['name']) || empty($patrol['start_time'])) continue;

                                // Combine date with posted time (handles H:i or H:i:s)
                                try {
                                    $startDateTime = $this->combineDateTime($shiftDate->shift_date, $patrol['start_time']);
                                } catch (\Exception $e) {
                                    $startDateTime = Carbon::parse($shiftDate->shift_date . ' ' . $patrol['start_time']);
                                }

                                Patrol::create([
                                    'shift_id'              => $shiftDate->id,
                                    'name'                  => $patrol['name'],
                                    'summary'               => 'Custom patrol scheduled at ' . $startDateTime->format('H:i'),
                                    'start_time'            => $startDateTime->format('Y-m-d H:i:s'),
                                    'status'                => 'pending',
                                    'total_checkpoints'     => $totalCheckpoints,
                                    'completed_checkpoints' => 0,
                                    'issues_reported'       => 0,
                                    'completed_at'          => null,
                                ]);
                            }
                        }
                    } // end quantity loop

                    // ensure $shiftDate references last created instance for downstream usage
                    $shiftDate = $lastCreated;
                    
                    // Send push notification if staff was assigned
                    if ($shift->staff_id) {
                        try {
                            send_push_notification(
                                $shift->staff_id,
                                'New shift assigned',
                                'A new shift has been assigned to you. Please check your schedule and respond.',
                                ['type' => 'shift', 'shiftId' => $shiftDate->id]
                            );
                        } catch (\Exception $e) {
                            \Log::error('Failed to send push notification in store: ' . $e->getMessage());
                        }
                    }
                }
                }
            }
                Logger::log(Auth::user(), 'Create', 'Amount: ('.$shiftDatesCreated.' shift dates created) for site ' . $shift->site->site_name . ' Starting at: ' . $shiftDate->start_time . ' On ' . $shiftDate->shift_date.' From date: ' .$fromDate. ' To Date: '. $toDate);

        return response()->json([
            'message' => 'Shifts created successfully!',
            'redirect_url' => route('shiftDates.view', [
                'shiftDate' => $shiftDate->id,   // must match the {shiftDate} route param
            ])
        ]);
        } catch (\Throwable $e) {
            // Log full exception for diagnostics
            \Log::error('ShiftController@store exception: ' . $e->getMessage(), ['exception' => $e]);

            $payload = ['error' => $e->getMessage()];
            if (config('app.debug')) {
                $payload['trace'] = $e->getTraceAsString();
            }

            return response()->json($payload, 500);
        }
    }

    public function edit($id)
    {
        $shift = ShiftDate::with(['staff', 'shift.client', 'shift.site', 'shift.staff', 'shift.subcontractor'])
            ->withCount(['checkCalls', 'patrols'])
            ->findOrFail($id);

        // Provide lists used by the edit modal so the frontend can populate selects
        $clients = User::role('client')->orderBy('first_name', 'asc')->get();
        $sites = Site::orderBy('site_name', 'asc')->get();
        $staffs = User::role('security_staff')->orderBy('first_name', 'asc')->get();
        $subcontractors = $this->getSubcontractors();
        $services = EmployeeType::all();

        return response()->json([
            'shift' => $shift,
            'parent_shift' => $shift->shift,
            'clients' => $clients,
            'sites' => $sites,
            'staffs' => $staffs,
            'subcontractors' => $subcontractors,
            'services' => $services,
            'check_calls_count' => $shift->check_calls_count,
            'patrols_count'     => $shift->patrols_count,
        ]);
    }

    public function update(Request $request, $id)
    {
        $shift = ShiftDate::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status_id'   => 'nullable|integer',
            'staff_id'    => 'nullable|integer',
            'guard_rate'  => 'nullable|numeric',
            'subcontractor_id' => 'nullable',
            'start_shift' => 'nullable',
            'end_shift'   => 'nullable',
            'book_on'     => 'nullable',
            'book_off'    => 'nullable',
            'shift_date'  => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        
        // Only keep filled values, preserve existing data for empty fields
        $data = array_filter($data, function($value) {
            return $value !== null && $value !== '';
        });
        
        // preserve guard_rate if provided
        if ($request->filled('guard_rate')) {
            $data['guard_rate'] = $request->input('guard_rate');
        }

        if ($request->has('subcontractor_id')) {
            $data['subcontractor_id'] = $this->resolveSubcontractorUserId($request->subcontractor_id);
        }
        
        if (isset($data['book_on'])) {
            $data['absentee_start_time'] = $data['book_on'];
        }
        if (isset($data['book_off'])) {
            $data['absentee_end_time'] = $data['book_off'];
        }
        
        // Use existing values if not provided
        $startShift = $data['start_shift'] ?? $shift->start_time;
        $endShift = $data['end_shift'] ?? $shift->end_time;
        
        if (isset($data['start_shift'])) {
            $data['start_time'] = $data['start_shift'];
            // Normalize time format
            if (strlen($data['start_shift']) === 5) {
                $data['start_shift'] .= ':00';
                $data['start_time'] .= ':00';
            }
            $startShift = $data['start_shift'];
        }
        
        if (isset($data['end_shift'])) {
            $data['end_time'] = $data['end_shift'];
            // Normalize time format
            if (strlen($data['end_shift']) === 5) {
                $data['end_shift'] .= ':00';
                $data['end_time'] .= ':00';
            }
            $endShift = $data['end_shift'];
        }

        // Calculate total hours only if we have valid times
        if ($startShift && $endShift) {
            try {
                $data['total_hours'] = $this->calculateTotalHours(
                    $startShift,
                    $endShift,
                    'H:i:s'
                );
            } catch (\Exception $e) {
                // Keep existing total hours if calculation fails
                $data['total_hours'] = $shift->total_hours;
            }
        }

        if (isset($data['status_id'])) {
            $data['is_assign'] = $data['status_id'];
        }
        
        // Only update staff and is_assign when staff is actually being changed
        $staffChanged = isset($data['staff_id']) && $data['staff_id'] != $shift->staff_id;
        
        if ($staffChanged) {
            // Delete any existing bookings for this shift date before reassigning
            ShiftBooking::where('shift_id', $shift->id)->delete();

            // Staff is being changed - set to dispatched
            if ($data['staff_id']) {
                $shift->staff_id = $data['staff_id'];
                $shift->is_assign = 1;
                $shift->status = 'pending';

            } else {
                // Staff is being removed
                $shift->staff_id = null;
                $shift->is_assign = 1;
                $shift->status = 'pending';
            }
        } elseif (!isset($data['staff_id'])) {
            // No staff change - preserve existing status
        }
        
        if ($staffChanged) {
            $staffUser = Employee::where('user_id', $data['staff_id'])->first();
            if ($staffUser) {
                $staff = Employee::findOrFail($staffUser->id);

                // Ban restriction: do not allow assigning banned staff. Check site first,
                // then client if site not present. This cannot be overridden.
                $parentShift = $shift->shift ?? null;
                $parentSiteId = $parentShift->site_id ?? null;
                $parentClientId = $parentShift->client_id ?? null;
                if (EmployeeBan::isBannedFor($staff->id, $parentSiteId, $parentClientId)) {
                    return response()->json(['errors' => ['ban_forbidden' => 'Selected staff is banned for the shift site/client and cannot be assigned.']], 422);
                }


                $shiftDateValue = $data['shift_date'] ?? $shift->shift_date;
                $leave = LeaveRequest::where('user_id', $staff->user_id)
                    ->where('status', 'approved')
                    ->where('start_date', '<=', $shift->shift_date) // full datetime comparison
                    ->where('end_date', '>=', $shift->shift_date)   // full datetime comparison
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

                $newShiftHours = $data['total_hours'] ?? $shift->total_hours;

                // Build new shift start/end
                $shiftDate    = $data['shift_date'] ?? $shift->shift_date;
                $newStartTime = \Carbon\Carbon::parse($shiftDate . ' ' . $startShift);
                $newEndTime   = \Carbon\Carbon::parse($shiftDate . ' ' . $endShift);


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
                    ['type' => 'shift', 'shiftId' => $shift->id],
                );

                // $weekStart = now()->startOfWeek();
                // $weekEnd   = now()->endOfWeek();

                // $totalWeekHours = \App\Models\ShiftDate::where('staff_id', $staff->id)
                //     ->whereBetween('shift_date', [$weekStart, $weekEnd])
                //     ->sum('total_hours');

                // $minWeeklyHours = $entity->hour_per_week ?? 40;

                // $expectedHours = $totalWeekHours + $newShiftHours;

                // if ($expectedHours < $minWeeklyHours) {
                //     // 👇 Instead of blocking, just trigger a notification
                //     Notify::toDashboard(
                //         null,
                //         'alert',
                //         'Worked Hours',
                //         "Guard {$staff->fore_name} {$staff->sur_name} has only {$expectedHours} hours scheduled this week. Minimum is {$minWeeklyHours}.",
                //         "#"
                //     );
                // }
            }
        }

        $checkcalls = $shift->checkCalls;

        if ($staffChanged && $checkcalls && $checkcalls->isNotEmpty()) {
            foreach ($checkcalls as $checkCall) {
                $checkCall->employee_id = $request->input('staff_id');
                $checkCall->save();
            }
        }

        // ✅ Update shift
        $shift->update($data);

        // If subcontractor provided, ensure parent shift record is updated as well
        if (!empty($data['subcontractor_id'])) {
            try {
                $parent = $shift->shift;
                if ($parent) {
                    $parent->subcontractor_id = $data['subcontractor_id'];
                    $parent->save();
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to update parent shift subcontractor (updateWithOverride): '.$e->getMessage());
            }
        }

        // If subcontractor was provided in the request, ensure it's persisted
        if ($request->has('subcontractor_id')) {
            $shift->subcontractor_id = $data['subcontractor_id'] ?? null;
            $shift->save();

            try {
                $parent = $shift->shift;
                if ($parent) {
                    $parent->subcontractor_id = $data['subcontractor_id'] ?? null;
                    $parent->save();
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to update parent shift subcontractor (update): '.$e->getMessage());
            }
        }

        try { $this->rescheduleShiftEvents($shiftDate); } catch (\Throwable $_) {}


        return response()->json(['message' => 'Shift updated successfully']);
    }

    public function destroy($id)
    {
        $shiftDate = ShiftDate::findOrFail($id);

        Logger::log($shiftDate, 'Deleted', 'Shift at '.$shiftDate->shift->site->site_name.' Deleted');

        $bookings = ShiftBooking::where('shift_id', $shiftDate->id)->get();
        if($bookings){
            foreach($bookings as $booking){
                $booking->delete();
            }
        }

        if ($shiftDate->checkCalls) {
            foreach ($shiftDate->checkCalls as $checkCall) {
                $checkCall->delete();
            }
        }

        if ($shiftDate->patrols) {
            foreach ($shiftDate->patrols as $patrol) {
                $patrol->delete();
            }
        }

        if($shiftDate->staff_id){
            send_push_notification(
                $shiftDate->staff_id,
                'Shift Deleted',
                'An assigned shift for you has been deleted. (ID: ' . $shiftDate->id . ') at ' . $shiftDate->shift->site->site_name,
                ['type' => 'shift', 'shiftId' => $shiftDate->id],
                );
        }
        $shiftDate->forceDelete();

        // If the request expects JSON (AJAX), return JSON response.
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Shift deleted successfully',
                'redirect' => url('/scheduling'),
            ]);
        }

        // Non-AJAX: redirect back to scheduling page
        return redirect('/scheduling');
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:shift_dates,id',
        ]);

        $shifts= ShiftDate::whereIn('id', $request->ids)->get();
        foreach($shifts as $shiftDate){
            $bookings = ShiftBooking::where('shift_id', $shiftDate->id)->get();
            if($bookings){
                foreach($bookings as $booking){
                    $booking->delete();
                }
            }

            if ($shiftDate->checkCalls) {
                foreach ($shiftDate->checkCalls as $checkCall) {
                    $checkCall->delete();
                }
            }

            if ($shiftDate->patrols) {
                foreach ($shiftDate->patrols as $patrol) {
                    $patrol->delete();
                }
            }

            if($shiftDate->staff_id){
                send_push_notification(
                    $shiftDate->staff_id,
                    'Shift Deleted',
                    'An assigned shift for you has been deleted. (ID: ' . $shiftDate->id . ') at ' . $shiftDate->shift->site->site_name,
                    ['type' => 'shift', 'shiftId' => $shiftDate->id],
                    );
            }
            Logger::log($shiftDate, 'Deleted', 'Shift at '.$shiftDate->shift->site->site_name.' Deleted');
            $shiftDate->forceDelete();
        }

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
                    ['type' => 'shift', 'shiftId' => $shiftDate->id],
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

                try { $this->rescheduleShiftEvents($shiftDate); } catch (\Throwable $_) {}

                // Create shift booking
                $latestBooking = ShiftBooking::where('user_id', $shiftDate->staff_id)
                ->where('type', 'book_on')
                ->latest('created_at')
                ->first();

            if ($latestBooking) {
                $latestBooking->type = 'book_off';
                $latestBooking->timestamp = now();
                $latestBooking->save();
            }

                // Notify staff
                send_push_notification(
                    $shiftDate->staff_id,
                    'Shift assigned',
                    'An admin booked you OFF for shift (ID: ' . $shiftDate->id . ') ending at ' . $shiftDate->end_time,
                    ['type' => 'shift', 'shiftId' => $shiftDate->id],
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
    $query = \App\Models\ShiftDate::query()
        ->select([
            'shift_dates.id',
            'shift_dates.shift_date',
            'shift_dates.start_time',
            'shift_dates.end_time',
            'shift_dates.is_assign',
            'shift_dates.staff_id',
            'shift_dates.created_at',

            'shifts.id as shift_id',
            'shifts.site_id',
            'shifts.service_type_1',
            'shifts.service_type_2',

            // Return the parent shift's client identifier (this is the user's id stored on shifts.client_id)
            'shifts.client_id as client_id',
            'clients.client_name',

            'sites.site_name',

            'users.first_name',
            'users.last_name',

            // subcontractor stored as Subcontractor model id on shift_date or parent shift
            'shift_dates.subcontractor_id',
            'shifts.subcontractor_id as parent_subcontractor',

            'shift_notes.note',
            'shift_notes.note_type',
        ])
        ->join('shifts', 'shifts.id', '=', 'shift_dates.shift_id')
        ->leftJoin('clients', 'clients.user_id', '=', 'shifts.client_id')
        ->leftJoin('sites', 'sites.id', '=', 'shifts.site_id')
        ->leftJoin('users', 'users.id', '=', 'shift_dates.staff_id')
        // subcontractor resolved via Subcontractor model in PHP (avoid extra joins)
        ->leftJoin('shift_notes', 'shift_notes.shift_date_id', '=', 'shift_dates.id');

    /* ---------- FILTERS ---------- */

    if ($request->filled('site')) {
        $query->where('shifts.site_id', $request->site);
    }

    if ($request->filled('staff')) {
        $query->where('shift_dates.staff_id', $request->staff);
    }

    if ($request->filled('client_id')) {
        $query->where('shifts.client_id', $request->client_id);
    }

    if ($request->filled('status')) {
        $query->where('shift_dates.is_assign', $request->status);
    }

    if ($request->filled('start_time')) {
        $query->where('shift_dates.start_time', '>=', $request->start_time);
    }

    if ($request->filled('end_time')) {
        $query->where('shift_dates.end_time', '<=', $request->end_time);
    }

    if ($request->filled('created_at')) {
        $query->whereDate('shift_dates.created_at', $request->created_at);
    }

    $from = Carbon::parse($request->from_shift ?? now()->subMonths(1))->startOfDay();
    $to   = Carbon::parse($request->to_shift ?? now()->addMonths(2))->endOfDay();

    // cap range to avoid huge queries
    $maxDays = 90;
    if ($to->diffInDays($from) > $maxDays) {
        $to = $from->copy()->addDays($maxDays)->endOfDay();
    }

    $query->whereBetween('shift_dates.shift_date', [$from->format('Y-m-d'), $to->format('Y-m-d')]);

    // short cache to reduce repeated load. Increase default TTL to reduce CPU spikes
    // and allow DB to use an index for ordered scans.
    $cacheKey = 'gantt_v2:' . md5(json_encode($request->all()) . '|from:' . $from->format('Y-m-d') . '|to:' . $to->format('Y-m-d'));
    if ($cached = Cache::get($cacheKey)) {
        return response()->json(['data' => $cached]);
    }

    // Ensure predictable ordering (helps DB use indexes) before streaming rows
    $query->orderBy('shift_dates.shift_date', 'asc')->orderBy('shift_dates.start_time', 'asc');

    $ttl = config('gantt.cache_ttl', 10);

    $ganttArray = $this->formatGanttArray($query->cursor());
    Cache::put($cacheKey, $ganttArray, $ttl);

    return response()->json(['data' => $ganttArray]);
}

private function formatGanttData($shiftDates)
{
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

    $ganttData = [];

    foreach ($shiftDates as $sd) {

        $startTime = \Carbon\Carbon::createFromFormat('H:i:s', $sd->start_time);
        $endTime   = \Carbon\Carbon::createFromFormat('H:i:s', $sd->end_time);

        $startDate = \Carbon\Carbon::parse($sd->shift_date)->setTimeFrom($startTime);
        $endDate   = \Carbon\Carbon::parse($sd->shift_date)->setTimeFrom($endTime);

        if ($endTime->lessThanOrEqualTo($startTime)) {
            $endDate->addDay();
        }

        $durationHours   = $startDate->diffInHours($endDate);
        $durationMinutes = $startDate->diffInMinutes($endDate) % 60;

        // Clean staff name by removing all parenthesised tags (server-side canonical cleaning)
        $staffRaw = trim((($sd->first_name ?? '') . ' ' . ($sd->last_name ?? '')));
        $staffNameWithoutSub = $staffRaw;
        // remove nested parenthesis groups defensively
        while (preg_match('/\([^()]*\)/', $staffNameWithoutSub)) {
            $staffNameWithoutSub = preg_replace('/\s*\([^()]*\)/', '', $staffNameWithoutSub);
        }
        $staffNameClean = preg_replace('/\s+/', ' ', trim($staffNameWithoutSub));

        // Simplified subcontractor resolution: subcontractor_id references Subcontractor model
        $resolvedSubcontractorName = '';
        $resolvedSubcontractorId = $sd->subcontractor_id ?? ($sd->parent_subcontractor ?? null);

        if (!empty($resolvedSubcontractorId)) {
            try {
                $subModel = $this->findSubcontractorByStoredId($resolvedSubcontractorId);
                if ($subModel) {
                    $resolvedSubcontractorName = trim($subModel->company_name ?? '') ?: trim($subModel->contact_person ?? '');
                    if (!$resolvedSubcontractorName && isset($subModel->user) && $subModel->user) {
                        $resolvedSubcontractorName = trim((($subModel->user->first_name ?? '') . ' ' . ($subModel->user->last_name ?? '')));
                    }
                }
            } catch (\Throwable $_) {
                // ignore resolution errors
            }
        }

        $ganttData[] = [
            'id' => $sd->id,
            'site_id' => $sd->site_id,
            'site_name' => $sd->site_name ?? 'Unknown Site',
            'title' => $sd->title ?? 'Shift',
            'start_date' => $sd->shift_date,
            'end_date' => $sd->shift_date,
            'start_time' => $sd->start_time,
            'end_time' => $sd->end_time,
            'service_type' => $sd->service_type_2 ?? $sd->service_type_1,
            'formatted_time' => "{$startTime->format('H:i')} - {$endTime->format('H:i')}",
            'duration' => "({$durationHours} hr {$durationMinutes} min)",
            'staff_name' => $staffNameClean ?: 'Not Assigned',
            'staff_name_raw' => $staffRaw ?: '',
            'staff_name_clean' => $staffNameClean ?: '',
            'staff_id' => $sd->staff_id,
            'client_id' => $sd->client_id,
            'client_name' => $sd->client_name ?? 'Unknown Client',
            'color_class' => $statusColorMap[$sd->is_assign] ?? 'bg-secondary',
            'status' => $sd->is_assign,
            'is_assigned' => $sd->is_assign != 0,
            'duration_hours' => $durationHours + ($durationMinutes / 60),
            'start_datetime' => $startDate->format('Y-m-d\TH:i:s'),
            'end_datetime' => $endDate->format('Y-m-d\TH:i:s'),
            'note' => $sd->note,
            'note_type' => $sd->note_type,
            // Subcontractor data: prefer joined alias columns -> relation -> parent shift subcontractor
            'subcontractor_id' => $resolvedSubcontractorId,
            'subcontractor_name' => $resolvedSubcontractorName ?: '',
            'created_at' => optional($sd->created_at)->format('Y-m-d\TH:i:s'),
        ];
    }

    return response()->json(['data' => $ganttData]);
}

/**
 * Return gantt data as an array (used for caching/streaming)
 */
private function formatGanttArray($shiftDates)
{
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

    $ganttData = [];

    foreach ($shiftDates as $sd) {
        try {
            $startTime = \Carbon\Carbon::createFromFormat('H:i:s', $sd->start_time);
            $endTime   = \Carbon\Carbon::createFromFormat('H:i:s', $sd->end_time);
        } catch (\Throwable $e) {
            // skip malformed rows
            continue;
        }

        $startDate = \Carbon\Carbon::parse($sd->shift_date)->setTimeFrom($startTime);
        $endDate   = \Carbon\Carbon::parse($sd->shift_date)->setTimeFrom($endTime);

        if ($endTime->lessThanOrEqualTo($startTime)) {
            $endDate->addDay();
        }

        $durationHours   = $startDate->diffInHours($endDate);
        $durationMinutes = $startDate->diffInMinutes($endDate) % 60;

        $staffRaw = trim((($sd->first_name ?? '') . ' ' . ($sd->last_name ?? '')));
        $staffNameWithoutSub = $staffRaw;
        while (preg_match('/\([^()]*\)/', $staffNameWithoutSub)) {
            $staffNameWithoutSub = preg_replace('/\s*\([^()]*\)/', '', $staffNameWithoutSub);
        }
        $staffNameClean = preg_replace('/\s+/', ' ', trim($staffNameWithoutSub));

        // Simplified subcontractor resolution: subcontractor_id references Subcontractor model
        $resolvedSubcontractorName = '';
        $resolvedSubcontractorId = $sd->subcontractor_id ?? ($sd->parent_subcontractor ?? null);

        if (!empty($resolvedSubcontractorId)) {
            try {
                $subModel = $this->findSubcontractorByStoredId($resolvedSubcontractorId);
                if ($subModel) {
                    $resolvedSubcontractorName = trim($subModel->company_name ?? '') ?: trim($subModel->contact_person ?? '');
                    if (!$resolvedSubcontractorName && isset($subModel->user) && $subModel->user) {
                        $resolvedSubcontractorName = trim((($subModel->user->first_name ?? '') . ' ' . ($subModel->user->last_name ?? '')));
                    }
                }
            } catch (\Throwable $_) {
                // ignore resolution errors
            }
        }

        $ganttData[] = [
            'id' => $sd->id,
            'site_id' => $sd->site_id,
            'site_name' => $sd->site_name ?? 'Unknown Site',
            'title' => $sd->title ?? 'Shift',
            'start_date' => $sd->shift_date,
            'end_date' => $sd->shift_date,
            'start_time' => $sd->start_time,
            'end_time' => $sd->end_time,
            'service_type' => $sd->service_type_2 ?? $sd->service_type_1,
            'formatted_time' => "{$startTime->format('H:i')} - {$endTime->format('H:i')}",
            'duration' => "({$durationHours} hr {$durationMinutes} min)",
            'staff_name' => $staffNameClean ?: 'Not Assigned',
            'staff_name_raw' => $staffRaw ?: '',
            'staff_name_clean' => $staffNameClean ?: '',
            'staff_id' => $sd->staff_id,
            'client_id' => $sd->client_id,
            'client_name' => $sd->client_name ?? 'Unknown Client',
            'color_class' => $statusColorMap[$sd->is_assign] ?? 'bg-secondary',
            'status' => $sd->is_assign,
            'is_assigned' => $sd->is_assign != 0,
            'duration_hours' => $durationHours + ($durationMinutes / 60),
            'start_datetime' => $startDate->format('Y-m-d\TH:i:s'),
            'end_datetime' => $endDate->format('Y-m-d\TH:i:s'),
            'note' => $sd->note,
            'note_type' => $sd->note_type,
            'subcontractor_id' => $resolvedSubcontractorId,
            'subcontractor_name' => $resolvedSubcontractorName ?: '',
            'created_at' => optional($sd->created_at)->format('Y-m-d\TH:i:s'),
        ];
    }

    return $ganttData;
}



public function getShiftsWithStaff()
{
    $startDefault = now()->subMonths(2)->startOfDay();
    $endDefault   = now()->addMonths(1)->endOfDay();

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

    /**
     * SINGLE FAST QUERY
     */
    $shiftDates = ShiftDate::query()
        ->select([
            'shift_dates.id as sd_id',
            'shift_dates.shift_date',
            'shift_dates.start_time',
            'shift_dates.end_time',
            'shift_dates.is_assign',
            'shifts.id as shift_id',
            'shifts.days',
            'sites.site_name',
            'users.first_name',
            'users.last_name',
        ])
        ->join('shifts', 'shifts.id', '=', 'shift_dates.shift_id')
        ->leftJoin('sites', 'sites.id', '=', 'shifts.site_id')
        ->leftJoin('users', 'users.id', '=', 'shift_dates.staff_id')
        ->whereNotNull('shift_dates.staff_id')
        ->whereBetween('shift_dates.shift_date', [$startDefault, $endDefault])
        ->get();

    $events = [];
    $highlightDates = [];

    foreach ($shiftDates as $sd) {

        $dayList = json_decode($sd->days, true) ?: [];
        $dayName = Carbon::parse($sd->shift_date)->format('D');

        if (!empty($dayList) && !in_array($dayName, $dayList)) {
            continue;
        }

        $startTime = Carbon::createFromFormat('H:i:s', $sd->start_time);
        $endTime   = Carbon::createFromFormat('H:i:s', $sd->end_time);

        $startDateTime = Carbon::parse($sd->shift_date)->setTimeFrom($startTime);
        $endDateTime   = Carbon::parse($sd->shift_date)->setTimeFrom($endTime);

        // Handle overnight shift
        $endForDuration = $endTime->copy();
        if ($endForDuration->lessThan($startTime)) {
            $endForDuration->addDay();
        }

        $durationFormatted = sprintf(
            '%d hr %02d min',
            $startTime->diffInHours($endForDuration),
            $startTime->diffInMinutes($endForDuration) % 60
        );

        $events[] = [
            'title' => trim($sd->first_name . ' ' . $sd->last_name) ?: 'Unassigned',
            'start' => $startDateTime->format('Y-m-d\TH:i:s'),
            'end'   => $endDateTime->format('Y-m-d\TH:i:s'),
            'classNames' => [$statusColorMap[$sd->is_assign] ?? 'bg-secondary'],
            'extendedProps' => [
                'shift_id' => $sd->shift_id,
                'sd_id'    => $sd->sd_id,
                'location' => $sd->site_name ?? 'Unknown Site',
                'duration' => $durationFormatted,
                'start_time_str' => $startTime->format('H:i'),
                'end_time_str'   => $endTime->format('H:i'),
            ]
        ];

        $highlightDates[] = $sd->shift_date;
    }

    return response()->json([
        'events' => $events,
        'highlightDates' => array_values(array_unique($highlightDates))
    ]);
}



public function getShiftsBySite()
{
    $user = Auth::user();

    $startDefault = now()->subMonths(2)->startOfDay();
    $endDefault   = now()->addMonths(1)->endOfDay();

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

    /**
     * 🔥 SINGLE FAST QUERY
     */
    $query = \App\Models\ShiftDate::query()
        ->select([
            'shift_dates.id as sd_id',
            'shift_dates.shift_date',
            'shift_dates.start_time',
            'shift_dates.end_time',
            'shift_dates.is_assign',
            'shifts.client_id',
            'sites.site_name',
        ])
        ->join('shifts', 'shifts.id', '=', 'shift_dates.shift_id')
        ->leftJoin('sites', 'sites.id', '=', 'shifts.site_id')
        ->whereBetween('shift_dates.shift_date', [$startDefault, $endDefault]);

    // ✅ Client filter at DB level
    if ($user && method_exists($user, 'hasRole') && $user->hasRole('client')) {
        $query->where('shifts.client_id', $user->id);
    }

    $rows = $query->get();

    $events = [];
    $highlightDates = [];

    foreach ($rows as $sd) {

        $startTime = Carbon::createFromFormat('H:i:s', $sd->start_time);
        $endTime   = Carbon::createFromFormat('H:i:s', $sd->end_time);

        // Duration (handle overnight)
        $endForDuration = $endTime->copy();
        if ($endForDuration->lessThanOrEqualTo($startTime)) {
            $endForDuration->addDay();
        }

        $durationFormatted = sprintf(
            '%d hr %02d min',
            $startTime->diffInHours($endForDuration),
            $startTime->diffInMinutes($endForDuration) % 60
        );

        // Calendar display (same day)
        $calendarStart = Carbon::parse($sd->shift_date . ' ' . $sd->start_time)->format('Y-m-d\TH:i:s');
        $calendarEnd   = Carbon::parse($sd->shift_date . ' ' . $sd->end_time)->format('Y-m-d\TH:i:s');

        $events[] = [
            'title' => $sd->site_name ?? 'Unknown Site',
            'start' => $calendarStart,
            'end'   => $calendarEnd,
            'allDay' => false,
            'classNames' => [$statusColorMap[$sd->is_assign] ?? 'bg-secondary'],
            'color' => '#3a87ad',
            'extendedProps' => [
                'duration' => $durationFormatted,
                'startTime' => $startTime->format('H:i'),
                'endTime'   => $endTime->format('H:i'),
                'start_time' => $startTime->format('H:i:s'),
                'end_time'   => $endTime->format('H:i:s'),
                'startTimeStr' => $startTime->format('H:i'),
                'endTimeStr'   => $endTime->format('H:i'),
                'urgent' => rand(0, 1) === 1,
                'sd_id' => $sd->sd_id,
            ]
        ];

        $highlightDates[] = $sd->shift_date;
    }

    return response()->json([
        'events' => $events,
        'highlightDates' => array_values(array_unique($highlightDates)),
    ]);
}



public function getTodayShifts()
{
    $today     = now()->toDateString(); // Y-m-d
    $todayDay  = now()->format('D');    // Mon, Tue...

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

    /**
     * 🔥 SINGLE FAST QUERY
     */
    $rows = \App\Models\ShiftDate::query()
        ->select([
            'shift_dates.id as sd_id',
            'shift_dates.shift_date',
            'shift_dates.start_time',
            'shift_dates.end_time',
            'shift_dates.is_assign',
            'shifts.days',
            'clients.client_name as client_name',
            'sites.site_name',
            'users.first_name',
            'users.last_name',
        ])
        ->join('shifts', 'shifts.id', '=', 'shift_dates.shift_id')
        ->leftJoin('clients', 'clients.id', '=', 'shifts.client_id')
        ->leftJoin('sites', 'sites.id', '=', 'shifts.site_id')
        ->leftJoin('users', 'users.id', '=', 'shift_dates.staff_id')
        ->whereDate('shift_dates.shift_date', $today)
        ->get();

    $events = [];

    foreach ($rows as $sd) {

        // Day filter (PHP side, cheap)
        $dayList = json_decode($sd->days, true) ?: [];
        if (!empty($dayList) && !in_array($todayDay, $dayList)) {
            continue;
        }

        $startTime = Carbon::createFromFormat('H:i:s', $sd->start_time);
        $endTime   = Carbon::createFromFormat('H:i:s', $sd->end_time);

        // Duration (overnight handling)
        $endForDuration = $endTime->copy();
        if ($endForDuration->lessThan($startTime)) {
            $endForDuration->addDay();
        }

        $durationFormatted = sprintf(
            '%d hr %02d min',
            $startTime->diffInHours($endForDuration),
            $startTime->diffInMinutes($endForDuration) % 60
        );

        $startDateTime = Carbon::parse("{$sd->shift_date} {$sd->start_time}");
        $endDateTime   = Carbon::parse("{$sd->shift_date} {$sd->end_time}");

        $events[] = [
            'title' => $sd->client_name ?? 'Unknown Client',
            'start' => $startDateTime->format('Y-m-d\TH:i:s'),
            'end'   => $endDateTime->format('Y-m-d\TH:i:s'),
            'allDay' => false,
            'color'  => '#3a87ad',
            'className' => $statusColorMap[$sd->is_assign] ?? 'bg-secondary',
            'extendedProps' => [
                'duration' => $durationFormatted,
                'client'   => $sd->client_name ?? '',
                'site'     => $sd->site_name ?? '',
                'staff'    => trim($sd->first_name . ' ' . $sd->last_name),
                'urgent'   => rand(0, 1) === 1,
                'sd_id'    => $sd->sd_id,
                'start_time_str' => $startTime->format('H:i'),
                'end_time_str'   => $endTime->format('H:i'),
            ]
        ];
    }

    return response()->json($events);
}


    private function calculateTotalHours($start, $end, $format = null)
    {
        // Accept a variety of input formats (H:i, H:i:s). If the caller supplied
        // a preferred format we'll try it first, otherwise try common time formats.
        $tryFormats = [];
        if ($format) $tryFormats[] = $format;
        $tryFormats = array_merge($tryFormats, ['H:i:s', 'H:i']);

        $parseTime = function ($value) use ($tryFormats) {
            foreach ($tryFormats as $fmt) {
                try {
                    $t = \Carbon\Carbon::createFromFormat($fmt, $value);
                    if ($t) return $t;
                } catch (\Throwable $_) {
                    // continue
                }
            }

            // As a final fallback, let Carbon attempt to parse any reasonable time string
            try {
                return \Carbon\Carbon::parse($value);
            } catch (\Throwable $e) {
                throw new \Exception('Unable to parse time: ' . $value);
            }
        };

        $startTime = $parseTime($start);
        $endTime = $parseTime($end);

        // Handle overnight shifts (e.g. 22:00 to 06:00 next day)
        if ($endTime->lessThanOrEqualTo($startTime)) {
            $endTime->addDay();
        }

        $totalHours = $startTime->diffInMinutes($endTime) / 60;

        // Return numeric rounded hours (float)
        return round($totalHours, 2);
    }

    /**
     * Reschedule Patrols and CheckCalls for a ShiftDate when its start/end
     * times change or the shift is updated.
     *
     * Strategy:
     *  - Keep already-completed events untouched.
     *  - Delete ALL pending auto-generated checkcalls ("Auto CheckCall …") and
     *    auto-generated patrols ("Auto Patrol …") for this shift.
     *  - Recreate them based on the new duration (one per hour, same as
     *    the original creation logic).
     *  - Manually-named checkcalls/patrols have their scheduled_time updated
     *    proportionally instead of being deleted.
     */
    private function rescheduleShiftEvents(\App\Models\ShiftDate $shiftDate)
    {
        // ── 1. Parse new shift window ────────────────────────────────────────
        try {
            $dateOnly = \Carbon\Carbon::parse($shiftDate->shift_date)->toDateString();
        } catch (\Throwable $_) {
            $dateOnly = (string) $shiftDate->shift_date;
        }

        try {
            $start = \Carbon\Carbon::parse($dateOnly . ' ' . $shiftDate->start_time);
        } catch (\Throwable $_) {
            return; // can't do anything without a valid start
        }

        try {
            $end = \Carbon\Carbon::parse($dateOnly . ' ' . $shiftDate->end_time);
        } catch (\Throwable $_) {
            return;
        }

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay(); // overnight shift
        }

        $durationMinutes = $start->diffInMinutes($end);
        if ($durationMinutes <= 0) {
            $durationMinutes = 1440;
        }

        // Number of hourly slots (same formula used during creation)
        $slots = (int) ceil($durationMinutes / 60);

        // ── 2. Get site info for patrol checkpoints ───────────────────────────
        $totalCheckpoints = 0;
        try {
            $site = \App\Models\Site::with('checkpoints')->find($shiftDate->shift->site_id ?? null);
            $totalCheckpoints = $site?->checkpoints?->count() ?? 0;
        } catch (\Throwable $_) {}

        $requireMedia = $shiftDate->require_media ?? 0;
        $staffId      = $shiftDate->staff_id ?? null;

        // ── 3. Handle CheckCalls ─────────────────────────────────────────────
        $existingCCs = \App\Models\CheckCall::where('shift_id', $shiftDate->id)->get();

        // Separate completed from pending auto-generated
        $completedCCs = $existingCCs->filter(fn($cc) =>
            ($cc->status === 'completed') || !empty($cc->completed_at)
        );
        $pendingAutoCCs = $existingCCs->filter(fn($cc) =>
            ($cc->status !== 'completed') &&
            empty($cc->completed_at) &&
            preg_match('/^Auto CheckCall\s+\d+$/i', $cc->name ?? '')
        );
        $pendingManualCCs = $existingCCs->filter(fn($cc) =>
            ($cc->status !== 'completed') &&
            empty($cc->completed_at) &&
            !preg_match('/^Auto CheckCall\s+\d+$/i', $cc->name ?? '')
        );

        // Only recreate auto checkcalls if there were any before (or if none exist yet)
        $hadAutoCCs = $pendingAutoCCs->isNotEmpty();

        // Delete pending auto checkcalls
        foreach ($pendingAutoCCs as $cc) {
            try { $cc->delete(); } catch (\Throwable $_) {}
        }

        // Recreate auto checkcalls if there were auto ones originally
        if ($hadAutoCCs) {
            for ($n = 0; $n < $slots; $n++) {
                $checkTime = $start->copy()->addHours($n);
                try {
                    \App\Models\CheckCall::create([
                        'shift_id'       => $shiftDate->id,
                        'employee_id'    => $staffId,
                        'name'           => 'Auto CheckCall ' . ($n + 1),
                        'scheduled_time' => $checkTime->format('Y-m-d H:i:s'),
                        'status'         => 'pending',
                        'require_media'  => $requireMedia,
                    ]);
                } catch (\Throwable $_) {}
            }
        }

        // Update manual checkcall times to stay within the new window
        foreach ($pendingManualCCs as $cc) {
            try {
                $scheduled = \Carbon\Carbon::parse($cc->scheduled_time);
                // If it falls outside the new window, clamp to shift start
                if ($scheduled->lessThan($start) || $scheduled->greaterThan($end)) {
                    $cc->scheduled_time = $start->format('Y-m-d H:i:s');
                    $cc->save();
                }
            } catch (\Throwable $_) {}
        }

        // ── 4. Handle Patrols ────────────────────────────────────────────────
        $existingPatrols = \App\Models\Patrol::where('shift_id', $shiftDate->id)->get();

        $completedPatrols = $existingPatrols->filter(fn($p) =>
            ($p->status === 'completed') || !empty($p->completed_at)
        );
        $pendingAutoPatrols = $existingPatrols->filter(fn($p) =>
            ($p->status !== 'completed') &&
            empty($p->completed_at) &&
            preg_match('/^Auto Patrol\s+\d+$/i', $p->name ?? '')
        );
        $pendingManualPatrols = $existingPatrols->filter(fn($p) =>
            ($p->status !== 'completed') &&
            empty($p->completed_at) &&
            !preg_match('/^Auto Patrol\s+\d+$/i', $p->name ?? '')
        );

        $hadAutoPatrols = $pendingAutoPatrols->isNotEmpty();

        // Delete pending auto patrols
        foreach ($pendingAutoPatrols as $p) {
            try { $p->delete(); } catch (\Throwable $_) {}
        }

        // Recreate auto patrols if there were auto ones originally
        if ($hadAutoPatrols) {
            for ($n = 0; $n < $slots; $n++) {
                $patrolTime = $start->copy()->addHours($n);
                try {
                    \App\Models\Patrol::create([
                        'shift_id'              => $shiftDate->id,
                        'name'                  => 'Auto Patrol ' . ($n + 1),
                        'summary'               => 'Scheduled patrol at ' . $patrolTime->format('H:i'),
                        'start_time'            => $patrolTime->format('Y-m-d H:i:s'),
                        'status'                => 'pending',
                        'total_checkpoints'     => $totalCheckpoints,
                        'completed_checkpoints' => 0,
                        'issues_reported'       => 0,
                        'completed_at'          => null,
                    ]);
                } catch (\Throwable $_) {}
            }
        }

        // Update manual patrol times to stay within the new window
        foreach ($pendingManualPatrols as $p) {
            try {
                $pt = \Carbon\Carbon::parse($p->start_time);
                if ($pt->lessThan($start) || $pt->greaterThan($end)) {
                    $p->start_time = $start->format('Y-m-d H:i:s');
                    $p->summary    = 'Scheduled patrol at ' . $start->format('H:i');
                    $p->save();
                }
            } catch (\Throwable $_) {}
        }
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

            // if ($combinedMinutes > 960) {
            //     throw new \Exception("Shift on " . $date->format('Y-m-d') . " exceeds 16 hours including existing shifts.");
            // }

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
            'subcontractor_id' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if override flag is set
        $override = $request->input('override', false);

        $staffId   = $request->staff_id;
        // tolerate missing Employee record
        $staffUser = Employee::where('user_id', $staffId)->first();
        $staffUserId = $staffUser?->user_id ?? $staffId;
        $shiftDate = ShiftDate::findOrFail($request->shift_id);
        $shift     = Shift::findOrFail($shiftDate->shift_id);

        // Ban restriction: always enforce, cannot be overridden
        if (EmployeeBan::isBannedFor($staffUser->id, $shift->site_id ?? null, $shift->client_id ?? null)) {
            return response()->json(['errors' => ['ban_forbidden' => 'Selected staff is banned for the shift site/client and cannot be assigned.']], 422);
        }

        // Ban restriction: always enforce, cannot be overridden
        if (EmployeeBan::isBannedFor($staffUser->id, $shift->site_id ?? null, $shift->client_id ?? null)) {
            return response()->json(['errors' => ['ban_forbidden' => 'Selected staff is banned for the shift site/client and cannot be assigned.']], 422);
        }

        // ====== 1️⃣ Check for approved leave ======
        if (!$override) {
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
        $newShiftStart = $this->combineDateTime($shiftDate->shift_date, $shiftDate->start_time);

        // ✅ Apply all restrictions only if not overriding
        if (!$override) {
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
        }

        // ✅ Overlapping check - only if not overriding
        if (!$override) {
            $newStart = $this->combineDateTime($shiftDate->shift_date, $shiftDate->start_time);
            $newEnd   = $this->combineDateTime($shiftDate->shift_date, $shiftDate->end_time);

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
        }

        // ✅ Assign shift if passes all checks
        $shiftDate->staff_id = $staff->user_id;
        $shiftDate->is_assign = 1;
        $shiftDate->status = 'pending';
        // store subcontractor on the shift_date and also update parent shift record
        $resolvedSubcontractorUserId = $this->resolveSubcontractorUserId($request->subcontractor_id);
        $shiftDate->subcontractor_id = $resolvedSubcontractorUserId;
        $shiftDate->save();


        if ($request->has('subcontractor_id')) {
            try {
                $parent = $shiftDate->shift;
                if ($parent) {
                    $parent->subcontractor_id = $resolvedSubcontractorUserId;
                    $parent->save();
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to update parent shift subcontractor: '.$e->getMessage());
            }
        }

        send_push_notification(
            $staff->user_id,
            'Shift assigned',
            'An admin assigned a shift for you, You have to respond!',
            ['type' => 'shift', 'shiftId' => $shiftDate->id],
        );

        // $weekStart = now()->startOfWeek();
        // $weekEnd   = now()->endOfWeek();

        // $totalWeekHours = \App\Models\ShiftDate::where('staff_id', $shiftDate->staff_id)
        //     ->whereBetween('shift_date', [$weekStart, $weekEnd])
        //     ->sum('total_hours');

        // $minWeeklyHours = $entity->hour_per_week ?? 40;

        // $expectedHours = $totalWeekHours + $newShiftHours;

        // if ($expectedHours < $minWeeklyHours) {
        //     // 👇 Instead of blocking, just trigger a notification
        //     Notify::toDashboard(
        //         null,
        //         'alert',
        //         'Worked Hours',
        //         "Guard {$staff->fore_name} {$staff->sur_name} has only {$expectedHours} hours scheduled this week. Minimum is {$minWeeklyHours}.",
        //         "#"
        //     );
        // }

        $checkcalls = $shiftDate->checkCalls;
        
        if($checkcalls && $checkcalls->isNotEmpty()){
            foreach ($checkcalls as $checkcall) {
                if($checkcall->status !=='completed'){
                    $checkcall->employee_id = $request->staff_id;
                    $checkcall->save();
                }
            }
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

    public function subcontractorsForEmployee($id)
    {
        $employee = \App\Models\Employee::where('user_id', $id)->first();

        $subs = collect();
        if (! $employee) {
            return response()->json(['data' => $subs]);
        }

        $raw = $employee->subcontractor;

        // Normalize possible formats: array, Collection, JSON string, CSV string, single value
        if (is_null($raw) || $raw === '') {
            $ids = [];
        } elseif (is_array($raw)) {
            $ids = array_filter($raw);
        } elseif ($raw instanceof \Illuminate\Support\Collection || $raw instanceof \Illuminate\Database\Eloquent\Collection) {
            $ids = array_filter($raw->all());
        } elseif (is_object($raw) && method_exists($raw, 'toArray')) {
            $ids = array_filter($raw->toArray());
        } else {
            // Attempt JSON decode first (handles '[]' formatted strings)
            $decoded = @json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $ids = array_filter($decoded);
            } else {
                // Fallback: comma-separated list or single scalar
                $ids = array_filter(array_map('trim', explode(',', (string) $raw)));
            }
        }

        $ids = array_map('intval', $ids);

        if (empty($ids)) {
            return response()->json(['data' => $subs]);
        }

        // 1) Prefer resolving the stored ids as Subcontractor.user_id (employee stores user ids)
        $found = \App\Models\Subcontractor::whereIn('user_id', $ids)
            ->select('id', 'company_name', 'user_id', 'email')
            ->orderBy('company_name')
            ->get();

        // 2) Fallback: maybe the stored ids are Subcontractor model ids
        if ($found->isEmpty()) {
            $found = \App\Models\Subcontractor::whereIn('id', $ids)
                ->select('id', 'company_name', 'user_id', 'email')
                ->orderBy('company_name')
                ->get();
        }

        // 3) Final fallback: maybe the stored ids are user ids (users with subcontractor role)
        if ($found->isEmpty()) {
            $users = User::role('subcontractor')
                ->whereIn('id', $ids)
                ->select('id', 'first_name', 'last_name', 'email')
                ->orderBy('first_name')
                ->get();

            $subs = $users->map(function ($u) {
                return [
                    'id' => $u->id,
                    'first_name' => $u->first_name,
                    'last_name' => $u->last_name,
                    'email' => $u->email,
                ];
            });

            return response()->json(['data' => $subs]);
        }

        // Map Subcontractor model to frontend shape: company_name => first_name, last_name => null
        $subs = $found->map(function ($s) {
            return [
                'id' => $s->id,
                'first_name' => $s->company_name,
                'last_name' => null,
                'email' => $s->email,
                'user_id' => $s->user_id,
            ];
        });

        return response()->json(['data' => $subs]);
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

    /**
     * Site tracking view. Shows latest locations for a single site when refreshed.
     */
    public function siteTracking($siteId)
    {
        $site = \App\Models\Site::find($siteId);
        if (!$site) abort(404);

        // Provide address/postcode to the view to allow client-side geocoding and centering
        $siteAddress = $site->address ?? '';
        $siteZip = $site->post_code ?? $site->postcode ?? '';

        return view('tracking.site', compact('siteId', 'site', 'siteAddress', 'siteZip'));
    }

    public function shiftLocations(Request $request, $shiftDateId)
    {
        $shiftDate = ShiftDate::findOrFail($shiftDateId);
        $user = $shiftDate->staff;

        if (!$user) {
            return response()->json(['message' => 'No staff assigned to this shift.'], 404);
        }

        // optional requested cap; default 2500
        $max = (int) $request->query('max_points', 2500);
        $max = max(50, min($max, 5000)); // enforce safe bounds

        // Use the GPS timestamp for ordering and display (created_at may reflect DB insert time)
        // Only include locations with acceptable accuracy (<= 50 meters)
        $q = Location::where('shiftdate_id', $shiftDate->id)
            ->whereNotNull('accuracy')
            ->where('accuracy', '<=', 100)
            ->orderBy('timestamp');

        $total = $q->count();

        // If total is small, return all rows
        if ($total <= $max) {
            $rows = $q->get(['latitude', 'longitude', 'timestamp', 'accuracy'])->toArray();
        } else {
            // Sample: pick roughly evenly-spaced indices to reduce payload
            // Calculate sampling step and select those rows by offset (efficient approach: use chunk but simpler here)
            $step = ceil($total / $max);
            $rows = [];
            // Use cursor to avoid memory spike
            $index = 0;
            foreach ($q->cursor() as $r) {
                if ($index % $step === 0) {
                    $rows[] = [
                        'latitude'  => (string) $r->latitude,
                        'longitude' => (string) $r->longitude,
                        'timestamp' => $r->timestamp,
                        'accuracy'  => $r->accuracy ?? null,
                    ];
                    if (count($rows) >= $max) break;
                }
                $index++;
            }
        }

        return response()->json([
            'shift' => [
                'id' => $shiftDate->id,
                'date' => $shiftDate->shift_date,
                'start_time' => $shiftDate->start_time,
                'end_time' => $shiftDate->end_time,
            ],
            'user' => [
                'id' => $user->id,
                'name' => ($user->fore_name ?? $user->first_name ?? '') . ' ' . ($user->sur_name ?? $user->last_name ?? ''),
            ],
            'locations' => $rows,
            'meta' => [
                'requested_max' => $max,
                'returned' => count($rows),
                'total_available' => $total,
            ],
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

        $subcontractors = $this->getSubcontractors();


        return view('security_boards.shift-detail', compact('shiftDate','subcontractors'));
    }

public function patrolUpdate(Request $request, $id)
{
    $patrol = Patrol::findOrFail($id);

    $request->validate([
        'name' => 'required|string|max:255',
        'start_time' => 'required_unless:status,pending|nullable|regex:/^\d{1,2}:\d{2}(:\d{2})?$/',
        'status' => 'required|in:pending,in_progress,completed,missed',
        'approval_status' => 'nullable|in:pending,approved,rejected',
    ]);

    $status = $request->input('status');

    $patrolDate = $patrol->date ?? Carbon::now()->toDateString();

    $updateData = [
        'name' => $request->input('name'),
        'status' => $status,
    ];

    if ($status === 'pending' && $patrol->status !== 'pending') {
        // Use patrol date + current time
        $timeNow = Carbon::now()->format('H:i:s');
        $updateData['start_time'] = $patrolDate . ' ' . $timeNow;
    } else {
        $raw = $request->input('start_time'); // e.g. "05:00" or "05:00:00"
        if ($raw !== null) {
            $timePart = (strlen($raw) === 5) ? $raw . ':00' : $raw;
            $updateData['start_time'] = $patrolDate . ' ' . $timePart;
        }
    }

    if ($request->has('approval_status')) {
        $updateData['approval_status'] = $request->input('approval_status');
    }

    $patrol->update($updateData);
    $patrol->refresh();

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
        Logger::log($patrol, 'Deleted', 'CheckCall deleted for shift at ' . $patrol->shift->shift->site->site_name);

        $patrol->delete();

        return response()->json(['success' => true]);
    }

    public function patrolApprove($id)
    {
        $patrol = Patrol::findOrFail($id);

        // Only allow approval if patrol is completed
        if ($patrol->status !== 'completed') {
            return response()->json([
                'message' => 'Only completed patrols can be approved'
            ], 400);
        }

        // Only allow approval if currently pending
        if ($patrol->approval_status !== 'pending' && $patrol->approval_status !== null) {
            return response()->json([
                'message' => 'Patrol has already been ' . $patrol->approval_status
            ], 400);
        }

        $patrol->approval_status = 'approved';
        $patrol->save();

        $shiftDate = ShiftDate::find($patrol->shift_id);
        if ($shiftDate && $shiftDate->staff_id) {
            send_push_notification(
                $shiftDate->staff_id,
                'Patrol Approved',
                'Your patrol "' . $patrol->name . '" has been approved by admin.',
                ['type' => 'patrol', 'patrolId' => $patrol->id],
            );
        }

        return response()->json([
            'message' => 'Patrol approved successfully',
            'patrol' => $patrol
        ]);
    }

    public function patrolReject($id)
    {
        $patrol = Patrol::findOrFail($id);

        // Only allow rejection if patrol is completed
        if ($patrol->status !== 'completed') {
            return response()->json([
                'message' => 'Only completed patrols can be rejected'
            ], 400);
        }

        // Only allow rejection if currently pending
        if ($patrol->approval_status !== 'pending' && $patrol->approval_status !== null) {
            return response()->json([
                'message' => 'Patrol has already been ' . $patrol->approval_status
            ], 400);
        }

        $patrol->approval_status = 'rejected';
        $patrol->status = 'pending';
        $patrol->save();

        $shiftDate = ShiftDate::find($patrol->shift_id);
        if ($shiftDate && $shiftDate->staff_id) {
            send_push_notification(
                $shiftDate->staff_id,
                'Patrol Rejected',
                'Your patrol "' . $patrol->name . '" has been rejected by admin.',
                ['type' => 'patrol', 'patrolId' => $patrol->id],
            );
        }

        return response()->json([
            'message' => 'Patrol rejected successfully',
            'patrol' => $patrol
        ]);
    }

    public function multiAssign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shift_ids'   => 'required|array',
            'shift_ids.*' => 'exists:shift_dates,id',
            'staff_id'    => 'nullable|exists:users,id',
            'subcontractor_id' => 'nullable',
            'start_times' => 'nullable|array', // optional keyed by shift ID
            'end_times'   => 'nullable|array', // optional keyed by shift ID
            'book_on'     => 'nullable|array', // optional keyed by shift ID
            'book_off'    => 'nullable|array', // optional keyed by shift ID
            'shift_dates'    => 'nullable|array', // optional keyed by shift ID
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $staffId   = $request->staff_id ?? null;
        // Allow missing Employee record: prefer Employee but tolerate absence.
        $staffUser = null;
        $staffEmployeeId = null; // employee table id (may be null)
        $staffUserId = $staffId; // users.id
        if ($request->filled('staff_id')) {
            $staffUser = Employee::where('user_id', $staffId)->first();
            $staffEmployeeId = $staffUser?->id;
            $staffUserId = $staffId;
        }

        $updatedShifts = [];
        $errors = [];

        foreach ($request->shift_ids as $shiftId) {
            $shiftDate = ShiftDate::findOrFail($shiftId);
            $shift     = Shift::findOrFail($shiftDate->shift_id);

            // ====== 1️⃣ Check for approved leave ======
            if ($staffId) {
                $shiftStart = $this->combineDateTime($shiftDate->shift_date, ($request->start_times[$shiftId] ?? $shiftDate->start_time));
                $shiftEnd   = $this->combineDateTime($shiftDate->shift_date, ($request->end_times[$shiftId] ?? $shiftDate->end_time));

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

            $newShiftStart = $this->combineDateTime($shiftDate->shift_date, $newStart);
            $newShiftEnd   = $this->combineDateTime($shiftDate->shift_date, $newEnd);
            if ($newShiftEnd->lte($newShiftStart)) {
                $newShiftEnd->addDay();
            }

            // Only perform restriction and overlap checks when a staff_id was provided
            if ($staffId) {
                // Calculate total hours for restriction checks
                $selectedDays = explode(',', trim($shift->days, '"[]"'));
                $newShiftHours = $this->calculateTotalWorkingHours(
                    $staffEmployeeId,
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
                $overlap = ShiftDate::where('staff_id', $staffUser?->user_id ?? $staffUserId)
                    ->where(function ($query) use ($newShiftStart, $newShiftEnd) {
                        $query->whereRaw('TIMESTAMP(shift_date, start_time) < ?', [$newShiftEnd])
                            ->whereRaw('TIMESTAMP(shift_date, end_time) > ?', [$newShiftStart]);
                    })
                    ->exists();

                if ($overlap) {
                    $errors[$shiftId] = ['overlap' => 'This staff already has a shift during this time.'];
                    continue;
                }
            }

            // Update shift times and dates
            $shiftDate->start_time          = $newStart;
            $shiftDate->end_time            = $newEnd;
            $shiftDate->absentee_start_time = $bookOn;
            $shiftDate->absentee_end_time   = $bookOff;
            // Normalize incoming date to Y-m-d to avoid storing different formats
            try {
                $normalized = \Carbon\Carbon::parse($newDate)->format('Y-m-d');
            } catch (\Exception $e) {
                $normalized = $newDate; // fallback to raw value
            }
            $shiftDate->shift_date          = $normalized;

            // Normalize time format for hours calculation
            $startCalc = strlen($newStart) === 5 ? $newStart . ':00' : $newStart;
            $endCalc   = strlen($newEnd) === 5 ? $newEnd . ':00' : $newEnd;
            $shiftDate->total_hours = $this->calculateTotalHours($startCalc, $endCalc, 'H:i:s');

            if ($request->has('subcontractor_id')) {
                $resolvedSubcontractorUserId = $this->resolveSubcontractorUserId($request->subcontractor_id);
                $shiftDate->subcontractor_id = $resolvedSubcontractorUserId;
                try {
                    $parent = $shiftDate->shift;
                    if ($parent) {
                        $parent->subcontractor_id = $resolvedSubcontractorUserId;
                        $parent->save();
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to update parent shift subcontractor (multiAssign non-override): '.$e->getMessage());
                }
            }

            // Only update staff and is_assign when staff is actually being changed
            $oldStaffId = $shiftDate->staff_id;
            $newStaffId = $staffUser?->user_id ?? $staffUserId;

            if ($newStaffId != $oldStaffId) {
                // Delete any existing bookings before reassigning
                ShiftBooking::where('shift_id', $shiftDate->id)->delete();

                // Staff is being changed - set to dispatched
                $shiftDate->staff_id = $newStaffId;
                $shiftDate->is_assign = 1;
                $shiftDate->status = 'pending';
            }
            // If same staff, preserve current is_assign and status

            $shiftDate->save();

            // After saving a changed shift, reschedule related patrols & checkcalls
            try { $this->rescheduleShiftEvents($shiftDate); } catch (\Throwable $_) {}

            // Push notification only if staff was assigned
            if (!empty($newStaffId)) {
                send_push_notification(
                    $newStaffId,
                    'Shift assigned',
                    'An admin assigned a shift for you, You have to respond!',
                    ['type' => 'shift', 'shiftId' => $shiftDate->id]
                );
            }

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
                'subcontractor_id' => $shift->subcontractor_id ?? ($shift->shift->subcontractor_id ?? null),
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

    public function recentNotes(Request $request)
    {
        $after = intval($request->query('after', 0));

        $notes = ShiftNote::where('id', '>', $after)->orderBy('id', 'asc')->get();

        return response()->json(['notes' => $notes]);
    }

    public function deleteNote($shiftDateId)
    {
        // The routes use /shift-dates/{id}/note for GET/POST/DELETE where {id}
        // refers to the shift_date id. For consistency, delete the ShiftNote
        // associated with the given shift_date_id.
        $note = ShiftNote::where('shift_date_id', $shiftDateId)->first();
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
            'subcontractor_id' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $staffId   = $request->staff_id;
        // tolerate missing Employee record
        $staffUser = Employee::where('user_id', $staffId)->first();
        $staffUserId = $staffUser?->user_id ?? $staffId;
        $shiftDate = ShiftDate::findOrFail($request->shift_id);
        $shift     = Shift::findOrFail($shiftDate->shift_id);

        // ⚠ Skip restrictions entirely
        // ✅ Still enforce overlap check to avoid double booking
        $newStart = $this->combineDateTime($shiftDate->shift_date, $shiftDate->start_time);
        $newEnd   = $this->combineDateTime($shiftDate->shift_date, $shiftDate->end_time);

        if ($newEnd->lte($newStart)) {
            $newEnd->addDay();
        }

        $overlap = ShiftDate::where('staff_id', $staffUser?->user_id ?? $staffUserId)
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
        $shiftDate->staff_id = $staffUser?->user_id ?? $staffUserId;
        $shiftDate->is_assign = 1;
        $shiftDate->status = 'pending';
        // store subcontractor on the shift_date and also update parent shift record
        $resolvedSubcontractorUserId = $this->resolveSubcontractorUserId($request->subcontractor_id);
        $shiftDate->subcontractor_id = $resolvedSubcontractorUserId;
        $shiftDate->save();


        if ($request->has('subcontractor_id')) {
            try {
                $parent = $shiftDate->shift;
                if ($parent) {
                    $parent->subcontractor_id = $resolvedSubcontractorUserId;
                    $parent->save();
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to update parent shift subcontractor (override): '.$e->getMessage());
            }
        }

        send_push_notification(
            $staffUser?->user_id ?? $staffUserId,
            'Shift assigned (override)',
            'An admin assigned a shift for you, overriding restrictions.',
            ['type' => 'shift', 'shiftId' => $shiftDate->id],
        );

        $checkcalls = $shiftDate->checkCalls;
        
        if($checkcalls && $checkcalls->isNotEmpty()){
            foreach ($checkcalls as $checkcall) {
                if($checkcall->status !=='completed'){
                    $checkcall->employee_id = $request->staff_id;
                    $checkcall->save();
                }
            }
        }
        return response()->json(['success' => 'Shift assigned with override!'], 200);
    }

    public function multiAssignWithOverride(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shift_ids'   => 'required|array',
            'shift_ids.*' => 'exists:shift_dates,id',
            'staff_id'    => 'required|exists:users,id',
            'subcontractor_id' => 'nullable',
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
        // tolerate missing Employee record
        $staffUser = Employee::where('user_id', $staffId)->first();
        $staffUserId = $staffUser?->user_id ?? $staffId;
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
            $overlap = ShiftDate::where('staff_id', $staffUser?->user_id ?? $staffUserId)
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
            $shiftDate->staff_id            = $staffUser?->user_id ?? $staffUserId;
            $shiftDate->is_assign           = 1;
            $shiftDate->status              = 'pending';
            $shiftDate->start_time          = $newStart;
            $shiftDate->end_time            = $newEnd;
            $shiftDate->absentee_start_time = $bookOn;
            $shiftDate->absentee_end_time   = $bookOff;
            try {
                $normalized = \Carbon\Carbon::parse($newDate)->format('Y-m-d');
            } catch (\Exception $e) {
                $normalized = $newDate;
            }
            $shiftDate->shift_date          = $normalized;

            // Recalculate total hours
            $startCalc = strlen($newStart) === 5 ? $newStart . ':00' : $newStart;
            $endCalc   = strlen($newEnd) === 5 ? $newEnd . ':00' : $newEnd;
            $shiftDate->total_hours = $this->calculateTotalHours($startCalc, $endCalc, 'H:i:s');

            // persist subcontractor if provided for bulk assign
            if ($request->has('subcontractor_id')) {
                $resolvedSubcontractorUserId = $this->resolveSubcontractorUserId($request->subcontractor_id);
                $shiftDate->subcontractor_id = $resolvedSubcontractorUserId;
                try {
                    $parent = $shiftDate->shift;
                    if ($parent) {
                        $parent->subcontractor_id = $resolvedSubcontractorUserId;
                        $parent->save();
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to update parent shift subcontractor (multiAssign): '.$e->getMessage());
                }
            }

            $shiftDate->save();

            try { $this->rescheduleShiftEvents($shiftDate); } catch (\Throwable $_) {}

            send_push_notification(
                $staffUser->user_id,
                'Shift assigned (override)',
                'An admin assigned a shift for you, overriding restrictions.',
                ['type' => 'shift', 'shiftId' => $shiftDate->id]
            );

            $updatedShifts[] = $shiftDate->id;

        $checkcalls = $shift->checkCalls;

        if($checkcalls && $checkcalls->isNotEmpty()){
            foreach ($checkcalls as $checkcall) {
                if($checkcall->status !=='completed'){
                    $checkcall->employee_id = $request->staff_id;
                    $checkcall->save();
                }
            }
        }
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
            'subcontractor_id'  => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['absentee_start_time'] = $data['book_on'] ?? null;
        $data['absentee_end_time']   = $data['book_off'] ?? null;
        $data['start_time']          = $data['start_shift'];
        $data['end_time']            = $data['end_shift'];
        $data['subcontractor_id']            = $this->resolveSubcontractorUserId($data['subcontractor_id'] ?? null);

        // Normalize time format
        if (strlen($data['start_shift']) === 5) $data['start_shift'] .= ':00';
        if (strlen($data['end_shift']) === 5)   $data['end_shift']   .= ':00';

        // Calculate total hours
        $data['total_hours'] = $this->calculateTotalHours($data['start_shift'], $data['end_shift'], 'H:i:s');

        // ⚠ Skip restrictions completely
        $staffUser = null;
        $staffUserId = null;
        if (!empty($data['staff_id'])) {
            // tolerate missing Employee record
            $staffUser = Employee::where('user_id', $data['staff_id'])->first();
            $staffUserId = $staffUser?->user_id ?? $data['staff_id'];

            $staffChanged = $staffUserId != $shift->staff_id;

            if ($staffChanged) {
                // Delete any existing bookings before reassigning
                ShiftBooking::where('shift_id', $shift->id)->delete();

                $shift->staff_id = $staffUserId;
                $data['is_assign'] = 1;
                $data['status'] = 'pending';
            }
        } elseif (array_key_exists('staff_id', $data) && empty($data['staff_id']) && $shift->staff_id) {
            // Staff is being removed
            ShiftBooking::where('shift_id', $shift->id)->delete();
            $shift->staff_id = null;
            $data['is_assign'] = 0;
            $data['status'] = 'pending';
        }

        $shift->update($data);

        if ($request->has('subcontractor_id')) {
            try {
                $parent = $shift->shift;
                if ($parent) {
                    $parent->subcontractor_id = $data['subcontractor_id'] ?? null;
                    $parent->save();
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to update parent shift subcontractor (updateWithOverride): '.$e->getMessage());
            }
        }

        // Send notification
        if (!empty($data['staff_id'])) {
            send_push_notification(
                $staffUser?->user_id ?? $data['staff_id'],
                'Shift updated (override)',
                'An admin updated a shift for you, overriding restrictions.',
                ['type' => 'shift', 'shiftId' => $shift->id]
            );
        }

        $checkcalls = $shift->checkCalls;
        
        if($checkcalls && $checkcalls->isNotEmpty()){
            foreach ($checkcalls as $checkcall) {
                if($checkcall->status !=='completed'){
                    $checkcall->employee_id = $request->staff_id;
                    $checkcall->save();
                }
            }
        }

        return response()->json(['success' => 'Shift updated with override!']);
    }

    public function storeOverride(Request $request)
    {
        try {
        $shiftCount = count($request->client_id);

        $shiftDatesCreated=0;
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
                'restrict_start_time' => $request->restrict_start_time[$i] ?? null,
                'enforce_picture_check' => $request->enforce_picture_check[$i] ?? null,
                'restrict_location_check' => $request->restrict_location_check[$i] ?? null,
            ], [
                'client_id' => 'required|integer',
                'site_id' => 'required|integer',
                'days' => 'nullable|string',
                'staff_id' => 'nullable|integer|exists:users,id',
                'start_shift' => 'required|date_format:H:i',
                'end_shift' => 'required|date_format:H:i',
                'from_shift' => 'required|date',
                'to_shift' => 'required|date|after_or_equal:from_shift',
                'restrict_start_time' => 'nullable',
                'enforce_picture_check' => 'nullable',
                'restrict_location_check' => 'nullable',
                'training_id' => 'nullable|array',
                'training_id.*' => 'exists:training_materials,id',
            ]);

            // Additional simple validations
            $validator->after(function ($validator) use ($request, $i) {
                $start = $request->start_shift[$i] ?? null;
                $end = $request->end_shift[$i] ?? null;
                $from = $request->from_shift[$i] ?? null;
                $to   = $request->to_shift[$i] ?? null;

                if ($start && $end) {
                    $startTime = \Carbon\Carbon::createFromFormat('H:i', $start);
                    $endTime = \Carbon\Carbon::createFromFormat('H:i', $end);

                    if ($startTime->eq($endTime)) {
                        $validator->errors()->add("end_shift", "End time must not be the same as start time.");
                    }
                }

                // ✅ Prevent accidental multi-year shift ranges
                if ($from && $to) {
                    try {
                        $fromDate = \Carbon\Carbon::parse($from);
                        $toDate   = \Carbon\Carbon::parse($to);
                        if ($toDate->diffInDays($fromDate) > 365) {
                            $validator->errors()->add('to_shift', 'The shift date range cannot exceed 1 year. Please split large ranges into separate shifts.');
                        }
                    } catch (\Exception $e) {
                        // let the existing date validation handle parse errors
                    }
                }
            });

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors(), 'index' => $i], 422);
            }

            $data = $validator->validated();

            $data['restrict_start_time'] = !empty($data['restrict_start_time']) ? 1 : 0;
            $data['enforce_picture_check'] = !empty($data['enforce_picture_check']) ? 1 : 0;
            $data['restrict_location_check'] = !empty($data['restrict_location_check']) ? 1 : 0;
            $data['days'] = json_encode([str_replace(['"', '[', ']'], '', $data['days'])]);
            $data['is_assign'] = !empty($data['staff_id']) ? 1 : 0;

            $serviceType1 = DB::table('employee_types')->where('id', $request->service_type_1[$i])->first();
            $serviceType2 = DB::table('employee_types')->where('id', $request->service_type_2[$i])->first();
            $resolvedSubcontractorUserId = $this->resolveSubcontractorUserId($request->subcontractor_id[$i] ?? null);
            
            // Create main Shift
            $shift = Shift::create([
                'client_id'   => $request->client_id[$i],
                'site_id'     => $request->site_id[$i],
                'staff_id'    => $request->staff_id[$i] ?? null,
                'start_shift' => $request->start_shift[$i],
                'end_shift'   => $request->end_shift[$i],
                'service_type_1' => $serviceType1?->name,
                'service_type_2' => $serviceType2?->name,
                'subcontractor_id' => $resolvedSubcontractorUserId,
                'restrict_start_time' => $data['restrict_start_time'],
                'enforce_picture_check' => $data['enforce_picture_check'],
                'restrict_location_check' => $data['restrict_location_check'],
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
                    'status'  => $shift->staff_id ? 'pending' : 'unassigned',
                    'end_time'    => $request->end_shift[$i],
                    'is_assign'   => !empty($shift->staff_id) ? 1 : 0,
                    'break_time'  => $request->{'break-mins_shift'}[$i] ?? null,
                    'total_hours' => $this->calculateTotalHours(
                        $request->start_shift[$i],
                        $request->end_shift[$i]
                    ),
                    'guard_rate'  => $request->guard_rate[$i] ?? $shift->site?->guard_rate ?? 0,
                ]);

                $shiftDatesCreated++;

                if (!empty($data['training_id'])) {
                    $trainingIds = is_array($data['training_id']) ? $data['training_id'] : [$data['training_id']];
                    $shiftDate->trainings()->sync($trainingIds);
                }

                // Auto CheckCalls / Patrols
                // Ensure we only use the date part of shift_date when appending times
                try {
                    $shiftDateOnly = Carbon::parse($shiftDate->shift_date)->toDateString();
                } catch (\Exception $e) {
                    $shiftDateOnly = (string) $shiftDate->shift_date;
                }
                $start = Carbon::parse($shiftDateOnly . ' ' . $shiftDate->start_time);
                $end   = Carbon::parse($shiftDateOnly . ' ' . $shiftDate->end_time);

                if ($end->lessThanOrEqualTo($start)) {
                    $end->addDay();
                }

                $durationMinutes = $start->diffInMinutes($end);
                $durationMinutes = $durationMinutes <= 0 ? 1440 : $durationMinutes;
                $numberOfCheckCalls = ceil($durationMinutes / 60);

                $site = Site::with('checkpoints')->find($shift->site_id);
                $totalCheckpoints = $site->checkpoints->count() ?? 0;

                for ($n = 0; $n < $numberOfCheckCalls; $n++) {
                    $checkTime  = $start->copy()->addHours($n);
                    $patrolTime = $start->copy()->addHours($n);

                    // Support both scalar and per-shift array submission for auto_checkcall_enabled
                    $autoEnabledForGroup = false;
                    if ($request->has('auto_checkcall_enabled')) {
                        // array case
                        if (is_array($request->auto_checkcall_enabled) && isset($request->auto_checkcall_enabled[$i])) {
                            $autoEnabledForGroup = !empty($request->auto_checkcall_enabled[$i]);
                        } else {
                            // scalar case
                            $autoEnabledForGroup = (bool) $request->auto_checkcall_enabled;
                        }
                    }

                    if ($autoEnabledForGroup) {
                        CheckCall::create([
                            'shift_id' => $shiftDate->id,
                            'employee_id' => $shiftDate->staff_id ?? null,
                            'name' => 'Auto CheckCall ' . ($n + 1),
                            'scheduled_time' => $checkTime->format('Y-m-d H:i:s'),
                            'status' => 'pending',
                            'require_media' => $shiftDate->require_media ?? 0,
                        ]);
                    }

                    Patrol::create([
                        'shift_id' => $shiftDate->id,
                        'name' => 'Auto Patrol ' . ($n + 1),
                        'summary' => 'Scheduled patrol at ' . $patrolTime->format('H:i'),
                        'start_time' => $patrolTime->format('Y-m-d H:i:s'),
                        'status' => 'pending',
                        'total_checkpoints' => $totalCheckpoints,
                        'completed_checkpoints' => 0,
                        'issues_reported' => 0,
                        'completed_at' => null,
                    ]);
                }
            }
            
            // Send push notification once per shift if staff is assigned
            if ($shift->staff_id) {
                try {
                    send_push_notification(
                        $shift->staff_id,
                        'New shift assigned',
                        'A new shift has been assigned to you. Please check your schedule and respond.',
                        ['type' => 'shift', 'shiftId' => $shiftDate->id]
                    );
                } catch (\Exception $e) {
                    \Log::error('Failed to send push notification in storeOverride: ' . $e->getMessage());
                }
            }
        }

        Logger::log(Auth::user(), 'Create', 'Amount: ('.$shiftDatesCreated.' shift dates created) for site ' . $shift->site->site_name . ' Starting at: ' . $shiftDate->start_time . ' On ' . $shiftDate->shift_date.' From date: ' .$fromDate. ' To Date: '. $toDate);

        return response()->json([
            'message' => 'Shifts overridden successfully!',
        ]);
        } catch (\Throwable $e) {
            \Log::error('ShiftController@storeOverride exception: ' . $e->getMessage(), ['exception' => $e]);

            $payload = ['error' => $e->getMessage()];
            if (config('app.debug')) {
                $payload['trace'] = $e->getTraceAsString();
            }

            return response()->json($payload, 500);
        }
    }

    /**
     * Lightweight AJAX update used by the edit modal.
     * Accepts singular form fields and updates the ShiftDate record.
     */
    public function updateSimple(Request $request, $id)
    {
        $shiftDate = ShiftDate::findOrFail($id);
        $parentShift = Shift::findOrFail($shiftDate->shift_id);

        $validator = Validator::make($request->all(), [
            'staff_id' => 'nullable|integer',
            'employee_rate' => 'nullable|numeric',
            'site_rate' => 'nullable|numeric',
            'start_shift' => 'nullable',
            'end_shift' => 'nullable',
            'book_on' => 'nullable',
            'book_off' => 'nullable',
            'shift_date' => 'nullable|date',
            'status_id' => 'nullable|integer',
            'subcontractor_id' => 'nullable|integer',
            'client_id' => 'nullable|integer',
            'site_id' => 'nullable|integer',
            'from_shift' => 'nullable|date',
            'to_shift' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

                
        if (array_key_exists('status_id', $data)) {
            $shiftDate->is_assign = $data['status_id'];
        }
        
        // Update ShiftDate fields
        // Only change is_assign when staff is actually being changed
        if (array_key_exists('staff_id', $data)) {
            $oldStaffId = $shiftDate->staff_id;
            $newStaffId = $data['staff_id'];
            
            if ($newStaffId && $newStaffId != $oldStaffId) {
                // Staff is being changed to a new person - set to dispatched
                // Check ban: cannot assign banned staff
                $employee = \App\Models\Employee::where('user_id', $newStaffId)->first();
                if ($employee && EmployeeBan::isBannedFor($employee->id, $parentShift->site_id ?? null, $parentShift->client_id ?? null)) {
                    return response()->json(['errors' => ['ban_forbidden' => 'Selected staff is banned for the shift site/client and cannot be assigned.']], 422);
                }

                // Delete any existing bookings before reassigning
                ShiftBooking::where('shift_id', $shiftDate->id)->delete();

                $shiftDate->staff_id = $newStaffId;
                $shiftDate->is_assign = 1;
                $shiftDate->status = 'pending';

            } elseif (!$newStaffId && $oldStaffId) {
                // Staff is being removed - delete bookings and set to unassigned
                ShiftBooking::where('shift_id', $shiftDate->id)->delete();
                $shiftDate->staff_id = null;
                $shiftDate->is_assign = 0;
                $shiftDate->status = 'pending';
            } elseif ($newStaffId && $newStaffId == $oldStaffId) {
                // Same staff - don't change is_assign, just keep current status
                // No changes needed
            }
        }
        
        if (array_key_exists('start_shift', $data) && $data['start_shift']) {
            $shiftDate->start_time = $data['start_shift'];
        }
        
        if (array_key_exists('end_shift', $data) && $data['end_shift']) {
            $shiftDate->end_time = $data['end_shift'];
        }
        
        if (array_key_exists('book_on', $data)) {
            $shiftDate->absentee_start_time = $data['book_on'];
        }
        
        if (array_key_exists('book_off', $data)) {
            $shiftDate->absentee_end_time = $data['book_off'];
        }
        
        if (array_key_exists('employee_rate', $data) && $data['employee_rate'] !== null && $data['employee_rate'] !== '') {
            // Only treat this as an update when a non-empty value was provided
            $newRate = (float) $data['employee_rate'];
            if ((float) $shiftDate->guard_rate !== $newRate) {
                $shiftDate->guard_rate = $newRate;
            }
            $parentShift->employee_rate = $newRate;
        }

        if (array_key_exists('site_rate', $data) && $data['site_rate'] !== null && $data['site_rate'] !== '') {
            // Only update when an explicit non-empty site_rate was provided
            $newSiteRate = (float) $data['site_rate'];
            $shiftDate->guard_rate = $newSiteRate;
            $parentShift->site_rate = $newSiteRate;
        }
        
        if (array_key_exists('shift_date', $data)) {
            $shiftDate->shift_date = $data['shift_date'];
        }

        // Update parent Shift fields (client_id, site_id)
        if (array_key_exists('client_id', $data) && $data['client_id']) {
            $parentShift->client_id = $data['client_id'];
        }
        
        if (array_key_exists('site_id', $data) && $data['site_id']) {
            $parentShift->site_id = $data['site_id'];
        }
        
        if (array_key_exists('from_shift', $data) && $data['from_shift']) {
            $parentShift->from_shift = $data['from_shift'];
        }
        
        if (array_key_exists('to_shift', $data) && $data['to_shift']) {
            $parentShift->to_shift = $data['to_shift'];
        }

        if (array_key_exists('subcontractor_id', $data)) {
            $resolvedSubcontractorUserId = $this->resolveSubcontractorUserId($data['subcontractor_id']);
            $shiftDate->subcontractor_id = $resolvedSubcontractorUserId;
            $parentShift->subcontractor_id = $resolvedSubcontractorUserId;
        }

        // Calculate total hours if both times are present
        if ((array_key_exists('start_shift', $data) && $data['start_shift']) && (array_key_exists('end_shift', $data) && $data['end_shift'])) {
            try {
                $shiftDate->total_hours = $this->calculateTotalHours(
                    $data['start_shift'], 
                    $data['end_shift'], 
                    'H:i'
                );
            } catch (\Throwable $e) {
                \Log::warning('Failed to calculate total hours in updateSimple', [
                    'start' => $shiftDate->start_time,
                    'end' => $shiftDate->end_time,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $shiftDate->save();
        try { $this->rescheduleShiftEvents($shiftDate); } catch (\Throwable $_) {}
        $parentShift->save();

        // Process custom checkcalls and patrols BEFORE returning
        if ($request->has('checkcalls') && is_array($request->checkcalls)) {
            foreach ($request->checkcalls as $checkcall) {
                if (!empty($checkcall['name']) && !empty($checkcall['scheduled_time'])) {
                    CheckCall::create([
                        'shift_id' => $shiftDate->id,
                        'employee_id' => $shiftDate->staff_id ?? null,
                        'name' => $checkcall['name'],
                        'scheduled_time' => $shiftDate->shift_date . ' ' . $checkcall['scheduled_time'],
                        'status' => 'pending',
                        'require_media' => $shiftDate->require_media ?? 0,
                    ]);
                }
            }
        }
        
        $site = Site::with('checkpoints')->find($parentShift->site_id);
        $totalCheckpoints = $site ? $site->checkpoints->count() : 0;
       
        if ($request->has('patrols') && is_array($request->patrols)) {
            foreach ($request->patrols as $patrol) {
                if (!is_array($patrol)) continue;
                if (empty($patrol['name']) || empty($patrol['start_time'])) continue;

                // Combine date with posted time (handles H:i or H:i:s)
                try {
                    $startDateTime = $this->combineDateTime($shiftDate->shift_date, $patrol['start_time']);
                } catch (\Exception $e) {
                    $startDateTime = Carbon::parse($shiftDate->shift_date . ' ' . $patrol['start_time']);
                }

                Patrol::create([
                    'shift_id'              => $shiftDate->id,
                    'name'                  => $patrol['name'],
                    'summary'               => 'Custom patrol scheduled at ' . $startDateTime->format('H:i'),
                    'start_time'            => $startDateTime->format('Y-m-d H:i:s'),
                    'status'                => 'pending',
                    'total_checkpoints'     => $totalCheckpoints,
                    'completed_checkpoints' => 0,
                    'issues_reported'       => 0,
                    'completed_at'          => null,
                ]);
            }
        }
        
        // If the lightweight update enabled auto-checkcalls or auto-patrols,
        // create any missing ones idempotently.
        try {
            $autoCheckEnabled = false;
            if ($request->has('auto_checkcall_enabled')) {
                $val = $request->input('auto_checkcall_enabled');
                if (is_array($val)) {
                    $autoCheckEnabled = !empty(array_filter($val));
                } else {
                    $autoCheckEnabled = (bool)$val;
                }
            }

            $autoPatrolEnabled = false;
            if ($request->has('auto_patrol_enabled')) {
                $val = $request->input('auto_patrol_enabled');
                if (is_array($val)) {
                    $autoPatrolEnabled = !empty(array_filter($val));
                } else {
                    $autoPatrolEnabled = (bool)$val;
                }
            }

            if ($autoCheckEnabled || $autoPatrolEnabled) {
                try {
                    $start = $this->combineDateTime($shiftDate->shift_date, $shiftDate->start_time);
                } catch (\Exception $e) {
                    $start = \Carbon\Carbon::parse($shiftDate->shift_date . ' ' . $shiftDate->start_time);
                }
                try {
                    $end = $this->combineDateTime($shiftDate->shift_date, $shiftDate->end_time);
                } catch (\Exception $e) {
                    $end = \Carbon\Carbon::parse($shiftDate->shift_date . ' ' . $shiftDate->end_time);
                }
                if ($end->lessThanOrEqualTo($start)) {
                    $end->addDay();
                }

                $durationMinutes = $start->diffInMinutes($end);
                if ($durationMinutes <= 0) $durationMinutes = 1440;
                $numberOfCheckCalls = (int) ceil($durationMinutes / 60);

                $site = \App\Models\Site::with('checkpoints')->find($parentShift->site_id);
                $totalCheckpoints = $site?->checkpoints->count() ?? 0;

                for ($n = 0; $n < $numberOfCheckCalls; $n++) {
                    $checkTime = $start->copy()->addHours($n);
                    $patrolTime = $start->copy()->addHours($n);

                    if ($autoCheckEnabled) {
                        $scheduled = $checkTime->format('Y-m-d H:i:s');
                        $exists = CheckCall::where('shift_id', $shiftDate->id)
                            ->where('scheduled_time', $scheduled)
                            ->exists();
                        if (!$exists) {
                            CheckCall::create([
                                'shift_id' => $shiftDate->id,
                                'employee_id' => $shiftDate->staff_id ?? null,
                                'name' => 'Auto CheckCall ' . ($n + 1),
                                'scheduled_time' => $scheduled,
                                'status' => 'pending',
                                'require_media' => $shiftDate->require_media ?? 0,
                            ]);
                        }
                    }

                    if ($autoPatrolEnabled) {
                        $startStr = $patrolTime->format('Y-m-d H:i:s');
                        $existsP = Patrol::where('shift_id', $shiftDate->id)
                            ->where('start_time', $startStr)
                            ->exists();
                        if (!$existsP) {
                            Patrol::create([
                                'shift_id' => $shiftDate->id,
                                'name' => 'Auto Patrol ' . ($n + 1),
                                'summary' => 'Scheduled patrol at ' . $patrolTime->format('H:i'),
                                'start_time' => $startStr,
                                'status' => 'pending',
                                'total_checkpoints' => $totalCheckpoints,
                                'completed_checkpoints' => 0,
                                'issues_reported' => 0,
                                'completed_at' => null,
                            ]);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to create auto checkcalls/patrols in updateSimple: ' . $e->getMessage());
        }

        // Reload with counts so the response tells the frontend how many checkcalls/patrols exist
        $shiftDate->loadCount(['checkCalls', 'patrols']);

        return response()->json([
            'message'           => 'Shift updated successfully',
            'shift'             => $shiftDate,
            'check_calls_count' => $shiftDate->check_calls_count,
            'patrols_count'     => $shiftDate->patrols_count,
        ]);
    }

    public function exportPatrolsPdf($shiftDateId)
    {
        $shiftDate = ShiftDate::with(['shift.site', 'staff'])->findOrFail($shiftDateId);
        $patrols = Patrol::where('shift_id', $shiftDateId)
            ->orderBy('start_time', 'asc')
            ->get();

        $site = Site::with('checkpoints')->find($shiftDate->shift->site_id);
        $checkpoints = PatrolCheckPoint::where('site_id', $site->id)->get();

        // For each patrol, get scans and media
        foreach ($patrols as $patrol) {
            $patrol->scans = CheckpointScan::where('patrol_id', $patrol->id)
                ->orderBy('timestamp', 'desc')
                ->get();
            $patrol->media = PatrolMedia::where('patrol_id', $patrol->id)->get();
            
            // Convert media images to base64 for PDF embedding
            foreach ($patrol->media as $media) {
                $filePath = public_path($media->file_path);
                $fileType = strtolower(pathinfo($media->file_path, PATHINFO_EXTENSION));
                
                if (in_array($fileType, ['jpg', 'jpeg', 'png', 'gif']) && file_exists($filePath)) {
                    try {
                        $imageData = file_get_contents($filePath);
                        if ($imageData) {
                            $mimeType = mime_content_type($filePath);
                            $media->base64Image = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                        } else {
                            $media->base64Image = null;
                        }
                    } catch (\Exception $e) {
                        $media->base64Image = null;
                    }
                } else {
                    $media->base64Image = null;
                }
            }
            
            // Get patrol route locations for static map
            $patrol->locations = Location::where('shiftdate_id', $shiftDateId)
                ->whereBetween('created_at', [
                    $patrol->started_at ?? $patrol->start_time,
                    $patrol->completed_at ?? now()
                ])
                ->get();

            // Get first and last location
            if ($patrol->locations && $patrol->locations->count() > 0) {
                $geoService = new GeoService();
                
                $firstLocation = $patrol->locations->first();
                $lastLocation = $patrol->locations->last();
                
                $patrol->firstLocation = [
                    'latitude' => $firstLocation->latitude,
                    'longitude' => $firstLocation->longitude,
                    'timestamp' => $firstLocation->created_at,
                    'address' => null
                ];
                
                $patrol->lastLocation = [
                    'latitude' => $lastLocation->latitude,
                    'longitude' => $lastLocation->longitude,
                    'timestamp' => $lastLocation->created_at,
                    'address' => null
                ];
                
                // Get addresses from coordinates
                try {
                    $firstAddress = $geoService->getAddressFromCoordinates(
                        $firstLocation->latitude,
                        $firstLocation->longitude
                    );
                    if ($firstAddress) {
                        $patrol->firstLocation['address'] = $firstAddress['formatted_address'];
                    }
                    
                    $lastAddress = $geoService->getAddressFromCoordinates(
                        $lastLocation->latitude,
                        $lastLocation->longitude
                    );
                    if ($lastAddress) {
                        $patrol->lastLocation['address'] = $lastAddress['formatted_address'];
                    }
                } catch (\Exception $e) {
                    // Addresses will remain null
                }
            } else {
                $patrol->firstLocation = null;
                $patrol->lastLocation = null;
            }
            
            // Generate map image as base64
            if ($patrol->locations && $patrol->locations->count() > 1) {
                $apiKey = env('GOOGLE_MAPS_API_KEY');
                if ($apiKey) {
                    $center = $patrol->locations->first();
                    $mapUrl = "https://maps.googleapis.com/maps/api/staticmap?";
                    $mapUrl .= "center={$center->latitude},{$center->longitude}";
                    $mapUrl .= "&zoom=15&size=600x300&maptype=roadmap";
                    
                    $pathCoords = $patrol->locations->map(fn($loc) => "{$loc->latitude},{$loc->longitude}")->join('|');
                    $mapUrl .= "&path=color:0xff0000ff|weight:3|{$pathCoords}";
                    
                    $first = $patrol->locations->first();
                    $last = $patrol->locations->last();
                    $mapUrl .= "&markers=color:green|label:S|{$first->latitude},{$first->longitude}";
                    $mapUrl .= "&markers=color:red|label:E|{$last->latitude},{$last->longitude}";
                    $mapUrl .= "&key={$apiKey}";

                    // Download and convert to base64
                    try {
                        $imageData = @file_get_contents($mapUrl);
                        if ($imageData) {
                            $patrol->mapImage = 'data:image/png;base64,' . base64_encode($imageData);
                        } else {
                            $patrol->mapImage = null;
                        }
                    } catch (\Exception $e) {
                        $patrol->mapImage = null;
                    }
                } else {
                    $patrol->mapImage = null;
                }
            } else {
                $patrol->mapImage = null;
            }
        }

        $pdf = PDF::loadView('exports.patrols-pdf', [
            'shiftDate' => $shiftDate,
            'patrols' => $patrols,
            'site' => $site,
            'checkpoints' => $checkpoints,
        ]);

        // Enable remote image loading for maps
        $pdf->setOptions([
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
            'isFontSubsettingEnabled' => true,
        ]);

        $filename = 'patrols_' . $shiftDate->shift_date . '_' . $site->site_name . '.pdf';
        return $pdf->download($filename);
    }

    public function exportPatrolsExcel($shiftDateId)
    {
        $shiftDate = ShiftDate::with(['shift.site', 'staff'])->findOrFail($shiftDateId);
        $site = $shiftDate->shift->site;
        $filename = 'patrols_' . $shiftDate->shift_date . '_' . $site->site_name . '.xlsx';

        return Excel::download(new PatrolsExport($shiftDateId), $filename);
    }

}
