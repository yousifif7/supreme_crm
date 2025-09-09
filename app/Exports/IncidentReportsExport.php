<?php

namespace App\Exports;

use App\Models\IncidentReport;
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

class IncidentReportsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithCustomStartCell, WithEvents
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
            return collect([
                (object) [
                    'user_id' => 1,
                    'shift_id' => 10,
                    'category' => 'theft',
                    'severity' => 'high',
                    'title' => 'Sample Theft Incident',
                    'description' => 'A sample description of the theft incident.',
                    'location' => json_encode(['latitude' => '34.11', 'longitude' => '22.11', 'address' => 'Main Street 123']),
                    'police_notified' => true,
                    'police_reference' => 'REF12345',
                    'immediate_action_taken' => 'Reported to supervisor and secured the area',
                    'status' => 'draft',
                    'created_at' => now(),
                ],
                (object) [
                    'user_id' => 2,
                    'shift_id' => 12,
                    'category' => 'fire',
                    'severity' => 'critical',
                    'title' => 'Warehouse Fire',
                    'description' => 'A fire broke out in the storage warehouse.',
                    'location' => json_encode(['latitude' => '35.00', 'longitude' => '25.00', 'address' => 'Industrial Zone 45']),
                    'police_notified' => false,
                    'police_reference' => '',
                    'immediate_action_taken' => 'Fire department contacted immediately',
                    'status' => 'submitted',
                    'created_at' => now(),
                ]
            ]);
        }

        return IncidentReport::orderBy('created_at', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            '#',
            'User ID',
            'Shift ID',
            'Category',
            'Severity',
            'Title',
            'Description',
            'Location Address',
            'Latitude',
            'Longitude',
            'Police Notified',
            'Police Reference',
            'Immediate Action Taken',
            'Status',
            'Reported At',
        ];
    }

    public function map($report): array
    {
        $location = is_string($report->location) ? json_decode($report->location, true) : $report->location;

        return [
            ++$this->rowNumber,
            $report->user_id,
            $report->shift_id,
            ucfirst(str_replace('_', ' ', $report->category)),
            ucfirst($report->severity),
            $report->title,
            $report->description,
            $location['address'] ?? '',
            $location['latitude'] ?? '',
            $location['longitude'] ?? '',
            $report->police_notified ? 'Yes' : 'No',
            $report->police_reference,
            $report->immediate_action_taken,
            ucfirst(str_replace('_', ' ', $report->status)),
            $report->created_at ? $report->created_at->format('Y-m-d H:i:s') : '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            'B2:Q2' => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => '000000'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFFF00'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            'B:Q' => [
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                $sheet->getStyle('B2:Q2')->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getStyle('B2:Q' . $highestRow)->applyFromArray([
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
            'B' => 5,   // #
            'C' => 10,  // User ID
            'D' => 10,  // Shift ID
            'E' => 20,  // Category
            'F' => 15,  // Severity
            'G' => 30,  // Title
            'H' => 50,  // Description
            'I' => 35,  // Location Address
            'J' => 15,  // Latitude
            'K' => 15,  // Longitude
            'L' => 15,  // Police Notified
            'M' => 20,  // Police Reference
            'N' => 40,  // Immediate Action Taken
            'O' => 15,  // Status
            'P' => 20,  // Reported At
        ];
    }
}
