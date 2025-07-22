<?php

namespace App\Http\Controllers;

use App\DataTables\VehicleCompliancesDataTable;
use App\Models\Vehicle;
use App\Models\VehicleCompliance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VehicleComplianceController extends Controller
{
    public function index(VehicleCompliancesDataTable $dataTable)
    {
        $vehicles = Vehicle::all();
        return $dataTable->render('vehicle_management.compliances', compact('vehicles'));
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required',
            'mot_certificate_number'      => 'required|string|max:255',
            'mot_expiry_date'             => 'required|date',
            'insurance_provider'          => 'required|string|max:255',
            'insurance_policy_number'     => 'required|string|max:255',
            'insurance_expiry_date'       => 'required|date',
            'vehicle_tax_status'          => 'required|string|max:255',
            'tax_expiry_date'             => 'required|date',
            'tax_class'                   => 'required|string|max:255',
            'v5c_logbook_reference_number' => 'required|string|max:255',
            'lez_ulez_compliant'          => 'required|boolean',
            'tachograph_certificate_number' => 'required|string|max:255',
            'tachograph_calibration_expiry' => 'required|date',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            } else {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }


        VehicleCompliance::create($validator->validated());

        return response()->json(['message' => 'Vehicle compliance created successfully']);
    }
    public function update(Request $request, $id)
    {
        $vehicle = VehicleCompliance::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required',
            'mot_certificate_number'      => 'required|string|max:255',
            'mot_expiry_date'             => 'required|date',
            'insurance_provider'          => 'required|string|max:255',
            'insurance_policy_number'     => 'required|string|max:255',
            'insurance_expiry_date'       => 'required|date',
            'vehicle_tax_status'          => 'required|string|max:255',
            'tax_expiry_date'             => 'required|date',
            'tax_class'                   => 'required|string|max:255',
            'v5c_logbook_reference_number' => 'required|string|max:255',
            'lez_ulez_compliant'          => 'required|boolean',
            'tachograph_certificate_number' => 'required|string|max:255',
            'tachograph_calibration_expiry' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vehicle->update($validator->validated());

        return response()->json(['message' => 'Vehicle compliance updated successfully']);
    }

    public function edit($id)
    {
        $compliance = VehicleCompliance::findOrFail($id);
        return response()->json(['compliance' => $compliance]);
    }

    public function delete($id)
    {
        $vehicle = VehicleCompliance::findOrFail($id);
        $vehicle->delete();

        return response()->json(['success' => true]);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:vehicles,id',
        ]);

        VehicleCompliance::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => 'Selected vehicles deleted.']);
    }
}
