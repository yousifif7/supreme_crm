<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class DocumentReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithEvents
{
    private int $rowNumber = 0;

    /**
     * @param Collection $employees       The already-filtered employees to report on.
     * @param array       $selectedFields The selected document field keys (may include 'other').
     * @param array       $documentFields Map of field key => human label.
     * @param array       $expiryFields   Map of document field key => expiry field name.
     * @param string|null $otherDocument  Optional custom name filter for the "Other" documents.
     */
    public function __construct(
        private Collection $employees,
        private array $selectedFields,
        private array $documentFields,
        private array $expiryFields,
        private ?string $otherDocument = null,
    ) {}

    public function collection(): Collection
    {
        return $this->employees;
    }

    public function headings(): array
    {
        return [
            '#',
            'ID',
            'Name',
            'Status',
            'Document Status',
            'Expiry Date',
            'Days Remaining',
        ];
    }

    public function map($employee): array
    {
        return [
            ++$this->rowNumber,
            $employee->id,
            trim(($employee->fore_name ?? '') . ' ' . ($employee->sur_name ?? '')),
            ucfirst($employee->status ?? ''),
            $this->documentStatus($employee),
            $this->expiryDates($employee),
            $this->daysRemaining($employee),
        ];
    }

    /** Build the "X/Y documents" summary plus per-document state. */
    private function documentStatus($employee): string
    {
        $lines = [];
        $uploaded = 0;
        $total = 0;

        foreach ($this->selectedFields as $field) {
            if ($field === 'other') {
                continue;
            }
            $total++;
            $label = $this->documentFields[$field] ?? $field;
            if (!empty($employee->{$field})) {
                $uploaded++;
                $lines[] = "{$label}: Uploaded";
            } else {
                $lines[] = "{$label}: Missing";
            }
        }

        if (in_array('other', $this->selectedFields, true)) {
            $total++;
            $additionalFiles = $employee->additional_files ?? [];
            $matches = $this->otherDocument
                ? array_filter($additionalFiles, fn($f) => stripos(basename($f), $this->otherDocument) !== false)
                : $additionalFiles;

            if (!empty($matches)) {
                $uploaded++;
                foreach ($matches as $file) {
                    $lines[] = 'Other: ' . $file;
                }
            } else {
                $lines[] = 'Other: Missing';
            }
        }

        $summary = "{$uploaded}/{$total} documents";

        return $lines ? $summary . " — " . implode('; ', $lines) : $summary;
    }

    /** @return array<string,string> field => expiry date string */
    private function expiryDatesFor($employee): array
    {
        $dates = [];
        foreach ($this->selectedFields as $field) {
            if (
                $field !== 'other' &&
                array_key_exists($field, $this->expiryFields) &&
                $employee->{$field}
            ) {
                $dates[$field] = $employee->{$this->expiryFields[$field]};
            }
        }
        return $dates;
    }

    private function expiryDates($employee): string
    {
        $dates = $this->expiryDatesFor($employee);
        if (!$dates) {
            return 'N/A';
        }

        $formatted = [];
        foreach ($dates as $field => $date) {
            $label = $this->documentFields[$field] ?? $field;
            $formatted[] = $label . ': ' . ($date ? Carbon::parse($date)->format('d/m/Y') : 'N/A');
        }
        return implode('; ', $formatted);
    }

    private function daysRemaining($employee): string
    {
        $dates = $this->expiryDatesFor($employee);
        if (!$dates) {
            return 'N/A';
        }

        $parts = [];
        foreach ($dates as $field => $date) {
            if (!$date) {
                continue;
            }
            $label = $this->documentFields[$field] ?? $field;
            $days = (int) round(now()->diffInDays(Carbon::parse($date), false));
            $parts[] = $days > 0
                ? "{$label}: {$days} days"
                : "{$label}: Expired " . abs($days) . " days ago";
        }
        return $parts ? implode('; ', $parts) : 'N/A';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            'A1:G1' => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '000000']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            'A:G' => [
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                $sheet->getStyle('A1:G' . $highestRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
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
            'A' => 5,   // #
            'B' => 8,   // ID
            'C' => 25,  // Name
            'D' => 12,  // Status
            'E' => 50,  // Document Status
            'F' => 30,  // Expiry Date
            'G' => 35,  // Days Remaining
        ];
    }
}
