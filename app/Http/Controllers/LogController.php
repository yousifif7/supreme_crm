<?php

namespace App\Http\Controllers;

use App\Models\Log;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request)
    {
        // DataTables sends AJAX for paging/searching/sorting; render the shell on first hit.
        if ($request->ajax()) {
            return $this->datatable($request);
        }

        return view('logs.index');
    }

    /**
     * Server-side DataTables endpoint. Returns only the slice the table needs,
     * keeping memory + payload sane even when the logs table has millions of rows.
     */
    protected function datatable(Request $request)
    {
        $draw   = (int) $request->input('draw', 1);
        $start  = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 25);
        if ($length <= 0 || $length > 500) {
            $length = 25; // hard cap to avoid `length=-1` (DataTables "All") hammering the DB
        }

        $searchValue = trim((string) data_get($request->input('search'), 'value', ''));
        $fromDate    = $request->input('from_date');
        $toDate      = $request->input('to_date');

        // Map DataTables column index -> DB column for ordering.
        $columnMap = [
            0 => 'user_name',
            1 => 'action',
            2 => 'description',
            3 => 'created_at',
        ];
        $orderColIdx = (int) data_get($request->input('order.0'), 'column', 3);
        $orderDir    = strtolower((string) data_get($request->input('order.0'), 'dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $orderCol    = $columnMap[$orderColIdx] ?? 'created_at';

        $base = Log::query();
        $recordsTotal = $base->count();

        $filtered = Log::query();

        if ($fromDate) {
            $filtered->whereDate('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $filtered->whereDate('created_at', '<=', $toDate);
        }

        if ($searchValue !== '') {
            $like = '%' . $searchValue . '%';
            $filtered->where(function ($q) use ($like) {
                $q->where('user_name', 'like', $like)
                  ->orWhere('action', 'like', $like)
                  ->orWhere('description', 'like', $like);
            });
        }

        $recordsFiltered = $filtered->count();

        $rows = $filtered
            ->orderBy($orderCol, $orderDir)
            ->offset($start)
            ->limit($length)
            ->get(['id', 'user_name', 'action', 'description', 'created_at']);

        $data = $rows->map(function ($log) {
            return [
                'user_name'   => $log->user_name,
                'action'      => $log->action,
                'description' => $log->description,
                'created_at'  => optional($log->created_at)->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }
}
