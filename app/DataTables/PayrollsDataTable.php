<?php

namespace App\DataTables;

use App\Models\Invoice;
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
                return '<div class="d-flex align-items-center file-name-icon">
                            <div class="ms-2">
                                <h6 class="fw-medium"><a href="' . route('payrolls.show', $row->id) . '">' . $row->invoice_no . '</a></h6>
                            </div>
                        </div>';
            })
            ->addColumn('employee_name', fn($row) => $row->employee ? $row->employee->fore_name.' '. $row->employee->sur_name : '')
            ->addColumn('site_name', fn($row) => $row->site ? $row->site->site_name : '')
            ->addColumn('action', fn($row) => view('invoices.action', compact('row'))->render())
            ->rawColumns(['checkbox', 'number', 'payroll_no', 'action']);
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
                "pageLength" => 15,
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
            Column::computed('checkbox')->title('<input type="checkbox" id="select-all-checkbox">')
                ->exportable(false)->printable(false)->width(20)
                ->addClass('text-center px-2')->orderable(false)->searchable(false),

            Column::computed('number')->title('#')->width(30)
                ->addClass('px-2')->orderable(false)->searchable(false),

            Column::make('invoice_number')->title('Payroll No')->addClass('ps-0'),
            Column::make('employee_name')->title('Employee Name'),
            Column::make('site_name')->title('Site Name'),
            Column::make('issue_date')->title('Issue Date'),
            Column::make('due_date')->title('Due Date'),
            Column::make('total_shift_hours')->title('Total Shift Hours'),
            Column::make('net_amount')->title('Net Amount'),
            Column::make('total_amount')->title('Total Amount'),
        ];
    }

    protected function filename(): string
    {
        return 'Payrolls_' . date('YmdHis');
    }
}
