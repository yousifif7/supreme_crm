<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Client;
use App\Models\DobEntry;
use App\Models\DobMedia;
use App\Models\ShiftDate;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\DataTables\DobsDataTable;
use App\Exports\DobEntriesExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DobController extends Controller
{
    public function index(DobsDataTable $dataTable)
    {
        return $dataTable->render('dob_reports.index');
    }

    /**
     * Resolve client / site / officer details for a given shift_dates id.
     * Returns 'Unknown' fallbacks so the view never breaks when a shift is missing.
     *
     * @return array{client_name:string, site_name:string, site_address:string, officer:string}
     */
    public static function resolveShiftContext($shiftId): array
    {
        // Memoise per shift_id within the request — DataTable rows ask for
        // client/site/officer separately, so this avoids 3 lookups per row.
        static $cache = [];

        $context = [
            'client_name'  => 'Unknown',
            'site_name'    => 'Unknown',
            'site_address' => 'Unknown',
            'officer'      => 'Unknown',
        ];

        if (empty($shiftId)) {
            return $context;
        }

        if (isset($cache[$shiftId])) {
            return $cache[$shiftId];
        }

        $shiftDate = ShiftDate::with(['shift.site', 'staff'])->find($shiftId);
        if (!$shiftDate) {
            return $context;
        }

        $site = $shiftDate->shift?->site;
        if ($site) {
            $context['site_name']    = $site->site_name ?: 'Unknown';
            $context['site_address'] = $site->address ?: 'Unknown';

            if (!empty($site->client_id)) {
                // sites.client_id / shifts.client_id store the client's USER id,
                // while the readable name lives on clients.client_name keyed by user_id.
                $clientName = Client::where('user_id', $site->client_id)->value('client_name');
                if ($clientName) {
                    $context['client_name'] = $clientName;
                }
            }
        }

        $staff = $shiftDate->staff;
        if ($staff) {
            $name = trim(($staff->first_name ?? '') . ' ' . ($staff->last_name ?? ''));
            $context['officer'] = $name !== '' ? $name : ($staff->name ?? 'Unknown');
        }

        $cache[$shiftId] = $context;

        return $context;
    }

    // SHOW DOB ENTRY
    public function show($id)
    {
        $dobEntry = DobEntry::with(['media'])->findOrFail($id);
        $user = User::find($dobEntry->user_id);

        $location = is_string($dobEntry->location)
            ? json_decode($dobEntry->location, true)
            : ($dobEntry->location ?? []);

        $context = self::resolveShiftContext($dobEntry->shift_id);

        $data = [
            'id' => $dobEntry->id,
            'title' => $dobEntry->title,
            'entry_type' => $dobEntry->entry_type,
            'description' => $dobEntry->description,
            'timestamp' => $dobEntry->timestamp,
            'location' => [
                'latitude' => $location['latitude'] ?? null,
                'longitude' => $location['longitude'] ?? null,
            ],
            // Guard's captured GPS at submission time
            'latitude' => $location['latitude'] ?? null,
            'longitude' => $location['longitude'] ?? null,
            'address' => $context['site_address'],
            'client_name' => $context['client_name'],
            'site_name' => $context['site_name'],
            'officer' => $context['officer'],
            // The guard who submitted the entry (the logged-in app user)
            'user' => $user ? trim($user->first_name . ' ' . $user->last_name) : 'Unknown',
            'media' => $dobEntry->media->map(function ($m) {
                return [
                    'id' => $m->id,
                    'file_url' => asset($m->file_url),
                    'type' => $m->type ?? null,
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
            'location' => json_encode($data['location'] ?? []),
            'timestamp' => $data['timestamp'] ?? now(),
        ]);

        // Save media if present (actually persist uploaded files to disk)
        $this->saveUploadedMedia($request, $dobEntry);

        return response()->json(['message' => 'DOB Entry created successfully']);
    }

    /**
     * Persist any uploaded DOB media files (from the admin create/edit modals)
     * to public/dob_media and create DobMedia rows pointing at the saved path.
     * Accepts files under either the "media_files" or "files" form field.
     */
    private function saveUploadedMedia(Request $request, DobEntry $dobEntry): void
    {
        $files = [];
        foreach (['media_files', 'files'] as $field) {
            if ($request->hasFile($field)) {
                $files = array_merge($files, $request->file($field));
            }
        }

        if (empty($files)) {
            return;
        }

        if (!file_exists(public_path('dob_media'))) {
            mkdir(public_path('dob_media'), 0755, true);
        }

        foreach ($files as $file) {
            if (!$file instanceof \Illuminate\Http\UploadedFile) {
                continue;
            }

            try {
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('dob_media'), $filename);

                DobMedia::create([
                    'dob_entry_id' => $dobEntry->id,
                    'file_url' => 'dob_media/' . $filename,
                ]);
            } catch (\Throwable $e) {
                Log::error('DobController: failed to save uploaded media: ' . $e->getMessage());
            }
        }
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

        $updatePayload = [
            'entry_type' => $data['entry_type'],
            'title' => $data['title'],
            'description' => $data['description'],
            'timestamp' => $data['timestamp'] ?? now(),
        ];

        // Only touch shift_id / location when explicitly provided so an edit that
        // omits them does not wipe the original submission data.
        if (array_key_exists('shift_id', $data) && $data['shift_id'] !== null) {
            $updatePayload['shift_id'] = $data['shift_id'];
        }
        if (array_key_exists('location', $data) && $data['location'] !== null) {
            $updatePayload['location'] = json_encode($data['location']);
        }

        $dobEntry->update($updatePayload);

        // Save new media if present (actually persist uploaded files to disk)
        $this->saveUploadedMedia($request, $dobEntry);

        send_push_notification(
            $dobEntry->user_id,
            'Dob report updated',
            'An admin has updated your dob report, check your DOBs ',
            ['type' => 'dob', 'dobId' => $dobEntry->id],
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
