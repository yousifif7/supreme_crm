<?php

namespace App\Http\Controllers\API;

use Notify;
use App\Models\Employee;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\IncidentMedia;
use App\Models\IncidentPerson;
use App\Models\IncidentReport;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class IncidentReportController extends Controller
{
    //
    public function store(Request $request)
    {
        // Validate input
        $data = $request->validate([
            'shift_id' => 'required',
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
            'police_notified' => 'required',
            'police_reference' => 'nullable|string',
            'immediate_action_taken' => 'nullable|string',
        ]);

        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        $data['police_notified'] = filter_var($request->input('police_notified'), FILTER_VALIDATE_BOOLEAN);
        // Create Incident Report
        $report = IncidentReport::create([
            'user_id' => $user->id,
            'shift_id' => $data['shift_id'],
            'category' => $data['category'],
            'severity' => $data['severity'],
            'title' => $data['title'],
            'description' => $data['description'],
            'location' => $data['location'],
            'police_notified' => $data['police_notified'] ? 1 : 0,
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

        // Notifications
        Notify::toDashboard(
            null,
            'alert',
            'Incident report',
            'Incident report by ' . $employee->fore_name . ' ' . $employee->sur_name . ' in shift #' . $data['shift_id'],
            '/incident_report'
        );

        Notification::create([
            'user_id' => Auth::id(),
            'employee_id' => null,
            'type' => 'alert',
            'title' => 'Incident Report',
            'message' => 'You have submitted a Inicident report successfully',
        ]);

        send_push_notification(
            $user->id,
            'Incident report',
            'You have submitted an incident report successfully.',
            ['employee' => $employee->id],
        );

        return response()->json([
            'message' => 'Incident report created successfully',
            'incident_id' => $report->id
        ], 201);
    }


    public function index(Request $request)
    {
        $query = IncidentReport::with(['media', 'people'])
            ->latest('created_at')
            ->where('user_id', Auth::id());

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $reports = $query->paginate($request->query('limit', 10));

        $reports->getCollection()->transform(function ($incident) {
            $location = $incident->location ?? [];

            $latitude = $location['latitude'] ?? null;
            $longitude = $location['longitude'] ?? null;
            $address = $location['address'] ?? null;

            return [
                'id' => $incident->id,
                'user_id' => $incident->user_id,
                'shift_id' => $incident->shift_id,
                'category' => $incident->category,
                'severity' => $incident->severity,
                'title' => $incident->title,
                'description' => $incident->description,
                'location' => $location, // now it’s an array, not a string
                'latitude' => $latitude,
                'longitude' => $longitude,
                'address' => $address,
                'humanized_address' => $address
                    ? $address
                    : ($latitude && $longitude ? "Lat: {$latitude}, Lng: {$longitude}" : null),
                'police_notified' => $incident->police_notified,
                'police_reference' => $incident->police_reference,
                'immediate_action_taken' => $incident->immediate_action_taken,
                'status' => $incident->status,
                'formatted_address' => $incident->formatted_address,
                'media' => $incident->media->map(fn($m) => $m->file_url) ?? [],
                'people' => $incident->people ?? [],
                'created_at' => $incident->created_at,
                'updated_at' => $incident->updated_at,
            ];
        });

        return response()->json([
            'incidents' => $reports
        ]);
    }

    public function update(Request $request, $id)
    {
        $report = IncidentReport::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

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

        // Update report
        $report->update([
            'shift_id' => $data['shift_id'],
            'category' => $data['category'],
            'severity' => $data['severity'],
            'title' => $data['title'],
            'description' => $data['description'],
            'location' => json_encode($data['location']),
            'police_notified' => $data['police_notified'],
            'police_reference' => $data['police_reference'] ?? null,
            'immediate_action_taken' => $data['immediate_action_taken'] ?? null,
        ]);

        // Helper for saving media files
        $saveMedia = function ($files, $type) use ($report) {
            foreach ($files ?? [] as $file) {
                $filePath = null;

                if ($file instanceof \Illuminate\Http\UploadedFile) {
                    $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('incident_media'), $fileName);
                    $filePath = 'incident_media/' . $fileName;
                } elseif (is_string($file) && preg_match('/^data:/', $file)) {
                    $fileData = preg_replace('#^data:\w+/\w+;base64,#i', '', $file);
                    $extension = 'png';
                    if (preg_match('/^data:(\w+\/\w+);base64,/', $file, $matches)) {
                        $mime = $matches[1];
                        $extMap = [
                            'image/jpeg' => 'jpg',
                            'image/png' => 'png',
                            'image/gif' => 'gif',
                            'video/mp4' => 'mp4',
                            'video/avi' => 'avi',
                            'application/pdf' => 'pdf',
                        ];
                        $extension = $extMap[$mime] ?? 'png';
                    }
                    if (!file_exists(public_path('incident_media'))) {
                        mkdir(public_path('incident_media'), 0755, true);
                    }
                    $fileName = time() . '_' . uniqid() . '.' . $extension;
                    file_put_contents(public_path('incident_media/' . $fileName), base64_decode($fileData));
                    $filePath = 'incident_media/' . $fileName;
                } else {
                    continue;
                }

                IncidentMedia::create([
                    'incident_report_id' => $report->id,
                    'type' => $type,
                    'file_url' => $filePath,
                ]);
            }
        };

        // Save new media
        $saveMedia($data['pre_captured_media'] ?? [], 'pre_captured');
        $saveMedia($data['live_media'] ?? [], 'live');

        // Update people involved
        if (!empty($data['people_involved'])) {
            // Optional: remove old people involved if needed
            IncidentPerson::where('incident_report_id', $report->id)->delete();

            foreach ($data['people_involved'] as $person) {
                IncidentPerson::create([
                    'incident_report_id' => $report->id,
                    'name' => $person['name'],
                    'role' => $person['role'],
                    'contact' => $person['contact'] ?? null,
                    'description' => $person['description'] ?? null,
                ]);
            }
        }


        send_push_notification(
            auth::id(),
            'Incident report',
            'You have submitted an incident report successfully.',
            ['data' => $data],
        );

        return response()->json([
            'message' => 'Incident report updated successfully',
            'incident_id' => $report->id
        ], 200);
    }
}
