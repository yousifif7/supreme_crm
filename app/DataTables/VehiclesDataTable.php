<?php

namespace App\DataTables;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class VehiclesDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<Vehicle> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function ($vehicle) {
                return view('vehicle_management.vehicles.action', compact('vehicle'));
            })
            ->addColumn('checkbox', function ($vehicle) {
                return '<input type="checkbox" class="dT-row-checkbox" value="' . $vehicle->id . '">';
            })
            ->addColumn('number', function ($vehicle) {
                return '';
            })
            ->editColumn('assigned_to', function ($vehicle) {
                return $vehicle->assigned_to ?? 'Unassigned';
            })
            ->editColumn('vehicle_category', function ($vehicle) {
                return ucfirst($vehicle->vehicle_category);
            })
            ->editColumn('first_registration_date', function ($vehicle) {
                return \Carbon\Carbon::parse($vehicle->first_registration_date)->format('d M Y');
            })
            ->editColumn('odometer_reading', function ($vehicle) {
                return number_format($vehicle->odometer_reading) . ' mile ';
            })
            ->filterColumn('assigned_to', function($query, $keyword) {
                $query->where('assigned_to', 'like', "%{$keyword}%");
            })
            ->filterColumn('vehicle_category', function($query, $keyword) {
                $query->where('vehicle_category', 'like', "%{$keyword}%");
            })
            ->filterColumn('first_registration_date', function($query, $keyword) {
                $query->where('first_registration_date', 'like', "%{$keyword}%");
            })
            ->filterColumn('odometer_reading', function($query, $keyword) {
                $query->where('odometer_reading', 'like', "%{$keyword}%");
            })
            ->rawColumns(['action', 'checkbox', 'number', 'registration_number'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Vehicle>
     */
    public function query(Vehicle $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->select('vehicles.*');

        if ($this->filter === 'archived') {
            $query = $model->onlyTrashed()
                ->select('vehicles.*');
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
public function html(): HtmlBuilder
{
    return $this->builder()
        ->setTableId('vehicles-table')
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
        ->orderBy([2, 'DESC'])
        ->parameters([
            "scrollX" => true,
            "pageLength" => 25,
            "headerCallback" => "function(thead, data, start, end, display) {
                $(thead).addClass('thead-light');
            }",
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
            Column::make('registration_number')->title('Registration No.')->addClass('ps-0')->orderable(false),
            Column::make('make')->title('Make'),
            Column::make('model')->title('Model'),
            Column::make('assigned_to')->title('Assigned To'),
            Column::make('vehicle_category')->title('Category'),
            Column::make('first_registration_date')->title('Registration Date'),
            Column::make('odometer_reading')->title('Odometer')->orderable(false),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Vehicles_' . date('YmdHis');
    }
}
