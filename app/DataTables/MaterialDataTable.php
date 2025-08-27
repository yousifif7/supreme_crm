<?php

namespace App\DataTables;

use App\Models\Material;
use App\Models\TrainingMaterial;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;

class MaterialDataTable extends DataTable
{
public function dataTable($query): EloquentDataTable
{
    return (new EloquentDataTable($query))
        ->addColumn('checkbox', function ($row) {
            return '<input type="checkbox" class="rowCheckbox" value="'.$row->id.'">';
        })
        ->addColumn('pdf_url', function ($row) {
            if ($row->pdf_url) {
                return '<a href="'.asset($row->pdf_url).'" target="_blank"><i class="ti ti-download"></i></a>';
            }
            return '—';
        })
        ->addColumn('action', function ($row) {
            return '
                <a class="viewMaterial" data-id="'.$row->id.'" title="View">
                    <i class="ti ti-eye"></i>
                </a>
                <a class="text-danger deleteMaterial" data-id="'.$row->id.'" title="Delete">
                    <i class="ti ti-trash"></i>
                </a>
            ';
        })
        ->rawColumns(['checkbox','pdf_url','action']);
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

            Column::make('id'),
            Column::make('title'),
            Column::make('description'),
            Column::make('type'),
            Column::make('expiry_date')->title('Expiry Date'),
            Column::make('pdf_url')->title('File'),

            // Column::computed('action')
            //     ->exportable(false)
            //     ->printable(false)
            //     ->width(100)
            //     ->addClass('text-center'),
        ];
    }
}
