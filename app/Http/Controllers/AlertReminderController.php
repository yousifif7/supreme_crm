<?php

namespace App\Http\Controllers;

use App\DataTables\AlertRemindersDataTable;
use App\Models\AlertReminder;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AlertReminderController extends Controller
{
    public function index()
    {
        $vehicles = Vehicle::all();
        return view('vehicle_management.alert_reminders', compact('vehicles'));
    }

    public function data(AlertRemindersDataTable $dataTable)
    {
        // $vehicles = Vehicle::all();
        return $dataTable->ajax();
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,id',
            'mot_due_date' => 'required|date',
            'insurance_renewal_date' => 'required|date',
            'tax_renewal_date' => 'required|date',
            'service_due_date' => 'required|date',
            'tachograph_calibration_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            } else {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }

        AlertReminder::create($validator->validated());

        return response()->json(['message' => 'Reminder added successfully.']);
    }
    public function update(Request $request, $id)
    {
        $reminder = AlertReminder::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,id',
            'mot_due_date' => 'required|date',
            'insurance_renewal_date' => 'required|date',
            'tax_renewal_date' => 'required|date',
            'service_due_date' => 'required|date',
            'tachograph_calibration_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $reminder->update($validator->validated());

        return response()->json(['message' => 'Reminder updated successfully.']);
    }

    public function edit($id)
    {
        $reminder = AlertReminder::find($id);
        return response()->json(['reminder' => $reminder]);
    }
    public function delete($id)
    {
        $reminder = AlertReminder::findOrFail($id);
        $reminder->delete();

        return response()->json(['success' => true, 'message' => 'Reminder record deleted successfully.']);
    }
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:alert_reminders,id',
        ]);

        AlertReminder::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => 'Selected Alert & reminder records deleted successfully.']);
    }
}
