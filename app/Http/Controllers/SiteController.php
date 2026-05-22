<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\User;
use App\Models\Client;
use App\Helpers\Logger;
use App\Models\EmployeeType;
use Illuminate\Http\Request;
use App\DataTables\SitesDataTable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SiteController extends Controller
{
    public function index(SitesDataTable $dataTable)
    {
        $clients = User::role('client')->get();
        $employee_types = EmployeeType::all();

        return $dataTable->render('sites.index', compact('clients', 'employee_types'));
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id'      => 'required|integer',
            'site_name'      => 'required|string|max:255',
            'guard_names'    => 'nullable|string|max:255',
            'address'        => 'nullable|string|max:255',
            'post_code'      => 'nullable|string|max:50',
            'plus_code'      => 'nullable|string',
            'site_code'      => 'nullable|string|max:50',
            'radius'         => 'nullable|numeric',
            'contact_number' => 'nullable|string|max:50',
            'note'           => 'nullable|string|max:1000',
            'manager_1_id'   => 'nullable|integer',
            'manager_2_id'   => 'nullable|integer',
            'start_time'     => 'nullable',
            'end_time'       => 'nullable',
            'break_time'     => 'nullable',
            'guard_rate'     => 'nullable|numeric',
            'office_rate'    => 'nullable|numeric',
            'billable_rate'  => 'nullable|numeric',
            'payable_rate'   => 'nullable|numeric',
            'employee_types' => 'nullable|array',
            'employee_types.*' => 'integer|exists:employee_types,id',
            'employee_guard_rate' => 'nullable|array',
            'employee_office_rate' => 'nullable|array',
            'staff_rates' => 'nullable|array',
            'staff_rates.*.user_id' => 'nullable|integer|exists:users,id',
            'staff_rates.*.guard_rate' => 'nullable|numeric',
            'holiday_rates' => 'nullable|array',
            'holiday_rates.*.holiday_name' => 'required_with:holiday_rates|string|max:255',
            'holiday_rates.*.holiday_date' => 'required_with:holiday_rates|date',
            'holiday_rates.*.site_rate' => 'nullable|numeric',
            'holiday_rates.*.guard_rate' => 'nullable|numeric',

            // ✅ Checkpoints validation
            'checkpoints'                => 'nullable|array',
            'checkpoints.*.name'         => 'required_with:patrol_check_points|string|max:255',
            'checkpoints.*.latitude'     => 'nullable|numeric',
            'checkpoints.*.longitude'    => 'nullable|numeric',
            'checkpoints.*.qr_code'      => 'nullable|string|max:255',
            'checkpoints.*.nfc_tag'      => 'nullable|string|max:255',
            'checkpoints.*.required'     => 'nullable|boolean',
            'has_qr' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            } else {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }

        $data = $validator->validated();
        $data['has_qr'] = $request->has('has_qr') ? 1 : 0;

        // ✅ Create Site
        $site = Site::create($data);

        // ✅ Attach employee types with pivot data
        if ($request->has('employee_types')) {
            $pivotData = [];
            foreach ($request->employee_types as $typeId) {
                $pivotData[$typeId] = [
                    'guard_rate'  => $request->employee_guard_rate[$typeId] ?? null,
                    'office_rate' => $request->employee_office_rate[$typeId] ?? null,
                ];
            }
            $site->employeeTypes()->sync($pivotData);
        }

        // ✅ Save Checkpoints (auto-generate NFC tag per checkpoint if not provided)
        if ($request->has('checkpoints')) {
            foreach ($request->checkpoints as $checkpoint) {
                $nfcTag = !empty($checkpoint['nfc_tag'])
                    ? $checkpoint['nfc_tag']
                    : 'NFC-CP-' . strtoupper(substr(sha1(uniqid((string) rand(), true)), 0, 10));

                $site->checkpoints()->create([
                    'name'      => $checkpoint['name'],
                    'latitude'  => $checkpoint['latitude'] ?? null,
                    'longitude' => $checkpoint['longitude'] ?? null,
                    'qr_code'   => $checkpoint['qr_code'] ?? null,
                    'nfc_tag'   => $nfcTag,
                    'required'  => $checkpoint['required'] ?? false,
                ]);
            }
        }

        // ✅ Save staff-specific rates (if provided). Accept either an array or JSON string.
        if ($request->has('staff_rates')) {
            $rates = $request->input('staff_rates', []);
            if (is_string($rates)) {
                $decoded = json_decode($rates, true);
                $rates = is_array($decoded) ? $decoded : [];
            }
            if (!is_array($rates)) {
                $rates = [];
            }
            foreach ($rates as $r) {
                if (empty($r['user_id'])) continue;
                $site->staffRates()->create([
                    'user_id' => $r['user_id'],
                    'guard_rate' => $r['guard_rate'] ?? null,
                ]);
            }
        }

        // ✅ Save holiday-specific rates (if provided). Accept either an array or JSON string.
        if ($request->has('holiday_rates')) {
            $holidayRates = $request->input('holiday_rates', []);
            if (is_string($holidayRates)) {
                $decoded = json_decode($holidayRates, true);
                $holidayRates = is_array($decoded) ? $decoded : [];
            }
            if (!is_array($holidayRates)) {
                $holidayRates = [];
            }
            foreach ($holidayRates as $rate) {
                if (empty($rate['holiday_name']) || empty($rate['holiday_date'])) {
                    continue;
                }
                $site->siteHolidayRates()->create([
                    'holiday_name' => $rate['holiday_name'],
                    'holiday_date' => $rate['holiday_date'],
                    'site_rate' => $rate['site_rate'] ?? null,
                    'guard_rate' => $rate['guard_rate'] ?? null,
                ]);
            }
        }

        Logger::log(Auth::user(), 'Create', 'Site '.$site->site_name.' Created');

        // Generate QR image and NFC tag if requested
        if (!empty($data['has_qr'])) {
            try {
                // Generate NFC tag for the site
                $nfcTag = 'NFC-SITE-' . strtoupper(substr(sha1(uniqid((string) rand(), true)), 0, 10));
                $site->update(['nfc_tag' => $nfcTag]);

                // persist initial NFC to filesystem so multiple tags are supported
                try {
                    $nfcDir = public_path('nfcForSites');
                    if (!File::exists($nfcDir)) File::makeDirectory($nfcDir, 0755, true);
                    $filename = 'site_' . $site->id . '_' . time() . '_' . substr(sha1($nfcTag),0,6) . '.txt';
                    file_put_contents($nfcDir . DIRECTORY_SEPARATOR . $filename, $nfcTag);
                } catch (\Exception $e) {
                    Log::warning('Failed to save initial NFC file for site ' . $site->id . ': ' . $e->getMessage());
                }

                $this->generateQrForSite($site);
            } catch (\Exception $e) {
                Log::warning('Failed to generate QR/NFC for site ' . $site->id . ': ' . $e->getMessage());
            }
        }

        return response()->json(['message' => 'Site created successfully']);
    }

    public function update(Request $request, $id)
    {
        $site = Site::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'client_id'      => 'required|integer',
            'site_name'      => 'required|string|max:255',
            'guard_names'    => 'nullable|string|max:255',
            'address'        => 'nullable|string|max:255',
            'post_code'      => 'nullable|string|max:50',
            'site_code'      => 'nullable|string|max:50',
            'plus_code'      => 'nullable|string',
            'radius'         => 'nullable|numeric',
            'contact_number' => 'nullable|string|max:50',
            'note'           => 'nullable|string|max:1000',
            'manager_1_id'   => 'nullable|integer',
            'manager_2_id'   => 'nullable|integer',
            'start_time'     => 'nullable|string',
            'end_time'       => 'nullable|string',
            'break_time'     => 'nullable|string',
            'guard_rate'     => 'nullable|numeric',
            'office_rate'    => 'nullable|numeric',
            'billable_rate'  => 'nullable|numeric',
            'payable_rate'   => 'nullable|numeric',

            // employee types
            'employee_types' => 'nullable|array',
            'employee_types.*' => 'integer|exists:employee_types,id',
            'employee_guard_rate' => 'nullable|array',
            'employee_office_rate' => 'nullable|array',
            'staff_rates' => 'nullable|array',
            'staff_rates.*.user_id' => 'nullable|integer|exists:users,id',
            'staff_rates.*.guard_rate' => 'nullable|numeric',
            'holiday_rates' => 'nullable|array',
            'holiday_rates.*.holiday_name' => 'required_with:holiday_rates|string|max:255',
            'holiday_rates.*.holiday_date' => 'required_with:holiday_rates|date',
            'holiday_rates.*.site_rate' => 'nullable|numeric',
            'holiday_rates.*.guard_rate' => 'nullable|numeric',

            // checkpoints validation
            'checkpoints'   => 'nullable|array',
            'checkpoints.*.id' => 'nullable|integer|exists:patrol_check_points,id',
            'checkpoints.*.name' => 'required_with:patrol_check_points|string|max:255',
            'checkpoints.*.latitude' => 'required_with:patrol_check_points|numeric',
            'checkpoints.*.longitude' => 'required_with:patrol_check_points|numeric',
            'has_qr' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            } else {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }

        $data = $validator->validated();
        $data['has_qr'] = $request->has('has_qr') ? 1 : 0;

        // ✅ Update site
        $site->update($data);

        // Handle QR generation or removal on update
        try {
            if (!empty($data['has_qr'])) {
                // Generate NFC tag if not exists
                if (empty($site->nfc_tag)) {
                    $nfcTag = 'NFC-SITE-' . strtoupper(substr(sha1(uniqid((string) rand(), true)), 0, 10));
                    $site->update(['nfc_tag' => $nfcTag]);

                    // Save initial NFC tag to filesystem as well
                    try {
                        $nfcDir = public_path('nfcForSites');
                        if (!File::exists($nfcDir)) File::makeDirectory($nfcDir, 0755, true);
                        $filename = 'site_' . $site->id . '_' . time() . '_' . substr(sha1($nfcTag),0,6) . '.txt';
                        file_put_contents($nfcDir . DIRECTORY_SEPARATOR . $filename, $nfcTag);
                    } catch (\Exception $e) {
                        Log::warning('Failed to save initial NFC file for site ' . $site->id . ': ' . $e->getMessage());
                    }
                }
                
                $this->generateQrForSite($site);
            } else {
                // remove existing QR image and NFC tag
                $filename = public_path('qrForSites/site_' . $site->id . '.png');
                if (File::exists($filename)) {
                    File::delete($filename);
                }
                $site->update(['nfc_tag' => null]);

                // Remove all NFC files for this site
                try {
                    $nfcDir = public_path('nfcForSites');
                    if (File::exists($nfcDir)) {
                        $pattern = $nfcDir . DIRECTORY_SEPARATOR . 'site_' . $site->id . '_*.txt';
                        foreach (glob($pattern) as $f) {
                            try { File::delete($f); } catch (\Exception $e) {}
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to delete NFC files for site ' . $site->id . ': ' . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to update QR/NFC for site ' . $site->id . ': ' . $e->getMessage());
        }

        // ✅ Sync employee types
        if ($request->has('employee_types')) {
            $pivotData = [];
            foreach ($request->employee_types as $typeId) {
                $pivotData[$typeId] = [
                    'guard_rate'  => $request->employee_guard_rate[$typeId] ?? null,
                    'office_rate' => $request->employee_office_rate[$typeId] ?? null,
                ];
            }
            $site->employeeTypes()->sync($pivotData);
        } else {
            $site->employeeTypes()->detach();
        }

        // ✅ Sync checkpoints (generate NFC tag if missing)
        if ($request->has('checkpoints')) {
            $existingIds = $site->checkpoints()->pluck('id')->toArray();
            $submittedIds = collect($request->checkpoints)->pluck('id')->filter()->toArray();

            // Delete removed checkpoints
            $toDelete = array_diff($existingIds, $submittedIds);
            if (!empty($toDelete)) {
                $site->checkpoints()->whereIn('id', $toDelete)->delete();
            }

            // Add/update checkpoints
            foreach ($request->checkpoints as $cp) {
                $nfc = $cp['nfc_tag'] ?? null;
                if (empty($nfc)) {
                    $nfc = 'NFC-' . strtoupper(substr(sha1(uniqid((string) rand(), true)), 0, 10));
                }

                if (!empty($cp['id'])) {
                    // Update existing
                    $site->checkpoints()->where('id', $cp['id'])->update([
                        'name'      => $cp['name'],
                        'latitude'  => $cp['latitude'],
                        'longitude' => $cp['longitude'],
                        'nfc_tag'   => $nfc,
                    ]);
                } else {
                    // Create new
                    $site->checkpoints()->create([
                        'name'      => $cp['name'],
                        'latitude'  => $cp['latitude'],
                        'longitude' => $cp['longitude'],
                        'nfc_tag'   => $nfc,
                    ]);
                }
            }
        } else {
            // If no checkpoints submitted, remove all
            $site->checkpoints()->delete();
        }

        // ✅ Sync staff-specific rates if submitted. If present, replace existing entries.
        if ($request->has('staff_rates')) {
            $rates = $request->input('staff_rates', []);
            if (is_string($rates)) {
                $decoded = json_decode($rates, true);
                $rates = is_array($decoded) ? $decoded : [];
            }
            if (!is_array($rates)) {
                $rates = [];
            }
            // remove existing
            $site->staffRates()->delete();
            foreach ($rates as $r) {
                if (empty($r['user_id'])) continue;
                $site->staffRates()->create([
                    'user_id' => $r['user_id'],
                    'guard_rate' => $r['guard_rate'] ?? null,
                ]);
            }
        } else {
            $site->staffRates()->delete();
        }

        // ✅ Sync holiday-specific rates if submitted. If none are sent, remove existing rates.
        if ($request->has('holiday_rates')) {
            $holidayRates = $request->input('holiday_rates', []);
            if (is_string($holidayRates)) {
                $decoded = json_decode($holidayRates, true);
                $holidayRates = is_array($decoded) ? $decoded : [];
            }
            if (!is_array($holidayRates)) {
                $holidayRates = [];
            }
            $site->siteHolidayRates()->delete();
            foreach ($holidayRates as $rate) {
                if (empty($rate['holiday_name']) || empty($rate['holiday_date'])) {
                    continue;
                }
                $site->siteHolidayRates()->create([
                    'holiday_name' => $rate['holiday_name'],
                    'holiday_date' => $rate['holiday_date'],
                    'site_rate' => $rate['site_rate'] ?? null,
                    'guard_rate' => $rate['guard_rate'] ?? null,
                ]);
            }
        } else {
            $site->siteHolidayRates()->delete();
        }

        return response()->json(['message' => 'Site updated successfully']);
    }

    public function edit($id)
    {
        $site = Site::with('employeeTypes','checkpoints','staffRates','siteHolidayRates')->find($id);
        // Attach NFC tags read from filesystem (not stored exclusively in DB)
        try {
            $site->nfc_tags = $this->getNfcTagsForSite($site->id);
        } catch (\Exception $e) {
            $site->nfc_tags = [];
        }

        $staffs = User::role('security_staff')->orderBy('first_name','asc')->get(['id','first_name','last_name']);
        $staffRates = $site->staffRates ? $site->staffRates->map(function ($r) {
            return [
                'id' => $r->id,
                'user_id' => $r->user_id,
                'guard_rate' => $r->guard_rate,
                'name' => optional($r->user)->first_name . ' ' . optional($r->user)->last_name,
            ];
        })->toArray() : [];

        $siteHolidayRates = $site->siteHolidayRates ? $site->siteHolidayRates->map(function ($r) {
            $holidayDate = $r->holiday_date;
            if (!($holidayDate instanceof \Carbon\Carbon) && !($holidayDate instanceof \Illuminate\Support\Carbon)) {
                $holidayDate = \Carbon\Carbon::parse($holidayDate);
            }
            return [
                'id' => $r->id,
                'holiday_name' => $r->holiday_name,
                'holiday_date' => $holidayDate->format('Y-m-d'),
                'site_rate' => $r->site_rate,
                'guard_rate' => $r->guard_rate,
            ];
        })->toArray() : [];

        $ukHolidays = [];
        try {
            $response = Http::get('https://www.gov.uk/bank-holidays.json');
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['england-and-wales']['events'])) {
                    $ukHolidays = collect($data['england-and-wales']['events'])
                        ->filter(function ($holiday) {
                            return \Carbon\Carbon::parse($holiday['date'])->isFuture();
                        })
                        ->map(function ($holiday) {
                            return [
                                'title' => $holiday['title'],
                                'date' => $holiday['date'],
                            ];
                        })
                        ->sortBy('date')
                        ->values()
                        ->all();
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch UK bank holidays: ' . $e->getMessage());
        }

        return response()->json([
            'site' => $site,
            'employee_types' => $site->employeeTypes->map(function ($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'guard_rate' => $type->pivot->guard_rate,
                    'office_rate' => $type->pivot->office_rate,
                ];
            }),
            'staffs' => $staffs,
            'staff_rates' => $staffRates,
            'site_holiday_rates' => $siteHolidayRates,
            'uk_holidays' => $ukHolidays,
        ]);
    }

    public function holidays()
    {
        $ukHolidays = [];
        try {
            $response = Http::get('https://www.gov.uk/bank-holidays.json');
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['england-and-wales']['events'])) {
                    $ukHolidays = collect($data['england-and-wales']['events'])
                        ->filter(function ($holiday) {
                            return \Carbon\Carbon::parse($holiday['date'])->isFuture();
                        })
                        ->map(function ($holiday) {
                            return [
                                'title' => $holiday['title'],
                                'date' => $holiday['date'],
                            ];
                        })
                        ->sortBy('date')
                        ->values()
                        ->all();
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch UK bank holidays: ' . $e->getMessage());
        }

        return response()->json(['uk_holidays' => $ukHolidays]);
    }

    public function delete($id)
    {
        $site = Site::findOrFail($id);
        Logger::log(Auth::user(), 'Delete', 'Site '.$site->site_name.' Deleted');

        $site->delete();

        return response()->json(['success' => true]);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:sites,id',
        ]);

        $sites = Site::whereIn('id', $request->ids)->get();
        foreach($sites as $site){
            Logger::log(Auth::user(), 'Delete', 'Site '.$site->site_name.' Deleted');
            $site->delete();
        }

        return response()->json(['message' => 'Selected sites deleted.']);
    }
    public function getLogs($id)
    {
        $site = Site::with('logs')->findOrFail($id);

        return response()->json([
            'logs' => $site->logs->map(function ($log) {
                return [
                    'user_name' => $log->user_name,
                    'action' => $log->action,
                    'description' => $log->description,
                    'time' => $log->created_at->diffForHumans(),
                    'success' => 'success',
                ];
            })
        ]);
    }
    public function view($id)
    {
        $site = Site::with(['client', 'checkpoints'])->findOrFail($id);

        return response()->json([
            'site_name'        => $site->site_name,
            'guard_names'      => $site->guard_names,
            'address'          => $site->address,
            'post_code'        => $site->post_code,
            'plus_code'        => $site->plus_code,
            'site_code'        => $site->site_code,
            'contact_number'   => $site->contact_number,
            'contact_person'   => $site->contact_person,
            'note'             => $site->note,
            'start_time'       => $site->start_time,
            'end_time'         => $site->end_time,
            'break_time'       => $site->break_time,
            'guard_rate'       => $site->guard_rate,
            'office_rate'      => $site->office_rate,
            'billable_rate'    => $site->billable_rate,
            'payable_rate'     => $site->payable_rate,
            'manager_1_name'   => $site->manager_1_id ?? '',
            'manager_2_name'   => $site->manager_2_id ?? '',
            'has_qr' => (bool) $site->has_qr,
            'nfc_tag' => $site->nfc_tag,
            'nfc_tags' => $this->getNfcTagsForSite($site->id),

            // QR image URL if generated (served from public/qrForSites)
            'qr_image' => file_exists(public_path('qrForSites/site_' . $site->id . '.png')) ? asset('qrForSites/site_' . $site->id . '.png') : null,

            // ✅ Add checkpoints array
            'checkpoints' => $site->checkpoints->map(function ($cp) {
                return [
                    'id'        => $cp->id,
                    'name'      => $cp->name,
                    'latitude'  => $cp->latitude,
                    'longitude' => $cp->longitude,
                    'qr_code'   => $cp->qr_code,
                    'nfc_tag'   => $cp->nfc_tag,
                    'required'  => $cp->required,
                ];
            })->toArray(),
                    'radius' => $site->radius,
        ]);
    }

    /**
     * Generate a QR image for a site and save it to public/sites/qrcodes
     */
    public function generateQr($id)
    {
        $site = Site::findOrFail($id);
        try {
            $this->generateQrForSite($site);
            return response()->json(['message' => 'QR generated', 'qr_image' => asset('qrForSites/site_' . $site->id . '.png')]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'QR generation failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Regenerate the single NFC tag for a site (overwrites existing)
     */
    public function generateNfc($id)
    {
        $site = Site::findOrFail($id);

        if (empty($site->has_qr)) {
            return response()->json(['error' => 'NFC generation is disabled for this site'], 422);
        }

        $nfcTag = 'NFC-SITE-' . strtoupper(substr(sha1(uniqid((string) rand(), true)), 0, 10));

        try {
            // Overwrite the single site NFC tag in the DB
            $site->update(['nfc_tag' => $nfcTag]);

            return response()->json([
                'message'  => 'NFC tag regenerated',
                'tag'      => $nfcTag,
                'nfc_tags' => [['tag' => $nfcTag]],
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to regenerate NFC for site ' . $site->id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to regenerate NFC'], 500);
        }
    }

    /**
     * Return an array of NFC tags (read from filesystem) for a site
     */
    private function getNfcTagsForSite($siteId)
    {
        $nfcDir = $this->getNfcDir();
        $result = [];
        if (!File::exists($nfcDir)) return $result;

        $pattern = $nfcDir . DIRECTORY_SEPARATOR . 'site_' . $siteId . '_*.txt';
        foreach (glob($pattern) as $path) {
            try {
                $tag = trim(file_get_contents($path));
                $filename = basename($path);
                $result[] = [
                    'tag' => $tag,
                    'file' => asset('nfcForSites/' . $filename),
                    'filename' => $filename,
                ];
            } catch (\Exception $e) {
                // skip
            }
        }

        return $result;
    }

    private function getNfcDir()
    {
        return public_path('nfcForSites');
    }

    /**
     * Helper: generate and save QR PNG for a Site
     */
    private function generateQrForSite(Site $site)
    {
        // Save temporarily under storage/app/qrForSites, then copy to public/qrForSites
        $tempDir = storage_path('app/qrForSites');
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        // Content to encode in QR (use site id URL or a token)
        $content = config('app.url') . '/sites/' . $site->id;

        $size = 400;
        $chl = urlencode($content);
        $qrUrl = "https://chart.googleapis.com/chart?cht=qr&chs={$size}x{$size}&chl={$chl}&chld=L|1";

        try {
            $resp = Http::timeout(10)->get($qrUrl);
        } catch (\Exception $e) {
            Log::warning('QR generation primary request failed for site ' . $site->id . ': ' . $e->getMessage());
            $resp = null;
        }

        // If primary service failed or returned non-success, try fallback QR provider
        if (empty($resp) || !$resp->successful()) {
            $primaryStatus = $resp ? $resp->status() : 'no-response';
            $primaryBody = $resp ? substr($resp->body(), 0, 500) : '';
            Log::warning("Primary QR service failed (status={$primaryStatus}) for site {$site->id}; attempting fallback. body=" . $primaryBody);

            $fallbackUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$chl}";
            try {
                $resp = Http::timeout(10)->get($fallbackUrl);
            } catch (\Exception $e) {
                Log::warning('QR generation fallback request failed for site ' . $site->id . ': ' . $e->getMessage());
                $resp = null;
            }
        }

        if (empty($resp) || !$resp->successful()) {
            $status = $resp ? $resp->status() : 'no-response';
            $bodySnippet = $resp ? substr($resp->body(), 0, 500) : '';
            throw new \Exception('Failed to fetch QR image from external service (status=' . $status . '). body=' . $bodySnippet);
        }

        $filename = 'site_' . $site->id . '.png';
        $tempPath = $tempDir . DIRECTORY_SEPARATOR . $filename;
        file_put_contents($tempPath, $resp->body());

        // Ensure public target exists, then copy file there for web serving
        $publicDir = public_path('qrForSites');
        if (!File::exists($publicDir)) {
            File::makeDirectory($publicDir, 0755, true);
        }
        $publicPath = $publicDir . DIRECTORY_SEPARATOR . $filename;
        File::copy($tempPath, $publicPath);

        // If the site has checkpoints, ensure each checkpoint has an NFC tag
        // that points to the same URL encoded in the QR (with a checkpoint query).
        // This makes the NFC payload effectively the same destination as the QR.
        try {
            $checkpoints = $site->checkpoints()->get();
            foreach ($checkpoints as $cp) {
                $expected = $content . '?checkpoint=' . $cp->id;
                if (empty($cp->nfc_tag)) {
                    $cp->update(['nfc_tag' => $expected]);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to set NFC tags for site ' . $site->id . ': ' . $e->getMessage());
        }

        return $publicPath;
    }
}
