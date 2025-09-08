<?php

namespace App\DataTables;

use App\Models\EmployeeLeave;
use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class LeavesDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<EmployeeLeave> $query Results from query() method.
     */
public function dataTable(QueryBuilder $query): EloquentDataTable
{
    return (new EloquentDataTable($query))
        ->addColumn('action', fn($leave) => view('leave_management.leaves.action', compact('leave')))
        ->addColumn('checkbox', fn($leave) => '<input type="checkbox" class="dT-row-checkbox" value="' . $leave->id . '">')
        ->addColumn('number', fn($leave) => '')
        ->editColumn('reason', fn($leave) => view('leave_management.leaves.name_column', ['leave' => $leave]))
        ->editColumn('start_date', fn($leave) => $leave->start_date)
        ->editColumn('end_date', fn($leave) => $leave->end_date)
        ->editColumn('status', fn($leave) => ucfirst($leave->status ?? 'pending'))
        ->editColumn('type', fn($leave) => ucfirst($leave->type ?? 'other'))
        ->editColumn('hours', fn($leave) => $leave->hours)
        ->editColumn('approved_hours', fn($leave) => $leave->approved_hours)
        ->editColumn('paid', fn($leave) => $leave->paid ? 'Yes' : 'No')
        ->editColumn('ssp_paid_days', fn($leave) => $leave->ssp_paid_days)
        ->editColumn('unpaid_days', fn($leave) => $leave->unpaid_days)
        ->editColumn('amount_paid', fn($leave) => number_format($leave->amount_paid, 2))
        ->editColumn('created_at', fn($leave) => $leave->created_at?->format('Y-m-d'))
        ->filterColumn('reason', fn($query, $keyword) => $query->where('reason', 'like', "%{$keyword}%"))
        ->rawColumns(['action', 'checkbox', 'number', 'reason'])
        ->setRowId('id');
}


    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<EmployeeLeave>
     */
    public function query(LeaveRequest $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->select('leave_requests.*');

        if ($this->filter === 'archived') {
            $query = $model->onlyTrashed()
                ->select('leave_requests.*');
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('leave_requests-table')
            ->setTableAttribute('class', 'table table-row-bordered table-row-dashed gy-4 align-middle fw-bold')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->addAction(['width' => '80px'])
            ->dom(
                't
                <"d-flex justify-content-between mt-2"
                  <"col-sm-12 col-md-5 align-self-center ps-3"i>
                  <"d-flex justify-content-between" p>
                >'
            )
            ->orderBy([6, 'DESC'])
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

    /**
     * Get the dataTable columns definition.
     */
public function getColumns(): array
{
    return [
        Column::computed('checkbox')->title('<input type="checkbox" id="selectAll">')->exportable(false)->printable(false)->width(20)->addClass('text-center px-2')->orderable(false)->searchable(false),
        Column::computed('number')->title('#')->width(30)->addClass('px-2')->orderable(false)->searchable(false),
        Column::make('reason')->title('Details')->addClass('ps-0')->orderable(false),
        Column::make('start_date')->title('Start date'),
        Column::make('end_date')->title('End date'),
        Column::make('status')->title('Status'),
        Column::make('type')->title('Type'),
        Column::make('hours')->title('Requested Hours'),
        Column::make('approved_hours')->title('Approved Hours'),
        Column::make('paid')->title('Paid'),
        Column::make('ssp_paid_days')->title('SSP Paid Days'),
        Column::make('unpaid_days')->title('Unpaid Days'),
        Column::make('amount_paid')->title('Amount Paid'),
        Column::make('created_at')->title('Created at'),
    ];
}

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Leaves_' . date('YmdHis');
    }
}
