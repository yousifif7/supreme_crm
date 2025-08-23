<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Site;
use App\Models\User;
use App\Models\Shift;
use App\Models\Client;
use App\Models\Invoice;
use Carbon\CarbonPeriod;
use App\Models\ShiftDate;
use App\Models\EmployeeType;
use Illuminate\Http\Request;
use App\DataTables\InvoicesDataTable;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends Controller
{
    public function index(InvoicesDataTable $dataTable)
    {
        $clients = User::role('client')->get();
        $sites = Site::all();
        return $dataTable->render('invoices.index', compact('clients', 'sites'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id'     => 'required|string|max:255',
            'notes'         => 'required|string|max:355',
            'site_id' => 'required',
            'due_date'   => 'required|date',
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
            ->where('client_id', $request->client_id)
            ->where('site_id', $request->site_id)
            ->exists();

        if ($overlap) {
            if ($request->ajax()) {
                return response()->json(['errors' => ['date_from' => ['An invoice already exists for this date range.']]], 422);
            } else {
                return redirect()->back()->withErrors(['date_from' => ['An invoice already exists for this date range.']])->withInput();
            }
        }

        $data = $validator->validated();

        // $client = Client::find($request->get('client_id'));
        $shift = Shift::where('client_id', $data['client_id'])->where('site_id', $data['site_id'])->first();
        if (empty($shift)) {
            if ($request->ajax()) {
                return response()->json(['errors' => ['site_id' => ['Shift data not found against that site.']]], 422);
            } else {
                return redirect()->back()->withErrors(['site_id' => ['Shift data not found against that site.']])->withInput();
            }
        }

        $invoiceData = [
            'client_id' => $data['client_id'],
            'site_id' => $data['site_id'],
            'notes' => $data['notes'],
            'due_date' => $data['due_date'],
            'date_from' => $data['date_from'],
            'date_to' => $data['date_to'],
            'issue_date' => Carbon::now(),
            // 'invoice_title' => "Invoice of ".$data['client_name']." for ".$data['date_from']." - ".$data['date_to'],
        ];

        $invoice = Invoice::create($invoiceData);

        if ($invoice) {
            $client = User::role('client')->where('id', $invoice->client_id)->first();

            $startDate = Carbon::parse($invoice->date_from);
            $endDate = Carbon::parse($invoice->date_to);

            $startTime = $shift->start_shift;
            $endTime = $shift->end_shift;
            $breakMinutesPerDay = $shift->{'break-mins_shift'};

            $string = $shift->days;
            $shiftDays = json_decode($string, true);
            $daysAllowed = [];

            if ($shiftDays && isset($shiftDays[0])) {
                $daysAllowed = explode(',', $shiftDays[0]);
            }

            $totalHours = 0;
            $totalBreaks = 0;
            $totalBookOnHours = 0;
            $totalBookOffHours = 0;

            // Create a period from start to end date
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

                    $totalBreaks += $breakMinutesPerDay / 60;
                    $durationInMinutes = $startDateTime->diffInMinutes($endDateTime) - $breakMinutesPerDay;
                    $totalHours += $durationInMinutes / 60;
                }
            }

            $totalDeductions = ($totalBookOnHours * $client->office_rate) + ($totalBookOffHours * $client->office_rate);

            $invoice->update([
                'payment_note' => $client->payment_terms,
                'rate_per_hour' => $client->office_rate,
                'total_shift_hours' => $totalHours - $totalBreaks - $totalBookOnHours - $totalBookOffHours,
                'total_duration_hours' => $totalHours,
                'total_break_hours' => $totalBreaks,
                'total_deductions_hours' => $totalBreaks + $totalBookOnHours + $totalBookOffHours,
                'gross_amount' => ($totalHours - $totalBreaks) * $client->office_rate,
                'net_amount' => (($totalHours - $totalBreaks) * $client->office_rate) - $totalDeductions,
            ]);
        }
        return response()->json(['message' => 'Invoice created successfully']);
    }

    public function update(Request $request, $id) {}

    public function edit($id)
    {
        $client = Client::with('site')->find($id);
        $sites = $client->site;
        return response()->json(['client' => $client, 'sites' => $sites]);
    }

    public function show($id)
    {
        $invoice = Invoice::findOrFail($id);
        // $invoices = Invoice::orderBy('id', 'desc')->paginate(15);
        $client = Client::findOrFail($invoice->client_id);
        $site = Site::where('id', $invoice->site_group_id)->where('client_id', $client->id)->first();
        $shift = Shift::where('client_id', $client->id)->where('site_id', $site->id)->first();

        $startDate = Carbon::parse($invoice->date_from);
        $endDate = Carbon::parse($invoice->date_to);

        $startTime = $shift->start_shift ?? '';
        $endTime = $shift->end_shift ?? '';
        $breakMinutesPerDay = $shift->{'break-mins_shift'} ?? '';

        $string = $shift->days ?? '';
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

        return view('invoices.show', compact('invoice', 'client', 'site', 'shift', 'totalHours', 'totalBreaks', 'totalBookOnHours', 'totalBookOffHours', 'shiftDays'));
    }

    public function delete($id)
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->delete();

        return response()->json(['success' => true]);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:invoices,id',
        ]);

        Invoice::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => 'Selected invoices deleted.']);
    }
}
