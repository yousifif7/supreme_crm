<?php

namespace App\Http\Controllers\API;

use Notify;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Models\IncidentMedia;
use App\Models\IncidentPerson;
use App\Models\IncidentReport;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class IncidentReportController extends Controller
{
    //
    public function store(Request $request)
    {
        $data = $request->validate([
            'shift_id' => 'required|exists:shifts,id',
            'category' => 'required|in:theft,assault,fire,medical,property_damage,suspicious_activity,other',
            'severity' => 'required|in:low,medium,high,critical',
            'title' => 'required|string',
            'description' => 'required|string',
            'pre_captured_media' => 'nullable|array',
            'live_media' => 'nullable|array',
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

        $report = IncidentReport::create([
            'user_id' => Auth::id(),
            'shift_id' => $data['shift_id'],
            'category' => $data['category'],
            'severity' => $data['severity'],
            'title' => $data['title'],
            'description' => $data['description'],
            'location' => json_encode($data['location']),
            'police_notified' => $data['police_notified'],
            'police_reference' => $data['police_reference'],
            'immediate_action_taken' => $data['immediate_action_taken'],
        ]);

        foreach (($data['pre_captured_media'] ?? []) as $file) {
            IncidentMedia::create([
                'incident_report_id' => $report->id,
                'type' => 'pre_captured',
                'file_url' => $file,
            ]);
        }

        foreach (($data['live_media'] ?? []) as $file) {
            IncidentMedia::create([
                'incident_report_id' => $report->id,
                'type' => 'live',
                'file_url' => $file,
            ]);
        }

        foreach (($data['people_involved'] ?? []) as $person) {
            IncidentPerson::create([
                'incident_report_id' => $report->id,
                'name' => $person['name'],
                'role' => $person['role'],
                'contact' => $person['contact'] ?? null,
                'description' => $person['description'] ?? null,
            ]);
        }

        $employee = Employee::find(Auth::id());
        Notify::toDashboard(
            $employee->id,
            'alert',
            'Incident report',
            'Incident report by ' . $employee->fore_name . ' ' . $employee->sur_name. ' In shift NO. #'. $request->shift_id,
            '#'
        );

        return response()->json([
            'message' => 'Incident report created',
            'incident_id' => $report->id
        ]);
    }

    public function index(Request $request)
    {
        $query = IncidentReport::with(['media', 'people'])
            ->where('user_id', Auth::id());

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $reports = $query->paginate($request->query('limit', 10));

        return response()->json([
            'incidents' => $reports
        ]);
    }

    public function update(Request $request, $id)
    {
        $report = IncidentReport::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // same validation as store
        $data = $request->validate([
            'shift_id' => 'required|exists:shifts,id',
            'category' => 'required|in:theft,assault,fire,medical,property_damage,suspicious_activity,other',
            'severity' => 'required|in:low,medium,high,critical',
            'title' => 'required|string',
            'description' => 'required|string',
            'pre_captured_media' => 'nullable|array',
            'live_media' => 'nullable|array',
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

        $report->update([
            'shift_id' => $data['shift_id'],
            'category' => $data['category'],
            'severity' => $data['severity'],
            'title' => $data['title'],
            'description' => $data['description'],
            'location' => json_encode($data['location']),
            'police_notified' => $data['police_notified'],
            'police_reference' => $data['police_reference'],
            'immediate_action_taken' => $data['immediate_action_taken'],
        ]);

        // Optionally add new media/people
        foreach (($data['pre_captured_media'] ?? []) as $file) {
            IncidentMedia::create([
                'incident_report_id' => $report->id,
                'type' => 'pre_captured',
                'file_url' => $file,
            ]);
        }

        foreach (($data['live_media'] ?? []) as $file) {
            IncidentMedia::create([
                'incident_report_id' => $report->id,
                'type' => 'live',
                'file_url' => $file,
            ]);
        }

        return response()->json(['message' => 'Incident updated']);
    }
}
