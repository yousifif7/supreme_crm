<?php

namespace App\Http\Controllers\API;

use Notify;
use Carbon\Carbon;
use App\Models\Document;
use App\Models\Employee;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentAPIController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'document_type' => 'required|in:sia_licence_file,passport_file,proof_of_address_file,ni_letter_file,first_aid_certificate_file,act_certificate_file',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png',
            'expiry_date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        // Get the uploaded file
        $file = $request->file('file');

        // Define the destination path inside the public folder
        $destinationPath = public_path('documents'); // public/documents

        // Ensure the directory exists
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        // Move the file to the public folder
        $fileName = time() . '_' . $file->getClientOriginalName();
        $file->move($destinationPath, $fileName);

        // Full path (for saving in DB, if needed)
        $filePath = 'documents/' . $fileName;

        $document = Document::create([
            'user_id' => $request->user()->id,
            'document_type' => $request->document_type,
            'file_path' => $filePath,
            'expiry_date' => $request->expiry_date,
            'description' => $request->description,
            'status' => 'pending',
        ]);

        // Sync to employee table if applicable
        $syncToEmployeeTable = [
            'sia_licence_file' => 'sia_licence_file',
            'passport_file' => 'passport_file',
            'proof_of_address_file' => 'proof_of_address_file',
            'ni_letter_file' => 'ni_letter_file',
            'first_aid_certificate_file' => 'first_aid_certificate_file',
            'act_certificate_file' => 'act_certificate_file'
        ];

        $user = $request->user();
        $employee = $user->employee; // Assuming you have $user->employee relationship

        if ($employee && isset($syncToEmployeeTable[$request->document_type])) {
            $employeeColumn = $syncToEmployeeTable[$request->document_type];

            $employee->update([
                $employeeColumn => basename($filePath),
                'licence_expiry' => $request->expiry_date
            ]);

            Notify::toDashboard(
                auth::id(),

                'alert',
                'Document Uploaded',
                'Document uploaded by ' . $employee->fore_name . ' ' . $employee->sur_name,
                '/employees'
            );

            Notify::toDashboard(
                $employee->id,
                'alert',
                'Document Uploaded',
                'You have uploaded a file',
                '/employees'
            );

            // Send push notification to employee/device
            send_push_notification(
                $user->id,
                'You uploaded a document',
                'Your Document has been uploaded succesfully.',
                ['document_id' => $document->id],
            );
        }

        return response()->json([
            'document_id' => $document->id,
            'status' => $document->status,
            'uploaded_at' => $document->created_at,
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
                'expiry_date' => $doc->expiry_date,
                'uploaded_at' => $doc->created_at,
                'admin_comments' => $doc->admin_comments,
            ];
        });

        return response()->json(['documents' => $documents]);
    }

    // 9. Document Expiry Alerts
    public function alerts(Request $request)
    {
        $expiringSoon = $request->user()->documents()
            ->whereDate('expiry_date', '>=', now())
            ->whereDate('expiry_date', '<=', now()->addDays(30))
            ->get()
            ->map(function ($doc) {
                return [
                    'document_id' => $doc->id,
                    'type' => $doc->document_type,
                    'expiry_date' => $doc->expiry_date,
                    'days_remaining' => Carbon::parse($doc->expiry_date)->diffInDays(now()),
                ];
            });


        foreach ($expiringSoon as $exp) {
            send_push_notification(
                Auth::id(),
                'Document expiry alert',
                'Your document is about to expire.',
                ['document_id' => $exp->id]
            );
        }


        return response()->json(['expiring_soon' => $expiringSoon]);
    }
}
