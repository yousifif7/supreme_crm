<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\EmployeeType;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SiteController extends Controller
{
    public function index()
    {
        $clients = Client::get();
        $employee_types = EmployeeType::all();
        $sites = Site::with('client')->orderBy('id', 'desc')->paginate(15);
        return view('sites.index', compact('sites', 'clients', 'employee_types'));
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id'      => 'required|integer',
            'site_name'      => 'required|string|max:255',
            'site_group'     => 'nullable|string|max:255',
            'address'        => 'required|string|max:255',
            'post_code'      => 'required|string|max:50',
            'site_code'      => 'required|string|max:50',
            'contact_number' => 'required|string|max:50',
            'note'           => 'required|string|max:1000',
            'manager_1_id'   => 'nullable|integer',
            'manager_2_id'   => 'nullable|integer',
            'start_time'     => 'nullable|string',
            'end_time'       => 'nullable|string',
            'break_time'     => 'nullable|string',
            'guard_rate'     => 'required|numeric',
            'office_rate'    => 'required|numeric',
            'billable_rate'  => 'required|numeric',
            'payable_rate'   => 'required|numeric',
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
            'site_group'     => 'nullable|string|max:255',
            'address'        => 'required|string|max:255',
            'post_code'      => 'required|string|max:50',
            'site_code'      => 'required|string|max:50',
            'contact_number' => 'required|string|max:50',
            'note'           => 'required|string|max:1000',
            'manager_1_id'   => 'nullable|integer',
            'manager_2_id'   => 'nullable|integer',
            'start_time'     => 'nullable|string',
            'end_time'       => 'nullable|string',
            'break_time'     => 'nullable|string',
            'guard_rate'     => 'required|numeric',
            'office_rate'    => 'required|numeric',
            'billable_rate'  => 'required|numeric',
            'payable_rate'   => 'required|numeric',
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
}
