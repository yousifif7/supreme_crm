<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\Department;
use App\Models\Employee;
use App\Exports\DocumentReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

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
    [$employees, $filters] = $this->buildReport($request);

    return view('employees.doc_report', array_merge($filters, [
        'employees' => $employees,
        'documentFields' => $this->documentFields,
        'departments' => Department::all(),
        'expiryFields' => $this->expiryFields,
    ]));
}

    /**
     * Export the (filtered) document report to Excel. Uses the same filters as report().
     */
    public function exportExcel(Request $request)
    {
        [$employees, $filters] = $this->buildReport($request);

        $export = new DocumentReportExport(
            $employees,
            $filters['documentField'],
            $this->documentFields,
            $this->expiryFields,
            $filters['otherDocument'],
        );

        return Excel::download($export, 'document_report_' . now()->format('Ymd_His') . '.xlsx');
    }

    /**
     * Export the (filtered) document report to PDF. Uses the same filters as report().
     */
    public function exportPdf(Request $request)
    {
        [$employees, $filters] = $this->buildReport($request);

        $pdf = Pdf::loadView('employees.doc_report_pdf', array_merge($filters, [
            'employees' => $employees,
            'documentFields' => $this->documentFields,
            'expiryFields' => $this->expiryFields,
        ]))->setPaper('a4', 'landscape');

        return $pdf->download('document_report_' . now()->format('Ymd_His') . '.pdf');
    }

    /**
     * Apply the report filters from the request and return [Collection $employees, array $filters].
     * Shared by the on-screen report and the Excel/PDF exports so they stay in sync.
     */
    private function buildReport(Request $request): array
    {
        $hasFilters = $request->anyFilled([
            'document_field',
            'department_id',
            'status',
            'expiry_status',
            'upload_status',
            'other_document',
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

        $filters = [
            'documentField' => $selectedDocumentFields,
            'departmentId' => $departmentId,
            'status' => $status,
            'expiryStatus' => $expiryStatus,
            'uploadStatus' => $uploadStatus,
            'otherDocument' => $otherDocument,
            'hasFilters' => $hasFilters,
        ];

        return [$employees, $filters];
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
            ['type' => 'document']
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
            ['type' => 'document']
        );

        return response()->json(['message' => 'Document rejected', 'document' => $doc]);
    }

    // Delete a document for an employee: remove DB row and clear employee's reference
    public function deleteByEmployee(Request $request, $employeeId)
    {
        $employee = Employee::find($employeeId);
        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        $filePath = $request->input('file_path');
        if (!$filePath) {
            return response()->json(['error' => 'file_path is required'], 422);
        }

        // Try to find exact match first
        $doc = Document::where('user_id', $employee->user_id)
            ->where('file_path', $filePath)
            ->first();

        // Fallback: match by basename
        if (!$doc) {
            $basename = basename($filePath);
            $doc = Document::where('user_id', $employee->user_id)
                ->where('file_path', 'like', "%{$basename}%")
                ->first();
        }

        // If we found a DB record, delete it
        if ($doc) {
            $docType = $doc->document_type;
            $storedPath = $doc->file_path;
            try {
                $doc->delete();
            } catch (\Exception $e) {
                // ignore deletion failure but continue to clear employee fields
            }
        } else {
            // no DB record found; still attempt to infer document type from request path
            $docType = null;
            $storedPath = $filePath;
        }

        // Clear references on Employee model ONLY if the file being deleted is the
        // one currently referenced by the employee's main field. We never clear the
        // expiry date — the user wants the date preserved when a document is removed.
        $mainFields = array_keys($this->documentFields);
        $basename = basename($storedPath);
        if ($docType && in_array($docType, $mainFields) && $docType !== 'other') {
            $current = $employee->{$docType};
            if ($current && (basename($current) === $basename || str_contains($current, $basename))) {
                $employee->{$docType} = null;
                $employee->save();
            }
        } else {
            // It may be an employee field (passed as e.g. 'documents/filename.pdf')
            // Try to find which main field points to this basename and clear it.
            foreach ($mainFields as $f) {
                if ($f === 'other') continue;
                $val = $employee->{$f};
                if (!$val) continue;
                if ((basename($val) === $basename) || str_contains($val, $basename)) {
                    $employee->{$f} = null;
                    $employee->save();
                }
            }
        }

        // If this was an 'other' / additional_files entry, remove from JSON array
        try {
            if (empty($doc) || ($doc && $doc->document_type === 'other')) {
                $add = $employee->additional_files;
                if ($add) {
                    if (is_string($add)) $add = json_decode($add, true) ?: [];
                    if (is_array($add) && count($add) > 0) {
                        $filtered = array_values(array_filter($add, function ($it) use ($storedPath) {
                            if (is_string($it)) return basename($it) !== basename($storedPath) && $it !== $storedPath;
                            if (is_array($it)) {
                                $p = $it['path'] ?? ($it['file'] ?? ($it['file_path'] ?? null));
                                return basename($p) !== basename($storedPath) && $p !== $storedPath;
                            }
                            return true;
                        }));
                        $employee->additional_files = empty($filtered) ? null : $filtered;
                        $employee->save();
                    }
                }
            }
        } catch (\Exception $e) {
            // ignore JSON parse issues
        }

        // Attempt to remove physical file if it looks like an app-managed path
        try {
            $publicPath = public_path($storedPath);
            if ($storedPath && file_exists($publicPath) && is_file($publicPath)) {
                @unlink($publicPath);
            }
        } catch (\Exception $e) {
            // ignore
        }

        return response()->json(['message' => 'Document deleted']);
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
