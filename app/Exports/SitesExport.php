<?php

namespace App\Exports;

use App\Models\Site;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SitesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithCustomStartCell, WithEvents
{
    private $rowNumber = 0;
    private $isTemplate;

    public function __construct($isTemplate = false)
    {
        $this->isTemplate = $isTemplate;
    }

    public function startCell(): string
    {
        return 'B2';
    }

    public function collection()
    {
        if ($this->isTemplate) {
            // Return dummy data for template
            return collect([
                (object) [
                    'client_name' => 'ABC Corporation Ltd',
                    'site_name' => 'Main Office Security',
                    'address' => '123 Business Plaza, Downtown, State 12345',
                    'site_code' => 'ABC001',
                    'post_code' => '12345',
                    'guard_names' => 'John Doe, Jane Smith',
                    'contact_number' => '+1-555-0199',
                    'contact_person' => 'Site Manager',
                    'note' => 'Main office building - high security area',
                    'start_time' => '08:00',
                    'end_time' => '18:00',
                    'break_time' => '12:00-13:00',
                    'guard_rate' => 25.00,
                    'office_rate' => 30.00,
                    'billable_rate' => 35.00,
                    'payable_rate' => 20.00,
                ],
                (object) [
                    'client_name' => 'XYZ Services Inc',
                    'site_name' => 'Warehouse Complex',
                    'address' => '456 Industrial Drive, Warehouse District, State 67890',
                    'site_code' => 'XYZ002',
                    'post_code' => '67890',
                    'guard_names' => 'Mike Johnson',
                    'contact_number' => '+1-555-0299',
                    'contact_person' => 'Warehouse Supervisor',
                    'note' => '24/7 warehouse security coverage required',
                    'start_time' => '00:00',
                    'end_time' => '23:59',
                    'break_time' => '02:00-02:30, 14:00-14:30',
                    'guard_rate' => 28.00,
                    'office_rate' => 32.00,
                    'billable_rate' => 38.00,
                    'payable_rate' => 22.00,
                ],
                (object) [
                    'client_name' => '', // Example of site without client
                    'site_name' => 'Independent Security Post',
                    'address' => '789 Standalone Street, Independent City, State 11111',
                    'site_code' => 'IND003',
                    'post_code' => '11111',
                    'guard_names' => 'Security Officer',
                    'contact_number' => '+1-555-0399',
                    'contact_person' => 'Site Coordinator',
                    'note' => 'Independent site not linked to any specific client',
                    'start_time' => '06:00',
                    'end_time' => '22:00',
                    'break_time' => '12:00-12:30, 18:00-18:30',
                    'guard_rate' => 24.00,
                    'office_rate' => 28.00,
                    'billable_rate' => 32.00,
                    'payable_rate' => 19.00,
                ]
            ]);
        }

        return Site::with('client')
            ->select(
                'id',
                'client_id',
                'site_name',
                'address',
                'site_code',
                'post_code',
                'guard_names',
                'contact_number',
                'contact_person',
                'note',
                'start_time',
                'end_time',
                'break_time',
                'guard_rate',
                'office_rate',
                'billable_rate',
                'payable_rate'
            )
            ->orderBy('site_name', 'asc')
            ->get();
    }

    public function headings(): array
    {
        return [
            '#',
            'Client Name',
            'Site Name',
            'Address',
            'Site Code',
            'Post Code',
            'Guard Names',
            'Contact Number',
            'Contact Person',
            'Note',
            'Start Time',
            'End Time',
            'Break Time',
            'Guard Rate',
            'Office Rate',
            'Billable Rate',
            'Payable Rate'
        ];
    }

    public function map($site): array
    {
        return [
            ++$this->rowNumber,
            $site->client ? $site->client->client_name : ($site->client_name ?? 'N/A'),
            $site->site_name,
            $site->address,
            $site->site_code,
            $site->post_code,
            $site->guard_names,
            $site->contact_number,
            $site->contact_person,
            $site->note,
            $site->start_time,
            $site->end_time,
            $site->break_time,
            $site->guard_rate,
            $site->office_rate,
            $site->billable_rate,
            $site->payable_rate,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style only the header cells (B2:R2) - excluding A2
            'B2:R2' => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => '000000'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFFF00'], // Yellow background
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            // Style all data cells (from B2 onwards)
            'B:R' => [
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                // Add borders to header row (B2:R2)
                $sheet->getStyle('B2:R2')->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                // Add borders to the entire data table (B2:R{lastRow})
                $sheet->getStyle('B2:R' . $highestRow)->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000'],
                        ],
                        'inside' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
            },
        ];
    }

    public function columnWidths(): array
    {
        return [
            'B' => 5,  // # column
            'C' => 25, // Client Name
            'D' => 25, // Site Name
            'E' => 35, // Address
            'F' => 12, // Site Code
            'G' => 12, // Post Code
            'H' => 25, // Guard Names
            'I' => 18, // Contact Number
            'J' => 20, // Contact Person
            'K' => 30, // Note
            'L' => 12, // Start Time
            'M' => 12, // End Time
            'N' => 20, // Break Time
            'O' => 12, // Guard Rate
            'P' => 12, // Office Rate
            'Q' => 12, // Billable Rate
            'R' => 12, // Payable Rate
        ];
    }
}
