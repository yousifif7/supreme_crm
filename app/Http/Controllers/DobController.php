<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\DobEntry;
use App\Models\DobMedia;
use App\Models\ShiftDate;
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
        $dobEntry = DobEntry::with(['media'])->findOrFail($id);
        $user = User::find($dobEntry->user_id);
        $shiftdate = ShiftDate::find($dobEntry->shift_id);

        $shift = $shiftdate?->shift;
        $site = $shift?->site;

        $data = [
            'id' => $dobEntry->id,
            'title' => $dobEntry->title,
            'entry_type' => $dobEntry->entry_type,
            'description' => $dobEntry->description,
            'timestamp' => $dobEntry->timestamp,
            'location' => [
                'latitude' => $dobEntry->location['latitude'] ?? null,
                'longitude' => $dobEntry->location['longitude'] ?? null,
            ],
            'address' => $site ? $site->address : 'Unknown',
            'user' => $user ? $user->first_name . ' ' . $user->last_name : 'Unknown',
            'media' => $dobEntry->media->map(function ($m) {
                return [
                    'id' => $m->id,
                    'file_url' => asset($m->file_url),
                    'type' => $m->type,
                ];
            }),
        ];

        return response()->json($data);
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
            'location.latitude' => 'nullable|numeric',
            'location.longitude' => 'nullable|numeric',
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
            'location.latitude' => 'nullable|numeric',
            'location.longitude' => 'nullable|numeric',
            'timestamp' => 'nullable|date',
        ]);

        $dobEntry = DobEntry::findOrFail($id);

        $dobEntry->update([
            'shift_id' => $data['shift_id'] ?? 0,
            'entry_type' => $data['entry_type'],
            'title' => $data['title'],
            'description' => $data['description'],
            'location' => $data['location'] ?? null,
            'timestamp' => $data['timestamp'] ?? now(),
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

        send_push_notification(
            $dobEntry->user_id,
            'Dob report updated',
            'An admin has updated your dob report, check your DOBs ',
            ['dobEntry' => $dobEntry],
        );

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
