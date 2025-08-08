<?php

namespace App\DataTables;

use App\Models\IncidentReport;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class IncidentReportsDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<IncidentReport> $query
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function ($report) {
                return view('incident_reports.action', compact('report'));
            })
            ->addColumn('checkbox', function ($report) {
                return '<input type="checkbox" class="dT-row-checkbox" value="' . $report->id . '">';
            })
            ->addColumn('number', function () {
                return '';
            })
            ->editColumn('category', function ($report) {
                return ucfirst(str_replace('_', ' ', $report->category));
            })
            ->editColumn('severity', function ($report) {
                return ucfirst($report->severity);
            })
            ->editColumn('location', function ($report) {
                $loc = json_decode($report->location, true);
                return $loc['address'] ?? '';
            })
            ->editColumn('police_notified', function ($report) {
                return $report->police_notified ? 'Yes' : 'No';
            })
            ->editColumn('status', function ($report) {
                return ucfirst(str_replace('_', ' ', $report->status));
            })
            ->editColumn('created_at', function ($report) {
                return $report->created_at?->format('Y-m-d');
            })
            ->filterColumn('category', function($query, $keyword) {
                $query->where('category', 'like', "%{$keyword}%");
            })
            ->filterColumn('severity', function($query, $keyword) {
                $query->where('severity', 'like', "%{$keyword}%");
            })
            ->rawColumns(['action', 'checkbox', 'number', 'location'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<IncidentReport>
     */
    public function query(IncidentReport $model): QueryBuilder
    {
        return $model->newQuery()->select('incident_reports.*');
    }

    /**
     * HTML Builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('incident_reports-table')
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
            ->orderBy([8, 'DESC']) // created_at column index
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
     * Get the DataTable columns.
     */
    public function getColumns(): array
    {
        return [
            Column::computed('checkbox')
                ->title('<input type="checkbox" id="selectAll">')
                ->exportable(false)
                ->printable(false)
                ->width(20)
                ->addClass('text-center px-2')
                ->orderable(false)
                ->searchable(false),

            Column::computed('number')
                ->title('#')
                ->width(30)
                ->addClass('px-2')
                ->orderable(false)
                ->searchable(false),

            Column::make('title')->title('Title'),
            Column::make('category')->title('Category'),
            Column::make('severity')->title('Severity'),
            Column::make('location')->title('Location')->orderable(false),
            Column::make('police_notified')->title('Police Notified'),
            Column::make('status')->title('Status'),
            Column::make('created_at')->title('Reported At'),
        ];
    }

    /**
     * Export filename.
     */
    protected function filename(): string
    {
        return 'IncidentReports_' . date('YmdHis');
    }
}
