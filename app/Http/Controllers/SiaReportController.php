<?php

namespace App\Http\Controllers;

use App\Models\SiaCheckReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class SiaReportController extends Controller
{
    /**
     * Index: list of all runs (one row per run_id with aggregated stats).
     */
    public function index(Request $request)
    {
        // Build a summary table grouped by run_id
        $runs = SiaCheckReport::select(
                'run_id',
                DB::raw('MIN(checked_at) as run_date'),
                DB::raw('COUNT(*) as total_scanned'),
                DB::raw('SUM(changed) as total_changed'),
                DB::raw('SUM(CASE WHEN status_after = "Active" AND changed = 1 THEN 1 ELSE 0 END) as activated'),
                DB::raw('SUM(CASE WHEN status_after = "Inactive" AND changed = 1 THEN 1 ELSE 0 END) as deactivated'),
                DB::raw('SUM(CASE WHEN error IS NOT NULL THEN 1 ELSE 0 END) as errors')
            )
            ->groupBy('run_id')
            ->orderByDesc('run_date')
            ->paginate(25);

        return view('reports.sia_reports', compact('runs'));
    }

    /**
     * Show: detailed view of a single run.
     */
    public function show(string $runId)
    {
        $entries = SiaCheckReport::where('run_id', $runId)
            ->orderBy('checked_at')
            ->get();

        if ($entries->isEmpty()) {
            abort(404, 'Run not found');
        }

        $runDate = $entries->first()->checked_at;

        // Summary stats for the run header
        $stats = [
            'total_scanned' => $entries->count(),
            'total_changed' => $entries->where('changed', true)->count(),
            'activated'     => $entries->where('changed', true)->where('status_after', 'Active')->count(),
            'deactivated'   => $entries->where('changed', true)->where('status_after', 'Inactive')->count(),
            'errors'        => $entries->whereNotNull('error')->count(),
        ];

        return view('reports.sia_report_detail', compact('entries', 'runId', 'runDate', 'stats'));
    }

    /**
     * Download a run as CSV (no extra package needed).
     */
    public function downloadCsv(string $runId)
    {
        $entries = SiaCheckReport::where('run_id', $runId)->orderBy('checked_at')->get();

        if ($entries->isEmpty()) {
            abort(404, 'Run not found');
        }

        $runDate = $entries->first()->checked_at->format('Y-m-d_H-i');
        $filename = "sia_report_{$runDate}.csv";

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($entries) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Employee ID', 'Employee Name', 'SIA Licence',
                'Status Before', 'Status After', 'Changed', 'Error', 'Checked At',
            ]);

            foreach ($entries as $row) {
                fputcsv($handle, [
                    $row->employee_id,
                    $row->employee_name,
                    $row->sia_licence,
                    $row->status_before ?? '',
                    $row->status_after ?? '',
                    $row->changed ? 'Yes' : 'No',
                    $row->error ?? '',
                    $row->checked_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
