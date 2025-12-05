<?php

namespace App\DataTables;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class PayrollsDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn(
                'checkbox',
                fn($row) =>
                '<input type="checkbox" class="dT-row-checkbox" value="' . $row->id . '">'
            )
            ->addColumn('number', fn($row) => '')
            ->addColumn('payroll_no', function ($row) {
                return '<a href="' . route('payrolls.show', $row->id) . '">' . $row->invoice_number . '</a>';
            })
            ->addColumn(
                'employee_name',
                fn($row) =>
                $row->securityStaff ? $row->securityStaff->first_name . ' ' . $row->securityStaff->last_name : ''
            )
            ->addColumn('status', function ($row) {
                if ($row->paid_amount >= $row->net_amount) {
                    return '<span class="badge bg-success">Paid</span>';
                } else {
                    return '<span class="badge bg-warning text-dark">Unpaid</span>';
                }
            })
            ->addColumn(
                'site_name',
                fn($row) =>
                $row->site ? $row->site->site_name : ''
            )
            ->addColumn('ssp_days', fn($row) => $row->ssp_days ?? 0)
            ->addColumn('ssp_amount', fn($row) => number_format($row->ssp_amount ?? 0, 2))
            ->addColumn('holiday_hours', fn($row) => $row->holiday_hours ?? 0)
            ->addColumn('holiday_amount', fn($row) => number_format($row->holiday_amount ?? 0, 2))
            ->addColumn('unpaid_leave_hours', fn($row) => $row->unpaid_leave_hours ?? 0)
            ->addColumn('unpaid_leave_amount', fn($row) => number_format($row->unpaid_leave_amount ?? 0, 2))
            // Format issue_date and due_date as MM/DD/YYYY
            ->addColumn('issue_date', function($row) {
                if (empty($row->issue_date)) return '';
                try {
                    return Carbon::parse($row->issue_date)->format('d/m/Y');
                } catch (\Exception $e) {
                    return $row->issue_date;
                }
            })
            ->addColumn('due_date', function($row) {
                if (empty($row->due_date)) return '';
                try {
                    return Carbon::parse($row->due_date)->format('m/d/Y');
                } catch (\Exception $e) {
                    return $row->due_date;
                }
            })
            ->addColumn('action', function ($row) {
                // Single delete button for payrolls
                $deleteBtn = '<a href="javascript:void(0)" class="btn btn-sm btn-danger" ' .
                    'onclick="deleteRecord(' . $row->id . ', \'payrolls\')">' .
                    'Delete</a>';

                // You can include more action buttons here if needed
                return $deleteBtn;
            })
            ->filterColumn('employee_name', function ($query, $keyword) {
                $query->whereHas('securityStaff', function ($q) use ($keyword) {
                    $q->where('first_name', 'like', "%{$keyword}%")
                        ->orWhere('last_name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('site_name', function ($query, $keyword) {
                $query->whereHas('site', function ($q) use ($keyword) {
                    $q->where('site_name', 'like', "%{$keyword}%");
                });
            })
            ->rawColumns(['checkbox', 'number', 'payroll_no', 'action', 'status']);
    }

    public function query(Invoice $model): QueryBuilder
    {
        return $model->newQuery()
            ->with(['employee', 'site'])
            ->whereNull('client_id') // Only payrolls
            ->whereNotNull('security_staff_id')
            ->orderBy('id', 'desc');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('payrolls-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom(
                't
                <"d-flex justify-content-between mt-2"
                  <"col-sm-12 col-md-5 align-self-center ps-3"i>
                  <"d-flex justify-content-between" p>
                >'
            )
            ->addAction(['width' => '120px'])
            ->orderBy([2, 'DESC'])
            ->parameters([
                "scrollX" => true,
                "pageLength" => 25,
                "drawCallback" => "function(settings) {
                    feather.replace();
                    var api = this.api();
                    var start = api.page.info().start;
                    api.column(1, {page: 'current'}).nodes().each(function(cell, i) {
                        cell.innerHTML = start + i + 1;
                    });
                }",
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('checkbox')
                ->title('<input type="checkbox" id="payrolls-select-all">')->addClass('px-2 fw-bold'),

            Column::computed('number')->title('#')->width(30)
                ->addClass('px-2')->orderable(false)->searchable(false)->addClass('px-2 fw-bold'),

            Column::computed('payroll_no')->title('Payroll No')->addClass('ps-0')->addClass('px-2 fw-bold'),
            Column::make('employee_name')->title('Employee Name')->addClass('px-2 fw-bold')->searchable(true),
            Column::make('site_name')->title('Site Name')->addClass('px-2 fw-bold'),
            Column::make('issue_date')->title('Issue Date')->addClass('px-2 fw-bold'),
            Column::make('due_date')->title('Due Date')->addClass('px-2 fw-bold'),
            Column::make('total_shift_hours')->title('Total Shift Hours')->addClass('px-2 fw-bold'),
            Column::make('net_amount')->title('Net Amount')->addClass('px-2 fw-bold'),
            Column::make('total_amount')->title('Total Amount')->addClass('px-2 fw-bold'),
            Column::make('ssp_days')->title('SSP Days')->addClass('px-2 fw-bold'),
            Column::make('ssp_amount')->title('SSP Amount')->addClass('px-2 fw-bold'),
            Column::make('holiday_hours')->title('Holiday Hours')->addClass('px-2 fw-bold'),
            Column::make('holiday_amount')->title('Holiday Amount')->addClass('px-2 fw-bold'),
            Column::make('unpaid_leave_hours')->title('Unpaid Leave Hours')->addClass('px-2 fw-bold'),
            Column::make('unpaid_leave_amount')->title('Unpaid Leave Amount')->addClass('px-2 fw-bold'),

        ];
    }

    protected function filename(): string
    {
        return 'Payrolls_' . date('YmdHis');
    }
}
