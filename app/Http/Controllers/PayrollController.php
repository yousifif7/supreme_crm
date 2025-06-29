<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\EmployeeType;
use App\Models\Invoice;
use App\Models\Site;
use App\Models\Shift;
use App\Models\Employee;
use App\Models\ShiftDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\CarbonPeriod;
use Carbon\Carbon;

class PayrollController extends Controller
{
    public function index()
    {
        $payrolls = Invoice::with(['employee','site'])->orderBy('id', 'desc')->paginate(15);
        return view('payrolls.index', compact('payrolls'));
    }
    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id'     => 'required|string|max:255',
            'employee_name'     => 'required|string|max:255',
            'notes'         => 'required|string|max:355',
            'site_id' => 'required',
            'date_from'   => 'required|date',
            'date_to'     => 'required|date|after_or_equal:date_from',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            } else {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }

        $newStart = Carbon::parse($request->date_from);
        $newEnd = Carbon::parse($request->date_to);

        // Check for overlap
        $overlap = Invoice::where(function ($query) use ($newStart, $newEnd) {
                $query->whereBetween('date_from', [$newStart, $newEnd])
                      ->orWhereBetween('date_to', [$newStart, $newEnd])
                      ->orWhere(function ($query) use ($newStart, $newEnd) {
                          $query->where('date_from', '<=', $newStart)
                                ->where('date_to', '>=', $newEnd);
                      });
            })
            ->where('employee_id', $request->employee_id)
            ->where('site_group_id', $request->site_id)
            ->exists();

        if ($overlap) {
            if ($request->ajax()) {
                return response()->json(['errors' => ['date_from' => ['A payroll already exists for this date range.']]], 422);
            } else {
                return redirect()->back()->withErrors(['date_from' => ['A payroll already exists for this date range.']])->withInput();
            }
        }

        $data = $validator->validated();

        $payrollData = [
            'employee_id' => $data['employee_id'],
            'site_group_id' => $data['site_id'],
            'notes' => $data['notes'],
            'date_from' => $data['date_from'],
            'date_to' => $data['date_to'],
            'invoice_date' => Carbon::now(),
            'invoice_title' => "Payroll of ".$data['employee_name']." for ".$data['date_from']." - ".$data['date_to'],
        ];

        $payroll = Invoice::create($payrollData);

        if($payroll)
        {
            $employee = Employee::findOrFail($payroll->employee_id);
            $shift = Shift::where('staff_id', $payroll->employee_id)->where('site_id', $payroll->site_group_id)->first();

            $startDate = Carbon::parse($payroll->date_from);
            $endDate = Carbon::parse($payroll->date_to);

            $startTime = $shift->start_shift;
            $endTime = $shift->end_shift;
            $breakMinutesPerDay = $shift->{'break-mins_shift'};

            $string = $shift->days;
            // Step 1: Decode the JSON string into PHP array
            $shiftDays = $array = json_decode($string, true);
            // Step 2: Explode the first string element into individual days
            $daysAllowed = explode(',', $array[0]);

            $totalHours = 0;
            $totalBreaks = 0;
            $totalBookOnHours = 0;
            $totalBookOffHours = 0;

            // Create a period from start to end date
            // $period = CarbonPeriod::create($startDate, $endDate);
            $shiftDates = ShiftDate::where('shift_id',$shift->id)->whereBetween('shift_date', [$startDate, $endDate])->orderBy('id')->get();
            foreach ($shiftDates as $shiftDate) {

                $date = Carbon::parse($shiftDate->shift_date);
                // $date = Carbon::parse($shiftDate->shift_date);
                $startTime = $shiftDate->start_time;
                $endTime = $shiftDate->end_time;
                if (in_array($date->format('D'), $daysAllowed)) {
                    $startDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $startTime);
                    $endDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $endTime);

                    if ($endDateTime->lessThan($startDateTime)) {
                        // If end time is before start time (e.g. overnight shift), add one day
                        $endDateTime->addDay();
                    }

                    $totalBreaks += $breakMinutesPerDay / 60;
                    $durationInMinutes = $startDateTime->diffInMinutes($endDateTime) - $breakMinutesPerDay;
                    $totalHours += $durationInMinutes / 60;

                    if($shiftDate->absentee_start_time)
                    {
                        $bookonTime = $shiftDate->absentee_start_time;
                        $bookOnDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $bookonTime);

                        $bookonDurationInMinutes = $durationInMinutes - $bookOnDateTime->diffInMinutes($endDateTime);
                        $totalBookOnHours += $bookonDurationInMinutes / 60;
                    }

                    if($shiftDate->absentee_end_time)
                    {
                        $bookoffTime = $shiftDate->absentee_end_time;
                        $bookOffDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $bookoffTime);

                        $bookoffDurationInMinutes = $durationInMinutes - $startDateTime->diffInMinutes($bookOffDateTime);
                        $totalBookOffHours += $bookoffDurationInMinutes / 60;
                    }

                }
            }

            $totalDeductions = ($totalBookOnHours * $employee->guard_rate) + ($totalBookOffHours * $employee->guard_rate);


            $payroll->update([
                'rate_per_hour' => $employee->guard_rate,
                'total_shift_hours' => $totalHours-$totalBreaks-$totalBookOnHours-$totalBookOffHours,
                'total_duration_hours' => $totalHours,
                'total_break_hours' => $totalBreaks,
                'total_deductions_hours' => $totalBreaks+$totalBookOnHours+$totalBookOffHours,
                'gross_amount' => ($totalHours-$totalBreaks) * $employee->guard_rate,
                'net_amount' => (($totalHours-$totalBreaks) * $employee->guard_rate) - $totalDeductions,
            ]);

        }

        return response()->json(['message' => 'Payroll created successfully']);
    }

    public function update(Request $request, $id)
    {
        
    }

    public function edit($id)
    {
        $employee = Employee::find($id);
        $sites = Shift::select('id','site_id')->with('site:id,site_name')->where('staff_id', $employee->id)->get();
        return response()->json(['employee' => $employee, 'sites' => $sites]);        
    }

    public function show($id)
    {
        $invoice = Invoice::findOrFail($id);
        // $invoices = Invoice::orderBy('id', 'desc')->paginate(15);
        $employee = Employee::findOrFail($invoice->employee_id);
        $site = Site::where('id', $invoice->site_group_id)->first();
        $shift = Shift::where('staff_id', $employee->id)->where('site_id', $site->id)->first();

        $startDate = Carbon::parse($invoice->date_from);
        $endDate = Carbon::parse($invoice->date_to);

        $startTime = $shift->start_shift;
        $endTime = $shift->end_shift;
        $breakMinutesPerDay = $shift->{'break-mins_shift'};

        $string = $shift->days;
        // Step 1: Decode the JSON string into PHP array
        $shiftDays = $array = json_decode($string, true);
        // Step 2: Explode the first string element into individual days
        $daysAllowed = explode(',', $array[0]);

        $totalHours = 0;
        $totalBreaks = 0;
        $totalBookOnHours = 0;
        $totalBookOffHours = 0;

        // Create a period from start to end date
        // $period = CarbonPeriod::create($startDate, $endDate);
        $shiftDates = ShiftDate::where('shift_id',$shift->id)->whereBetween('shift_date', [$startDate, $endDate])->orderBy('id')->get();
        foreach ($shiftDates as $shiftDate) {

            $date = Carbon::parse($shiftDate->shift_date);
            // $date = Carbon::parse($shiftDate->shift_date);
            $startTime = $shiftDate->start_time;
            $endTime = $shiftDate->end_time;
            if (in_array($date->format('D'), $daysAllowed)) {
                $startDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $startTime);
                $endDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $endTime);

                if ($endDateTime->lessThan($startDateTime)) {
                    // If end time is before start time (e.g. overnight shift), add one day
                    $endDateTime->addDay();
                }

                $durationInMinutes = $startDateTime->diffInMinutes($endDateTime) - $breakMinutesPerDay;
                // $totalHours += $durationInMinutes / 60;
                // $totalBreaks += $breakMinutesPerDay / 60;

                if($shiftDate->absentee_start_time)
                {
                    $bookonTime = $shiftDate->absentee_start_time;
                    $bookOnDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $bookonTime);

                    $bookonDurationInMinutes = $bookOnDateTime->diffInMinutes($endDateTime) - $durationInMinutes;
                    $totalBookOnHours += $bookonDurationInMinutes / 60;
                }

                if($shiftDate->absentee_end_time)
                {
                    $bookoffTime = $shiftDate->absentee_end_time;
                    $bookOffDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $bookoffTime);

                    $bookoffDurationInMinutes = $startDateTime->diffInMinutes($bookOffDateTime) - $durationInMinutes;
                    $totalBookOffHours += $bookoffDurationInMinutes / 60;
                }

            }
        }

        $totalHours = $invoice->total_duration_hours;
        $totalBreaks = $invoice->total_break_hours;

        return view('invoices.show', compact('invoice', 'employee', 'site', 'shift', 'totalHours', 'totalBreaks', 'totalBookOnHours' , 'totalBookOffHours', 'shiftDays'));   
    }

    public function delete($id)
    {
        $payroll = Invoice::findOrFail($id);
        $payroll->delete();

        return response()->json(['success' => true]);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:pay_rolls,id',
        ]);

        Invoice::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => 'Selected payrolls deleted.']);
    }
}
