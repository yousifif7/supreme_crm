<?php

namespace App\Http\Controllers;

use App\DataTables\ShiftsDataTable;
use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeeTerm;
use App\Models\EmployeeType;
use App\Models\Shift;
use App\Models\ShiftDate;
use App\Models\Site;
use App\Models\Subcontractor;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShiftController extends Controller
{
    public function index(ShiftsDataTable $dataTable)
    {
        $clients = Client::all();
        $sites = Site::all();
        $staffs = Employee::all();
        $subcontractors = Subcontractor::all();
        $users = User::all();
        $shfits = Shift::with(['guard','site'])->get();
        $services = EmployeeType::all();
        return $dataTable->render('security_boards.shifts', compact('clients', 'sites', 'staffs', 'subcontractors', 'users', 'services', 'shifts'));
    }
    public function scheduling()
    {
        // $shifts = Shift::all();
        $clients = Client::all();
        $sites = Site::all();
        $staffs = Employee::all();
        $subcontractors = Subcontractor::all();
        // $users = User::all();
        $services = EmployeeType::all();
        return view('security_boards.scheduling', compact('sites', 'staffs', 'clients', 'services', 'subcontractors'));
    }
    public function worker_calendar()
    {
        $shifts = Shift::all();
        $clients = Client::all();
        $sites = Site::all();
        $staffs = Employee::all();
        $subcontractors = Subcontractor::all();
        $users = User::all();
        $services = EmployeeType::all();
        return view('security_boards.worker_calendar', compact('shifts', 'clients', 'sites', 'staffs', 'subcontractors', 'users', 'services'));
    }
    public function site_calendar()
    {
        $shifts = Shift::all();
        $clients = Client::all();
        $sites = Site::all();
        $staffs = Employee::all();
        $subcontractors = Subcontractor::all();
        $users = User::all();
        $services = EmployeeType::all();
        return view('security_boards.site_calendar', compact('shifts', 'clients', 'sites', 'staffs', 'subcontractors', 'users', 'services'));
    }
    public function today_rota()
    {
        $shifts = Shift::all();
        $clients = Client::all();
        $sites = Site::all();
        $staffs = Employee::all();
        $subcontractors = Subcontractor::all();
        $users = User::all();
        $services = EmployeeType::all();
        return view('security_boards.today_rota', compact('shifts', 'clients', 'sites', 'staffs', 'subcontractors', 'users', 'services'));
    }

    public function show(ShiftDate $shiftDate)
    {
        $shiftDate->load(['staff', 'shift.client', 'shift.site', 'shift.staff', 'logs', 'shift.checkCalls']);
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
                'from_shift' => 'required|date|after_or_equal:today',
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
                'checkpoints' => 'nullable',
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
                    } elseif ($endTime->lt($startTime)) {
                        $validator->errors()->add("end_shift", "End time cannot be earlier than start time on the same day.");
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
                    $staff = \App\Models\Employee::find($staffId);
                    if ($staff && $staff->sia_expiry && \Carbon\Carbon::parse($staff->sia_expiry)->lt(now())) {
                        $validator->errors()->add("staff_id", "Staff SIA license has expired.");
                    }

                    if ($staff && $staff->visa_expiry && \Carbon\Carbon::parse($staff->visa_expiry)->lt(now())) {
                        $validator->errors()->add("staff_id", "Staff Visa has expired.");
                    }

                    if ($staff && $staff->passport_expiry && \Carbon\Carbon::parse($staff->passport_expiry)->lt(now())) {
                        $validator->errors()->add("staff_id", "Staff Passport has expired.");
                    }

                    if ($staff && $staff->passport_expiry && \Carbon\Carbon::parse($staff->passport_expiry)->lt(now())) {
                        $validator->errors()->add("staff_id", "Staff Passport has expired.");
                    }

                    $missingDocuments = [];

                    foreach ($documents as $key => $doc) {
                        if (empty($staff->$key)) {
                            $missingDocuments[] = $doc;
                        }
                    }

                    if (count($missingDocuments)) {
                        $validator->errors()->add("staff_id", "Missing required documents: " . implode(', ', $missingDocuments));
                    }

                    $selectedDays = array_map('trim', explode(',', $dayString));
                    // $newShiftHours = $this->calculateTotalWorkingHours($staffId, $from, $to, $start, $end, $breakMinutes, $selectedDays);
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

                    // Get week start and end for that shift date
                    $weekStart = $fromDate->startOfWeek(Carbon::MONDAY);
                    $weekEnd = $toDate->endOfWeek(Carbon::SUNDAY);


                    // Fetch existing shifts for this staff in the same week
                    $totalWeekHours = \App\Models\ShiftDate::where('staff_id', $staffId)
                        ->whereBetween('shift_date', [$weekStart, $weekEnd])
                        ->sum('total_hours');

                    // Check if adding new shift exceeds weekly limit
                    $maxWeeklyHours = $staff->hour_per_week ?? 40;

                    if (($totalWeekHours + $newShiftHours) > $maxWeeklyHours) {
                        $validator->errors()->add("staff_id", "The guard cannot be assigned more than $maxWeeklyHours hours in a week.");
                    }

                    // check if staff visa is student visa and if the weekly hours exceed 20
                    if ($staff && $staff->visa_type === 'Student') {

                        // // if the staff has terms with from_date and to_date overlapping the shift, ignore 20-hour limit, because he can work any hours during the term
                        $isShiftCommingInActiveTermPeriod = EmployeeTerm::where('employee_id', $staffId)
                            ->where(function ($q) use ($from, $to) {
                                $q->where('from_date', '<=', $to)
                                    ->where('to_date', '>=', $from);
                            })
                            ->exists();

                        if (!$isShiftCommingInActiveTermPeriod) {


                            $weeklyHours = \App\Models\ShiftDate::where('staff_id', $staffId)
                                ->whereBetween('shift_date', [now()->startOfWeek(), now()->endOfWeek()])
                                ->sum('total_hours') + $newShiftHours; //$this->calculateTotalWorkingHours($from, $to, $start, $end, $breakMinutes, $selectedDays);
                            $shiftsWorkingHours += $weeklyHours;

                            if ($shiftsWorkingHours > 20) {
                                $validator->errors()->add("staff_id", "The guard cannot be assigned more than 20 hours a week.");
                            }
                        }
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

            $shift = Shift::create($data);

            if (isset($data['checkpoints']) && $data['checkpoints']) {
                foreach ($data['checkpoints'] as $checkpoint) {
                    // foreach ($checkpoints as $checkpoint)
                    // {
                    \App\Models\ShiftCheckpoint::create([
                        'shift_id' => $shift->id,
                        'staff_id' => $shift->staff_id ?? null,
                        'checkpoint_name' => $checkpoint['checkpoint_name'],
                        'checkpoint_time' => $checkpoint['checkpoint_time'],
                    ]);
                    // }
                }
            }

            $dayString = $request->days[$i] ?? 'Mon,Tue,Wed,Thu,Fri,Sat,Sun';
            $selectedDays = array_map('trim', explode(',', $dayString));

            $fromDate = \Carbon\Carbon::parse($data['from_shift']);
            $toDate = \Carbon\Carbon::parse($data['to_shift']);
            $period = \Carbon\CarbonPeriod::create($fromDate, $toDate);

            $is_assign = !empty($data['staff_id']) ? 1 : 0;

            foreach ($period as $date) {
                if (in_array($date->format('D'), $selectedDays)) {
                    \App\Models\ShiftDate::create([
                        'shift_id' => $shift->id,
                        'staff_id' => $shift->staff_id ?? null,
                        'shift_date' => $date->format('Y-m-d'),
                        'start_time' => $data['start_shift'],
                        'end_time' => $data['end_shift'],
                        'is_assign' => $is_assign,
                        'break_time' => $data['break-mins_shift'] ?? null,
                        'total_hours' => $this->calculateTotalHours(
                            $data['start_shift'],
                            $data['end_shift'],
                        ),

                    ]);
                }
            }
        }

        return response()->json(['message' => 'All shifts created successfully']);
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
            // 'client_id' => 'required|integer',
            // 'site_id' => 'required|integer',
            // 'company_id' => 'nullable|integer',
            'staff_id' => 'nullable|integer',
            'start_shift' => 'required',
            'end_shift' => 'required',
            'book_on' => 'nullable',
            'book_off' => 'nullable',
            // 'break-mins_shift' => 'nullable',
            // 'number_shift' => 'nullable|integer|min:0',
            // 'site_rate' => 'nullable|numeric',
            // 'service_type_1' => 'nullable|string|max:255',
            // 'service_type_2' => 'nullable|string|max:255',
            'shift_date' => 'nullable|date',
            // 'to_shift' => 'nullable|date|after_or_equal:from_shift',
            // 'comments' => 'nullable|string|max:1000',
            // 'days' => 'nullable',
            // 'employee_rate' => 'nullable|numeric',
            // 'po_number' => 'nullable|string|max:255',
            // 'lost_time' => 'nullable|string|max:255',
            // 'po_rate' => 'nullable|numeric',
            // 'start' => 'nullable',
            // 'end' => 'nullable',
            // 'manager_1_id' => 'nullable|integer',
            // 'manager_2_id' => 'nullable|integer',
            // 'restrict_start_time' => 'nullable',
            // 'enforce_picture_check' => 'nullable',
            // 'restrict_location_check' => 'nullable',
        ]);


        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $data['absentee_start_time'] = $data['book_on'] ?? null;
        $data['absentee_end_time'] = $data['book_off'] ?? null;
        $data['start_time'] = $data['start_shift'];
        $data['end_time'] = $data['end_shift'];

        if (strlen($data['start_shift']) === 5) { // e.g., "09:30"
            $data['start_shift'] .= ':00';
        }

        if (strlen($data['end_shift']) === 5) {
            $data['end_shift'] .= ':00';
        }

        $data['total_hours'] = $this->calculateTotalHours($data['start_shift'], $data['end_shift'], 'H:i:s');

        // $data['restrict_start_time'] = $request->has('restrict_start_time') ? 1 : 0;
        // $data['enforce_picture_check'] = $request->has('enforce_picture_check') ? 1 : 0;
        // $data['restrict_location_check'] = $request->has('restrict_location_check') ? 1 : 0;


        $data['is_assign'] = !empty($data['staff_id']) ? 1 : 0;

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
            'absentee_start_time' => 'required',
        ]);

        $shiftDate = \App\Models\ShiftDate::find($request->input('book_on_id'));

        if ($shiftDate->staff_id) {
            // $shiftDate->absentee_start = $shiftDate->shift_date;
            $shiftDate->absentee_start_time = $request->input('absentee_start_time');
            $shiftDate->update();
            return response()->json(['message' => 'Shift bookon updated successfully']);
        }

        return response()->json([
            'error' => 'This staff is not assigned to the shift.'
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
            // $shiftDate->absentee_start = $shiftDate->shift_date;
            $shiftDate->absentee_end_time = $request->input('absentee_end_time');

            $shiftDate->update();

            return response()->json(['message' => 'Shift bookoff updated successfully']);
        }

        return response()->json([
            'error' => 'This staff is not assigned to the shift.'
        ], 422);
    }

    public function getShifts()
    {
        $shiftDates = \App\Models\ShiftDate::with(['staff', 'shift.client', 'shift.site', 'shift.staff'])->get();
        $events = [];

        // Status color map
        $statusColorMap = [
            0 => 'bg-dark-blue',     // Pending
            1 => 'bg-lighter',       // Dispatched
            2 => 'bg-dark-green',    // Accepted
            3 => 'bg-light-yellow',  // Started
            4 => 'bg-light-blue',    // Ended
            5 => 'bg-purple1',       // Rejected
            6 => 'bg-red',           // Cancelled
            7 => 'bg-primary11',     // Pre-start
            8 => 'bg-orange',        // Await-finish
        ];

        foreach ($shiftDates as $sd) {
            $shift = $sd->shift;

            if (!$shift) continue;

            // Format shift time
            $startFormatted = \Carbon\Carbon::createFromFormat('H:i:s', $sd->start_time)->format('h:i A');
            $endFormatted = \Carbon\Carbon::createFromFormat('H:i:s', $sd->end_time)->format('h:i A');
            $diffMinutes = \Carbon\Carbon::createFromFormat('H:i:s', $sd->start_time)
                ->diffInMinutes(\Carbon\Carbon::createFromFormat('H:i:s', $sd->end_time)->copy()->addDay());
            $diffHours = number_format($diffMinutes / 60, 2);
            $total_hour = number_format($sd->total_hours, 2);

            // Split to hours and minutes
            $hours = floor($sd->total_hours);
            $minutes = round(($sd->total_hours - $hours) * 60);

            $total_hour = sprintf('%02d hr %02d min', $hours, $minutes);
            $book_on = $sd->shift_date . ", at  " . $sd->absentee_start_time;
            $book_off = $sd->shift_date . ", at " . $sd->absentee_end_time;
            $events[] = [
                'title' => $shift->client->client_name ?? 'Unknown Client',
                'start' => $sd->shift_date . 'T' . $sd->start_time,
                'end' => $sd->shift_date . 'T' . $sd->end_time,
                'location' => $shift->site->site_name ?? 'Unknown Site',
                'urgent' => rand(0, 1) === 1,
                'className' => $statusColorMap[$sd->is_assign] ?? 'bg-secondary',
                'site_name' => $shift->site->site_name ?? '',
                'site_address' => $shift->site->address ?? '',
                'shift_time' => "{$startFormatted} - {$endFormatted} ({$total_hour})",
                'phone_number' => $sd->staff->contact ?? '',
                'email' => $sd->staff->email ?? '',
                'sia_number' => $sd->staff->sia_licence ?? '',
                'sia_expiry' => $sd->staff->sia_expiry ?? '',
                'profile_picture' => $sd->staff->profile_picture ?? '',
                'name' => $sd->staff->fore_name ?? '',
                'subcontractor' => $sd->staff->subcontractor ?? '',
                'client_name' => $shift->client->client_name ?? '',
                'book_on' => $book_on,
                'book_off' => $book_off,
                'absentee_start_time' => $sd->absentee_start_time ?? null,
                'absentee_end_time' => $sd->absentee_end_time ?? null,
                'is_assigned' => $sd->is_assign == 0 ? false : true, // 🔸 Add this line
                'sd_id' => $sd->id,
            ];
        }

        return response()->json($events);
    }



    public function getShiftsWithStaff()
    {
        $shifts = Shift::with(['client', 'site', 'staff'])->get();
        $events = [];
        $highlightDates = [];

        // Optional: Status-to-color map (use shift status if available)
        $statusColorMap = [
            0 => 'bg-dark-blue',     // Pending
            1 => 'bg-lighter',       // Dispatched
            2 => 'bg-dark-green',    // Accepted
            3 => 'bg-light-yellow',  // Started
            4 => 'bg-light-blue',    // Ended
            5 => 'bg-purple',        // Rejected
            6 => 'bg-red',           // Cancelled
            7 => 'bg-primary11',       // Pre-start
            8 => 'bg-orange',        // Await-finish
        ];

        foreach ($shifts as $shift) {
            $dayList = explode(',', trim($shift->days, '[]"'));
            // if (count($dayList) === 1 && $dayList[0] === "") {
            //     $dayList = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
            // }

            $startDate = new \DateTime($shift->from_shift);
            $endDate = new \DateTime($shift->to_shift);

            for ($date = clone $startDate; $date <= $endDate; $date->modify('+1 day')) {
                $dayName = $date->format('D');

                if (in_array($dayName, $dayList)) {
                    $shiftDate = $date->format('Y-m-d');
                    $startTime = date('H:i:s', strtotime($shift->start_shift));
                    $endTime = date('H:i:s', strtotime($shift->end_shift));

                    $startTimestamp = strtotime("$shiftDate $startTime");
                    $endTimestamp = strtotime("$shiftDate $endTime");

                    if ($endTimestamp <= $startTimestamp) {
                        $endTimestamp = strtotime('+1 hour', $startTimestamp);
                    }

                    if (date('Y-m-d', $endTimestamp) !== $shiftDate || $endTime === '00:00:00') {
                        $endTimestamp = strtotime("$shiftDate 23:59:59");
                    }

                    $startDateTime = date('Y-m-d\TH:i:s', $startTimestamp);
                    $endDateTime = date('Y-m-d\TH:i:s', $endTimestamp);

                    // guess the shiftDate model id from the start and end time
                    $sd = ShiftDate::where('shift_id', $shift->id)
                        ->where('start_time', $startTime)
                        ->where('end_time', $endTime)
                        ->where('shift_date', $shiftDate)
                        ->with('staff')
                        ->first();

                    if (isset($sd->is_assign) && $sd->is_assign == 1) {
                        $events[] = [
                            'title' => isset($sd->staff->fore_name) ? $sd->staff->fore_name . " " . $sd->staff->last_name : 'Unknown Staff',
                            'start' => $startDateTime,
                            'end' => $endDateTime,
                            'location' => $shift->site->site_name ?? 'Unknown Site',
                            'first' => asset('assets/img/icons/crown.svg'),
                            'second' => asset('assets/img/icons/users_red.svg'),
                            'image' => asset('assets/img/users/user-01.jpg'),
                            'urgent' => rand(0, 1) === 1,
                            'className' => $statusColorMap[$sd->is_assign] ?? 'bg-secondary', // fallback
                            'sd_id' => $sd->id ?? null,
                        ];

                        $highlightDates[] = $shiftDate;
                    }
                }
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
        // Optional: Status-to-color map (use shift status if available)
        $statusColorMap = [
            0 => 'bg-dark-blue',     // Pending
            1 => 'bg-lighter',       // Dispatched
            2 => 'bg-dark-green',    // Accepted
            3 => 'bg-light-yellow',  // Started
            4 => 'bg-light-blue',    // Ended
            5 => 'bg-purple',        // Rejected
            6 => 'bg-red',           // Cancelled
            7 => 'bg-primary11',       // Pre-start
            8 => 'bg-orange',        // Await-finish
        ];
        foreach ($shifts as $shift) {
            // Decode days string: ["Mon,Tue,Fri"] → ['Mon', 'Tue', 'Fri']
            $dayList = explode(',', trim($shift->days, '[]"'));

            $startDate = new \DateTime($shift->from_shift);
            $endDate = new \DateTime($shift->to_shift);

            // Loop through each day in the range
            for ($date = clone $startDate; $date <= $endDate; $date->modify('+1 day')) {
                $dayName = $date->format('D'); // Example: 'Mon'

                if (in_array($dayName, $dayList)) {
                    $shiftDate = $date->format('Y-m-d');
                    $startTime = date('H:i:s', strtotime($shift->start_shift));
                    $endTime = date('H:i:s', strtotime($shift->end_shift));

                    $startTimestamp = strtotime("$shiftDate $startTime");
                    $endTimestamp = strtotime("$shiftDate $endTime");

                    // Fix reversed or invalid time
                    if ($endTimestamp <= $startTimestamp) {
                        $endTimestamp = strtotime('+1 hour', $startTimestamp);
                    }

                    // Clamp to end of same day
                    if (date('Y-m-d', $endTimestamp) !== $shiftDate || $endTime === '00:00:00') {
                        $endTimestamp = strtotime("$shiftDate 23:59:59");
                    }

                    $startDateTime = date('Y-m-d\TH:i:s', $startTimestamp);
                    $endDateTime = date('Y-m-d\TH:i:s', $endTimestamp);

                    // guess the shiftDate model id from the start and end time
                    $sd = ShiftDate::where('shift_id', $shift->id)
                        ->where('start_time', $startTime)
                        ->where('end_time', $endTime)
                        ->where('shift_date', $shiftDate)
                        ->with('staff')
                        ->first();

                    if (isset($sd->is_assign) && $sd->is_assign == 1) {
                        $events[] = [
                            'title' => $sd->shift->site->site_name ?? 'Unknown Site',
                            'start' => $startDateTime,
                            'end' => $endDateTime,
                            'allDay' => false,
                            'urgent' => rand(0, 1) === 1,
                            'color' => '#3a87ad',
                            'className' => $statusColorMap[$sd->is_assign] ?? 'bg-secondary', // fallback
                            'sd_id' => $sd->id ?? null,
                        ];

                        $highlightDates[] = $shiftDate;
                    }
                }
            }
        }

        return response()->json([
            'events' => $events,
            'highlightDates' => array_values(array_unique($highlightDates))
        ]);
    }
    public function getTodayShifts()
    {
        $today = now()->format('Y-m-d');

        $shifts = Shift::with(['client', 'site'])
            ->whereDate('from_shift', '<=', $today)
            ->whereDate('to_shift', '>=', $today)
            ->get();

        $events = [];
        // Optional: Status-to-color map (use shift status if available)
        $statusColorMap = [
            0 => 'bg-dark-blue',     // Pending
            1 => 'bg-lighter',       // Dispatched
            2 => 'bg-dark-green',    // Accepted
            3 => 'bg-light-yellow',  // Started
            4 => 'bg-light-blue',    // Ended
            5 => 'bg-purple',        // Rejected
            6 => 'bg-red',           // Cancelled
            7 => 'bg-primary11',       // Pre-start
            8 => 'bg-orange',        // Await-finish
        ];
        foreach ($shifts as $shift) {
            $dayList = explode(',', trim($shift->days, '[]"'));
            $todayDay = now()->format('D'); // Mon, Tue, etc.

            if (!in_array($todayDay, $dayList)) continue;

            $start = $today . 'T' . date('H:i:s', strtotime($shift->start_shift));
            $end = $today . 'T' . date('H:i:s', strtotime($shift->end_shift));

            $sd = ShiftDate::where('shift_id', $shift->id)
                ->where('start_time', date('H:i:s', strtotime($shift->start_shift)))
                ->where('end_time', date('H:i:s', strtotime($shift->end_shift)))
                ->where('shift_date', $today)
                ->first();

            if (isset($sd->is_assign) && $sd->is_assign == 1) {
                $events[] = [
                    'title' => $shift->client->client_name ?? 'Unknown Client',
                    'start' => $start,
                    'end' => $end,
                    'client' => $shift->client->client_name ?? '',
                    'site' => $shift->site->site_name ?? '',
                    // 'staff' => $sd->staff->site_name ?? '',
                    'staff' => $sd?->staff?->fore_name . ' ' . $sd?->staff?->sur_name,
                    'allDay' => false,
                    'color' => '#3a87ad',
                    'urgent' => rand(0, 1) === 1,
                    'className' => $statusColorMap[$sd->is_assign] ?? 'bg-secondary', // fallback
                    'sd_id' => $sd->id ?? null
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
            'staff_id' => 'required|exists:employees,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        // $request->validate([
        //     'shift_id' => 'required|exists:shift_dates,id',
        //     'staff_id' => 'required|exists:employees,id',
        // ]);

        $documents = [
            'sia_licence_file'           => 'SIA Licence File',
            'passport_file'              => 'Passport File',
            'proof_of_address_file'      => 'Proof of Address File',
            'ni_letter_file'             => 'NI Letter File',
            'first_aid_certificate_file' => 'First Aid Certificate File',
            'act_certificate_file'       => 'ACT Certificate File',
        ];

        $staffId = $request->staff_id;
        $shiftDate = ShiftDate::findOrFail($request->shift_id);
        $shift = Shift::findOrFail($shiftDate->shift_id);

        $start = $shift->start_shift ?? null;
        $end = $shift->end_shift ?? null;
        $from = $shift->from_shift ?? null;
        $to = $shift->to_shift ?? null;
        $breakMinutes = $shift->{'break-mins_shift'} ?? null;

        $selectedDays = explode(',', trim($shift->days, '[]"'));
        // $newShiftHours = $this->calculateTotalWorkingHours($shift->staff_id, $from, $to, $start, $end, $breakMinutes, $selectedDays);
        $newShiftHours = 0;
        try {
            $newShiftHours = $this->calculateTotalWorkingHours(
                $staffId,
                $from,
                $to,
                $start,
                $end,
                $breakMinutes,
                $selectedDays,
                'H:i:s'
            );
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 422);
        }


        $staff = \App\Models\Employee::findOrFail($staffId);

        // 1. ✅ Check if staff has an overlapping shift at this time
        $overlap = \App\Models\ShiftDate::where('staff_id', $staff->id)
            ->where('shift_date', $shiftDate->shift_date)
            ->where(function ($query) use ($shiftDate) {
                $query->where(function ($q) use ($shiftDate) {
                    $q->where('start_time', '<', $shiftDate->end_time)
                        ->where('end_time', '>', $shiftDate->start_time);
                });
            })
            ->exists();

        if ($overlap) {
            return response()->json([
                'error' => 'This staff already has a shift during this time.'
            ], 422);
        }

        // 2. ✅ Check if staff SIA license is expired
        if ($staff->sia_expiry && \Carbon\Carbon::parse($staff->sia_expiry)->lt(now())) {
            return response()->json([
                'error' => 'This staff’s SIA license is expired.'
            ], 422);
        }

        if ($staff->visa_expiry && \Carbon\Carbon::parse($staff->visa_expiry)->lt(now())) {
            return response()->json([
                'error' => 'This staff’s Visa is expired.'
            ], 422);
        }

        if ($staff->passport_expiry && \Carbon\Carbon::parse($staff->passport_expiry)->lt(now())) {
            return response()->json([
                'error' => 'This staff’s Passport is expired.'
            ], 422);
        }

        $missingDocuments = [];

        foreach ($documents as $key => $doc) {
            if (empty($staff->$key)) {
                $missingDocuments[] = $doc;
            }
        }

        if (count($missingDocuments)) {
            return response()->json([
                'error' => "Missing required documents: " . implode(', ', $missingDocuments)
            ], 422);
        }

        $fromDate = \Carbon\Carbon::parse($from);
        $toDate = \Carbon\Carbon::parse($to);

        // Get week start and end for that shift date
        $weekStart = $fromDate->startOfWeek(Carbon::MONDAY);
        $weekEnd = $toDate->endOfWeek(Carbon::SUNDAY);


        // Fetch existing shifts for this staff in the same week
        $totalWeekHours = \App\Models\ShiftDate::where('staff_id', $staffId)
            ->whereBetween('shift_date', [$weekStart, $weekEnd])
            ->sum('total_hours');

        // Check if adding new shift exceeds weekly limit
        $maxWeeklyHours = $staff->hour_per_week ?? 40;

        if (($totalWeekHours + $newShiftHours) > $maxWeeklyHours) {
            $validator->errors()->add("staff_id", "The guard cannot be assigned more than 40 hours in a week.");
        }
        // Check if staff has a student visa and is exceeding 20 hours per week
        if ($staff->visa_type === 'Student') {
            // Check if the shift is within an active term period
            $isShiftInActiveTerm = \App\Models\EmployeeTerm::where('employee_id', $staff->id)
                ->where(function ($query) use ($shiftDate) {
                    $query->where('from_date', '<=', $shiftDate->shift_date)
                        ->where('to_date', '>=', $shiftDate->shift_date);
                })
                ->exists();
            if (!$isShiftInActiveTerm) {
                // Calculate total hours worked this week
                $weeklyHours = \App\Models\ShiftDate::where('staff_id', $staff->id)
                    ->whereBetween('shift_date', [now()->startOfWeek(), now()->endOfWeek()])
                    ->sum('total_hours') + $newShiftHours;
                if ($weeklyHours > 20) {
                    return response()->json([
                        'error' => 'The guard cannot be assigned more than 20 hours a week.'
                    ], 422);
                }
            }
        }

        // 3. ✅ Proceed to assign if checks pass (update without boot event and store logs manually)
        // $shiftDate->staff_id = $staff->id;
        // $shiftDate->is_assign = 1;
        // $shiftDate->save();
        $shiftDate->withoutEvents(function () use ($shiftDate, $staff) {
            $shiftDate->staff_id = $staff->id;
            $shiftDate->is_assign = 1;
            $shiftDate->save();
        });

        $staffName = $shiftDate?->staff?->fore_name . ' ' . $shiftDate?->staff?->sur_name;

        $shiftDate->logs()->create([
            'user_name' => auth()->user()->first_name . ' ' . auth()->user()->last_name,
            'action' => "Updated Staff",
            'description' => "Shift assigned to the Staff  $staffName",
        ]);

        return response()->json(['message' => 'Shift assigned successfully']);
    }

    public function getClient($id)
    {
        $client = Client::with('site')->findOrFail($id);

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
        $query = \App\Models\Shift::query();

        if ($request->filled('guard')) {
            $query->whereHas('guard', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->guard . '%');
            });
        }

        if ($request->filled('site')) {
            $query->whereHas('site', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->site . '%');
            });
        }

        if ($request->filled('security')) {
            $query->where('status', $request->security);
        }

        $shifts = $query->latest()->get();

        return view('security_boards.partials.shift_table', compact('shifts'))->render();
    }
}
