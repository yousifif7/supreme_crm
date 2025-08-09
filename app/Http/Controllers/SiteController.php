<?php

namespace App\Http\Controllers;

use App\DataTables\SitesDataTable;
use App\Models\Client;
use App\Models\EmployeeType;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

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
            'guard_names'        => 'nullable|string|max:255',
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
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            } else {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }

        $data = $validator->validated();

        // Save site
        $site = Site::create($data);
        // Attach employee types with pivot data
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
        return response()->json(['message' => 'Site created successfully']);
    }
    public function update(Request $request, $id)
    {
        $site = Site::find($id);

        $validator = Validator::make($request->all(), [
            'client_id'      => 'required|integer',
            'site_name'      => 'required|string|max:255',
            'guard_names'        => 'nullable|string|max:255',
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
            'employee_types' => 'nullable|array',
            'employee_types.*' => 'integer|exists:employee_types,id',
            'employee_guard_rate' => 'nullable|array',
            'employee_office_rate' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            } else {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }

        $data = $validator->validated();

        // ✅ Update site data
        $site->update($data);

        // ✅ Sync Employee Types with pivot rates
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
            // If no employee_types sent, detach all (optional but clean)
            $site->employeeTypes()->detach();
        }

        return response()->json(['message' => 'Site updated successfully']);
    }

    public function edit($id)
    {
        $site = Site::with('employeeTypes')->find($id);

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
        $site->delete();

        return response()->json(['success' => true]);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:sites,id',
        ]);

        Site::whereIn('id', $request->ids)->delete();

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
        $site = Site::with(['client'])->findOrFail($id);

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
        ]);
    }
}
