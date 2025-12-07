<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\Department;
use App\Models\Employee;

class DocumentController extends Controller
{
    // List of document fields we'll check in the Employee model
    protected $documentFields = [
        'sia_licence_file'          => 'SIA Licence',
        'passport_file'             => 'Passport',
        'proof_of_address_file'     => 'Proof of Address',
        'ni_letter_file'            => 'NI Letter',
        'first_aid_certificate_file' => 'First Aid Certificate',
        'act_certificate_file'      => 'ACT Certificate',
        'profile_picture'           => 'Profile Picture',
        'driving_licence_file'      => 'Driving Licence', // Added
        'other' => 'others'
    ];

    // Mapping of document fields to their expiry fields
    protected $expiryFields = [
        'sia_licence_file'      => 'sia_expiry',
        'passport_file'         => 'passport_expiry',
        'act_certificate_file'  => 'license_expiry',
        'driving_licence_file'  => 'driving_licence_expiry', //Added
    ];

  public function report(Request $request)
{
    $hasFilters = $request->anyFilled([
        'document_field', 
        'department_id', 
        'status', 
        'expiry_status', 
        'upload_status', 
        'other_document'
    ]);

    $employees = collect();
    $selectedDocumentFields = (array) $request->input('document_field', []);
    $departmentId = $request->input('department_id');
    $status = $request->input('status');
    $expiryStatus = $request->input('expiry_status');
    $uploadStatus = $request->input('upload_status');
    $otherDocument = $request->input('other_document');

    if ($hasFilters) {
        $query = Employee::with('department');

        // Handle built-in document fields first
        foreach ($selectedDocumentFields as $field) {
            if ($field === 'other') continue; // Skip "Other" for now
            $query->where(function ($q) use ($field, $uploadStatus, $expiryStatus) {
                // Uploaded/missing filter
                if ($uploadStatus === 'uploaded') {
                    $q->whereNotNull($field);
                } elseif ($uploadStatus === 'missing') {
                    $q->whereNull($field);
                }

                // Expiry filter
                if ($uploadStatus !== 'missing' && $this->hasExpiryField($field) && $expiryStatus) {
                    $expiryField = $this->getExpiryField($field);
                    if ($expiryStatus === 'expired') {
                        $q->whereDate($expiryField, '<', now());
                    } elseif ($expiryStatus === 'valid') {
                        $q->whereDate($expiryField, '>=', now());
                    }
                }
            });
        }

        // Handle "Other" / additional_files
        if (in_array('other', $selectedDocumentFields)) {
            $query->where(function ($q) use ($otherDocument, $uploadStatus) {
                if ($uploadStatus === 'uploaded') {
                    if ($otherDocument) {
                        $q->whereJsonContains('additional_files', $otherDocument);
                    } else {
                        $q->whereJsonLength('additional_files', '>', 0);
                    }
                } elseif ($uploadStatus === 'missing') {
                    if ($otherDocument) {
                        $q->where(function ($q2) use ($otherDocument) {
                            $q2->whereNull('additional_files')
                               ->orWhereRaw("NOT JSON_CONTAINS(additional_files, ?)", [json_encode($otherDocument)]);
                        });
                    } else {
                        $q->where(function ($q2) {
                            $q2->whereNull('additional_files')
                               ->orWhereJsonLength('additional_files', 0);
                        });
                    }
                }
            });
        }

        // Department & employee status filters
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }
        if ($status) {
            $query->where('status', $status);
        }

        $employees = $query->orderBy('sur_name')->get();
    }

    return view('employees.doc_report', [
        'employees' => $employees,
        'documentFields' => $this->documentFields,
        'departments' => Department::all(),
        'documentField' => $selectedDocumentFields,
        'departmentId' => $departmentId,
        'status' => $status,
        'expiryFields' => $this->expiryFields,
        'expiryStatus' => $expiryStatus,
        'uploadStatus' => $uploadStatus,
        'otherDocument' => $otherDocument,
        'hasFilters' => $hasFilters,
    ]);
}

    // Return documents for a given user id (AJAX)
    public function byUser($userId)
    {
        $docs = Document::where('user_id', $userId)->orderBy('created_at', 'desc')->get();
        return response()->json(['documents' => $docs]);
    }

    // Approve a document for an employee (find by employee->user_id and file_path)
    public function approveByEmployee(Request $request, $employeeId)
    {
        $employee = Employee::find($employeeId);
        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        $filePath = $request->input('file_path');
        if (!$filePath) {
            return response()->json(['error' => 'file_path is required'], 422);
        }

        $doc = Document::where('user_id', $employee->user_id)
            ->where('file_path', $filePath)
            ->first();

        if (!$doc) {
            // fallback: match by basename
            $basename = basename($filePath);
            $doc = Document::where('user_id', $employee->user_id)
                ->where('file_path', 'like', "%{$basename}%")
                ->first();
        }

        if (!$doc) {
            return response()->json(['error' => 'Document not found'], 404);
        }

        $doc->status = 'approved';
        $doc->admin_comments = null;
        $doc->save();

        send_push_notification(
            $employee->user_id,
            'Document Approved',
            'Your '.$doc->document_type.' document has been Approved.',
            ['document_id' => $doc->id]
        );

        return response()->json(['message' => 'Document approved', 'document' => $doc]);
    }

    // Reject a document for an employee (requires admin_comment)
    public function rejectByEmployee(Request $request, $employeeId)
    {
        $employee = Employee::find($employeeId);
        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        $filePath = $request->input('file_path');
        $comment = $request->input('admin_comment');

        if (!$filePath) {
            return response()->json(['error' => 'file_path is required'], 422);
        }
        if (!$comment) {
            return response()->json(['error' => 'admin_comment is required'], 422);
        }

        $doc = Document::where('user_id', $employee->user_id)
            ->where('file_path', $filePath)
            ->first();

        if (!$doc) {
            $basename = basename($filePath);
            $doc = Document::where('user_id', $employee->user_id)
                ->where('file_path', 'like', "%{$basename}%")
                ->first();
        }

        if (!$doc) {
            return response()->json(['error' => 'Document not found'], 404);
        }

        $doc->status = 'rejected';
        $doc->admin_comments = $comment;
        $doc->save();

        send_push_notification(
            $employee->user_id,
            'Document rejected',
            'Your '.$doc->document_type.' document has been rejected, check you application to view the reason.',
            ['document_id' => $doc->id]
        );

        return response()->json(['message' => 'Document rejected', 'document' => $doc]);
    }

    // Check if a document field has a corresponding expiry field
    protected function hasExpiryField($field)
    {
        return array_key_exists($field, $this->expiryFields);
    }

    // Get the expiry field for a document field
    protected function getExpiryField($field)
    {
        return $this->expiryFields[$field] ?? null;
    }
}
