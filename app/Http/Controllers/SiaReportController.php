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
        // Build a summary table grouped by run_id (show run-level status counts)
        $runs = SiaCheckReport::query()
            ->select([
                'run_id',
                DB::raw('MIN(checked_at) as run_date'),
                DB::raw('COUNT(*) as total_scanned'),
                DB::raw('SUM(CASE WHEN status_after = "Active" THEN 1 ELSE 0 END) as active'),
                DB::raw('SUM(CASE WHEN status_after = "Inactive" THEN 1 ELSE 0 END) as inactive'),
                DB::raw('SUM(CASE WHEN status_after = "Revoked" THEN 1 ELSE 0 END) as revoked'),
                DB::raw('SUM(CASE WHEN error IS NOT NULL THEN 1 ELSE 0 END) as errors')
            ])
            ->groupBy('run_id')
            ->orderByDesc('run_date')
            ->paginate(25);

        // Temporarily log the query to debug the admin scope
        $query = SiaCheckReport::query()
            ->select([
                'run_id',
                DB::raw('MIN(checked_at) as run_date'),
                DB::raw('COUNT(*) as total_scanned'),
                DB::raw('SUM(CASE WHEN status_after = "Active" THEN 1 ELSE 0 END) as active'),
                DB::raw('SUM(CASE WHEN status_after = "Inactive" THEN 1 ELSE 0 END) as inactive'),
                DB::raw('SUM(CASE WHEN status_after = "Revoked" THEN 1 ELSE 0 END) as revoked'),
                DB::raw('SUM(CASE WHEN error IS NOT NULL THEN 1 ELSE 0 END) as errors')
            ])
            ->groupBy('run_id')
            ->orderByDesc('run_date');
            
        \Illuminate\Support\Facades\Log::info('SIA Report Query: ' . $query->toSql());
        \Illuminate\Support\Facades\Log::info('SIA Report Bindings: ' . json_encode($query->getBindings()));
        
        $runs = $query->paginate(25);

        return view('reports.sia_reports', compact('runs'));
    }

    /**
     * Show: detailed view of a single run.
     */
    public function show(Request $request, string $runId)
    {
        // Base query for this run
        $baseQuery = SiaCheckReport::query()->where('run_id', $runId);

        $allEntries = (clone $baseQuery)->orderBy('checked_at')->get();

        if ($allEntries->isEmpty()) {
            abort(404, 'Run not found');
        }

        $runDate = $allEntries->first()->checked_at;

        // Summary stats for the run header (calculated from full run regardless of filters)
        $stats = [
            'total_scanned' => $allEntries->count(),
            'active'        => $allEntries->where('status_after', 'Active')->count(),
            'inactive'      => $allEntries->where('status_after', 'Inactive')->count(),
            'revoked'       => $allEntries->where('status_after', 'Revoked')->count(),
            'errors'        => $allEntries->whereNotNull('error')->count(),
        ];

        // Apply optional search filter (employee name or licence)
        $q = trim((string)$request->input('q', ''));
        $perPage = (int)$request->input('per_page', 50);

        // Optional status filter driven by clicking a summary card.
        // Accepts: active, inactive, revoked, errors (anything else = no filter).
        $status = strtolower(trim((string)$request->input('status', '')));

        $query = SiaCheckReport::query()->where('run_id', $runId);
        if ($q !== '') {
            $query->where(function ($r) use ($q) {
                $r->where('employee_name', 'like', "%{$q}%")
                  ->orWhere('sia_licence', 'like', "%{$q}%");
            });
        }

        if (in_array($status, ['active', 'inactive', 'revoked'], true)) {
            $query->where('status_after', ucfirst($status));
        } elseif ($status === 'errors') {
            $query->whereNotNull('error');
        } else {
            $status = ''; // normalize any unexpected value
        }

        $perPage = max(10, min(200, $perPage));

        $entries = $query->orderBy('checked_at')->paginate($perPage)->withQueryString();

        return view('reports.sia_report_detail', compact('entries', 'runId', 'runDate', 'stats', 'q', 'status'));
    }

    /**
     * Download a run as CSV (no extra package needed).
     */
    public function downloadCsv(string $runId)
    {
        $entries = SiaCheckReport::query()->where('run_id', $runId)->orderBy('checked_at')->get();

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


    /**
     * Delete all SIA check report rows that match the provided run_id.
     * This is the explicit delete action used by the reports UI.
     */
    public function deleteRun(string $runId)
    {
        $deleted = SiaCheckReport::query()->where('run_id', $runId)->delete();

        if (request()->wantsJson()) {
            return response()->json(['deleted' => $deleted]);
        }

        return redirect()->route('reports.sia')->with('status', "$deleted reports deleted for run_id: $runId");
    }

    /**
     * Debug endpoint: return counts and a sample row for a run_id.
     * Protected by auth in routes; remove when debugging is complete.
     */
    public function debugRun(string $runId)
    {
        $count = SiaCheckReport::query()->where('run_id', $runId)->count();
        $sample = SiaCheckReport::query()->where('run_id', $runId)->first();

        return response()->json([
            'run_id' => $runId,
            'count' => $count,
            'sample' => $sample,
        ]);
    }
}
