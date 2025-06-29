<?php

namespace App\Http\Controllers;

use App\Models\DocumentationUpload;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class DocumentationUploadController extends Controller
{
    public function index()
    {
        $documents = DocumentationUpload::with('vehicle')->paginate(10);
        $vehicles = Vehicle::all();
        return view('vehicle_management.documentation_uploads', compact('documents', 'vehicles'));
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,id',

            'mot_certificate'           => 'required|file|mimes:pdf|max:2048',
            'insurance_certificate'     => 'required|file|mimes:pdf|max:2048',
            'v5c_logbook'               => 'required|file|mimes:pdf|max:2048',
            'tax_confirmation'          => 'required|file|mimes:pdf|max:2048',
            'tachograph_certificate'    => 'required|file|mimes:pdf|max:2048',
            'service_report'            => 'required|file|mimes:pdf|max:2048',
            'inspection_report'         => 'required|file|mimes:pdf|max:2048',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            } else {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }

        $paths = [];

        // Store each file and record the path if present
        foreach (
            [
                'mot_certificate',
                'insurance_certificate',
                'v5c_logbook',
                'tax_confirmation',
                'tachograph_certificate',
                'service_report',
                'inspection_report'
            ] as $field
        ) {
            if ($request->hasFile($field)) {
                $uploadPath = public_path('uploads/documents');

                // Create the directory if it doesn't exist
                if (!File::exists($uploadPath)) {
                    File::makeDirectory($uploadPath, 0755, true);
                }

                $file = $request->file($field);
                $fileName = time() . '_' . uniqid() . "_{$field}." . $file->getClientOriginalExtension();

                $file->move($uploadPath, $fileName);

                // Store the relative path (or full path if preferred)
                $paths[$field . '_path'] = 'uploads/documents/' . $fileName;
            }
        }

        DocumentationUpload::create(array_merge(
            ['vehicle_id' => $request->vehicle_id],
            $paths
        ));

        return response()->json(['message' => 'Documents uploaded successfully.']);
    }

    public function update(Request $request, $id)
    {
        $doc = DocumentationUpload::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,id',

            'mot_certificate'           => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'insurance_certificate'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'v5c_logbook'               => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'tax_confirmation'          => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'tachograph_certificate'    => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'service_report'            => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'inspection_report'         => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $paths = [];

        foreach (
            [
                'mot_certificate',
                'insurance_certificate',
                'v5c_logbook',
                'tax_confirmation',
                'tachograph_certificate',
                'service_report',
                'inspection_report'
            ] as $field
        ) {
            if ($request->hasFile($field)) {
                $uploadPath = public_path('uploads/documents');

                if (!File::exists($uploadPath)) {
                    File::makeDirectory($uploadPath, 0755, true);
                }

                $file = $request->file($field);
                $fileName = time() . '_' . uniqid() . "_{$field}." . $file->getClientOriginalExtension();

                $file->move($uploadPath, $fileName);

                $paths[$field . '_path'] = 'uploads/documents/' . $fileName;
            }
        }

        $doc->update(array_merge(['vehicle_id' => $request->vehicle_id], $paths));

        return response()->json(['message' => 'Documentation updated successfully.']);
    }
    public function edit($id)
    {
        $document = DocumentationUpload::find($id);
        return response()->json(['document' => $document]);
    }

    public function delete($id)
    {
        $doc = DocumentationUpload::findOrFail($id);

        // List of all document path fields
        $fileFields = [
            'mot_certificate_path',
            'insurance_certificate_path',
            'v5c_logbook_path',
            'tax_confirmation_path',
            'tachograph_certificate_path',
            'service_report_path',
            'inspection_report_path',
        ];

        // Attempt to delete each associated file if it exists
        foreach ($fileFields as $field) {
            if (!empty($doc->$field)) {
                $fullPath = public_path($doc->$field);
                if (File::exists($fullPath)) {
                    File::delete($fullPath);
                }
            }
        }

        $doc->delete();

        return response()->json([
            'success' => true,
            'message' => 'Documentation record and associated files deleted successfully.'
        ]);
    }
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:documentation_uploads,id',
        ]);

        DocumentationUpload::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => 'Selected Documentation records deleted successfully.']);
    }
}
