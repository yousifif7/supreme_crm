<?php

namespace App\Exports;

use App\Models\Client;
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

class ClientsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithCustomStartCell, WithEvents
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
                    'address' => '123 Business Plaza, Downtown, State 12345',
                    'contact_number' => '+1-555-0199',
                    'contact_person' => 'John Manager',
                    'email' => 'contact@abccorp.com',
                    'username' => 'admin@abccorp.com',
                    'invoice_terms' => 'Net 30 Days',
                    'payment_terms' => 'Bank Transfer',
                    'contract_start' => '2024-01-01',
                    'contract_end' => '2024-12-31',
                    'company_id' => 1,
                    'guard_rate' => 25.00,
                    'office_rate' => 30.00,
                    'vat' => true,
                    'manager_id' => 1,
                ],
                (object) [
                    'client_name' => 'XYZ Services Inc',
                    'address' => '456 Corporate Street, Uptown, State 67890',
                    'contact_number' => '+1-555-0299',
                    'contact_person' => 'Jane Director',
                    'email' => 'info@xyzservices.com',
                    'username' => 'user@xyzservices.com',
                    'invoice_terms' => 'Net 15 Days',
                    'payment_terms' => 'Credit Card',
                    'contract_start' => '2024-02-01',
                    'contract_end' => '2025-01-31',
                    'company_id' => 2,
                    'guard_rate' => 28.00,
                    'office_rate' => 35.00,
                    'vat' => false,
                    'manager_id' => 2,
                ]
            ]);
        }

        return Client::with(['company', 'manager', 'user'])
            ->select(
                'id',
                'user_id',
                'client_name',
                'address',
                'contact_number',
                'contact_person',
                'email',
                'invoice_terms',
                'payment_terms',
                'contract_start',
                'contract_end',
                'company_id',
                'guard_rate',
                'office_rate',
                'vat',
                'manager_id'
            )
            ->orderBy('client_name', 'asc')
            ->get();
    }

    public function headings(): array
    {
        return [
            '#',
            'Name',
            'Address',
            'Contact Number',
            'Contact Person',
            'Contact Email',
            'Username',
            'Invoice Terms',
            'Payment Terms',
            'Contract Start',
            'Contract End',
            'Company ID',
            'Guard Rate',
            'Office Rate',
            'VAT',
            'Manager ID'
        ];
    }

    public function map($client): array
    {
        return [
            ++$this->rowNumber,
            $client->client_name,
            $client->address,
            $client->contact_number,
            $client->contact_person,
            $client->email,
            $client->username ?? ($client->user ? $client->user->username : ''),
            $client->invoice_terms,
            $client->payment_terms,
            $client->contract_start ? date('Y-m-d', strtotime($client->contract_start)) : '',
            $client->contract_end ? date('Y-m-d', strtotime($client->contract_end)) : '',
            $client->company_id,
            $client->guard_rate,
            $client->office_rate,
            $client->vat ? 'Yes' : 'No',
            $client->manager_id,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style only the header cells (B2:Q2) - excluding A2
            'B2:Q2' => [
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
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                // Add borders to header row (B2:Q2)
                $sheet->getStyle('B2:Q2')->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                // Add borders to the entire data table (B2:Q{lastRow})
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
            'B' => 5,  // # column
            'C' => 25, // Name
            'D' => 35, // Address
            'E' => 20, // Contact Number
            'F' => 25, // Contact Person
            'G' => 30, // Contact Email
            'H' => 30, // Username
            'I' => 20, // Invoice Terms
            'J' => 20, // Payment Terms
            'K' => 15, // Contract Start
            'L' => 15, // Contract End
            'M' => 12, // Company ID
            'N' => 15, // Guard Rate
            'O' => 15, // Office Rate
            'P' => 10, // VAT
            'Q' => 12, // Manager ID
        ];
    }
}
