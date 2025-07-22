<?php

namespace App\Exports;

use App\Models\EmployeeLeave;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LeavesExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        $leaves = EmployeeLeave::select(
            'employee_leaves.leave_entitlement',
            'employee_leaves.from_date',
            'employee_leaves.to_date',
            DB::raw("CONCAT(employees.fore_name, ' ', employees.sur_name) as full_name"),
            'employee_leaves.status',
            DB::raw("DATE_FORMAT(employee_leaves.created_at, '%Y-%m-%d %H:%i') as created_at"),
            DB::raw("DATE_FORMAT(employee_leaves.approved_at, '%Y-%m-%d %H:%i') as approved_at")
        )
        ->join('employees', 'employees.id', '=', 'employee_leaves.employee_id')
        ->get();

        return $leaves;
    }

    public function headings(): array
    {
        return [
            'Details',
            'Date From',
            'Date To',
            'Employee',
            'Status',
            'Applied At',
            'Approved At',
        ];
    }
}
