<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use App\Models\IncidentMedia;
use App\Models\IncidentPerson;
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
    $incident = IncidentReport::with('media')->findOrFail($id);

    return response()->json([
        'id' => $incident->id,
        'title' => $incident->title,
        'category' => $incident->category,
        'severity' => $incident->severity,
        'description' => $incident->description,
        'police_notified' => (bool) $incident->police_notified,
        'location' => $incident->location ? json_decode($incident->location) : null,
        'media' => $incident->media->map(fn($m) => ['file_url' => $m->file_url]),
    ]);
}

public function update(Request $request, $id)
{
    $request->validate([
        'title' => 'required|string|max:255',
        'category' => 'required|string|max:100',
        'severity' => 'required|string|max:50',
        'description' => 'nullable|string',
        'police_notified' => 'boolean',
    ]);

    $incident = IncidentReport::findOrFail($id);
    $incident->update($request->only([
        'title', 'category', 'severity', 'description', 'police_notified'
    ]));

    return response()->json([
        'message' => 'Incident updated successfully!',
        'incident' => $incident
    ]);
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

        public function bulkdelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:incident_reports,id',
        ]);

        IncidentReport::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => 'Selected clients deleted.']);
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

    public function store(Request $request)
    {
        // Validate input
        $data = $request->validate([
            'shift_id' => 'nullable',
            'category' => 'required|in:theft,assault,fire,medical,property_damage,suspicious_activity,other',
            'severity' => 'required|in:low,medium,high,critical',
            'title' => 'required|string',
            'description' => 'required|string',
            'pre_captured_media' => 'nullable|array', // files or base64
            'live_media' => 'nullable|array', // files or base64
            'location.latitude' => 'required|numeric',
            'location.longitude' => 'required|numeric',
            'location.address' => 'required|string',
            'people_involved' => 'nullable|array',
            'people_involved.*.name' => 'required|string',
            'people_involved.*.role' => 'required|in:witness,victim,suspect,staff,visitor',
            'people_involved.*.contact' => 'nullable|string',
            'people_involved.*.description' => 'nullable|string',
            'police_notified' => 'required|boolean',
            'police_reference' => 'nullable|string',
            'immediate_action_taken' => 'nullable|string',
        ]);

        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        // Create Incident Report
        $report = IncidentReport::create([
            'user_id' => $user->id,
            'shift_id' => $data['shift_id'] ?? null,
            'category' => $data['category'],
            'severity' => $data['severity'],
            'title' => $data['title'],
            'description' => $data['description'],
            'location' => json_encode($data['location']),
            'police_notified' => $data['police_notified'],
            'police_reference' => $data['police_reference'] ?? null,
            'immediate_action_taken' => $data['immediate_action_taken'] ?? null,
        ]);

        // Helper for media files
        $handleMedia = function ($mediaArray, $type) use ($report) {
            foreach ($mediaArray ?? [] as $file) {
                $filePath = null;

                if ($file instanceof \Illuminate\Http\UploadedFile) {
                    $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path("incidents/$type"), $fileName);
                    $filePath = "incidents/$type/$fileName";
                } elseif (is_string($file) && preg_match('/^data:/', $file)) {
                    $fileData = preg_replace('/^data:\w+\/\w+;base64,/', '', $file);
                    $extension = 'png';
                    if (preg_match('/^data:(\w+\/\w+);base64,/', $file, $matches)) {
                        $mime = $matches[1];
                        $extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'video/mp4' => 'mp4', 'video/avi' => 'avi', 'application/pdf' => 'pdf'];
                        $extension = $extMap[$mime] ?? 'png';
                    }
                    if (!file_exists(public_path("incidents/$type"))) {
                        mkdir(public_path("incidents/$type"), 0755, true);
                    }
                    $fileName = time() . '_' . uniqid() . '.' . $extension;
                    file_put_contents(public_path("incidents/$type/$fileName"), base64_decode($fileData));
                    $filePath = "incidents/$type/$fileName";
                } else {
                    continue;
                }

                IncidentMedia::create([
                    'incident_report_id' => $report->id,
                    'type' => $type === 'live_media' ? 'live' : 'pre_captured',
                    'file_url' => $filePath,
                ]);
            }
        };

        // Save media files
        $handleMedia($data['live_media'] ?? [], 'live_media');
        $handleMedia($data['pre_captured_media'] ?? [], 'pre_captured_media');

        // Save people involved
        foreach ($data['people_involved'] ?? [] as $person) {
            IncidentPerson::create([
                'incident_report_id' => $report->id,
                'name' => $person['name'],
                'role' => $person['role'],
                'contact' => $person['contact'] ?? null,
                'description' => $person['description'] ?? null,
            ]);
        }

        return response()->json([
            'message' => 'Incident report created successfully',
            'incident_id' => $report->id
        ], 201);
    }
}
