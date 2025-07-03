<?php

namespace App\DataTables;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class UsersDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<User> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function ($user) {
                return view('user_management.users.action', compact('user'));
            })
            ->addColumn('checkbox', function ($user) {
                return '<input type="checkbox" class="dT-row-checkbox" value="' . $user->id . '">';
            })
            ->editColumn('name', function ($user) {
                return view('user_management.users.name_column', ['user' => $user]);
            })
            ->editColumn('email', function ($user) {
                return $user->email;
            })
            ->editColumn('roles', function ($user) {
                $roles = '';
                if (!empty($user->getRoleNames())) {
                    foreach ($user->getRoleNames() as $rolename) {
                        $roles .= '<label class="badge bg-primary mx-1">' . $rolename . '</label>';
                    }
                }
                return $roles;
            })
            ->editColumn('status', function ($user) {
                return ucfirst($user->status ?? 'active');
            })
            ->rawColumns(['action', 'checkbox', 'name', 'roles'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<User>
     */
    public function query(User $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->with(['roles'])
            ->select('users.*');

        if ($this->filter === 'archived') {
            $query = $model->onlyTrashed()
                ->with(['roles'])
                ->select('users.*');
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('users-table')
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
            Column::computed('name')->title('Name')->orderable(false),
            Column::make('email')->title('Email'),
            Column::computed('roles')->title('Role')->orderable(false),
            Column::make('status')->title('Status')
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Users_' . date('YmdHis');
    }
}
