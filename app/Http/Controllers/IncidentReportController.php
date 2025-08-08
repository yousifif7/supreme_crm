<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DataTables\IncidentReportsDataTable;

class IncidentReportController extends Controller
{
    
    public function index(IncidentReportsDataTable $dataTable, Request $request)
    {


        return $dataTable->render('incident_reports.index');
        // view('clients.index');
    }
}
