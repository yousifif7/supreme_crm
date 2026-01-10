<?php

namespace App\Http\Controllers\API;

use Notify;
use Carbon\Carbon;
use App\Models\Patrol;
use App\Models\Document;
use App\Models\ShiftDate;
use App\Models\Employee;
use App\Models\CheckCall;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Services\FileCompressor;
use Illuminate\Support\Facades\Log;
use App\Helpers\Logger;

class DocumentAPIController extends Controller
{
    // Map document types to their corresponding expiry date fields in employees table
    protected $expiryFields = [
        'sia_licence_file' => 'sia_expiry',
        'passport_file' => 'passport_expiry',
        'driving_licence_file' => 'driving_licence_expiry',
        // Add other mappings as needed
    ];

    public function upload(Request $request)
    {
        $request->validate([
            'document_type' => 'required|in:sia_licence_file,passport_file,proof_of_address_file,ni_letter_file,first_aid_certificate_file,act_certificate_file,driving_licence_file,other',
            'file' => 'required|array',
            'file.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB per file
            'expiry_date' => 'nullable|date', // Only required for fixed documents
            'description' => 'nullable|string',
        ]);

        $documents = [];
        $destinationPath = public_path('documents');

        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        $user = $request->user();
        $employee = $user->employee;

        foreach ($request->file('file') as $file) {
            $fileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
            $file->move($destinationPath, $fileName);

            $filePath = 'documents/' . $fileName;
            try {
                (new FileCompressor())->compress($destinationPath . '/' . $fileName);
            } catch (\Exception $e) {
                Log::error('File compression failed for DocumentAPI upload: ' . $e->getMessage());
            }

            $document = Document::create([
                'user_id' => $user->id,
                'document_type' => $request->document_type,
                'file_path' => $filePath,
                'expiry_date' => $request->expiry_date,
                'description' => $request->description,
                'status' => 'pending',
            ]);

            try {
                Logger::log($document, 'Created', 'Document uploaded via API');
            } catch (\Exception $e) {
                Log::error('Logger failed for Document upload: ' . $e->getMessage());
            }

            $documents[] = $document;

            // Sync to employee table
            if ($employee) {
                if ($request->document_type === 'other') {
                    // Add to additional_files array
                    $additionalFiles = $employee->additional_files ?? [];
                    $additionalFiles[] = $fileName;
                    $employee->update([
                        'additional_files' => $additionalFiles
                    ]);
                } else {
                    // Fixed documents - update file path
                    $updateData = [
                        $request->document_type => basename($filePath),
                    ];
                    
                    // Update expiry date if provided and field mapping exists
                    if ($request->expiry_date && isset($this->expiryFields[$request->document_type])) {
                        $expiryField = $this->expiryFields[$request->document_type];
                        $updateData[$expiryField] = $request->expiry_date;
                    }
                    
                    $employee->update($updateData);
                }

                // Send notification (dashboard / push)
                Notify::toDashboard(
                    null,
                    'alert',
                    'Document Uploaded',
                    $request->document_type . ' Document uploaded by ' . $employee->fore_name . ' ' . $employee->sur_name,
                    '/employees#' . $employee->id,
                );

            }
        }

        return response()->json([
            'documents' => $documents,
            'uploaded_at' => now(),
        ]);
    }

    // 8. Get User Documents
    public function index(Request $request)
    {
        $user = $request->user();


        $documents = $user->documents()->latest('created_at')->get()->map(function ($doc) {
            return [
                'id' => $doc->id,
                'type' => $doc->document_type,
                'filename' => basename($doc->file_path),
                'status' => $doc->status,
                'description' => $doc->description,
                'expiry_date' => $doc->expiry_date,
                'uploaded_at' => $doc->created_at,
                'admin_comments' => $doc->admin_comments,
            ];
        });

        return response()->json(['documents' => $documents]);
    }

    public function alerts(Request $request)
    {
        $user = $request->user();
        Log::info("Alerts endpoint called", ['user_id' => $user->id, 'user_name' => $user->name ?? $user->first_name]);
        
        $alerts = [];
        $cooldownMinutes = 15; // show alerts for 15 minutes after first shown
        $patrolMarkDelay = 10; // minutes after detection before marking patrol as missed
        $checkcallMarkDelay = 5; // minutes after detection before marking checkcall as missed
        $visibilityMinutes = 5; // keep recent alerts visible for this many minutes

        /**
         * 1. Document Expiry Alerts (latest per type)
         */
        $documentTypes = [
            'sia_licence_file',
            'passport_file',
            'proof_of_address_file',
            'ni_letter_file',
            'first_aid_certificate_file',
            'act_certificate_file',
            'driving_licence_file',
        ];

        foreach ($documentTypes as $type) {
            $doc = $user->documents()
                ->where('document_type', $type)
                ->orderByDesc('expiry_date')
                ->first();

            $expiryDate = null;
            // Prefer expiry date from the document row
            if ($doc && !empty($doc->expiry_date)) {
                try {
                    $expiryDate = Carbon::parse($doc->expiry_date);
                    Log::info("Document expiry check for user {$user->id}", [
                        'type' => $type,
                        'doc_id' => $doc->id,
                        'expiry_date' => $doc->expiry_date,
                        'parsed_date' => $expiryDate->toDateString(),
                        'days_until_expiry' => now()->diffInDays($expiryDate, false)
                    ]);
                } catch (\Exception $e) {
                    $expiryDate = null;
                    Log::warning("Failed to parse document expiry date", ['type' => $type, 'doc_id' => $doc->id, 'expiry_date' => $doc->expiry_date]);
                }
            }

            // Fallback: check employee fixed-field mappings (some uploads update employee fields)
            if (!$expiryDate && $user->employee && isset($this->expiryFields[$type])) {
                $empField = $this->expiryFields[$type];
                if (!empty($user->employee->$empField)) {
                    try {
                        $expiryDate = Carbon::parse($user->employee->$empField);
                        Log::info("Document expiry from employee field for user {$user->id}", [
                            'type' => $type,
                            'field' => $empField,
                            'expiry_date' => $user->employee->$empField,
                            'parsed_date' => $expiryDate->toDateString()
                        ]);
                    } catch (\Exception $e) {
                        $expiryDate = null;
                    }
                }
            }

            if ($expiryDate) {
                // Include only documents expiring today or within the next 30 days
                if ($expiryDate->isToday() || ($expiryDate->isFuture() && $expiryDate->lte(now()->addDays(30)))) {
                    $daysRemaining = (int) now()->diffInDays($expiryDate); // 0 for today, positive for future

                    if ($expiryDate->isToday()) {
                        $message = "Your {$type} expires today ({$expiryDate->toDateString()}).";
                    } else {
                        $message = "Your {$type} is about to expire in {$daysRemaining} day(s) on {$expiryDate->toDateString()}.";
                    }

                    $alert = [
                        'type' => 'document_expiry',
                        'document_id' => $doc ? $doc->id : null,
                        'title' => 'Document Expiry Alert',
                        'message' => $message,
                        'expiry_date' => $expiryDate->toDateString(),
                        'days_remaining' => $daysRemaining,
                    ];

                    Log::info("Document expiry alert created for user {$user->id}", [
                        'type' => $type,
                        'message' => $message,
                        'days_remaining' => $daysRemaining
                    ]);

                    $docIdPart = $doc ? $doc->id : 'type_' . $type;
                    $cacheKey = "alerts:document_expiry:user:{$user->id}:doc:{$docIdPart}";
                    if (!Cache::has($cacheKey)) {
                        // first time: persist a marker for cooldown duration
                        Cache::put($cacheKey, true, now()->addMinutes($cooldownMinutes));
                        $alert['_first_shown'] = true;
                        // Dashboard notification for admins (user id 1)
                        try {
                            $emp = $user->employee;
                            $empName = $emp ? trim(($emp->fore_name ?? '') . ' ' . ($emp->sur_name ?? '')) : ($user->first_name ?? ($user->name ?? 'Employee'));
                            $adminTitle = "Expiring document for {$empName}";
                            $adminMessage = "{$empName}'s {$type} expires in {$daysRemaining} day(s) on {$expiryDate->toDateString()}.";
                            $actionUrl = '/employees#' . ($emp ? $emp->id : $user->id);
                            Notify::toDashboard(1, 'alert', $adminTitle, $adminMessage, $actionUrl);
                        } catch (\Exception $e) {
                            Log::warning('Dashboard notify failed for document_expiry: ' . $e->getMessage());
                        }
                    } else {
                        // still within cooldown window — show but mark as not new
                        $alert['_first_shown'] = false;
                    }

                    $alerts[] = $alert;
                } else {
                    Log::info("Document expiry excluded (outside window) for user {$user->id}", [
                        'type' => $type,
                        'expiry_date' => $expiryDate->toDateString(),
                        'days_until_expiry' => now()->diffInDays($expiryDate, false)
                    ]);
                }
            }
        }
        /**
         * 2. Patrol Alerts (5 min notification / 50 min missed)
         */
        // Eager-load shift to avoid N+1 and guard against missing shift records
        $patrols = Patrol::whereHas('shift', fn($q) => $q->where('staff_id', $user->id))
            ->where('status', 'pending')
            ->with('shift')
            ->get();

        foreach ($patrols as $patrol) {
            $shift = $patrol->shift;

            // skip if shift unexpectedly missing or not booked on
            if(!$shift || $shift->is_assign != 3){
                continue;
            }

            if (empty($patrol->start_time)) {
                continue;
            }

            $start = Carbon::parse($patrol->start_time);
            $diff = now()->diffInMinutes($start, false); // negative if past

            // 5-min warning
            if ($diff <= 5 && $diff > 0) {
                $alert = [
                    'type' => 'patrol_warning',
                    'patrol_id' => $patrol->id,
                    'title' => 'Upcoming Patrol',
                    'message' => 'Patrol starting soon: ' . $patrol->name,
                    'scheduled_time' => $patrol->start_time,
                ];

                $cacheKey = "alerts:patrol_warning:user:{$user->id}:patrol:{$patrol->id}";
                if (!Cache::has($cacheKey)) {
                    Cache::put($cacheKey, true, now()->addMinutes($cooldownMinutes));
                    $alert['_first_shown'] = true;
                    // Dashboard notify for admin
                    try {
                        $emp = $user->employee;
                        $empName = $emp ? trim(($emp->fore_name ?? '') . ' ' . ($emp->sur_name ?? '')) : ($user->first_name ?? ($user->name ?? 'Employee'));
                        $adminTitle = "Upcoming patrol for {$empName}";
                        $adminMessage = "{$empName} has an upcoming patrol '{$patrol->name}' scheduled at {$patrol->start_time}.";
                        $actionUrl = '/shift-dates/' . $patrol->shift->id.'/view';
                        Notify::toDashboard(1, 'alert', $adminTitle, $adminMessage, $actionUrl);
                    } catch (\Exception $e) {
                        Log::warning('Dashboard notify failed for patrol_warning: ' . $e->getMessage());
                    }
                } else {
                    $alert['_first_shown'] = false;
                }

                $alerts[] = $alert;
            }

            // 50-min missed (compute canonical mark time from start_time + threshold + delay)
            if ($diff <= -50) {
                $markerKey = "missed_marker:patrol:user:{$user->id}:patrol:{$patrol->id}";

                // canonical mark time = patrol start + 50 minutes (threshold) + patrolMarkDelay
                $missedThreshold = Carbon::parse($patrol->start_time)->addMinutes(50);
                $markAtCarbon = $missedThreshold->copy()->addMinutes($patrolMarkDelay);

                // If mark time already passed, mark immediately
                if (now()->gte($markAtCarbon)) {
                    try {
                        $patrol->update(['status' => 'missed']);
                    } catch (\Exception $e) {
                        Log::error('Failed to mark patrol missed', ['patrol_id' => $patrol->id, 'error' => $e->getMessage()]);
                    }
                    Cache::forget($markerKey);

                    $alertType = 'patrol_missed';
                    $alertMessage = 'You missed a patrol: ' . $patrol->name;
                } else {
                    // ensure a persistent marker exists until after the mark time
                    if (!Cache::has($markerKey)) {
                        $secondsUntilMark = max(1, $markAtCarbon->diffInSeconds(now()));
                        // keep marker a bit longer than the mark time so the next request can detect it
                        Cache::put($markerKey, $markAtCarbon->timestamp, now()->addSeconds($secondsUntilMark + 60));
                    }
                    $markAt = Cache::get($markerKey);

                    $alertType = 'patrol_missed_pending';
                    $remaining = $markAt ? max(0, (int)$markAt - now()->timestamp) : ($patrolMarkDelay * 60);
                    $alertMessage = 'Patrol appears missed and will be marked in ' . gmdate('i:s', $remaining) . ' unless handled: ' . $patrol->name;
                }

                $alert = [
                    'type' => $alertType,
                    'patrol_id' => $patrol->id,
                    'title' => ($alertType === 'patrol_missed') ? 'Missed Patrol' : 'Potential Missed Patrol',
                    'message' => $alertMessage,
                    'scheduled_time' => $patrol->start_time,
                ];

                $cacheKey = "alerts:patrol_missed:user:{$user->id}:patrol:{$patrol->id}";
                if (!Cache::has($cacheKey)) {
                    Cache::put($cacheKey, true, now()->addMinutes($cooldownMinutes));
                    $alert['_first_shown'] = true;
                    // Dashboard notify for admin
                    try {
                        $emp = $user->employee;
                        $empName = $emp ? trim(($emp->fore_name ?? '') . ' ' . ($emp->sur_name ?? '')) : ($user->first_name ?? ($user->name ?? 'Employee'));
                        $adminTitle = ($alertType === 'patrol_missed') ? "Missed patrol by {$empName}" : "Potential missed patrol for {$empName}";
                        $adminMessage = ($alertType === 'patrol_missed') ? "{$empName} missed patrol '{$patrol->name}'." : "{$empName} appears to have missed patrol '{$patrol->name}' and it will be marked soon unless handled.";
                        $actionUrl = '/shift-dates/' . $patrol->shift->id.'/view';
                        Notify::toDashboard(1, 'alert', $adminTitle, $adminMessage, $actionUrl);
                    } catch (\Exception $e) {
                        Log::warning('Dashboard notify failed for patrol_missed: ' . $e->getMessage());
                    }
                } else {
                    $alert['_first_shown'] = false;
                }

                $alerts[] = $alert;
            }
        }

        /**
         * 3. Check Call Alerts (5 min notification / 15 min missed)
         */
        // Eager-load shiftDate to avoid N+1 and guard against missing relations
        $checkCalls = CheckCall::whereHas('shiftDate', fn($q) => $q->where('staff_id', $user->id))
            ->where('status', 'pending')
            ->with('shiftDate')
            ->get();

        foreach ($checkCalls as $checkCall) {
            // guard against missing relation or missing scheduled time
            $shiftDate = $checkCall->shiftDate;

            // skip if shift unexpectedly missing or not booked on
            if(!$shiftDate || $shiftDate->is_assign != 3){
                continue;
            }

            if (empty($checkCall->scheduled_time)) {
                continue;
            }

            $scheduled = Carbon::parse($checkCall->scheduled_time);
            $diff = now()->diffInMinutes($scheduled, false);

            // 5-min warning
            if ($diff <= 5 && $diff > 0) {
                $alert = [
                    'type' => 'checkcall_warning',
                    'checkcall_id' => $checkCall->id,
                    'title' => 'Upcoming Check Call',
                    'message' => 'Check call coming up: ' . $checkCall->name,
                    'scheduled_time' => $checkCall->scheduled_time,
                ];

                $cacheKey = "alerts:checkcall_warning:user:{$user->id}:checkcall:{$checkCall->id}";
                if (!Cache::has($cacheKey)) {
                    Cache::put($cacheKey, true, now()->addMinutes($cooldownMinutes));
                    $alert['_first_shown'] = true;
                    // Dashboard notify for admin
                    try {
                        $emp = $user->employee;
                        $empName = $emp ? trim(($emp->fore_name ?? '') . ' ' . ($emp->sur_name ?? '')) : ($user->first_name ?? ($user->name ?? 'Employee'));
                        $adminTitle = "Upcoming check call for {$empName}";
                        $adminMessage = "{$empName} has an upcoming check call '{$checkCall->name}' scheduled at {$checkCall->scheduled_time}.";
                        $actionUrl = '/shift-dates/' . $checkCall->shift_date_id.'/view';
                        Notify::toDashboard(1, 'alert', $adminTitle, $adminMessage, $actionUrl);
                    } catch (\Exception $e) {
                        Log::warning('Dashboard notify failed for checkcall_warning: ' . $e->getMessage());
                    }
                } else {
                    $alert['_first_shown'] = false;
                }

                $alerts[] = $alert;
            }

            // 15-min missed (compute canonical mark time from scheduled_time + threshold + delay)
            if ($diff <= -15) {
                $markerKey = "missed_marker:checkcall:user:{$user->id}:checkcall:{$checkCall->id}";

                // canonical mark time = scheduled time + 15 minutes (threshold) + checkcallMarkDelay
                $missedThreshold = Carbon::parse($checkCall->scheduled_time)->addMinutes(15);
                $markAtCarbon = $missedThreshold->copy()->addMinutes($checkcallMarkDelay);

                if (now()->gte($markAtCarbon)) {
                    try {
                        $checkCall->update(['status' => 'missed']);
                    } catch (\Exception $e) {
                        Log::error('Failed to mark checkcall missed', ['checkcall_id' => $checkCall->id, 'error' => $e->getMessage()]);
                    }
                    Cache::forget($markerKey);

                    $alertType = 'checkcall_missed';
                    $alertMessage = 'You missed a check call: ' . $checkCall->name;
                } else {
                    if (!Cache::has($markerKey)) {
                        $secondsUntilMark = max(1, $markAtCarbon->diffInSeconds(now()));
                        Cache::put($markerKey, $markAtCarbon->timestamp, now()->addSeconds($secondsUntilMark + 60));
                    }
                    $markAt = Cache::get($markerKey);

                    $alertType = 'checkcall_missed_pending';
                    $remaining = $markAt ? max(0, (int)$markAt - now()->timestamp) : ($checkcallMarkDelay * 60);
                    $alertMessage = 'Check call appears missed and will be marked in ' . gmdate('i:s', $remaining) . ' unless handled: ' . $checkCall->name;
                }

                $alert = [
                    'type' => $alertType,
                    'checkcall_id' => $checkCall->id,
                    'title' => ($alertType === 'checkcall_missed') ? 'Missed Check Call' : 'Potential Missed Check Call',
                    'message' => $alertMessage,
                    'scheduled_time' => $checkCall->scheduled_time,
                ];

                $cacheKey = "alerts:checkcall_missed:user:{$user->id}:checkcall:{$checkCall->id}";
                if (!Cache::has($cacheKey)) {
                    Cache::put($cacheKey, true, now()->addMinutes($cooldownMinutes));
                    $alert['_first_shown'] = true;
                    // Dashboard notify for admin
                    try {
                        $emp = $user->employee;
                        $empName = $emp ? trim(($emp->fore_name ?? '') . ' ' . ($emp->sur_name ?? '')) : ($user->first_name ?? ($user->name ?? 'Employee'));
                        $adminTitle = ($alertType === 'checkcall_missed') ? "Missed check call by {$empName}" : "Potential missed check call for {$empName}";
                        $adminMessage = ($alertType === 'checkcall_missed') ? "{$empName} missed check call '{$checkCall->name}'." : "{$empName} appears to have missed check call '{$checkCall->name}' and it will be marked soon unless handled.";
                        $actionUrl = '/shift-dates/' . $checkCall->shift_date_id.'/view';
                        Notify::toDashboard(1, 'alert', $adminTitle, $adminMessage, $actionUrl);
                    } catch (\Exception $e) {
                        Log::warning('Dashboard notify failed for checkcall_missed: ' . $e->getMessage());
                    }
                } else {
                    $alert['_first_shown'] = false;
                }

                $alerts[] = $alert;
            }
        }

        // Recent-alerts cache: keep last few alerts visible for $visibilityMinutes
        $recentKey = "recent_alerts:user:{$user->id}";
        $recent = Cache::get($recentKey, []);

        // Build a set of existing UIDs for quick lookup
        $existingUids = [];
        foreach ($recent as $r) {
            if (!empty($r['_uid'])) {
                $existingUids[$r['_uid']] = true;
            }
        }

        // Prepare computed alerts with stable UIDs
        $prepared = [];
        foreach ($alerts as $alert) {
            $idPart = $alert['type'] . ':' . (
                $alert['document_id'] ?? $alert['patrol_id'] ?? $alert['checkcall_id'] ?? ($alert['scheduled_time'] ?? uniqid())
            );
            $uid = md5($idPart);
            $alert['_uid'] = $uid;
            // mark if it's the first time we see this alert in this visibility window
            $alert['_first_shown'] = !isset($existingUids[$uid]);
            $prepared[$uid] = $alert;
        }

        // Merge: keep newest alerts first (prepared), then fall back to cached recent for older items
        // Use associative map by uid to deduplicate
        $mergedMap = [];
        // add prepared (newest first)
        foreach ($prepared as $uid => $alert) {
            $mergedMap[$uid] = $alert;
        }
        // append older cached ones that aren't already present
        foreach ($recent as $r) {
            if (empty($r['_uid'])) continue;
            if (!isset($mergedMap[$r['_uid']])) {
                $mergedMap[$r['_uid']] = $r;
            }
        }

        // Keep order: prepared first (as added), then remaining recent; limit to 50
        $final = array_values($mergedMap);
        if (count($final) > 50) {
            $final = array_slice($final, 0, 50);
        }

        Cache::put($recentKey, $final, now()->addMinutes($visibilityMinutes));

        // Return the merged list (newest first), strip internal uid/first_shown keys
        $result = [];
        foreach ($final as $r) {
            if (isset($r['_uid'])) unset($r['_uid']);
            if (isset($r['_first_shown'])) unset($r['_first_shown']);
            $result[] = $r;
        }

        return response()->json(['alerts' => $result]);
    }

    public function alertsCount(Request $request)
    {
        // Call the main alerts endpoint logic and count the results
        $alertsResponse = $this->alerts($request);
        $alertsData = json_decode($alertsResponse->getContent(), true);
        
        return response()->json(['count' => count($alertsData['alerts'] ?? [])]);
    }
}

