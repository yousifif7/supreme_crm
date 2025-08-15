<?php
// app/Services/InvoiceService.php
namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Shift;
use App\Models\ShiftDate;
use App\Models\User;
use Carbon\Carbon;
use App\Models\Client;
class InvoiceService
{
    public function generateClientInvoice($clientId, $siteId, $dateFrom, $dateTo, $dueDate, $notes = null)
    {
        $client = Client::where('user_id',$clientId)->first();
        $shift = Shift::where('client_id', $clientId)
            ->where('site_id', $siteId)
            ->firstOrFail();

        $shiftDates = ShiftDate::where('shift_id', $shift->id)
            ->whereBetween('shift_date', [$dateFrom, $dateTo])
            ->orderBy('shift_date')
            ->get();

        $invoiceItems = [];
        $totalHours = 0;
        $totalBreaks = 0;
        $totalBookOnHours = 0;
        $totalBookOffHours = 0;

        foreach ($shiftDates as $shiftDate) {
            $item = $this->processShiftDate($shiftDate, $client->office_rate);
            $invoiceItems[] = $item;
            
            $totalHours += $item['hours'] + $item['break_hours'] + $item['book_on_hours'] + $item['book_off_hours'];
            $totalBreaks += $item['break_hours'];
            $totalBookOnHours += $item['book_on_hours'];
            $totalBookOffHours += $item['book_off_hours'];
        }

        $totalDeductionsHours = $totalBreaks + $totalBookOnHours + $totalBookOffHours;
        $grossAmount = ($totalHours - $totalBreaks) * $client->office_rate;
        $netAmount = $grossAmount - (($totalBookOnHours + $totalBookOffHours) * $client->office_rate);

        $invoice = Invoice::create([
            'type' => 'client',
            'client_id' => $clientId,
            'site_id' => $siteId,
            'issue_date' => now(),
            'due_date' => $dueDate,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'total_amount' => $netAmount,
            'status' => 'draft',
            'notes' => $notes,
            'payment_note' => $client->payment_terms,
            'rate_per_hour' => $client->office_rate,
            'total_shift_hours' => $totalHours - $totalDeductionsHours,
            'total_duration_hours' => $totalHours,
            'total_break_hours' => $totalBreaks,
            'total_deductions_hours' => $totalDeductionsHours,
            'gross_amount' => $grossAmount,
            'net_amount' => $netAmount,
        ]);

        foreach ($invoiceItems as $itemData) {
            $invoice->items()->create($itemData);
        }

        return $invoice;
    }

    public function generateSubcontractorInvoice($subcontractorId, $dateFrom, $dateTo, $dueDate, $notes = null)
    {
        $subcontractor = User::findOrFail($subcontractorId);
        
        // Get all shifts managed by this subcontractor
      $shifts = Shift::with([
    'shiftDates' => function ($q) use ($dateFrom, $dateTo, $subcontractorId) {
        $q->when($subcontractorId, function ($query) use ($subcontractorId) {
            $query->whereHas('staff.employee', function ($q) use ($subcontractorId) {
                $q->where('subcontractor', $subcontractorId);
            });
        });

        $q->whereBetween('shift_date', [$dateFrom, $dateTo]);
    }
])->get();



        $invoiceItems = [];
        $totalHours = 0;
        $totalAmount = 0;

        foreach ($shifts as $shift) {
            foreach ($shift->shiftDates as $shiftDate) {
                $hourlyRate = $shiftDate->shift->po_rate??0;

                $item = $this->processShiftDate($shiftDate, $hourlyRate);
                $invoiceItems[] = $item;
                
                $totalHours += $item['hours'];
                $totalAmount += $item['amount'];
            }
        }

        $invoice = Invoice::create([
            'type' => 'subcontractor',
            'subcontractor_id' => $subcontractorId,
            'issue_date' => now(),
            'due_date' => $dueDate,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'total_amount' => $totalAmount,
            'status' => 'draft',
            'notes' => $notes,
            'payment_note' => $subcontractor->payment_terms,
            'rate_per_hour' => $totalHours > 0 ? $totalAmount / $totalHours : 0,
            'total_shift_hours' => $totalHours,
        ]);

        foreach ($invoiceItems as $itemData) {
            $invoice->items()->create($itemData);
        }

        return $invoice;
    }

    public function generateSecurityStaffInvoice($staffId,$site_id, $dateFrom, $dateTo, $dueDate, $notes = null)
    {
        $staff = User::findOrFail($staffId);
        
        $shiftDates = ShiftDate::where('staff_id', $staffId) ->whereHas('shift', function ($query) use ($site_id) {
        $query->where('site_id', $site_id);
    })->whereBetween('shift_date', [$dateFrom, $dateTo])
            ->with('shift.site')
            ->get();


        $invoiceItems = [];
        $totalHours = 0;
        $totalAmount = 0;

        foreach ($shiftDates as $shiftDate) {
            $hourlyRate = $shiftDate->shift->po_rate??0;

            $item = $this->processShiftDate($shiftDate, $hourlyRate);
            $invoiceItems[] = $item;
            
            $totalHours += $item['hours'];
            $totalAmount += $item['amount'];
        }

        $invoice = Invoice::create([
            'type' => 'security_staff',
            'security_staff_id' => $staffId,
            'subcontractor_id' => $staff->subcontractor_id,
            'issue_date' => now(),
            'site_id'=>$site_id,
            'due_date' => empty($dueDate)?now():$dueDate,
            'date_from' => $dateFrom,
            'date_to' => empty($dateTo)?now():$dateTo,
            'total_amount' => $totalAmount,
            'status' => 'draft',
            'notes' => $notes,
            'payment_note' => $staff->subcontractor->payment_terms ?? null,
            'rate_per_hour' => $totalHours > 0 ? $totalAmount / $totalHours : 0,
            'total_shift_hours' => $totalHours,
        ]);

        foreach ($invoiceItems as $itemData) {
            $invoice->items()->create($itemData);
        }

        return $invoice;
    }

    protected function processShiftDate($shiftDate, $hourlyRate)
    {
        $date = Carbon::parse($shiftDate->shift_date);
        $start = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $shiftDate->start_time);
        $end = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $shiftDate->end_time);

        if ($end->lessThan($start)) {
            $end->addDay();
        }

        $breakHours = $shiftDate->break_minutes / 60;
        $bookOnHours = $shiftDate->absentee_start_time ? 
            Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $shiftDate->absentee_start_time)
                ->diffInMinutes($end) / 60 : 0;
        $bookOffHours = $shiftDate->absentee_end_time ? 
            $start->diffInMinutes(Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $shiftDate->absentee_end_time)) / 60 : 0;

        $totalHours = $start->diffInMinutes($end) / 60;
        $payableHours = $totalHours - $breakHours - $bookOnHours - $bookOffHours;
        $amount = $payableHours * $hourlyRate;

        return [
            'shift_id' => $shiftDate->shift_id,
            'shift_date_id' => $shiftDate->id,
            'security_staff_id' => $shiftDate->staff_id,
            'site_id' => $shiftDate->shift->site_id,
            'date' => $date->format('Y-m-d'),
            'description' => "Security services at {$shiftDate->shift->site->name} on {$date->format('Y-m-d')}",
            'start_time' => $shiftDate->start_time,
            'end_time' => $shiftDate->end_time,
            'hours' => $payableHours,
            'break_hours' => $breakHours,
            'book_on_hours' => $bookOnHours,
            'book_off_hours' => $bookOffHours,
            'rate' => $hourlyRate,
            'amount' => $amount,
        ];
    }
}