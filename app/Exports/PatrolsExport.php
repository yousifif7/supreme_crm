<?php

namespace App\Exports;

use App\Models\Patrol;
use App\Models\ShiftDate;
use App\Models\CheckpointScan;
use App\Models\PatrolMedia;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class PatrolsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    protected $shiftDateId;

    public function __construct($shiftDateId)
    {
        $this->shiftDateId = $shiftDateId;
    }

    public function collection()
    {
        return Patrol::where('shift_id', $this->shiftDateId)
            ->with(['shift.shift.site', 'shift.staff'])
            ->orderBy('start_time', 'asc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Patrol Name',
            'Site',
            'Start Time',
            'Started At',
            'Completed At',
            'Total Checkpoints',
            'Completed Checkpoints',
            'Issues Reported',
            'Status',
            'Duration (minutes)',
            'Guard',
            'Scans',
            'Media Files',
            'Notes',
        ];
    }

    public function map($patrol): array
    {
        $scans = CheckpointScan::where('patrol_id', $patrol->id)->count();
        $media = PatrolMedia::where('patrol_id', $patrol->id)->get();
        
        $mediaLinks = $media->map(function($m) {
            return url($m->file_path);
        })->join(', ');

        $duration = null;
        if ($patrol->started_at && $patrol->completed_at) {
            $start = Carbon::parse($patrol->started_at);
            $end = Carbon::parse($patrol->completed_at);
            $duration = $end->diffInMinutes($start);
        }

        return [
            $patrol->name,
            $patrol->shift->shift->site->site_name ?? 'N/A',
            Carbon::parse($patrol->start_time)->format('d-m-Y H:i'),
            $patrol->started_at ? Carbon::parse($patrol->started_at)->format('d-m-Y H:i') : 'Not started',
            $patrol->completed_at ? Carbon::parse($patrol->completed_at)->format('d-m-Y H:i') : 'Not completed',
            $patrol->total_checkpoints,
            $patrol->completed_checkpoints,
            $patrol->issues_reported,
            ucfirst(str_replace('_', ' ', $patrol->status)),
            $duration ?? 'N/A',
            $patrol->shift->staff ? $patrol->shift->staff->first_name . ' ' . $patrol->shift->staff->last_name : 'Unassigned',
            $scans,
            $mediaLinks ?: 'No media',
            $patrol->notes ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }

    public function title(): string
    {
        return 'Patrols';
    }
}
