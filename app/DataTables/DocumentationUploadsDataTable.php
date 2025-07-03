<?php

namespace App\DataTables;

use App\Models\DocumentationUpload;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class DocumentationUploadsDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="dT-row-checkbox" value="' . $row->id . '">';
            })
            ->addColumn('vehicle_registration', function ($row) {
                return $row->vehicle ? $row->vehicle->registration_number : 'N/A';
            })
            ->addColumn('mot_certificate', function ($row) {
                return $row->mot_certificate_path ? '<a href="' . asset($row->mot_certificate_path) . '" target="_blank">View</a>' : 'N/A';
            })
            ->addColumn('insurance_certificate', function ($row) {
                return $row->insurance_certificate_path ? '<a href="' . asset($row->insurance_certificate_path) . '" target="_blank">View</a>' : 'N/A';
            })
            ->addColumn('v5c_logbook', function ($row) {
                return $row->v5c_logbook_path ? '<a href="' . asset($row->v5c_logbook_path) . '" target="_blank">View</a>' : 'N/A';
            })
            ->addColumn('tax_confirmation', function ($row) {
                return $row->tax_confirmation_path ? '<a href="' . asset($row->tax_confirmation_path) . '" target="_blank">View</a>' : 'N/A';
            })
            ->addColumn('tachograph_certificate', function ($row) {
                return $row->tachograph_certificate_path ? '<a href="' . asset($row->tachograph_certificate_path) . '" target="_blank">View</a>' : 'N/A';
            })
            ->addColumn('service_report', function ($row) {
                return $row->service_report_path ? '<a href="' . asset($row->service_report_path) . '" target="_blank">View</a>' : 'N/A';
            })
            ->addColumn('inspection_report', function ($row) {
                return $row->inspection_report_path ? '<a href="' . asset($row->inspection_report_path) . '" target="_blank">View</a>' : 'N/A';
            })
            ->addColumn('action', function ($row) {
                return view('vehicle_management.documentation_uploads.action', compact('row'))->render();
            })
            ->rawColumns(['checkbox', 'mot_certificate', 'insurance_certificate', 'v5c_logbook', 'tax_confirmation', 'tachograph_certificate', 'service_report', 'inspection_report', 'action']);
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(DocumentationUpload $model): QueryBuilder
    {
        return $model->newQuery()->with('vehicle');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('documentation-uploads-table')
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
            Column::make('checkbox')->title('<input type="checkbox" id="select-all-checkbox">')->orderable(false)->searchable(false),
            Column::make('id'),
            Column::make('vehicle_registration')->title('Vehicle Registration'),
            Column::make('mot_certificate')->title('MOT Certificate'),
            Column::make('insurance_certificate')->title('Insurance Certificate'),
            Column::make('v5c_logbook')->title('V5C Logbook'),
            Column::make('tax_confirmation')->title('Tax Confirmation'),
            Column::make('tachograph_certificate')->title('Tachograph Certificate'),
            Column::make('service_report')->title('Service Report'),
            Column::make('inspection_report')->title('Inspection Report'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'DocumentationUploads_' . date('YmdHis');
    }
}
