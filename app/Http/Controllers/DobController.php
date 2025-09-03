<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\DobEntry;
use App\Models\DobMedia;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\DataTables\DobsDataTable;
use App\Exports\DobEntriesExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class DobController extends Controller
{
    public function index(DobsDataTable $dataTable)
    {
        return $dataTable->render('dob_reports.index');
    }

    // SHOW DOB ENTRY
    public function show($id)
    {
        $dobEntry = DobEntry::findOrFail($id);
        return response()->json($dobEntry->load('media'));
    }

    // CREATE DOB ENTRY
    public function store(Request $request)
    {
        $data = $request->validate([
            'shift_id' => 'nullable|exists:shift_dates,id',
            'entry_type' => 'required|in:incident,observation,maintenance,visitor,other',
            'title' => 'required|string',
            'description' => 'required|string',
            'media_files' => 'nullable|array',
            'location.latitude' => 'required|numeric',
            'location.longitude' => 'required|numeric',
            'timestamp' => 'nullable|date',
        ]);

        $dobEntry = DobEntry::create([
            'user_id' => auth()->id(),
            'shift_id' => $data['shift_id'] ?? 0,
            'entry_type' => $data['entry_type'],
            'title' => $data['title'],
            'description' => $data['description'],
            'location' => json_encode($data['location']),
            'timestamp' => $data['timestamp'] ?? now(),
        ]);

        // Save media if present
        if (!empty($data['media_files'])) {
            foreach ($data['media_files'] as $file) {
                DobMedia::create([
                    'dob_entry_id' => $dobEntry->id,
                    'file_url' => $file, // adjust if uploading files
                ]);
            }
        }

        return response()->json(['message' => 'DOB Entry created successfully']);
    }

    // EDIT DOB ENTRY
    public function edit(DobEntry $dobEntry)
    {
        return response()->json($dobEntry->load('media'));
    }

    // UPDATE DOB ENTRY
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'shift_id' => 'nullable|exists:shift_dates,id',
            'entry_type' => 'required|in:incident,observation,maintenance,visitor,other',
            'title' => 'required|string',
            'description' => 'required|string',
            'media_files' => 'nullable|array',
            'location.latitude' => 'required|numeric',
            'location.longitude' => 'required|numeric',
            'timestamp' => 'nullable|date',
        ]);

        $dobEntry = DobEntry::findOrFail($id);

        $dobEntry->update([
            'shift_id' => $data['shift_id'],
            'entry_type' => $data['entry_type'],
            'title' => $data['title'],
            'description' => $data['description'],
            'location' => json_encode($data['location']),
            'timestamp' => $data['timestamp'],
        ]);

        // Save new media if present
        if (!empty($data['media_files'])) {
            foreach ($data['media_files'] as $file) {
                DobMedia::create([
                    'dob_entry_id' => $dobEntry->id,
                    'file_url' => $file,
                ]);
            }
        }

        return response()->json(['message' => 'DOB Entry updated successfully']);
    }

    // DELETE DOB ENTRY
    public function destroy($id)
    {
        $dobEntry = DobEntry::findOrFail($id);
        // Delete associated media first
        foreach ($dobEntry->media as $media) {
            Storage::disk('public')->delete($media->file_url);
            $media->delete();
        }

        $dobEntry->delete();

        return response()->json(['message' => 'DOB entry deleted successfully!']);
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;

        if (!$ids || !is_array($ids)) {
            return response()->json(['message' => 'No entries selected.'], 400);
        }

        DobEntry::whereIn('id', $ids)->delete();

        return response()->json(['message' => 'Selected entries deleted successfully.']);
    }

    public function exportDobExcel()
    {
        return Excel::download(new DobEntriesExport, 'dob_entries.xlsx');
    }

    public function exportDobPdf()
    {
        $dobs = DobEntry::all();
        $pdf = Pdf::loadView('dob_reports.dob_pdf', compact('dobs'));
        return $pdf->download('dobs.pdf');
    }
}
