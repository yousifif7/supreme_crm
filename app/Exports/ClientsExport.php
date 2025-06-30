<?php

namespace App\Exports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ClientsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function collection()
    {
        return Client::select('client_name', 'address', 'contact_number', 'contact_person', 'email')
                    ->orderBy('client_name', 'asc')
                    ->get();
    }

    public function headings(): array
    {
        return [
            'Client Name',
            'Address',
            'Contact Number',
            'Contact Person',
            'Email Address'
        ];
    }

    public function map($client): array
    {
        return [
            $client->client_name ?? 'N/A',
            $client->address ?? 'N/A',
            $client->contact_number ?? 'N/A',
            $client->contact_person ?? 'N/A',
            $client->email ?? 'N/A',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row (header row)
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E69932'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            // Style for all data rows
            'A:E' => [
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'font' => [
                    'size' => 11,
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25, // Client Name
            'B' => 40, // Address
            'C' => 18, // Contact Number
            'D' => 25, // Contact Person
            'E' => 30, // Email Address
        ];
    }
}
