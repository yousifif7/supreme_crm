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
            ->addColumn('number', function ($shiftDate) {
                return '';
            })
            ->editColumn('client_name', function ($shiftDate) {
                return $shiftDate->shift->client->client_name ?? 'N/A';
            })
            ->editColumn('site_name', function ($shiftDate) {
                return $shiftDate->shift->site->site_name ?? 'N/A';
            })
            ->editColumn('staff_name', function ($shiftDate) {
                if ($shiftDate->staff) {
                    return $shiftDate->staff->fore_name . ' ' . $shiftDate->staff->sur_name;
                }
                return 'Unassigned';
            })
            // ->editColumn('created_at', function ($user) {
            //     return $user->created_at?->format('Y-m-d');
            // })
            ->editColumn('shift_date', function ($shiftDate) {
                return \Carbon\Carbon::parse($shiftDate->shift_date)->format('d M Y');
            })
            ->addColumn('shift_time', function ($shiftDate) {
                $start = \Carbon\Carbon::createFromFormat('H:i:s', $shiftDate->start_time)->format('h:i A');
                $end = \Carbon\Carbon::createFromFormat('H:i:s', $shiftDate->end_time)->format('h:i A');
                return $start . ' - ' . $end;
            })
            ->editColumn('total_hours', function ($shiftDate) {
                return number_format($shiftDate->total_hours, 2) . ' hrs';
            })
            ->addColumn('status', function ($shiftDate) {
                $statusMap = [
                    0 => '<span class="badge bg-secondary">Pending</span>',
                    1 => '<span class="badge bg-info">Assigned</span>',
                ];
                return $statusMap[$shiftDate->is_assign] ?? '<span class="badge bg-secondary">Pending</span>';
            })
            ->filterColumn('client_name', function($query, $keyword) {
                $query->whereHas('shift.client', function($q) use ($keyword) {
                    $q->where('client_name', 'like', "%$keyword%");
                });
            })
            ->filterColumn('site_name', function($query, $keyword) {
                $query->whereHas('shift.site', function($q) use ($keyword) {
                    $q->where('site_name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('staff_name', function($query, $keyword) {
                $query->whereHas('staff', function($q) use ($keyword) {
                    $q->where('fore_name', 'like', "%{$keyword}%")
                      ->orWhere('sur_name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('shift_date', function($query, $keyword) {
                $query->where('shift_date', 'like', "%{$keyword}%");
            })
            ->filterColumn('total_hours', function($query, $keyword) {
                $query->where('total_hours', 'like', "%{$keyword}%");
            })
            ->rawColumns(['action', 'checkbox', 'number', 'status'])
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
            ->orderBy([5, 'DESC'])
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
            Column::computed('checkbox')->title('<input type="checkbox" id="selectAll">')->exportable(false)->printable(false)->width(20)->addClass('text-center px-2')->orderable(false)->searchable(false),
            Column::computed('number')->title('#')->width(30)->addClass('px-2')->orderable(false)->searchable(false),
            Column::make('client_name')->addClass('ps-0')->orderable(false),
            Column::make('site_name')->orderable(false),
            Column::make('staff_name')->orderable(false),
            Column::make('shift_date')->orderable(true)->searchable(false),
            Column::make('shift_time')->orderable(false)->searchable(false),
            Column::make('break_time')->title('Break Time')->orderable(false),
            Column::make('total_hours')->title('Total Hours')->orderable(false),
            Column::make('status')->title('Status')->orderable(false)->searchable(false),
            // Column::make('created_at')->title('Created at'),
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
