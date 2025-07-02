<?php

namespace App\DataTables;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class EmployeesDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<Employee> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function ($employee) {
                return view('employees.action', compact('employee'));
            })
            ->addColumn('checkbox', function ($employee) {
                return '<input type="checkbox" class="dT-row-checkbox" value="' . $employee->id . '">';
            })
            ->editColumn('name', function ($employee) {
                return view('employees.name_column', ['employee' => $employee]);
            })
            ->editColumn('sia_licence', function ($employee) {
                return '<p class="mb-0 fw-semibold">' . $employee->sia_licence . '</p>
                        <span class="text-primary fw-bold">Active</span>';
            })
            ->editColumn('sia_expiry', function ($employee) {
                return $employee->sia_expiry;
            })
            ->editColumn('visa_expiry', function ($employee) {
                return $employee->visa_expiry;
            })
            ->editColumn('visa_type', function ($employee) {
                return $employee->visa_type;
            })
            ->editColumn('contact', function ($employee) {
                return $employee->contact;
            })
            ->editColumn('subcontractor', function ($employee) {
                return $employee->subcontractor;
            })
            ->rawColumns(['action', 'checkbox', 'name', 'sia_licence'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Employee>
     */
    public function query(Employee $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->select('employees.*');

        if ($this->filter === 'archived') {
            $query = $model->onlyTrashed()
                ->select('employees.*');
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('employees-table')
            ->setTableAttribute('class', 'table table-row-bordered table-row-dashed gy-4 align-middle fw-bold')
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
            ->orderBy([1, 'DESC'])
            ->parameters([
                "scrollX" => true,
                "drawCallback" => "function(settings) {
                    feather.replace();
                }",
            ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::computed('checkbox')->title('<input type="checkbox" id="selectAll">')->exportable(false)->printable(false)->width(30)->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('id')->title('#')->width(60),
            Column::computed('name')->title('Name')->orderable(false),
            Column::make('sia_licence')->title('SIA'),
            Column::make('sia_expiry')->title('EXPIRY'),
            Column::make('visa_expiry')->title('VISA EXPIRY'),
            Column::make('visa_type')->title('IMMIGRATION STATUS'),
            Column::make('contact')->title('CONTACT NO'),
            Column::make('subcontractor')->title('SUBCONTRACTOR')
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Employees_' . date('YmdHis');
    }
}
