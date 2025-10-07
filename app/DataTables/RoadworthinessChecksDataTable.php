<?php

namespace App\DataTables;

use App\Models\RoadworthinessCheck;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class RoadworthinessChecksDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<RoadworthinessCheck> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function ($check) {
                return view('vehicle_management.checks.action', compact('check'));
            })
            ->addColumn('checkbox', function ($check) {
                return '<input type="checkbox" class="dT-row-checkbox" value="' . $check->id . '">';
            })
            ->addColumn('number', function ($check) {
                return '';
            })
            ->editColumn('date_completed', function ($check) {
                return Carbon::parse($check->date_completed)->format('d M Y');
            })
            ->editColumn('checked_by', function ($check) {
                return $check->checked_by;
            })
            ->editColumn('defects_found', function ($check) {
                return $check->defects_found ? $check->defects_found : 'None';
            })
            ->editColumn('corrective_action_taken', function ($check) {
                return $check->corrective_action_taken ? $check->corrective_action_taken : 'None';
            })
            ->filterColumn('date_completed', function($query, $keyword) {
                $query->where('date_completed', 'like', "%{$keyword}%");
            })
            ->filterColumn('defects_found', function($query, $keyword) {
                $query->where('defects_found', 'like', "%{$keyword}%");
            })
            ->filterColumn('corrective_action_taken', function($query, $keyword) {
                $query->where('corrective_action_taken', 'like', "%{$keyword}%");
            })
            ->rawColumns(['action', 'checkbox', 'number'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<RoadworthinessCheck>
     */
    public function query(RoadworthinessCheck $model): QueryBuilder
    {
        return $model->newQuery()
            ->with('vehicle')
            ->select('roadworthiness_checks.*');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('roadworthiness-checks-table')
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
            ->orderBy(2, 'asc')
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
            Column::make('date_completed')->title('Date Completed')->addClass('ps-0'),
            Column::make('checked_by')->title('Checked By'),
            Column::make('defects_found')->title('Defects Found'),
            Column::make('corrective_action_taken')->title('Corrective Action Taken'),
            Column::computed('action')->exportable(false)->printable(false)->width(60)->addClass('text-center'),
        ];
    }
}
