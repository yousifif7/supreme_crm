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
            ->addColumn('actions', function ($row) {
                return '
                <div class="action-icon d-inline-flex">
                    <a href="javascript:void(0)" class="me-2" onclick="showIncident(' . $row->id . ')">
                        <i class="ti ti-eye"></i>
                    </a>
                    <a href="javascript:void(0)" class="me-2" onclick="editIncident(\'' . $row->id . '\')">
    <i class="ti ti-pencil text-success"></i>
</a>
                    <a href="javascript:void(0)" onclick="deleteIncident(' . $row->id . ')">
                        <i class="ti ti-trash text-danger"></i>
                    </a>
                </div>
            ';
            })
            ->addColumn('checkbox', function ($report) {
                return '<input type="checkbox" class="dT-row-checkbox" value="' . $report->id . '">';
            })
            ->addColumn('number', function () {
                return '';
            })
            ->editColumn('category', fn($report) => ucfirst(str_replace('_', ' ', $report->category)))
            ->editColumn('severity', fn($report) => ucfirst($report->severity))
            ->editColumn('location', function ($report) {
                return $report->formatted_address ?? 'N/A';
            })
            ->editColumn('police_notified', fn($report) => $report->police_notified ? 'Yes' : 'No')
            ->editColumn('status', function ($report) {
                $status = ucfirst(str_replace('_', ' ', $report->status));
                if ($report->status === 'draft') {
                    return '
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-success btn-approve" data-id="' . $report->id . '">
                    Approve
                </button>
                <button class="btn btn-sm btn-danger btn-reject" data-id="' . $report->id . '">
                    Reject
                </button>
            </div>
        ';
                }
                return $status;
            })
            ->addColumn('files', function ($report) {
                $html = '';
                foreach ($report->media as $media) {
                    $html .= '<a href="' . asset($media->file_url) . '" target="_blank" class="d-block mb-1 btn btn-sm text-light btn-secondary">Show</a>';
                }
                return $html;
            })
            ->editColumn('created_at', fn($report) => $report->created_at?->format('m-d-Y'))
            ->filterColumn('category', fn($query, $keyword) => $query->where('category', 'like', "%{$keyword}%"))
            ->filterColumn('severity', fn($query, $keyword) => $query->where('severity', 'like', "%{$keyword}%"))
            ->rawColumns(['actions', 'checkbox', 'number', 'files', 'status'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(IncidentReport $model): QueryBuilder
    {
        return $model->with('media')->newQuery()->select('incident_reports.*');
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
            ->dom(
                't
                <"d-flex justify-content-between mt-2"
                  <"col-sm-12 col-md-5 align-self-center ps-3"i>
                  <"d-flex justify-content-between" p>
                >'
            )
            ->orderBy([9, 'DESC']) // created_at column index
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
            Column::make('files')->title('Files')->orderable(false)->searchable(false),
            Column::make('created_at')->title('Reported At'),
            Column::computed('actions')
                ->title('Actions')
                ->exportable(false)
                ->printable(false)
                ->width(100)
                ->addClass('text-center px-2'),
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
