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
        'sia_licence_file' => 'SIA Licence',
        'passport_file' => 'Passport',
        'proof_of_address_file' => 'Proof of Address',
        'ni_letter_file' => 'NI Letter',
        'first_aid_certificate_file' => 'First Aid Certificate',
        'act_certificate_file' => 'ACT Certificate',
        'profile_picture' => 'Profile Picture',
    ];

    // Mapping of document fields to their expiry fields
    protected $expiryFields = [
        'sia_licence_file' => 'sia_expiry',
        'passport_file' => 'passport_expiry',
        'act_certificate_file' => 'license_expiry',
    ];

    public function report(Request $request)
    {
        // Only proceed with query if filters are applied
        $hasFilters = $request->anyFilled(['document_field', 'department_id', 'status', 'expiry_status', 'upload_status']);
        
        $employees = collect();
        $documentField = null;
        $departmentId = null;
        $status = null;
        $expiryStatus = null;
        $uploadStatus = null;

        if ($hasFilters) {
            // Get filter parameters from request
            $documentField = $request->input('document_field');
            $departmentId = $request->input('department_id');
            $status = $request->input('status');
            $expiryStatus = $request->input('expiry_status');
            $uploadStatus = $request->input('upload_status');

            // Build the query
            $query = Employee::with('department');

            // Apply document filter if selected
            if ($documentField) {
                if ($uploadStatus === 'uploaded') {
                    $query->whereNotNull($documentField);
                } elseif ($uploadStatus === 'missing') {
                    $query->whereNull($documentField);
                }

                // If we're checking a field with an expiry date and document is uploaded
                if ($uploadStatus !== 'missing' && $this->hasExpiryField($documentField) && $expiryStatus) {
                    $expiryField = $this->getExpiryField($documentField);
                    
                    if ($expiryStatus === 'expired') {
                        $query->whereDate($expiryField, '<', now());
                    } elseif ($expiryStatus === 'valid') {
                        $query->whereDate($expiryField, '>=', now());
                    }
                }
            }

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
            'documentField' => $documentField,
            'departmentId' => $departmentId,
            'status' => $status,
            'expiryFields'=>$this->expiryFields,
            'expiryStatus' => $expiryStatus,
            'uploadStatus' => $uploadStatus,
            'hasFilters' => $hasFilters,
        ]);
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
