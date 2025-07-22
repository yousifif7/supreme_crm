<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\Document;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class DocumentAPIController extends Controller
{
    // 7. Upload Documents
    public function upload(Request $request)
    {
        $request->validate([
            'document_type' => 'required|in:sia_licence,right_to_work,dbs,first_aid,site_clearance,other',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png',
            'expiry_date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        $filePath = $request->file('file')->store('documents', 'public');

        $document = Document::create([
            'user_id' => $request->user()->id,
            'document_type' => $request->document_type,
            'file_path' => $filePath,
            'expiry_date' => $request->expiry_date,
            'description' => $request->description,
            'status' => 'pending',
        ]);

        return response()->json([
            'document_id' => $document->id,
            'status' => $document->status,
            'uploaded_at' => $document->created_at,
        ]);
    }

    // 8. Get User Documents
    public function index(Request $request)
    {
        $documents = $request->user()->documents()->get()->map(function ($doc) {
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

        return response()->json(['expiring_soon' => $expiringSoon]);
    }
}
