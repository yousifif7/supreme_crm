<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\Site;
use App\Models\Subcontractor;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index()
    {
        return view('shifts.index');
    }
    public function scheduling()
    {
        $shifts = Shift::all();
        $clients = Client::all();
        $sites = Site::all();
        $staffs = Employee::all();
        $subcontractors = Subcontractor::all();
        return view('security_boards.scheduling', compact('shifts', 'clients', 'sites', 'staffs', 'subcontractors'));
    }
    public function worker_calendar()
    {
        return view('security_boards.worker_calendar');
    }
    public function site_calendar()
    {
        return view('security_boards.site_calendar');
    }
    public function today_rota()
    {
        return view('security_boards.today_rota');
    }
}
