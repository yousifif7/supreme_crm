<?php

namespace App\Exports;

use App\Models\TrainingMaterial;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class MaterialsExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * Fetch data for export
     */
    public function collection()
    {
        return TrainingMaterial::all();
    }

    /**
     * Define Excel headers
     */
    public function headings(): array
    {
        return [
            'ID',
            'Title',
            'Type',
            'Description',
            'Content URL',
            'PDF URL',
            'Required',
            'Expiry Date',
            'Created At',
            'Updated At',
        ];
    }

    /**
     * Map each row’s data
     */
    public function map($material): array
    {
        return [
            $material->id,
            $material->title,
            ucfirst($material->type),
            $material->description,
            $material->content_url,
            $material->pdf_url,
            $material->required ? 'Yes' : 'No',
            $material->expiry_date,
            $material->created_at ? $material->created_at->format('Y-m-d H:i:s') : null,
            $material->updated_at ? $material->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
