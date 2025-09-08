<?php

namespace App\Exports;

use App\Models\EmployeeLeave;
use App\Models\LeaveRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LeavesExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        $leaves = LeaveRequest::select(
            DB::raw("CONCAT(employees.fore_name, ' ', employees.sur_name) as full_name"),
            'leave_requests.employee_id',
            'leave_requests.leave_entitlement as details',
            'leave_requests.type as leave_type',
            'leave_requests.from_date',
            'leave_requests.to_date',
            'leave_requests.status',
            'leave_requests.reject_reason',
            'leave_requests.hours',
            'leave_requests.approved_hours',
            'leave_requests.paid',
            'leave_requests.ssp_paid_days',
            'leave_requests.unpaid_days',
            'leave_requests.amount_paid',
            DB::raw("DATE_FORMAT(leave_requests.created_at, '%Y-%m-%d %H:%i') as applied_at"),
            DB::raw("DATE_FORMAT(leave_requests.approved_at, '%Y-%m-%d %H:%i') as approved_at")
        )
        ->join('employees', 'employees.id', '=', 'leave_requests.employee_id')
        ->get();

        return $leaves;
    }

    public function headings(): array
    {
        return [
            'Employee',
            'Employee ID',
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
            'Approved At',
        ];
    }
}
