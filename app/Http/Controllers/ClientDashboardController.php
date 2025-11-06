<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Site;
use App\Models\ShiftDate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
class ClientDashboardController extends BaseController
{
    public function __construct()
    {
        $this->middleware(['auth','role:client']);
    }

    public function index(Request $request)
    {
        $clientId = Auth::id();

        $invoicesCount = Invoice::where('client_id', $clientId)->count();
        $outstanding = Invoice::where('client_id', $clientId)
            ->sum(DB::raw('net_amount'));
        $sitesCount = Site::where('client_id', $clientId)->count();
        $upcomingShifts = ShiftDate::whereHas('shift', function($q) use ($clientId) {
            $q->where('client_id', $clientId);
        })->whereDate('shift_date', '>=', now()->toDateString())
          ->orderBy('shift_date')
          ->limit(5)
          ->get();

        return view('clients.dashboard', compact('invoicesCount','outstanding','sitesCount','upcomingShifts'));
    }

    public function rota()
    {
        $clientId = Auth::id();

        $shifts = ShiftDate::with('shift.site','staff')
            ->whereHas('shift', function($q) use ($clientId) {
                $q->where('client_id', $clientId);
            })->orderBy('shift_date')->get();

        return view('clients.rota', compact('shifts'));
    }
}
