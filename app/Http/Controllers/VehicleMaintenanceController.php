<?php

namespace App\Http\Controllers;

use App\DataTables\VehicleMaintenancesDataTable;
use App\Models\Vehicle;
use App\Models\VehicleMaintenance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VehicleMaintenanceController extends Controller
{
    public function index()
    {
        $vehicles = Vehicle::all();
        return view('vehicle_management.vehicle_maintainance', compact('vehicles'));
    }
    
    public function data(VehicleMaintenancesDataTable $dataTable)
    {
        // $vehicles = Vehicle::all();
        return $dataTable->ajax();
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicle_id'              => 'required|exists:vehicles,id',
            'last_service_date'       => 'required|date',
            'next_service_due_date'   => 'required|date',
            'work_type'               => 'required|string|max:255',
            'maintenance_date'        => 'required|date',
            'garage_provider'         => 'required|string|max:255',
            'reported_by'             => 'required|string|max:255',
            'date_reported'           => 'required|date',
            'resolution_status'       => 'required|string|in:pending,resolved,in_progress',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            } else {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }

        VehicleMaintenance::create($validator->validated());

        return response()->json(['message' => 'Maintenance record created successfully']);
    }
    public function update(Request $request, $id)
    {
        $maintenance = VehicleMaintenance::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'vehicle_id'              => 'required|exists:vehicles,id',
            'last_service_date'       => 'required|date',
            'next_service_due_date'   => 'required|date',
            'work_type'               => 'required|string|max:255',
            'maintenance_date'        => 'required|date',
            'garage_provider'         => 'required|string|max:255',
            'reported_by'             => 'required|string|max:255',
            'date_reported'           => 'required|date',
            'resolution_status'       => 'required|string|in:pending,resolved,in_progress',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $maintenance->update($validator->validated());

        return response()->json(['message' => 'Maintenance record updated successfully']);
    }

    public function edit($id)
    {
        $maintenance = VehicleMaintenance::find($id);
        return response()->json(['maintenance' => $maintenance]);
    }
    public function delete($id)
    {
        $maintenance = VehicleMaintenance::findOrFail($id);
        $maintenance->delete();

        return response()->json(['success' => true, 'message' => 'Maintenance record deleted successfully.']);
    }
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:vehicle_maintenances,id',
        ]);

        VehicleMaintenance::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => 'Selected maintenance records deleted successfully.']);
    }
}
