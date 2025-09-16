<?php

namespace App\DataTables;

use App\Models\VehicleMaintenance;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class VehicleMaintenancesDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<VehicleMaintenance> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function ($maintenance) {
                return view('vehicle_management.maintenances.action', compact('maintenance'));
            })
            ->addColumn('checkbox', function ($maintenance) {
                return '<input type="checkbox" class="dT-row-checkbox" value="' . $maintenance->id . '">';
            })
            ->addColumn('number', function ($maintenance) {
                return '';
            })
            ->editColumn('last_service_date', function ($maintenance) {
                return Carbon::parse($maintenance->last_service_date)->format('d M Y');
            })
            ->editColumn('next_service_due_date', function ($maintenance) {
                return Carbon::parse($maintenance->next_service_due_date)->format('d M Y');
            })
            ->editColumn('work_type', function ($maintenance) {
                return $maintenance->work_type;
            })
            ->editColumn('maintenance_date', function ($maintenance) {
                return Carbon::parse($maintenance->maintenance_date)->format('d M Y');
            })
            ->editColumn('garage_provider', function ($maintenance) {
                return $maintenance->garage_provider;
            })
            ->editColumn('reported_by', function ($maintenance) {
                return $maintenance->reported_by;
            })
            ->editColumn('date_reported', function ($maintenance) {
                return Carbon::parse($maintenance->date_reported)->format('d M Y');
            })
            ->editColumn('resolution_status', function ($maintenance) {
                $status = $maintenance->resolution_status;
                $badge = '';

                switch ($status) {
                    case 'resolved':
                        $badge = '<span class="badge bg-success">Resolved</span>';
                        break;
                    case 'pending':
                        $badge = '<span class="badge bg-warning">Pending</span>';
                        break;
                    case 'in_progress':
                        $badge = '<span class="badge bg-info">In Progress</span>';
                        break;
                    default:
                        $badge = '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
                }

                return $badge;
            })
            ->filterColumn('last_service_date', function($query, $keyword) {
                $query->where('last_service_date', 'like', "%{$keyword}%");
            })
            ->filterColumn('next_service_due_date', function($query, $keyword) {
                $query->where('next_service_due_date', 'like', "%{$keyword}%");
            })
            ->filterColumn('maintenance_date', function($query, $keyword) {
                $query->where('maintenance_date', 'like', "%{$keyword}%");
            })
            ->filterColumn('date_reported', function($query, $keyword) {
                $query->where('date_reported', 'like', "%{$keyword}%");
            })
            ->filterColumn('resolution_status', function($query, $keyword) {
                $query->where('resolution_status', 'like', "%{$keyword}%");
            })
            ->rawColumns(['action', 'checkbox', 'number', 'resolution_status'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<VehicleMaintenance>
     */
    public function query(VehicleMaintenance $model): QueryBuilder
    {
        return $model->newQuery()
            ->with('vehicle')
            ->select('vehicle_maintenances.*');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('vehicle-maintenances-table')
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
            Column::make('last_service_date')->title('Last Service')->addClass('ps-0'),
            Column::make('next_service_due_date')->title('Next Service Due'),
            Column::make('work_type')->title('Work Type'),
            Column::make('maintenance_date')->title('Maintenance Date'),
            Column::make('garage_provider')->title('Garage/Provider'),
            Column::make('reported_by')->title('Reported By'),
            Column::make('date_reported')->title('Date Reported'),
            Column::make('resolution_status')->title('Resolution Status'),
            Column::computed('action')->exportable(false)->printable(false)->width(60)->addClass('text-center'),
        ];
    }
}
