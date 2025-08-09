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
            ->addColumn('number', function ($user) {
                return '';
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
            ->editColumn('created_at', function ($user) {
                return $user->created_at?->format('Y-m-d');
            })
            ->filterColumn('name', function($query, $keyword) {
                $query->where('name', 'like', "%{$keyword}%")
                      ->orWhere('first_name', 'like', "%{$keyword}%")
                      ->orWhere('last_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('roles', function($query, $keyword) {
                $query->whereHas('roles', function($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            })
            ->rawColumns(['action', 'checkbox', 'number', 'name', 'roles'])
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
        ->select('users.*')
        ->whereDoesntHave('roles', function ($q) {
            $q->whereIn('name', ['client', 'subcontractor', 'security_staff']);
        });

    if ($this->filter === 'archived') {
        $query = $model->onlyTrashed()
            ->with(['roles'])
            ->select('users.*')
            ->whereDoesntHave('roles', function ($q) {
                $q->whereIn('name', ['client', 'sub_contractor', 'security_staff']);
            });
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
            ->orderBy([6, 'DESC'])
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
            Column::make('name')->title('Name')->addClass('ps-0')->orderable(true),
            Column::make('email')->title('Email'),
            Column::make('roles')->title('Role')->orderable(false),
            Column::make('status')->title('Status'),
            Column::make('created_at')->title('Created at'),
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
