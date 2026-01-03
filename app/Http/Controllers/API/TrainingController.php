<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Exports\InvoiceExport;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\MaterialsExport;
use App\Models\TrainingMaterial;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\DataTables\MaterialDataTable;
use App\Models\TrainingAcknowledgement;
use Illuminate\Support\Facades\Storage;
use App\Services\FileCompressor;
use Illuminate\Support\Facades\Log;

class TrainingController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $materials = TrainingMaterial::with(['acknowledgedUsers' => function ($query) use ($userId) {
            $query->where('user_id', $userId);
        }])->get();

        $response = $materials->map(function ($material) use ($userId) {
            $ack = $material->acknowledgedUsers->first();
            return [
                'id' => $material->id,
                'title' => $material->title,
                'type' => $material->type,
                'content_url' => $material->content_url,
                'pdf_url' => $material->pdf_url,
                'required' => $material->required,
                'acknowledged' => $ack ? true : false,
                'implementation_date' => $material->implementation_date,
                'complete_by_date' => $material->deadline,
                'acknowledge_by_date' => $material->acknowledge_by_date,
                'created_at' => $material->created_at,
            ];
        });

        return response()->json(['materials' => $response]);
    }

    public function matsView(MaterialDataTable $dataTable)
    {
        $materials = TrainingMaterial::all();
        return $dataTable->render('hr.index', compact('materials'));
    }

    public function acknowledge(Request $request, $id)
    {
        $request->validate([
            'acknowledged_at' => 'date',
            'completion_time_seconds' => 'integer|min:1',
        ]);

        $material = TrainingMaterial::findOrFail($id);

        if($material->acknowledgedUsers()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Material already acknowledged.'], 400);
        }
        
        $ack = TrainingAcknowledgement::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'training_material_id' => $id,
            ],
            [
                'acknowledged_at' => now(),
                'completion_time_seconds' => $request->completion_time_seconds,
            ]
        );

        return response()->json(['message' => 'Acknowledged successfully.']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'pdf_url' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,png|max:2048', // max 2MB
            'type' => 'required|string',
            'implementation_date' => 'nullable|date',
            'deadline' => 'nullable|date',
            'acknowledge_by_date' => 'nullable|date',
        ]);

        $filePath = null;

        if ($request->hasFile('pdf_url')) {
            // Move file into the public/materials folder
            $fileName = time() . '_' . $request->file('pdf_url')->getClientOriginalName();
            $request->file('pdf_url')->move(public_path('materials'), $fileName);

            // Save only the relative path for later use
            $filePath = 'materials/' . $fileName;
            try {
                (new FileCompressor())->compress(public_path('materials/' . $fileName));
            } catch (\Exception $e) {
                Log::error('File compression failed for training material pdf: ' . $e->getMessage());
            }
        }

        TrainingMaterial::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'type' => $validated['type'],
            'implementation_date' => $validated['implementation_date'] ?? null,
            'deadline' => $validated['deadline'] ?? null,
            'acknowledge_by_date' => $validated['acknowledge_by_date'] ?? null,
            'pdf_url' => $filePath,
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Material created successfully']);
        }
        
        return redirect()->back()->with('success', 'Material created successfully');
    }

    public function exportMaterialsPdf()
    {
        $materials = TrainingMaterial::all();
        $pdf = Pdf::loadView('hr.materials_pdf', compact('materials'));
        return $pdf->download('materials.pdf');
    }

    public function exportMaterialsExcel()
    {
        return Excel::download(new MaterialsExport, 'materials.xlsx');
    }

    /**
     * Get a single training material by ID
     */
    public function show($id)
    {
        $userId = Auth::id();
        
        $material = TrainingMaterial::with(['acknowledgedUsers' => function ($query) use ($userId) {
            $query->where('user_id', $userId);
        }])->find($id);

        if (!$material) {
            return response()->json(['message' => 'Training material not found'], 404);
        }

        $ack = $material->acknowledgedUsers->first();

        return response()->json([
            'material' => [
                'id' => $material->id,
                'title' => $material->title,
                'description' => $material->description,
                'type' => $material->type,
                'content_url' => $material->content_url,
                'pdf_url' => $material->pdf_url,
                'required' => $material->required,
                'acknowledged' => $ack ? true : false,
                'acknowledged_at' => $ack ? $ack->acknowledged_at : null,
                'completion_time_seconds' => $ack ? $ack->completion_time_seconds : null,
                'implementation_date' => $material->implementation_date,
                'complete_by_date' => $material->deadline,
                'acknowledge_by_date' => $material->acknowledge_by_date,
                'created_at' => $material->created_at,
                'updated_at' => $material->updated_at,
            ]
        ]);
    }


    public function update(Request $request, $id)
    {
        $material = TrainingMaterial::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'pdf_url' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,png|max:2048', // max 2MB
            'type' => 'required|string',
            'expiry_date' => 'nullable|date',
            'implementation_date' => 'nullable|date',
            'deadline' => 'nullable|date',
            'acknowledge_by_date' => 'nullable|date',
        ]);


        // If file uploaded, replace the old file
        if ($request->hasFile('pdf_url')) {
            $fileName = time() . '_' . $request->file('pdf_url')->getClientOriginalName();
            $request->file('pdf_url')->move(public_path('materials'), $fileName);
            $validated['pdf_url'] = 'materials/' . $fileName;
            try {
                (new FileCompressor())->compress(public_path('materials/' . $fileName));
            } catch (\Exception $e) {
                Log::error('File compression failed for training material pdf (update): ' . $e->getMessage());
            }
        } else {
            unset($validated['pdf_url']);
        }

        // Update instead of create
        $material->update($validated);

        return response()->json(['success' => true]);
    }

    // Delete single material
    public function destroy($id)
    {
        $material = TrainingMaterial::findOrFail($id);
        $material->delete();
        return response()->json(['success' => true]);
    }

    // Bulk delete
    public function showAcknowledged($id)
    {
        $material = TrainingMaterial::with('acknowledgedUsers')->findOrFail($id);

        return response()->json([
            'title' => $material->title,
            'users' => $material->acknowledgedUsers->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,
                ];
            })
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:training_materials,id',
        ]);

        TrainingMaterial::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => 'Selected Hr materials deleted.']);
    }
}
