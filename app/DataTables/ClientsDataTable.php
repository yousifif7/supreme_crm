<?php

namespace App\DataTables;

use App\Models\Client;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ClientsDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<Client> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function ($client) {
                return view('clients.action', compact('client'));
            })
            ->addColumn('checkbox', function ($client) {
                return '<input type="checkbox" class="dT-row-checkbox" value="' . $client->id . '">';
            })
            ->addColumn('number', function ($client) {
                return '';
            })
            ->editColumn('client_name', function ($client) {
                return view('clients.client_name_column', ['client' => $client]);
            })
            ->addColumn('company_name', function ($client) {
                return $client->company ? $client->company->name : 'N/A';
            })
            ->editColumn('address', function ($client) {
                return strlen($client->address) > 3 ? substr($client->address, 0, 20) . '...' : $client->address;
            })
            ->editColumn('created_at', function ($user) {
                return $user->created_at?->format('m-d-Y');
            })
            ->addColumn('manager_name', function ($client) {
                return $client->manager ? $client->manager->fore_name : 'N/A';
            })
            ->filterColumn('client_name', function ($query, $keyword) {
                $query->where('client_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('company_name', function ($query, $keyword) {
                $query->whereHas('company', function ($q) use ($keyword) {
                    $q->where('company_name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('manager_name', function ($query, $keyword) {
                $query->whereHas('manager', function ($q) use ($keyword) {
                    $q->where('fore_name', 'like', "%{$keyword}%");
                });
            })
            ->rawColumns(['action', 'checkbox', 'number'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Client>
     */
    public function query(Client $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->with(['company', 'manager'])
            ->select('clients.*')
            ->orderBy('client_name', 'asc'); // alphabetical A → Z

        if ($this->filter === 'archived') {
            $query = $model->onlyTrashed()
                ->with(['company', 'manager'])
                ->select('clients.*')
                ->orderBy('client_name', 'asc'); // also alphabetical
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('clients-table')
            ->setTableAttribute('class', 'table table-row-bordered table-row-dashed gy-4 align-middle fw-bold') // Setting the classes
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
            // ->responsive(true)
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
            Column::make('client_name')->addClass('ps-0')->title('Name'),
            Column::make('address')->title('Address'),
            Column::make('company_name')->title('Company'),
            Column::make('manager_name')->title('Manager'),
            Column::make('contact_person')->title('Contact Person'),
            Column::make('contact_number')->title('Contact Number'),
            Column::make('email')->title('Email'),
            Column::make('created_at')->title('Created at'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Clients_' . date('YmdHis');
    }
}
