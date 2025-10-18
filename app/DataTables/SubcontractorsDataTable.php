<?php

namespace App\DataTables;

use App\Models\Subcontractor;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class SubcontractorsDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<Subcontractor> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function ($subcontractor) {
                return view('subcontractors.action', compact('subcontractor'));
            })
            ->addColumn('checkbox', function ($subcontractor) {
                return '<input type="checkbox" class="dT-row-checkbox" value="' . $subcontractor->id . '">';
            })
            ->addColumn('number', function ($subcontractor) {
                return '';
            })
            ->editColumn('company_name', function ($subcontractor) {
                return view('subcontractors.company_name_column', ['subcontractor' => $subcontractor]);
            })
            ->editColumn('company_address', function ($subcontractor) {
                return strlen($subcontractor->company_address) > 30 ? substr($subcontractor->company_address, 0, 30) . '...' : $subcontractor->company_address;
            })
            ->editColumn('contact_person', function ($subcontractor) {
                return $subcontractor->contact_person;
            })
            ->editColumn('contact_number', function ($subcontractor) {
                return $subcontractor->contact_number;
            })
            ->editColumn('created_at', function ($user) {
                return $user->created_at?->format('m-d-Y');
            })
            ->editColumn('email', function ($subcontractor) {
                return $subcontractor->email;
            })
            ->filterColumn('company_name', function($query, $keyword) {
                $query->where('company_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('company_address', function($query, $keyword) {
                $query->where('company_address', 'like', "%{$keyword}%");
            })
            ->rawColumns(['action', 'checkbox', 'number', 'company_name'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Subcontractor>
     */
    public function query(Subcontractor $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->with(['user'])
            ->select('sub_contractors.*');

        if ($this->filter === 'archived') {
            $query = $model->onlyTrashed()
                ->with(['user'])
                ->select('sub_contractors.*');
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('subcontractors-table')
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
            ->orderBy([7, 'DESC'])
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

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::computed('checkbox')->title('<input type="checkbox" id="selectAll">')->exportable(false)->printable(false)->width(20)->addClass('text-center px-2')->orderable(false)->searchable(false),
            Column::computed('number')->title('#')->width(30)->addClass('px-2')->orderable(false)->searchable(false),
            Column::make('company_name')->title('Name')->addClass('ps-0')->orderable(false),
            Column::make('company_address')->title('Address'),
            Column::make('contact_person')->title('Contact Person'),
            Column::make('contact_number')->title('Contact Number'),
            Column::make('email')->title('Contact Email'),
            Column::make('created_at')->title('Created at'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Subcontractors_' . date('YmdHis');
    }
}
