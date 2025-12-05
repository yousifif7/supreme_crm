<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\User;
use App\Models\ShiftDate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ShiftDateExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected $isTemplate;
    protected $ids;

    public function __construct($isTemplate = false, $ids = null)
    {
        $this->isTemplate = $isTemplate;
        $this->ids = $ids;
    }

    public function collection()
    {
        if ($this->isTemplate) {
            // Return empty collection for template
            return collect([]);
        }

        $query = ShiftDate::with(['shift.client', 'shift.site', 'staff'])
            ->orderBy('shift_date');

        if (!empty($this->ids) && is_array($this->ids)) {
            $query->whereIn('id', $this->ids);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            '#',
            'Date',
            'Day',
            'Officer',
            'Client',
            'Site',
            'Phone',
            'Start',
            'End',
            'Lost Time',
            'Hours',
            'Comments'
        ];
    }

    public function map($shiftDate): array
    {
        if ($this->isTemplate) {
            // Return empty array for template
            return [];
        }

        static $counter = 0;
        $counter++;

        $date = Carbon::parse($shiftDate->shift_date);

        $staff= User::find($shiftDate->staff_id);
        $client= User::role('client')->where('id',$shiftDate->shift->client_id)->first();
        return [
            $counter,
            $date->format('d-M-Y'),
            $date->format('l'),
            $shiftDate->staff ? trim($staff->first_name . ' ' . $staff->last_name) : '',
            $client->name ?? '',
            $shiftDate->shift->site->site_name ?? '',
            $shiftDate->shift->site->contact_number ?? '',
            Carbon::createFromFormat('H:i:s', $shiftDate->start_time)->format('H:i'),
            Carbon::createFromFormat('H:i:s', $shiftDate->end_time)->format('H:i'),
            $shiftDate->shift->lost_time ?? '0',
            $shiftDate->total_hours,
            $shiftDate->shift->comments ?? ''
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,  // # - narrow for row numbers
            'B' => 15, // Date - enough for "01-May-2025"
            'C' => 12, // Day - enough for "Wednesday"
            'D' => 20, // Officer - enough for full names
            'E' => 25, // Client - client names can be long
            'F' => 25, // Site - site names can be long
            'G' => 15, // Phone - phone numbers
            'H' => 8,  // Start - time format "06:00"
            'I' => 8,  // End - time format "18:00"
            'J' => 12, // Lost Time - lost time values
            'K' => 8,  // Hours - total hours "12.5"
            'L' => 40, // Comments - wide for comments text
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the header row (row 1) with yellow background
            1 => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => 'FFFFFF00', // Yellow background
                    ],
                ],
                'font' => [
                    'bold' => true,
                ],
            ],
        ];
    }
}
