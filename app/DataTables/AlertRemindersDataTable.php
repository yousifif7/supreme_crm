<?php

namespace App\DataTables;

use App\Models\AlertReminder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class AlertRemindersDataTable extends DataTable
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
            ->addColumn('number', function ($row) {
                return '';
            })
            ->addColumn('vehicle_registration', function ($row) {
                return $row->vehicle ? $row->vehicle->registration_number : 'N/A';
            })
            ->addColumn('mot_due_date', function ($row) {
                return $row->mot_due_date ?? '-';
            })
            ->addColumn('insurance_renewal_date', function ($row) {
                return $row->insurance_renewal_date ?? '-';
            })
            ->addColumn('tax_renewal_date', function ($row) {
                return $row->tax_renewal_date ?? '-';
            })
            ->addColumn('service_due_date', function ($row) {
                return $row->service_due_date ?? '-';
            })
            ->addColumn('tachograph_calibration_date', function ($row) {
                return $row->tachograph_calibration_date ?? '-';
            })
            ->addColumn('action', function ($row) {
                return view('vehicle_management.alert_reminders.action', compact('row'))->render();
            })
            ->filterColumn('vehicle_registration', function($query, $keyword) {
                $query->whereHas('vehicle', function($q) use ($keyword) {
                    $q->where('registration_number', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('mot_due_date', function($query, $keyword) {
                $query->where('mot_due_date', 'like', "%{$keyword}%");
            })
            ->filterColumn('insurance_renewal_date', function($query, $keyword) {
                $query->where('insurance_renewal_date', 'like', "%{$keyword}%");
            })
            ->filterColumn('tax_renewal_date', function($query, $keyword) {
                $query->where('tax_renewal_date', 'like', "%{$keyword}%");
            })
            ->filterColumn('service_due_date', function($query, $keyword) {
                $query->where('service_due_date', 'like', "%{$keyword}%");
            })
            ->filterColumn('tachograph_calibration_date', function($query, $keyword) {
                $query->where('tachograph_calibration_date', 'like', "%{$keyword}%");
            })
            ->rawColumns(['checkbox', 'number', 'action']);
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(AlertReminder $model): QueryBuilder
    {
        return $model->newQuery()->with('vehicle');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('alert-reminders-table')
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
            ->orderBy([2, 'DESC'])
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
            Column::computed('checkbox')->title('<input type="checkbox" id="select-all-checkbox">')->exportable(false)->printable(false)->width(20)->addClass('text-center px-2')->orderable(false)->searchable(false),
            Column::computed('number')->title('#')->width(30)->addClass('px-2')->orderable(false)->searchable(false),
            Column::make('vehicle_registration')->title('Vehicle Registration')->addClass('ps-0'),
            Column::make('mot_due_date')->title('MOT Due'),
            Column::make('insurance_renewal_date')->title('Insurance Renewal'),
            Column::make('tax_renewal_date')->title('Tax Renewal'),
            Column::make('service_due_date')->title('Service Due'),
            Column::make('tachograph_calibration_date')->title('Tachograph Calibration'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'AlertReminders_' . date('YmdHis');
    }
}
