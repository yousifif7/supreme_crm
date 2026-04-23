<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\ShiftDate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Shift date export.
 *
 * When a query builder is supplied the export uses FromQuery + chunked
 * processing so the whole result set is never loaded into memory at once.
 * When no query is supplied (template download) an empty collection is
 * returned via the FromCollection fallback path.
 */
class ShiftDateExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithCustomChunkSize
{
    protected bool $isTemplate;

    /** Query builder used for the streamed export. */
    protected ?QueryBuilder $exportQuery;

    /** Per-instance row counter (avoids static state leaking across requests). */
    protected int $counter = 0;

    public function __construct(bool $isTemplate = false, ?QueryBuilder $exportQuery = null)
    {
        $this->isTemplate = $isTemplate;
        $this->exportQuery = $exportQuery;
    }

    /**
     * Called by Maatwebsite Excel for the main export.
     * Returning a query builder allows the library to chunk records so
     * memory stays constant even for 100k+ rows.
     */
    public function query(): QueryBuilder
    {
        if ($this->isTemplate || $this->exportQuery === null) {
            // Return a query that yields zero rows for template downloads.
            return ShiftDate::query()->whereRaw('1 = 0');
        }

        return $this->exportQuery;
    }

    /**
     * Process 500 rows per SQL chunk to keep peak memory low.
     */
    public function chunkSize(): int
    {
        return 500;
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
            return [];
        }

        $this->counter++;

        $date = Carbon::parse($shiftDate->shift_date);
        $staffName = $shiftDate->staff
            ? trim(($shiftDate->staff->first_name ?? '') . ' ' . ($shiftDate->staff->last_name ?? ''))
            : '';

        $clientName = $shiftDate->shift->client->name ?? '';

        return [
            $this->counter,
            $date->format('d-M-Y'),
            $date->format('l'),
            $staffName,
            $clientName,
            $shiftDate->shift->site->site_name ?? '',
            $shiftDate->shift->site->contact_number ?? '',
            $shiftDate->start_time ? Carbon::createFromFormat('H:i:s', $shiftDate->start_time)->format('H:i') : '',
            $shiftDate->end_time   ? Carbon::createFromFormat('H:i:s', $shiftDate->end_time)->format('H:i')   : '',
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
