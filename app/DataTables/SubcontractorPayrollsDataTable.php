<?php

namespace App\DataTables;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class SubcontractorPayrollsDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="dT-row-checkbox" value="' . $row->id . '">')
            ->addColumn('number', fn($row) => '')
            ->addColumn('payroll_no', fn($row) => '<a href="' . route('payrolls.show', $row->id) . '">' . $row->invoice_number . '</a>')
            ->addColumn('subcontractor_name', fn($row) => $row->subcontractor ? $row->subcontractor->first_name . ' ' . $row->subcontractor->last_name : '')
            ->addColumn('status', function ($row) {
                if ($row->paid_amount >= $row->net_amount) {
                    return '<span class="badge bg-success">Paid</span>';
                }

                return '<span class="badge bg-warning text-dark">Unpaid</span>';
            })
            ->addColumn('site_name', fn($row) => $row->site ? $row->site->site_name : '')
            ->addColumn('issue_date', function($row) {
                if (empty($row->issue_date)) return '';
                try { return Carbon::parse($row->issue_date)->format('d/m/Y'); } catch (\Exception $e) { return $row->issue_date; }
            })
            ->addColumn('due_date', function($row) {
                if (empty($row->due_date)) return '';
                try { return Carbon::parse($row->due_date)->format('m/d/Y'); } catch (\Exception $e) { return $row->due_date; }
            })
            ->addColumn('total_shift_hours', fn($row) => $row->total_shift_hours ?? 0)
            ->addColumn('net_amount', fn($row) => number_format($row->net_amount ?? 0, 2))
            ->addColumn('total_amount', fn($row) => number_format($row->total_amount ?? 0, 2))
            ->addColumn('action', function ($row) {
                return '<a href="javascript:void(0)" class="btn btn-sm btn-danger" onclick="deleteRecord(' . $row->id . ', \"payrolls\")">Delete</a>';
            })
            ->rawColumns(['checkbox', 'number', 'payroll_no', 'action', 'status']);
    }

    public function query(Invoice $model): QueryBuilder
    {
        return $model->newQuery()
            ->with(['subcontractor', 'site'])
            ->where('type', 'subcontractor')
            ->orderBy('id', 'desc');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('subcontractor-payrolls-table')
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
                "drawCallback" => "function(settings) { feather.replace(); var api = this.api(); var start = api.page.info().start; api.column(1, {page: 'current'}).nodes().each(function(cell, i) { cell.innerHTML = start + i + 1; }); }",
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('checkbox')->title('<input type="checkbox" id="payrolls-select-all">')->addClass('px-2 fw-bold'),
            Column::computed('number')->title('#')->width(30)->addClass('px-2')->orderable(false)->searchable(false)->addClass('px-2 fw-bold'),
            Column::computed('payroll_no')->title('Payroll No')->addClass('ps-0')->addClass('px-2 fw-bold'),
            Column::make('subcontractor_name')->title('Subcontractor')->addClass('px-2 fw-bold')->searchable(true),
            Column::make('site_name')->title('Site Name')->addClass('px-2 fw-bold'),
            Column::make('issue_date')->title('Issue Date')->addClass('px-2 fw-bold'),
            Column::make('due_date')->title('Due Date')->addClass('px-2 fw-bold'),
            Column::make('total_shift_hours')->title('Total Shift Hours')->addClass('px-2 fw-bold'),
            Column::make('net_amount')->title('Net Amount')->addClass('px-2 fw-bold'),
            Column::make('total_amount')->title('Total Amount')->addClass('px-2 fw-bold'),
        ];
    }

    protected function filename(): string
    {
        return 'Subcontractor_Payrolls_' . date('YmdHis');
    }
}
