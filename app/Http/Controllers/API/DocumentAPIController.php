<?php

namespace App\Http\Controllers\API;

use Notify;
use Carbon\Carbon;
use App\Models\Patrol;
use App\Models\Document;
use App\Models\Employee;
use App\Models\CheckCall;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

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

        $documents = $user->documents()->get()->map(function ($doc) {
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
            // get the latest document of this type
            $doc = $user->documents()
                ->where('document_type', $type)
                ->orderByDesc('expiry_date')
                ->first();

            if ($doc && $doc->expiry_date) {
                $expiryDate = Carbon::parse($doc->expiry_date);

                // Only consider if within next 30 days
                if ($expiryDate->isFuture() && $expiryDate->lte(now()->addDays(30))) {
                    $cacheKey = "document_expiry_sent:user:{$user->id}:doc:{$doc->id}";

                    if (!Cache::has($cacheKey)) {
                        $daysRemaining = now()->diffInDays($expiryDate);

                        $alert = [
                            'type' => 'document_expiry',
                            'document_id' => $doc->id,
                            'title' => 'Document Expiry Alert',
                            'message' => "Your {$doc->document_type} is about to expire in {$daysRemaining} day(s) on {$doc->expiry_date}.",
                            'expiry_date' => $doc->expiry_date,
                            'days_remaining' => $daysRemaining,
                        ];

                        $alerts[] = $alert;

                        // Send push notification
                        send_push_notification(
                            $user->id,
                            $alert['title'],
                            $alert['message'],
                            $alert
                        );

                        // Cache for 30 days (until expiry), not just 7 days
                        Cache::put($cacheKey, true, $expiryDate->diffInSeconds(now()));
                    }
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
            $start = Carbon::parse($patrol->start_time);
            $diff = now()->diffInMinutes($start, false); // negative if past

            // 5-min warning
            if ($diff <= 5 && $diff > 0) {
                $alerts[] = [
                    'type' => 'patrol_warning',
                    'patrol_id' => $patrol->id,
                    'title' => 'Upcoming Patrol',
                    'message' => 'Patrol starting soon: ' . $patrol->name,
                    'scheduled_time' => $patrol->start_time,
                ];
            }

            // 50-min missed
            if ($diff <= -50) {
                $patrol->update(['status' => 'missed']);
                $alerts[] = [
                    'type' => 'patrol_missed',
                    'patrol_id' => $patrol->id,
                    'title' => 'Missed Patrol',
                    'message' => 'You missed a patrol: ' . $patrol->name,
                    'scheduled_time' => $patrol->start_time,
                ];
            }
        }

        /**
         * 3. Check Call Alerts (5 min notification / 15 min missed)
         */
        $checkCalls = CheckCall::whereHas('shiftDate', fn($q) => $q->where('staff_id', $user->id))
            ->where('status', 'pending')
            ->get();

        foreach ($checkCalls as $checkCall) {
            $scheduled = Carbon::parse($checkCall->scheduled_time);
            $diff = now()->diffInMinutes($scheduled, false);

            // 5-min warning
            if ($diff <= 5 && $diff > 0) {
                $alerts[] = [
                    'type' => 'checkcall_warning',
                    'checkcall_id' => $checkCall->id,
                    'title' => 'Upcoming Check Call',
                    'message' => 'Check call coming up: ' . $checkCall->name,
                    'scheduled_time' => $checkCall->scheduled_time,
                ];
            }

            // 15-min missed
            if ($diff <= -15) {
                $checkCall->update(['status' => 'missed']);
                $alerts[] = [
                    'type' => 'checkcall_missed',
                    'checkcall_id' => $checkCall->id,
                    'title' => 'Missed Check Call',
                    'message' => 'You missed a check call: ' . $checkCall->name,
                    'scheduled_time' => $checkCall->scheduled_time,
                ];
            }
        }

        // Push notifications
        foreach ($alerts as $alert) {
            send_push_notification(
                $user->id,
                $alert['title'],
                $alert['message'],
                $alert
            );
        }

        return response()->json(['alerts' => $alerts]);
    }
}
