<?php

namespace App\DataTables;

use App\Models\VehicleCompliance;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class VehicleCompliancesDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<VehicleCompliance> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function ($compliance) {
                return view('vehicle_management.compliances.action', compact('compliance'));
            })
            ->addColumn('checkbox', function ($compliance) {
                return '<input type="checkbox" class="dT-row-checkbox" value="' . $compliance->id . '">';
            })
            ->editColumn('vehicle_rn', function ($compliance) {
                return $compliance->vehicle ? $compliance->vehicle->registration_number : 'N/A';
            })
            ->editColumn('mot_certificate_number', function ($compliance) {
                return $compliance->mot_certificate_number;
            })
            ->editColumn('mot_expiry_date', function ($compliance) {
                return Carbon::parse($compliance->mot_expiry_date)->format('d M Y');
            })
            ->editColumn('insurance_provider', function ($compliance) {
                return $compliance->insurance_provider;
            })
            ->editColumn('insurance_expiry_date', function ($compliance) {
                return Carbon::parse($compliance->insurance_expiry_date)->format('d M Y');
            })
            ->editColumn('vehicle_tax_status', function ($compliance) {
                return $compliance->vehicle_tax_status;
            })
            ->editColumn('tax_expiry_date', function ($compliance) {
                return Carbon::parse($compliance->tax_expiry_date)->format('d M Y');
            })
            ->editColumn('lez_ulez_compliant', function ($compliance) {
                return $compliance->lez_ulez_compliant ? 'Yes' : 'No';
            })
            ->rawColumns(['action', 'checkbox'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<VehicleCompliance>
     */
    public function query(VehicleCompliance $model): QueryBuilder
    {
        return $model->newQuery()
            ->with('vehicle')
            ->select('vehicle_compliances.*');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('vehicle-compliances-table')
            ->setTableAttribute('class', 'table table-row-bordered table-row-dashed gy-4 align-middle fw-bold')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->addAction()
            ->dom(
                't
                <"d-flex justify-content-between mt-2"
                  <"col-sm-12 col-md-5 align-self-center ps-3"i>
                  <"d-flex justify-content-between" p>
                >'
            )
            ->orderBy(1, 'asc')
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
            Column::make('id')->title('#')->width(30),
            Column::make('vehicle_rn')->title('Vehicle RN')->data('vehicle_rn'),
            Column::make('mot_certificate_number')->title('MOT Certificate'),
            Column::make('mot_expiry_date')->title('MOT Expiry'),
            Column::make('insurance_provider')->title('Insurance Provider'),
            Column::make('insurance_expiry_date')->title('Insurance Expiry'),
            Column::make('vehicle_tax_status')->title('Tax Status'),
            Column::make('tax_expiry_date')->title('Tax Expiry'),
            Column::make('lez_ulez_compliant')->title('LEZ/ULEZ'),
        ];
    }
}
