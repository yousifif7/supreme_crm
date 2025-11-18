<?php

namespace App\DataTables;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class InvoicesDataTable extends DataTable
{
    /** Optional client filter id when rendering for client dashboard */
    protected $client_id = null;

    /**
     * Set the client id to scope the query to.
     */
    public function withClient($clientId)
    {
        $this->client_id = $clientId;
        return $this;
    }
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
            <h6 class="fw-medium">
                <a href="' . route('invoices.show', $row->id) . '" class="invoice-link">' . $row->invoice_number . '</a>
            </h6>
        </div>
    </div>';
            })
            ->addColumn('invoice_title', function ($row) {
                return $row->client ? $row->client->first_name : '';
            })
            ->addColumn('invoice_date', function ($row) {
                if ($row->created_at) {
                    try { return Carbon::parse($row->created_at)->format('m/d/Y'); }
                    catch (\Exception $e) { return $row->created_at; }
                }
                return '';
            })
            ->addColumn('issue_date', function ($row) {
                if (empty($row->issue_date)) return '';
                try { return Carbon::parse($row->issue_date)->format('m/d/Y'); }
                catch (\Exception $e) { return $row->issue_date; }
            })
            ->addColumn('client_name', function ($row) {
                return $row->client ? $row->client->first_name : '';
            })
            ->addColumn('site_name', function ($row) {
                return $row->site ? $row->site->site_name : '';
            })
            ->editColumn('net_amount', function ($row) {
                $amount = $row->net_amount ?? 0;
                return '£' . number_format((float)$amount, 2);
            })
            ->editColumn('total_amount', function ($row) {
                $amount = $row->total_amount ?? 0;
                return '£' . number_format((float)$amount, 2);
            })
            ->addColumn('action', function ($row) {
                return view('invoices.action', compact('row'))->render();
            })
            ->filterColumn('invoice_no', function ($query, $keyword) {
                $query->where('invoice_no', 'like', "%{$keyword}%");
            })
            ->filterColumn('client_name', function ($query, $keyword) {
                $query->whereHas('client', function ($q) use ($keyword) {
                    $q->where('first_name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('site_name', function ($query, $keyword) {
                $query->whereHas('site', function ($q) use ($keyword) {
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
        $query = $model->newQuery()->with(['client', 'site']);

        // If a client_id was provided (client dashboard), scope to that client.
        if ($this->client_id) {
            $query->where('client_id', $this->client_id);
        } else {
            // Default behavior: only client invoices
            $query->whereNotNull('client_id');
        }

        return $query->orderBy('id', 'desc');
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
            Column::computed('checkbox')->title('<input type="checkbox" id="select-all-checkbox">')->exportable(false)->printable(false)->width(20)->addClass('text-center px-2')->orderable(false)->searchable(false),
            Column::computed('number')->title('#')->width(30)->addClass('px-2')->orderable(false)->searchable(false),
            Column::computed('invoice_no')->title('Invoice No')->addClass('ps-0'),
            Column::make('client_name')->title('Client Name'),
            Column::make('site_name')->title('Site Name'),
            Column::make('issue_date')->title('Issue Date'),
            Column::make('total_shift_hours')->title('Total Shift Hours'),
            Column::make('net_amount')->title('Net Amount'),

            Column::make('total_amount')->title('Total Amount'),
            Column::computed('status')->title('Status')->addClass('text-center'),

        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Invoices_' . date('mdYHis');
    }
}
