<?php

namespace App\DataTables;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class InvoicesDataTable extends DataTable
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
            ->addColumn('invoice_no', function ($row) {
                return '<div class="d-flex align-items-center file-name-icon">
                            <div class="ms-2">
                                <h6 class="fw-medium"><a href="' . ($row->client_id ? route('invoices.show', $row->id) : route('payrolls.show', $row->id)) . '">' . $row->invoice_no . '</a></h6>
                            </div>
                        </div>';
            })
            ->addColumn('client_name', function ($row) {
                return $row->client ? $row->client->client_name : '';
            })
            ->addColumn('site_name', function ($row) {
                return $row->site ? $row->site->site_name : '';
            })
            ->addColumn('action', function ($row) {
                return view('invoices.action', compact('row'))->render();
            })
            ->filterColumn('invoice_no', function($query, $keyword) {
                $query->where('invoice_no', 'like', "%{$keyword}%");
            })
            ->filterColumn('client_name', function($query, $keyword) {
                $query->whereHas('client', function($q) use ($keyword) {
                    $q->where('client_name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('site_name', function($query, $keyword) {
                $query->whereHas('site', function($q) use ($keyword) {
                    $q->where('site_name', 'like', "%{$keyword}%");
                });
            })
            ->rawColumns(['checkbox', 'number', 'invoice_no', 'action']);
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Invoice $model): QueryBuilder
    {
        return $model->newQuery()
            ->with(['client', 'site'])
            ->whereNotNull('client_id') // Only client invoices
            ->orderBy('id', 'desc');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('invoices-table')
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
            Column::make('invoice_number')->title('Invoice No')->addClass('ps-0'),
            Column::make('client_name')->title('Client Name'),
            Column::make('site_name')->title('Site Name'),
            Column::make('issue_date')->title('Issue Date'),
            Column::make('due_date')->title('Due Date'),
            Column::make('total_shift_hours')->title('Total Shift Hours'),
            Column::make('net_amount')->title('Net Amount'),
            Column::make('total_amount')->title('Total Amount'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Invoices_' . date('YmdHis');
    }
}
