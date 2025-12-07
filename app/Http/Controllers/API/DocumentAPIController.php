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

class DocumentAPIController extends Controller
{

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
                    // Fixed documents
                    $employee->update([
                        $request->document_type => basename($filePath),
                        // Update expiry if defined
                        $this->expiryFields[$request->document_type] ?? null => $request->expiry_date
                    ]);
                }

                // Send notification (dashboard / push)
                Notify::toDashboard(
                    null,
                    'alert',
                    'Document Uploaded',
                    $request->document_type . ' Document uploaded by ' . $employee->fore_name . ' ' . $employee->sur_name,
                    '/employees#' . $employee->id,
                );

                Notification::create([
                    'user_id' => $user->id,
                    'employee_id' => null,
                    'type' => 'alert',
                    'title' => 'Document Uploaded',
                    'message' => 'You have uploaded a ' . $request->document_type . ' entry successfully',
                ]);

                send_push_notification(
                    $user->id,
                    'You uploaded a document',
                    'Your Document has been uploaded successfully.',
                    ['document_id' => $document->id],
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

            if ($doc && $doc->expiry_date) {
                $expiryDate = Carbon::parse($doc->expiry_date);

                if ($expiryDate->isFuture() && $expiryDate->lte(now()->addDays(30))) {
                    $daysRemaining = (int) now()->diffInDays($expiryDate); // always integer

                    $alert = [
                        'type' => 'document_expiry',
                        'document_id' => $doc->id,
                        'title' => 'Document Expiry Alert',
                        'message' => "Your {$doc->document_type} is about to expire in {$daysRemaining} day(s) on {$expiryDate->toDateString()}.",
                        'expiry_date' => $expiryDate->toDateString(),
                        'days_remaining' => $daysRemaining,
                    ];

                    $cacheKey = "alerts:document_expiry:user:{$user->id}:doc:{$doc->id}";
                    if (!Cache::has($cacheKey)) {
                        // first time: persist a marker for cooldown duration
                        Cache::put($cacheKey, true, now()->addMinutes($cooldownMinutes));
                        $alert['_first_shown'] = true;
                    } else {
                        // still within cooldown window — show but mark as not new
                        $alert['_first_shown'] = false;
                    }

                    $alerts[] = $alert;
                }
            }
        }
        /**
         * 2. Patrol Alerts (5 min notification / 50 min missed)
         */
        $patrols = Patrol::whereHas('shift', fn($q) => $q->where('staff_id', $user->id))
            ->where('status', 'pending')
            ->get();

        foreach ($patrols as $patrol) {
            $shift = ShiftDate::find($patrol->shift_id);

            if ($shift->is_assign == 2) {

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
                    } else {
                        $alert['_first_shown'] = false;
                    }

                    $alerts[] = $alert;
                }
            }
        }

        /**
         * 3. Check Call Alerts (5 min notification / 15 min missed)
         */
        $checkCalls = CheckCall::whereHas('shiftDate', fn($q) => $q->where('staff_id', $user->id))
            ->where('status', 'pending')
            ->get();

        foreach ($checkCalls as $checkCall) {
            if($checkCall->shiftDate->is_assign == 2){
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
                    } else {
                        $alert['_first_shown'] = false;
                    }
    
                    $alerts[] = $alert;
                }
            }
            }

        // Recent-alerts cache: keep last few alerts visible for $visibilityMinutes
        $recentKey = "recent_alerts:user:{$user->id}";
        $recent = Cache::get($recentKey, []);

        // Build a lookup of recent UIDs
        $recentMap = [];
        foreach ($recent as $idx => $r) {
            if (!empty($r['_uid'])) {
                $recentMap[$r['_uid']] = $idx;
            }
        }

        // For each newly computed alert, add to recent cache if not present
        foreach ($alerts as $alert) {
            // compute a stable uid for this alert
            $idPart = $alert['type'] . ':' . (
                $alert['document_id'] ?? $alert['patrol_id'] ?? $alert['checkcall_id'] ?? ($alert['scheduled_time'] ?? uniqid())
            );
            $uid = md5($idPart);
            $alert['_uid'] = $uid;

            if (!isset($recentMap[$uid])) {
                // new alert — prepend so newest are first
                array_unshift($recent, $alert);
                // limit stored alerts
                if (count($recent) > 50) {
                    array_pop($recent);
                }
                // update lookup
                $recentMap[$uid] = 0;

                // persist recent list for visibility window
                Cache::put($recentKey, $recent, now()->addMinutes($visibilityMinutes));

                // send push for new items only
                try {
                    send_push_notification(
                        $user->id,
                        $alert['title'],
                        $alert['message'],
                        $alert
                    );
                } catch (\Exception $e) {
                    Log::error('Failed to send push for alert', ['user_id' => $user->id, 'alert' => $alert, 'error' => $e->getMessage()]);
                }
            } else {
                // existing: update the stored alert content in case message changed
                $idx = $recentMap[$uid];
                $recent[$idx] = array_merge($recent[$idx], $alert);
                Cache::put($recentKey, $recent, now()->addMinutes($visibilityMinutes));
            }
        }

        // Return the recent list (newest first), strip internal uid keys
        $result = [];
        foreach ($recent as $r) {
            if (isset($r['_uid'])) {
                unset($r['_uid']);
            }
            if (isset($r['_first_shown'])) {
                unset($r['_first_shown']);
            }
            $result[] = $r;
        }

        return response()->json(['alerts' => $result]);
    }
}
