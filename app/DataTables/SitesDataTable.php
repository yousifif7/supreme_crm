<?php

namespace App\DataTables;

use App\Models\Site;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class SitesDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<Site> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function ($site) {
                return view('sites.action', compact('site'));
            })
            ->addColumn('checkbox', function ($site) {
                return '<input type="checkbox" class="dT-row-checkbox" value="' . $site->id . '">';
            })
            ->editColumn('client_name', function ($site) {
                return view('sites.client_name_column', ['site' => $site]);
            })
            ->editColumn('site_name', function ($site) {
                return $site->site_name;
            })
            ->editColumn('address', function ($site) {
                return strlen($site->address) > 30 ? substr($site->address, 0, 30) . '...' : $site->address;
            })
            ->editColumn('site_code', function ($site) {
                return $site->site_code;
            })
            ->editColumn('post_code', function ($site) {
                return $site->post_code;
            })
            ->rawColumns(['action', 'checkbox', 'client_name'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Site>
     */
    public function query(Site $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->with(['client'])
            ->select('sites.*');

        if ($this->filter === 'archived') {
            $query = $model->onlyTrashed()
                ->with(['client'])
                ->select('sites.*');
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('sites-table')
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
            Column::computed('client_name')->title('Client Name')->orderable(false),
            Column::make('site_name')->title('Site Name'),
            Column::make('address')->title('Address'),
            Column::make('site_code')->title('Site Code'),
            Column::make('post_code')->title('Post Code')
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Sites_' . date('YmdHis');
    }
}
