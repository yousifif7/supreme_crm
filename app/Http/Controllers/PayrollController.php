<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Site;
use App\Models\User;
use App\Models\Shift;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Employee;
use Carbon\CarbonPeriod;
use App\Models\ShiftDate;
use App\Models\EmployeeType;
use Illuminate\Http\Request;
use App\DataTables\PayrollsDataTable;
use Illuminate\Support\Facades\Validator;

class PayrollController extends Controller
{
    public function index(PayrollsDataTable $dataTable)
    {
        return $dataTable->render('invoices.payrolls');
    }
public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'security_staff_id' => 'required|integer|exists:users,id',
        'notes'             => 'nullable|string|max:355',
        'site_id'           => 'nullable|integer|exists:sites,id',
        'date_from'         => 'nullable|date',
        'date_to'           => 'nullable|date|after_or_equal:date_from',
    ]);

    if ($validator->fails()) {
        if ($request->ajax()) {
            return response()->json(['errors' => $validator->errors()], 422);
        } else {
            return redirect()->back()->withErrors($validator)->withInput();
        }
    }

    $data = $validator->validated();

    $employee = Employee::where('user_id', $request->security_staff_id)->firstOrFail();
    $user = User::findOrFail($request->security_staff_id);

    // ✅ Generate unique invoice number for today
    $today = now()->format('Ymd');
    $lastInvoiceToday = Invoice::whereDate('created_at', now()->toDateString())
        ->orderBy('id', 'desc')
        ->first();

    if ($lastInvoiceToday) {
        $lastSequence = (int)substr($lastInvoiceToday->invoice_number, -6);
        $nextSequence = $lastSequence + 1;
    } else {
        $nextSequence = 1;
    }

    $invoiceNumber = 'INV-' . $today . '-' . str_pad($nextSequence, 6, '0', STR_PAD_LEFT);

    $payrollData = [
        'security_staff_id' => $user->id,
        'site_id'           => $data['site_id'] ?? null,
        'employee_name'     => $employee->fore_name . ' ' . $employee->sure_name,
        'notes'             => $data['notes'] ?? '',
        'date_from'         => $data['date_from'] ?? null,
        'date_to'           => $data['date_to'] ?? null,
        'issue_date'        => now(),
        'due_date'          => now()->addDays(15),
        'invoice_number'    => $invoiceNumber,
    ];

    $payroll = Invoice::create($payrollData);

    // You can now add your shift + calculations logic here if needed
    // Example:
    // $shift = Shift::where('staff_id', $payroll->security_staff_id)
    //               ->where('site_id', $payroll->site_id)
    //               ->first();

    return response()->json([
        'message' => 'Payroll created successfully',
        'invoice_number' => $payroll->invoice_number,
    ]);
}



    public function update(Request $request, $id) {}

    public function edit($id)
    {
        $employee = Employee::where('user_id',$id)->first();
        $sites = Shift::select('id', 'site_id')
            ->with([
                'site:id,site_name',
                'shiftDates' => function ($query) use ($employee) {
                    $query->where('staff_id', $employee->user_id);
                }
            ])
            ->get();
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
        $shiftDates = ShiftDate::where('shift_id', $shift->id)->whereBetween('shift_date', [$startDate, $endDate])->orderBy('id')->get();
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

                if ($shiftDate->absentee_start_time) {
                    $bookonTime = $shiftDate->absentee_start_time;
                    $bookOnDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $bookonTime);

                    $bookonDurationInMinutes = $bookOnDateTime->diffInMinutes($endDateTime) - $durationInMinutes;
                    $totalBookOnHours += $bookonDurationInMinutes / 60;
                }

                if ($shiftDate->absentee_end_time) {
                    $bookoffTime = $shiftDate->absentee_end_time;
                    $bookOffDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $bookoffTime);

                    $bookoffDurationInMinutes = $startDateTime->diffInMinutes($bookOffDateTime) - $durationInMinutes;
                    $totalBookOffHours += $bookoffDurationInMinutes / 60;
                }
            }
        }

        $totalHours = $invoice->total_duration_hours;
        $totalBreaks = $invoice->total_break_hours;

        return view('invoices.show', compact('invoice', 'employee', 'site', 'shift', 'totalHours', 'totalBreaks', 'totalBookOnHours', 'totalBookOffHours', 'shiftDays'));
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
