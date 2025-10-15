<?php

namespace App\DataTables;

use App\Models\Material;
use App\Models\TrainingMaterial;
use Carbon\Carbon;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;

class MaterialDataTable extends DataTable
{
    public function dataTable($query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="rowCheckbox" value="' . $row->id . '">';
            })
            ->addColumn('pdf_url', function ($row) {
                if ($row->pdf_url) {
                    return '<a href="' . asset($row->pdf_url) . '" target="_blank"><i class="ti ti-download"></i></a>';
                }
                return '—';
            })
            // Format dates as MM/DD/YYYY
            ->addColumn('acknowledge_by_date', function($row) {
                if (empty($row->acknowledge_by_date)) return '';
                try { return Carbon::parse($row->acknowledge_by_date)->format('m/d/Y'); }
                catch (\Exception $e) { return $row->acknowledge_by_date; }
            })
            ->addColumn('implementation_date', function($row) {
                if (empty($row->implementation_date)) return '';
                try { return Carbon::parse($row->implementation_date)->format('m/d/Y'); }
                catch (\Exception $e) { return $row->implementation_date; }
            })
            ->addColumn('deadline', function($row) {
                if (empty($row->deadline)) return '';
                try { return Carbon::parse($row->deadline)->format('m/d/Y'); }
                catch (\Exception $e) { return $row->deadline; }
            })
            ->addColumn('action', function ($row) {
                return '
                <a class="viewMaterial" data-id="' . $row->id . '" title="View">
                    <i class="ti ti-eye"></i>
                </a>
                <a class="text-danger deleteMaterial" data-id="' . $row->id . '" title="Delete">
                    <i class="ti ti-trash"></i>
                </a>
                <a class="text-gray editMaterial" data-id="' . $row->id . '" title="Edit">
                    <i class="ti ti-pencil"></i>
                </a>
                <a class="text-success showAcknowledged" data-id="' . $row->id . '" title="Acknowledged">
                    <i class="ti ti-list"></i>
                </a>
            ';
            })
            ->rawColumns(['checkbox', 'pdf_url', 'action']);
    }


    public function query(TrainingMaterial $model)
    {
        return $model->newQuery();
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('materials-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('Bfrtip') // enables export/search
            ->orderBy(0)
            ->parameters([
                "pageLength" => 25,
            ])
            ->buttons([
                'excel',
                'csv',
                'pdf',
                'print',
                'reset',
                'reload'
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::computed('checkbox')
                ->exportable(false)
                ->printable(false)
                ->title('<input type="checkbox" id="selectAll">')
                ->width(10)
                ->addClass('text-center'),

            Column::computed('DT_RowIndex')->title('#')->width(30)->addClass('px-2')->orderable(false)->searchable(false),
            Column::make('title'),
            Column::make('description'),
            Column::make('type'),
            Column::make('acknowledge_by_date')->title('Acknowledge by Date')->addClass('text-center'),
            Column::make('implementation_date')->title('Implementation Date')->addClass('text-center'),
            Column::make('deadline')->title('Complete By Date')->addClass('text-center'),
            Column::make('pdf_url')->title('File'),

            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width(100)
                ->addClass('text-center'),
        ];
    }
}
