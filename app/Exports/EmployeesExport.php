<?php

namespace App\Exports;

use App\Models\Employee;
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
use Carbon\Carbon;

class EmployeesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithCustomStartCell, WithEvents
{
    private $rowNumber = 0;
    private $isTemplate;

    public function __construct($isTemplate = false)
    {
        $this->isTemplate = $isTemplate;
    }

    public function startCell(): string
    {
        return 'A1';
    }

    public function collection()
    {
        if ($this->isTemplate) {
            // Return dummy data for template
            return collect([
                (object) [
                    'entry_date' => '2024-01-15',
                    'full_name' => 'John Smith',
                    'subcontractor_name' => 'ABC Security Services',
                    'guard_rate' => 25.00,
                    'contact' => '+44 7700 900123',
                    'sia_licence' => 'SIA123456789',
                    'service_type' => 'Security Guard',
                    'sia_expiry' => '2025-08-15',
                    'dob' => '1990-05-20',
                    'email' => 'john.smith@example.com',
                    'username' => 'john.smith@example.com',
                    'address_group' => '123 Main Street, London, SW1A 1AA',
                    'account_name' => 'John Smith',
                    'sort_code' => '12-34-56',
                    'account_number' => '12345678',
                    'ni_number' => 'AB123456C',
                    'visa_type' => 'British Citizen',
                    'visa_expiry' => null,
                    'remaining_sia_days' => 230,
                    'remaining_visa_days' => null,
                ],
                (object) [
                    'entry_date' => '2024-02-01',
                    'full_name' => 'Maria Garcia Rodriguez',
                    'subcontractor_name' => 'Elite Protection Ltd',
                    'guard_rate' => 28.50,
                    'contact' => '+44 7700 900456',
                    'sia_licence' => 'SIA987654321',
                    'service_type' => 'Door Supervisor',
                    'sia_expiry' => '2024-12-20',
                    'dob' => '1985-12-10',
                    'email' => 'maria.garcia@example.com',
                    'username' => 'maria.garcia@example.com',
                    'address_group' => '456 Oak Avenue, Manchester, M1 2AB',
                    'account_name' => 'Maria Garcia Rodriguez',
                    'sort_code' => '98-76-54',
                    'account_number' => '87654321',
                    'ni_number' => 'YZ987654D',
                    'visa_type' => 'EU Settled Status',
                    'visa_expiry' => '2029-02-01',
                    'remaining_sia_days' => 120,
                    'remaining_visa_days' => 1825,
                ],
                (object) [
                    'entry_date' => '2024-03-10',
                    'full_name' => 'Ahmed',
                    'subcontractor_name' => '',
                    'guard_rate' => 24.00,
                    'contact' => '+44 7700 900789',
                    'sia_licence' => '',
                    'service_type' => 'CCTV Operator',
                    'sia_expiry' => null,
                    'dob' => '1992-08-30',
                    'email' => '',
                    'username' => '', // No username for this example
                    'address_group' => '789 High Street, Birmingham, B1 3CD',
                    'account_name' => 'Ahmed Ali',
                    'sort_code' => '11-22-33',
                    'account_number' => '11223344',
                    'ni_number' => 'CD555666E',
                    'visa_type' => 'Work Visa',
                    'visa_expiry' => '2026-03-10',
                    'remaining_sia_days' => null,
                    'remaining_visa_days' => 730,
                ]
            ]);
        }

        return Employee::with(['department', 'visatype'])
            ->leftJoin('sub_contractors', function($join) {
                $join->on('employees.subcontractor', '=', 'sub_contractors.id')
                     ->whereRaw('employees.subcontractor REGEXP "^[0-9]+$"'); // Only join if subcontractor is numeric (ID)
            })
            ->leftJoin('users', 'employees.user_id', '=', 'users.id')
            ->select(
                'employees.id',
                'employees.user_id',
                'employees.entry_date',
                'employees.fore_name',
                'employees.sur_name',
                'employees.subcontractor',
                'sub_contractors.company_name as subcontractor_name',
                'employees.guard_rate',
                'employees.contact',
                'employees.sia_licence',
                'employees.service_type',
                'employees.sia_expiry',
                'employees.dob',
                'employees.email',
                'users.username',
                'employees.address_group',
                'employees.account_name',
                'employees.sort_code',
                'employees.account_number',
                'employees.ni_number',
                'employees.visa_type',
                'employees.visa_expiry'
            )
            ->orderBy('employees.fore_name', 'asc')
            ->get();
    }

    public function headings(): array
    {
        return [
            '#',
            'Date of Registration',
            'Full Name',
            'Subcontractor',
            'Pay Rate',
            'Contact',
            'SIA Number',
            'Service Type',
            'SIA Expiry',
            'DOB',
            'Email',
            'Username',
            'Address with Post Code',
            'Address Group',
            'Account Name',
            'Sort Code',
            'Account Number',
            'NI Number',
            'Visa Status',
            'Visa Expiry Date',
            'Remaining SIA Days',
            'Remaining VISA Days'
        ];
    }

    public function map($employee): array
    {
        // For template data, handle the dummy objects
        if ($this->isTemplate) {
            // Calculate remaining days for template
            $remainingSiaDays = $employee->remaining_sia_days;
            $remainingVisaDays = $employee->remaining_visa_days;

            return [
                ++$this->rowNumber,
                $employee->entry_date ? Carbon::parse($employee->entry_date)->format('Y-m-d') : '',
                $employee->full_name,
                $employee->subcontractor_name,
                $employee->guard_rate,
                $employee->contact,
                $employee->sia_licence,
                $employee->service_type,
                $employee->sia_expiry ? Carbon::parse($employee->sia_expiry)->format('d-M-y') : '',
                $employee->dob ? Carbon::parse($employee->dob)->format('Y-m-d') : '',
                $employee->email,
                $employee->username,
                $employee->address_group,
                $employee->address_group, // Address Group
                $employee->account_name,
                $employee->sort_code,
                $employee->account_number,
                $employee->ni_number,
                $employee->visa_type,
                $employee->visa_expiry ? Carbon::parse($employee->visa_expiry)->format('d-M-y') : '',
                $remainingSiaDays,
                $remainingVisaDays,
            ];
        }

        // For real data, calculate remaining days
        $remainingSiaDays = null;
        $remainingVisaDays = null;

        if ($employee->sia_expiry) {
            $siaExpiry = Carbon::parse($employee->sia_expiry);
            $diffDays = $siaExpiry->diffInDays(now(), false);
            $remainingSiaDays = $diffDays < 0 ? abs($diffDays) : 0;
        }

        if ($employee->visa_expiry) {
            $visaExpiry = Carbon::parse($employee->visa_expiry);
            $diffDays = $visaExpiry->diffInDays(now(), false);
            $remainingVisaDays = $diffDays < 0 ? abs($diffDays) : 0;
        }

        // Get subcontractor name from relationship or direct field
        $subcontractorName = '';
        if ($employee->subcontractor_name) {
            // Use the joined subcontractor name if available
            $subcontractorName = $employee->subcontractor_name;
        } elseif ($employee->subcontractor && !is_numeric($employee->subcontractor)) {
            // If subcontractor is stored as text (company name)
            $subcontractorName = $employee->subcontractor;
        }

        return [
            ++$this->rowNumber,
            $employee->entry_date ? Carbon::parse($employee->entry_date)->format('Y-m-d') : '',
            trim(($employee->fore_name ?? '') . ' ' . ($employee->sur_name ?? '')),
            $subcontractorName,
            $employee->guard_rate,
            $employee->contact,
            $employee->sia_licence,
            $employee->service_type,
            $employee->sia_expiry ? Carbon::parse($employee->sia_expiry)->format('d-M-y') : '',
            $employee->dob ? Carbon::parse($employee->dob)->format('Y-m-d') : '',
            $employee->email,
            $employee->username,
            $employee->address_group,
            $employee->address_group, // Address Group - same for now
            $employee->account_name,
            $employee->sort_code,
            $employee->account_number,
            $employee->ni_number,
            $employee->visa_type,
            $employee->visa_expiry ? Carbon::parse($employee->visa_expiry)->format('d-M-y') : '',
            $remainingSiaDays,
            $remainingVisaDays,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style only the header cells (A1:V1)
            'A1:V1' => [
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
            // Style all data cells (from A1 onwards)
            'A:V' => [
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

                // Add borders to header row (A1:V1)
                $sheet->getStyle('A1:V1')->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                // Add borders to the entire data table (A1:V{lastRow})
                $sheet->getStyle('A1:V' . $highestRow)->applyFromArray([
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
            'A' => 5,  // # column
            'B' => 15, // Date of Registration
            'C' => 25, // Full Name
            'D' => 25, // Subcontractor
            'E' => 12, // Pay Rate
            'F' => 18, // Contact
            'G' => 18, // SIA Number
            'H' => 20, // Service Type
            'I' => 15, // SIA Expiry
            'J' => 12, // DOB
            'K' => 30, // Email
            'L' => 30, // Username
            'M' => 35, // Address with Post Code
            'N' => 25, // Address Group
            'O' => 20, // Account Name
            'P' => 12, // Sort Code
            'Q' => 15, // Account Number
            'R' => 12, // NI Number
            'S' => 18, // Visa Status
            'T' => 15, // Visa Expiry Date
            'U' => 15, // Remaining SIA Days
            'V' => 15, // Remaining VISA Days
        ];
    }
}
