<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\ShiftDate;
use App\Models\Site;
use App\Models\Subcontractor;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ShiftController extends Controller
{
    public function index()
    {
        $shifts = ShiftDate::with('shift.client', 'shift.site', 'shift.staff')->paginate(10);
        $clients = Client::all();
        $sites = Site::all();
        $staffs = Employee::all();
        $subcontractors = Subcontractor::all();
        $users = User::all();
        return view('security_boards.shifts', compact('shifts', 'clients', 'sites', 'staffs', 'subcontractors', 'users'));
    }
    public function scheduling()
    {
        $shifts = Shift::all();
        $clients = Client::all();
        $sites = Site::all();
        $staffs = Employee::all();
        $subcontractors = Subcontractor::all();
        $users = User::all();
        return view('security_boards.scheduling', compact('shifts', 'clients', 'sites', 'staffs', 'subcontractors', 'users'));
    }
    public function worker_calendar()
    {
        $shifts = Shift::all();
        $clients = Client::all();
        $sites = Site::all();
        $staffs = Employee::all();
        $subcontractors = Subcontractor::all();
        $users = User::all();
        return view('security_boards.worker_calendar', compact('shifts', 'clients', 'sites', 'staffs', 'subcontractors', 'users'));
    }
    public function site_calendar()
    {
        $shifts = Shift::all();
        $clients = Client::all();
        $sites = Site::all();
        $staffs = Employee::all();
        $subcontractors = Subcontractor::all();
        $users = User::all();
        return view('security_boards.site_calendar', compact('shifts', 'clients', 'sites', 'staffs', 'subcontractors', 'users'));
    }
    public function today_rota()
    {
        $shifts = Shift::all();
        $clients = Client::all();
        $sites = Site::all();
        $staffs = Employee::all();
        $subcontractors = Subcontractor::all();
        $users = User::all();
        return view('security_boards.today_rota', compact('shifts', 'clients', 'sites', 'staffs', 'subcontractors', 'users'));
    }

    public function store(Request $request)
    {
        $shiftCount = count($request->client_id); // Assuming client_id[] always present

        for ($i = 0; $i < $shiftCount; $i++) {
            $validator = Validator::make([
                'client_id' => $request->client_id[$i],
                'site_id' => $request->site_id[$i],
                'company_id' => $request->company_id[$i] ?? null,
                'staff_id' => $request->staff_id[$i] ?? null,
                'start_shift' => $request->start_shift[$i],
                'end_shift' => $request->end_shift[$i],
                'break_mins_shift' => $request->{'break-mins_shift'}[$i] ?? null,
                'number_shift' => $request->number_shift[$i] ?? null,
                'site_rate' => $request->site_rate[$i] ?? null,
                'service_type_1' => $request->service_type_1[$i] ?? null,
                'service_type_2' => $request->service_type_2[$i] ?? null,
                'from_shift' => $request->from_shift[$i] ?? null,
                'to_shift' => $request->to_shift[$i] ?? null,
                'comments' => $request->comments[$i] ?? null,
                'days' => $request->days[$i] ?? [],
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
            ], [
                'client_id' => 'required|integer',
                'site_id' => 'required|integer',
                'company_id' => 'nullable',
                'staff_id' => 'nullable|integer',
                'start_shift' => 'required|date_format:H:i',
                'end_shift' => 'required|date_format:H:i',
                'break_mins_shift' => 'nullable|date_format:H:i',
                'number_shift' => 'nullable|integer|min:0',
                'site_rate' => 'nullable|numeric',
                'service_type_1' => 'nullable|string|max:255',
                'service_type_2' => 'nullable|string|max:255',
                'from_shift' => 'nullable|date',
                'to_shift' => 'nullable|date|after_or_equal:from_shift',
                'comments' => 'nullable|string|max:1000',
                'days' => 'nullable',
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
            ]);
            $validator->after(function ($validator) use ($request, $i) {
                $start = \Carbon\Carbon::createFromFormat('H:i', $request->start_shift[$i]);
                $end = \Carbon\Carbon::createFromFormat('H:i', $request->end_shift[$i]);

                // Allow overnight shifts (e.g. 22:00 to 06:00 next day)
                if ($start->eq($end)) {
                    $validator->errors()->add('end_shift', 'End time must not be the same as start time.');
                }
            });

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors(), 'index' => $i], 422);
            }

            $data = $validator->validated();

            // Handle checkbox values (default 0)
            $data['restrict_start_time'] = $data['restrict_start_time'] ? 1 : 0;
            $data['enforce_picture_check'] = $data['enforce_picture_check'] ? 1 : 0;
            $data['restrict_location_check'] = $data['restrict_location_check'] ? 1 : 0;

            $data['days'] = json_encode([str_replace(['"', '[', ']'], '', $data['days'])]);


            if (!empty($data['staff_id'])) {
                $data['is_assign'] = 1;
            }

            $shift = Shift::create($data);

            // Directly split the incoming day string (no json_decode needed)
            $dayString = $request->days[$i]; // e.g. "Mon, Wed, Sun"
            $selectedDays = array_map('trim', explode(',', $dayString)); // ['Mon', 'Wed', 'Sun']

            // Create period from from_shift to to_shift
            $fromDate = \Carbon\Carbon::parse($data['from_shift']);
            $toDate = \Carbon\Carbon::parse($data['to_shift']);
            $period = \Carbon\CarbonPeriod::create($fromDate, $toDate);
            $is_assign = 0;
            if (!empty($data['staff_id'])) {
                $is_assign = 1;
            }
            // Loop through each day and insert if it matches
            foreach ($period as $date) {
                if (in_array($date->format('D'), $selectedDays)) {
                    \App\Models\ShiftDate::create([
                        'shift_id' => $shift->id,
                        'shift_date' => $date->format('Y-m-d'),
                        'start_time' => $data['start_shift'],
                        'end_time' => $data['end_shift'],
                        'is_assign' => $is_assign,
                        'break_time' => $data['break_mins_shift'] ?? null,
                        'total_hours' => $this->calculateTotalHours(
                            $data['start_shift'],
                            $data['end_shift'],
                            $data['break_mins_shift'] ?? '00:00'
                        ),
                    ]);
                }
            }
        }

        return response()->json(['message' => 'All shifts created successfully']);
    }

    public function edit($id)
    {
        $shift = Shift::with('client', 'site', 'staff')->find($id);
        return response()->json(['shift' => $shift]);
    }
    public function update(Request $request, $id)
    {
        $shift = Shift::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'client_id' => 'required|integer',
            'site_id' => 'required|integer',
            'company_id' => 'nullable|integer',
            'staff_id' => 'nullable|integer',
            'start_shift' => 'required',
            'end_shift' => 'required',
            'break-mins_shift' => 'nullable|date_format:H:i',
            'number_shift' => 'nullable|integer|min:0',
            'site_rate' => 'nullable|numeric',
            'service_type_1' => 'nullable|string|max:255',
            'service_type_2' => 'nullable|string|max:255',
            'from_shift' => 'nullable|date',
            'to_shift' => 'nullable|date|after_or_equal:from_shift',
            'comments' => 'nullable|string|max:1000',
            'days' => 'nullable',
            'employee_rate' => 'nullable|numeric',
            'po_number' => 'nullable|string|max:255',
            'lost_time' => 'nullable|string|max:255',
            'po_rate' => 'nullable|numeric',
            'start' => 'nullable',
            'end' => 'nullable',
            'manager_1_id' => 'nullable|integer',
            'manager_2_id' => 'nullable|integer',
            'restrict_start_time' => 'nullable',
            'enforce_picture_check' => 'nullable',
            'restrict_location_check' => 'nullable',
        ]);


        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();



        $data['restrict_start_time'] = $request->has('restrict_start_time') ? 1 : 0;
        $data['enforce_picture_check'] = $request->has('enforce_picture_check') ? 1 : 0;
        $data['restrict_location_check'] = $request->has('restrict_location_check') ? 1 : 0;


        $data['is_assign'] = !empty($data['staff_id']) ? 1 : 0;

        $shift->update($data);

        return response()->json(['message' => 'Shift updated successfully']);
    }

    public function getShifts()
    {
        $shiftDates = \App\Models\ShiftDate::with(['shift.client', 'shift.site', 'shift.staff'])->get();
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
            $book_on = $sd->shift_date . ", at  " . $sd->absentee_start;
            $book_off = $sd->shift_date . ", at " . $sd->absentee_end;
            $events[] = [
                'title' => $shift->client->client_name ?? 'Unknown Client',
                'start' => $sd->shift_date . 'T' . $sd->start_time,
                'end' => $sd->shift_date . 'T' . $sd->end_time,
                'location' => $shift->site->site_name ?? 'Unknown Site',
                'first' => asset('assets/img/icons/crown.svg'),
                'second' => asset('assets/img/icons/users_red.svg'),
                'third' => asset('assets/img/users/user-01.jpg'),
                'urgent' => rand(0, 1) === 1,
                'className' => $statusColorMap[$sd->is_assign] ?? 'bg-secondary',
                'site_name' => $shift->site->site_name ?? '',
                'site_address' => $shift->site->address ?? '',
                'shift_time' => "{$startFormatted} - {$endFormatted} ({$total_hour} hrs)",
                'phone_number' => $shift->staff->contact ?? '',
                'email' => $shift->staff->email ?? '',
                'sia_number' => $shift->staff->sia_licence ?? '',
                'sia_expiry' => $shift->staff->sia_expiry ?? '',
                'profile_picture' => $shift->staff->profile_picture ?? '',
                'name' => $shift->staff->fore_name ?? '',
                'subcontractor' => $shift->staff->subcontractor ?? '',
                'client_name' => $shift->client->client_name ?? '',
                'book_on' => $book_on,
                'book_off' => $book_off,
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

                    $events[] = [
                        'title' => $shift->staff->fore_name ?? 'Unknown Staff',
                        'start' => $startDateTime,
                        'end' => $endDateTime,
                        'location' => $shift->site->site_name ?? 'Unknown Site',
                        'first' => asset('assets/img/icons/crown.svg'),
                        'second' => asset('assets/img/icons/users_red.svg'),
                        'image' => asset('assets/img/users/user-01.jpg'),
                        'urgent' => rand(0, 1) === 1,
                        'className' => $statusColorMap[$shift->is_assign] ?? 'bg-secondary', // fallback
                    ];

                    $highlightDates[] = $shiftDate;
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

                    $events[] = [
                        'title' => $shift->site->site_name ?? 'Unknown Site',
                        'start' => $startDateTime,
                        'end' => $endDateTime,
                        'allDay' => false,
                        'urgent' => rand(0, 1) === 1,
                        'color' => '#3a87ad',
                        'className' => $statusColorMap[$shift->is_assign] ?? 'bg-secondary', // fallback
                    ];

                    $highlightDates[] = $shiftDate;
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

            $events[] = [
                'title' => $shift->client->client_name ?? 'Unknown Client',
                'start' => $start,
                'end' => $end,
                'client' => $shift->client->client_name ?? '',
                'site' => $shift->site->site_name ?? '',
                'allDay' => false,
                'color' => '#3a87ad',
                'urgent' => rand(0, 1) === 1,
                'className' => $statusColorMap[$shift->is_assign] ?? 'bg-secondary', // fallback
            ];
        }

        return response()->json($events);
    }

    private function calculateTotalHours($startTime, $endTime, $breakTime = '00:00')
    {
        $start = \Carbon\Carbon::createFromFormat('H:i', $startTime);
        $end = \Carbon\Carbon::createFromFormat('H:i', $endTime);

        // Handle overnight shifts
        if ($end->lessThan($start)) {
            $end->addDay();
        }

        $totalMinutes = $start->diffInMinutes($end);

        // Subtract break time
        if ($breakTime) {
            $break = \Carbon\Carbon::createFromFormat('H:i', $breakTime);
            $breakMinutes = ($break->hour * 60) + $break->minute;
            $totalMinutes -= $breakMinutes;
        }

        return $totalMinutes / 60; // Return as decimal hours
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
}
