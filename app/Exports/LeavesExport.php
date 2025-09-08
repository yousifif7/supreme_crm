<?php

namespace App\Exports;

use App\Models\LeaveRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LeavesExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return LeaveRequest::select(
            DB::raw("CONCAT(users.first_name, ' ', users.last_name) as employee"),
            'leave_requests.reason as details',
            'leave_requests.type as leave_type',
            'leave_requests.start_date as date_from',
            'leave_requests.end_date as date_to',
            'leave_requests.status',
            'leave_requests.reject_reason',
            'leave_requests.hours as hours_requested',
            'leave_requests.approved_hours',
            'leave_requests.paid',
            'leave_requests.ssp_paid_days',
            'leave_requests.unpaid_days',
            'leave_requests.amount_paid',
            DB::raw("DATE_FORMAT(leave_requests.created_at, '%Y-%m-%d') as applied_at")
        )
        ->join('users', 'users.id', '=', 'leave_requests.user_id')
        ->get();
    }

    public function headings(): array
    {
        return [
            'Employee',
            'Details',
            'Leave Type',
            'Date From',
            'Date To',
            'Status',
            'Reject Reason',
            'Hours Requested',
            'Approved Hours',
            'Paid',
            'SSP Paid Days',
            'Unpaid Days',
            'Amount Paid',
            'Applied At',
        ];
    }
}
