<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VehicleController extends Controller
{
    public function vehicle_details()
    {
        $vehicles = Vehicle::paginate(10);
        return view('vehicle_management/vehicle_detail', compact('vehicles'));
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'registration_number'     => 'required|string|max:255|unique:vehicles',
            'make'                    => 'required|string|max:255',
            'model'                   => 'required|string|max:255',
            'year_of_manufacture'     => 'required|integer|min:1900|max:' . date('Y'),
            'colour'                  => 'required|string|max:50',
            'body_type'               => 'required|string|max:50',
            'fuel_type'               => 'required|string|max:50',
            'engine_size'             => 'required|numeric|max:99.99',
            'vin'                     => 'required|string|max:255|unique:vehicles',
            'odometer_reading'        => 'required|integer',
            'first_registration_date' => 'required|date',
            'vehicle_category'        => 'required|string|max:100',
            'assigned_to'             => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            } else {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }


        Vehicle::create($validator->validated());

        return response()->json(['message' => 'Vehicle created successfully']);
    }

    public function update(Request $request, $id)
    {
        $vehicle = Vehicle::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'registration_number'     => 'required|string|max:255|unique:vehicles,registration_number,' . $vehicle->id,
            'make'                    => 'required|string|max:255',
            'model'                   => 'required|string|max:255',
            'year_of_manufacture'     => 'required|integer|min:1900|max:' . date('Y'),
            'colour'                  => 'required|string|max:50',
            'body_type'               => 'required|string|max:50',
            'fuel_type'               => 'required|string|max:50',
            'engine_size'             => 'required|numeric|max:99.99',
            'vin'                     => 'required|string|max:255|unique:vehicles,vin,' . $vehicle->id,
            'odometer_reading'        => 'required|integer',
            'first_registration_date' => 'required|date',
            'vehicle_category'        => 'required|string|max:100',
            'assigned_to'             => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vehicle->update($validator->validated());

        return response()->json(['message' => 'Vehicle updated successfully']);
    }

    public function edit($id)
    {
        $vehicle = Vehicle::findOrFail($id);
        return response()->json(['vehicle' => $vehicle]);
    }

    public function delete($id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $vehicle->delete();

        return response()->json(['success' => true]);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:vehicles,id',
        ]);

        Vehicle::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => 'Selected vehicles deleted.']);
    }
}
