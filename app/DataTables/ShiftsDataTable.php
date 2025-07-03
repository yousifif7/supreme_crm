<?php

namespace App\DataTables;

use App\Models\ShiftDate;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ShiftsDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<ShiftDate> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function ($shiftDate) {
                return view('security_boards.shifts.action', compact('shiftDate'));
            })
            ->addColumn('checkbox', function ($shiftDate) {
                return '<input type="checkbox" class="dT-row-checkbox" value="' . $shiftDate->id . '">';
            })
            ->editColumn('client_name', function ($shiftDate) {
                return $shiftDate->shift->client->client_name ?? 'N/A';
            })
            ->editColumn('site_name', function ($shiftDate) {
                return $shiftDate->shift->site->site_name ?? 'N/A';
            })
            ->editColumn('staff_name', function ($shiftDate) {
                if ($shiftDate->staff) {
                    return $shiftDate->staff->first_name . ' ' . $shiftDate->staff->last_name;
                }
                return 'Unassigned';
            })
            ->editColumn('shift_date', function ($shiftDate) {
                return \Carbon\Carbon::parse($shiftDate->shift_date)->format('d M Y');
            })
            ->editColumn('shift_time', function ($shiftDate) {
                $start = \Carbon\Carbon::createFromFormat('H:i:s', $shiftDate->start_time)->format('h:i A');
                $end = \Carbon\Carbon::createFromFormat('H:i:s', $shiftDate->end_time)->format('h:i A');
                return $start . ' - ' . $end;
            })
            ->editColumn('total_hours', function ($shiftDate) {
                return number_format($shiftDate->total_hours, 2) . ' hrs';
            })
            ->editColumn('status', function ($shiftDate) {
                $statusMap = [
                    0 => '<span class="badge bg-secondary">Pending</span>',
                    1 => '<span class="badge bg-info">Assigned</span>',
                ];
                return $statusMap[$shiftDate->is_assign] ?? '<span class="badge bg-secondary">Pending</span>';
            })
            ->rawColumns(['action', 'checkbox', 'status'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<ShiftDate>
     */
    public function query(ShiftDate $model): QueryBuilder
    {
        return $model->newQuery()
            ->with(['shift.client', 'shift.site', 'shift.staff', 'staff'])
            ->select('shift_dates.*');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('shifts-table')
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
            Column::computed('client_name')->orderable(false),
            Column::computed('site_name')->orderable(false),
            Column::computed('staff_name')->orderable(false),
            Column::computed('shift_date'),
            Column::computed('shift_time')->orderable(false),
            Column::computed('break_time')->title('Break Time')->orderable(false),
            Column::computed('total_hours')->title('Total Hours')->orderable(false),
            Column::computed('status')->title('Status')->orderable(false),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Shifts_' . date('YmdHis');
    }
}
