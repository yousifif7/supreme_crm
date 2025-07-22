<?php

namespace App\DataTables;

use App\Models\EmployeeLeave;
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
            ->addColumn('action', function ($leave) {
                return view('leave_management.leaves.action', compact('leave'));
            })
            ->addColumn('checkbox', function ($leave) {
                return '<input type="checkbox" class="dT-row-checkbox" value="' . $leave->id . '">';
            })
            ->addColumn('number', function ($leave) {
                return '';
            })
            ->editColumn('leave_entitlement', function ($leave) {
                return view('leave_management.leaves.name_column', ['leave' => $leave]);
            })
            ->editColumn('from_date', function ($leave) {
                return $leave->from_date;
            })
            ->editColumn('to_date', function ($leave) {
                return $leave->to_date;
            })
            ->editColumn('status', function ($leave) {
                return ucfirst($leave->status ?? 'applied');
            })
            ->editColumn('created_at', function ($user) {
                return $user->created_at?->format('Y-m-d');
            })
            ->filterColumn('leave_entitlement', function($query, $keyword) {
                $query->where('leave_entitlement', 'like', "%{$keyword}%");
            })
            ->rawColumns(['action', 'checkbox', 'number', 'from_date', 'to_date', 'leave_entitlement'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<EmployeeLeave>
     */
    public function query(EmployeeLeave $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->select('employee_leaves.*');

        if ($this->filter === 'archived') {
            $query = $model->onlyTrashed()
                ->select('employee_leaves.*');
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('employee_leaves-table')
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
            Column::make('leave_entitlement')->title('Details')->addClass('ps-0')->orderable(false),
            Column::make('from_date')->title('Date From'),
            Column::make('to_date')->title('Date To'),
            Column::make('status')->title('Status'),
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
