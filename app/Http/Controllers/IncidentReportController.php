<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IncidentReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\IncidentReportsExport;
use App\DataTables\IncidentReportsDataTable;

class IncidentReportController extends Controller
{
    
    public function index(IncidentReportsDataTable $dataTable, Request $request)
    {
        return $dataTable->render('incident_reports.index');
        // view('clients.index');
    }

    /**
     * Show details of an incident (for AJAX show modal).
     */
    public function show($id)
    {
        $report = IncidentReport::with(['media', 'people'])->findOrFail($id);
        $report->location = json_decode($report->location, true);

        return response()->json($report);
    }

    /**
     * Fetch incident for editing (prefill modal).
     */
    public function edit($id)
    {
        $report = IncidentReport::findOrFail($id);
        $report->location = json_decode($report->location, true);

        return response()->json($report);
    }

    /**
     * Update an incident.
     */
    public function update(Request $request, $id)
    {
        $report = IncidentReport::findOrFail($id);

        $data = $request->validate([
            'title' => 'required|string',
            'category' => 'required|in:theft,assault,fire,medical,property_damage,suspicious_activity,other',
            'severity' => 'required|in:low,medium,high,critical',
            'description' => 'required|string',
            'status' => 'nullable|in:pending,in_progress,resolved,closed',
        ]);

        $report->update($data);

        return response()->json(['message' => 'Incident updated successfully']);
    }

    /**
     * Delete an incident.
     */
    public function destroy($id)
    {
        $report = IncidentReport::findOrFail($id);
        $report->delete();

        return response()->json(['message' => 'Incident deleted successfully']);
    }

    public function exportIncidentPdf()
    {
        $incidents = IncidentReport::all();
        $pdf = Pdf::loadView('incident_reports.incidents_pdf', compact('incidents'));
        return $pdf->download('incidents.pdf');
    }

    public function exportIncidentExcel()
    {
        return Excel::download(new IncidentReportsExport, 'incidents.xlsx');
    }
}
