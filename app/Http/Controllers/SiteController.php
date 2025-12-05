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
            'site_code'      => 'nullable|string|max:50',
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

            // ✅ Checkpoints validation
            'checkpoints'                => 'nullable|array',
            'checkpoints.*.name'         => 'required_with:checkpoints|string|max:255',
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

        // ✅ Save Checkpoints
        if ($request->has('checkpoints')) {
            foreach ($request->checkpoints as $checkpoint) {
                $site->checkpoints()->create([
                    'name'      => $checkpoint['name'],
                    'latitude'  => $checkpoint['latitude'] ?? null,
                    'longitude' => $checkpoint['longitude'] ?? null,
                    'qr_code'   => $checkpoint['qr_code'] ?? null,
                    'nfc_tag'   => $checkpoint['nfc_tag'] ?? null,
                    'required'  => $checkpoint['required'] ?? false,
                ]);
            }
        }

        Logger::log(Auth::user(), 'Create', 'Site '.$site->site_name.' Created');

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

            // checkpoints validation
            'checkpoints'   => 'nullable|array',
            'checkpoints.*.id' => 'nullable|integer|exists:checkpoints,id',
            'checkpoints.*.name' => 'required_with:checkpoints|string|max:255',
            'checkpoints.*.latitude' => 'required_with:checkpoints|numeric',
            'checkpoints.*.longitude' => 'required_with:checkpoints|numeric',
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
        Logger::log(Auth::user(), 'Update', 'Site '.$site->site_name.' Updated');

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

        // ✅ Sync checkpoints
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
                if (!empty($cp['id'])) {
                    // Update existing
                    $site->checkpoints()->where('id', $cp['id'])->update([
                        'name'      => $cp['name'],
                        'latitude'  => $cp['latitude'],
                        'longitude' => $cp['longitude'],
                    ]);
                } else {
                    // Create new
                    $site->checkpoints()->create([
                        'name'      => $cp['name'],
                        'latitude'  => $cp['latitude'],
                        'longitude' => $cp['longitude'],
                    ]);
                }
            }
        } else {
            // If no checkpoints submitted, remove all
            $site->checkpoints()->delete();
        }

        return response()->json(['message' => 'Site updated successfully']);
    }

    public function edit($id)
    {
        $site = Site::with('employeeTypes','checkpoints')->find($id);

        return response()->json([
            'site' => $site,
            'employee_types' => $site->employeeTypes->map(function ($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'guard_rate' => $type->pivot->guard_rate,
                    'office_rate' => $type->pivot->office_rate,
                ];
            })
        ]);
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
        ]);
    }
}
