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

class TrainingController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $materials = TrainingMaterial::with(['acknowledgements' => function ($query) use ($userId) {
            $query->where('user_id', $userId);
        }])->get();

        $response = $materials->map(function ($material) use ($userId) {
            $ack = $material->acknowledgements->first();
            return [
                'id' => $material->id,
                'title' => $material->title,
                'type' => $material->type,
                'content_url' => $material->content_url,
                'pdf_url' => $material->pdf_url,
                'required' => $material->required,
                'acknowledged' => $ack ? true : false,
                'expiry_date' => $material->expiry_date,
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
            'acknowledged_at' => 'required|date',
            'completion_time_seconds' => 'required|integer|min:1',
        ]);

        $material = TrainingMaterial::findOrFail($id);

        $ack = TrainingAcknowledgement::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'training_material_id' => $id,
            ],
            [
                'acknowledged_at' => $request->acknowledged_at,
                'completion_time_seconds' => $request->completion_time_seconds,
            ]
        );

        return response()->json(['message' => 'Acknowledged successfully.']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'required',
            'pdf_url' => 'nullable|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,png|max:2048',
            'type' => 'required',
            'expiry_date' => 'required|date',
        ]);

        $path = null;
        if ($request->hasFile('pdf_url')) {
            $path = $request->file('pdf_url')->store('materials', 'public');
        }

        TrainingMaterial::create([
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
            'expiry_date' => $request->expiry_date,
            'pdf_url' => $path,
        ]);

       return back()->with('message','Material created successfully');
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

    public function show(TrainingMaterial $material)
    {
        return response()->json($material);
    }

public function update(Request $request, $id) {
        $material = TrainingMaterial::findOrFail($id);
        $request->validate([
            'title' => 'required',
            'description' => 'required',
            'pdf_url' => 'nullable|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,png|max:2048',
            'material_type' => 'required',
            'expiry_date' => 'required|date',
        ]);

        if ($request->hasFile('pdf_url')) {
            $material->pdf_url = $request->file('pdf_url')->store('materials', 'public');
        }

        $material->update([
            'title' => $request->title,
            'description' => $request->description,
            'material_type' => $request->material_type,
            'expiry_date' => $request->expiry_date,
        ]);

        return response()->json(['success' => true]);
    }

    // Delete single material
    public function destroy($id) {
        $material = TrainingMaterial::findOrFail($id);
        $material->delete();
        return response()->json(['success' => true]);
    }

    // Bulk delete
    public function bulkDelete(Request $request) {
        $ids = $request->ids;
        TrainingMaterial::whereIn('id', $ids)->delete();
        return response()->json(['success' => true,'message' => 'Material deleted succesfully']);
    }
}
