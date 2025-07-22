<?php

namespace App\Http\Controllers;

use App\DataTables\RoadworthinessChecksDataTable;
use App\Models\RoadworthinessCheck;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoadworthinessCheckController extends Controller
{
    public function index(RoadworthinessChecksDataTable $dataTable)
    {
        $vehicles = Vehicle::all();
        return $dataTable->render('vehicle_management.checks', compact('vehicles'));
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicle_id'                => 'required|exists:vehicles,id',
            'date_completed'           => 'required|date',
            'checked_by'               => 'required|string|max:255',
            'defects_found'            => 'nullable|string',
            'corrective_action_taken'  => 'nullable|string',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            } else {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }

        RoadworthinessCheck::create($validator->validated());

        return response()->json(['message' => 'Roadworthiness check added successfully.']);
    }
    public function update(Request $request, $id)
    {
        $check = RoadworthinessCheck::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'vehicle_id'               => 'required|exists:vehicles,id',
            'date_completed'          => 'required|date',
            'checked_by'              => 'required|string|max:255',
            'defects_found'           => 'nullable|string',
            'corrective_action_taken' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $check->update($validator->validated());

        return response()->json(['message' => 'Roadworthiness check updated successfully.']);
    }


    public function edit($id)
    {
        $check = RoadworthinessCheck::find($id);
        return response()->json(['check' => $check]);
    }
    public function delete($id)
    {
        $check = RoadworthinessCheck::findOrFail($id);
        $check->delete();

        return response()->json(['success' => true, 'message' => 'RoadworthinessCheck record deleted successfully.']);
    }
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:roadworthiness_checks,id',
        ]);

        RoadworthinessCheck::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => 'Selected RoadworthinessCheck records deleted successfully.']);
    }
}
