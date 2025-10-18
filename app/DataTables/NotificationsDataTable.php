<?php

namespace App\DataTables;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class NotificationsDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<Notification> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function ($notification) {
                return view('notification.action', compact('notification'));
            })
            ->addColumn('checkbox', function ($notification) {
                return '<input type="checkbox" class="dT-row-checkbox" value="' . $notification->id . '">';
            })
            ->addColumn('number', function ($notification) {
                return '';
            })
            ->editColumn('title', function ($notification) {
                return '<strong>' . $notification->title . '</strong>';
            })
            ->editColumn('message', function ($notification) {
                return strlen($notification->message) > 50
                    ? substr($notification->message, 0, 50) . '...'
                    : $notification->message;
            })
            ->addColumn('user_name', function ($notification) {
                return $notification->user ? $notification->user->name : 'N/A';
            })
            ->addColumn('employee_name', function ($notification) {
                return $notification->employee ? $notification->employee->fore_name : 'N/A';
            })
            ->editColumn('read', function ($notification) {
                return $notification->read ? '<span class="badge bg-success">Read</span>' : '<span class="badge bg-warning">Unread</span>';
            })
            ->editColumn('created_at', function ($notification) {
                return $notification->created_at?->format('Y-m-d H:i');
            })
            ->filterColumn('title', function ($query, $keyword) {
                $query->where('title', 'like', "%{$keyword}%");
            })
            ->filterColumn('user_name', function ($query, $keyword) {
                $query->whereHas('user', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('employee_name', function ($query, $keyword) {
                $query->whereHas('employee', function ($q) use ($keyword) {
                    $q->where('fore_name', 'like', "%{$keyword}%");
                });
            })
            ->rawColumns(['action', 'checkbox', 'number', 'read', 'title'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Notification>
     */
    public function query(Notification $model): QueryBuilder
    {
        return $model->newQuery()
            ->with(['user', 'employee'])
            ->select('notifications.*')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('notifications-table')
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
            ->addAction(['width' => '80px'])
            ->orderBy([9, 'DESC'])
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
            Column::make('title')->title('Title'),
            Column::make('message')->title('Message'),
            Column::make('user_name')->title('User'),
            Column::make('employee_name')->title('Employee'),
            Column::make('read')->title('Status')->orderable(false)->searchable(false),
            Column::make('action_url')->title('Action URL')->orderable(false)->searchable(false),
            Column::make('created_at')->title('Created At'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Notifications_' . date('YmdHis');
    }
}
