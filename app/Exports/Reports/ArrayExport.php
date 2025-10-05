<?php

namespace App\Exports\Reports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ArrayExport implements FromArray, WithHeadings
{
    protected $data;
    protected $headings;

    public function __construct(array $data, array $headings = [])
    {
        $this->data = $data;

        // If headings provided, use them, otherwise take the keys from first row
        $this->headings = $headings ?: (count($data) ? array_keys($data[0]) : []);
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return $this->headings;
    }
}
