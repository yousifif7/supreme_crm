<?php

namespace App\Exports;

use App\Models\Subcontractor;
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

class SubcontractorsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithCustomStartCell, WithEvents
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
                    'company_name' => 'Sample Corp Ltd',
                    'company_address' => '123 Business Street, City, State 12345',
                    'contact_person' => 'John Doe',
                    'contact_number' => '+1-555-0123',
                    'email' => 'contact@samplecorp.com',
                    'username' => 'admin@samplecorp.com',
                    'invoice_terms' => 'Net 30',
                    'payment_terms' => 'Bank Transfer',
                    'department' => 'Construction',
                    'vat_registered' => true,
                    'vat_number' => 'VAT123456789',
                    'pay_rate' => 50.00,
                    'pmva_trained_officer' => true,
                    'is_active' => true,
                ],
                (object) [
                    'company_name' => 'Example Services Inc',
                    'company_address' => '456 Industry Avenue, Town, State 67890',
                    'contact_person' => 'Jane Smith',
                    'contact_number' => '+1-555-0456',
                    'email' => 'info@exampleservices.com',
                    'username' => 'user@exampleservices.com',
                    'invoice_terms' => 'Net 15',
                    'payment_terms' => 'Cash',
                    'department' => 'Security',
                    'vat_registered' => false,
                    'vat_number' => '',
                    'pay_rate' => 45.00,
                    'pmva_trained_officer' => false,
                    'is_active' => true,
                ]
            ]);
        }

        return Subcontractor::with('user')
            ->select(
                'id',
                'user_id',
                'company_name',
                'company_address',
                'contact_person',
                'contact_number',
                'email',
                'invoice_terms',
                'payment_terms',
                'department',
                'vat_registered',
                'vat_number',
                'pay_rate',
                'pmva_trained_officer',
                'is_active'
            )
            ->orderBy('company_name', 'asc')
            ->get();
    }

    public function headings(): array
    {
        return [
            '#',
            'Company Name',
            'Company Address',
            'Contact Person',
            'Contact Number',
            'Email',
            'Username',
            'Invoice Terms',
            'Payment Terms',
            'Department',
            'VAT Registered',
            'VAT Number',
            'Pay Rate',
            'PMVA Trained Officer',
            'Is Active'
        ];
    }

    public function map($subcontractor): array
    {
        return [
            ++$this->rowNumber,
            $subcontractor->company_name,
            $subcontractor->company_address,
            $subcontractor->contact_person,
            $subcontractor->contact_number,
            $subcontractor->email,
            $subcontractor->username,
            $subcontractor->invoice_terms,
            $subcontractor->payment_terms,
            $subcontractor->department,
            $subcontractor->vat_registered ? 'Yes' : 'No',
            $subcontractor->vat_number,
            $subcontractor->pay_rate,
            $subcontractor->pmva_trained_officer ? 'Yes' : 'No',
            $subcontractor->is_active ? 'Active' : 'Inactive',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style only the header cells (B2:P2) - excluding A2
            'B2:P2' => [
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
            'B:P' => [
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                // Add borders to header row (B2:P2)
                $sheet->getStyle('B2:P2')->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                // Add borders to the entire data table (B2:P{lastRow})
                $sheet->getStyle('B2:P' . $highestRow)->applyFromArray([
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
            'C' => 25, // Company Name
            'D' => 35, // Company Address
            'E' => 25, // Contact Person
            'F' => 20, // Contact Number
            'G' => 30, // Email
            'H' => 30, // Username
            'I' => 20, // Invoice Terms
            'J' => 20, // Payment Terms
            'K' => 15, // Department
            'L' => 15, // VAT Registered
            'M' => 20, // VAT Number
            'N' => 15, // Pay Rate
            'O' => 20, // PMVA Trained Officer
            'P' => 12, // Is Active
        ];
    }
}
