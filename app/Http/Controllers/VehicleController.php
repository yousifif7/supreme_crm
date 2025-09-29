<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Models\VehicleCompliance;
use App\Models\VehicleMaintenance;
use Illuminate\Support\Facades\DB;
use App\Models\RoadworthinessCheck;
use App\DataTables\VehiclesDataTable;
use Illuminate\Support\Facades\Validator;
use App\DataTables\AlertRemindersDataTable;
use App\DataTables\VehicleCompliancesDataTable;
use App\DataTables\VehicleMaintenancesDataTable;
use App\DataTables\DocumentationUploadsDataTable;
use App\DataTables\RoadworthinessChecksDataTable;
use App\Models\AlertReminder;

class VehicleController extends Controller
{

    public function management(
        VehiclesDataTable $vehiclesDataTable,
        VehicleCompliancesDataTable $vehicleComplianceTable,
        VehicleMaintenancesDataTable $vehicleMaintenanceTable,
        RoadworthinessChecksDataTable $roadworthinessTable,
        DocumentationUploadsDataTable $documentationTable,
        AlertRemindersDataTable $alertTable
    ) {
        $vehicles = Vehicle::all();

        return view('vehicle_management.mainindex', compact(
            'vehiclesDataTable',
            'vehicleComplianceTable',
            'vehicleMaintenanceTable',
            'roadworthinessTable',
            'documentationTable',
            'alertTable',
            'vehicles',
        ));
    }

    public function vehicle_details()
    {
        return view('vehicle_management.vehicle_detail');
    }

    public function vehicle_details_data(VehiclesDataTable $dataTable)
    {
        return $dataTable->ajax();
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Vehicle
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

            // Compliance
            'mot_certificate_number'         => 'nullable|string|max:255',
            'mot_expiry_date'                => 'nullable|date',
            'insurance_provider'             => 'nullable|string|max:255',
            'insurance_policy_number'        => 'nullable|string|max:255',
            'insurance_expiry_date'          => 'nullable|date',
            'vehicle_tax_status'             => 'nullable|string|max:255',
            'tax_expiry_date'                => 'nullable|date',
            'tax_class'                      => 'nullable|string|max:255',
            'v5c_logbook_reference_number'   => 'nullable|string|max:255',
            'lez_ulez_compliant'             => 'nullable|boolean',
            'tachograph_certificate_number'  => 'nullable|string|max:255',
            'tachograph_calibration_expiry'  => 'nullable|date',

            // Maintenance
            'last_service_date'       => 'nullable|date',
            'next_service_due_date'   => 'nullable|date',
            'work_type'               => 'nullable|string|max:255',
            'maintenance_date'        => 'nullable|date',
            'garage_provider'         => 'nullable|string|max:255',
            'reported_by'             => 'nullable|string|max:255',
            'date_reported'           => 'nullable|date',
            'resolution_status'       => 'nullable|string|in:pending,resolved,in_progress',

            // Roadworthiness Check
            'date_completed'          => 'nullable|date',
            'checked_by'              => 'nullable|string|max:255',
            'defects_found'           => 'nullable|string',
            'corrective_action_taken' => 'nullable|string',

            // Alerts
            'mot_due_date'               => 'nullable|date',
            'insurance_renewal_date'     => 'nullable|date',
            'tax_renewal_date'           => 'nullable|date',
            'service_due_date'           => 'nullable|date',
            'tachograph_calibration_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            } else {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }

        $data = $validator->validated();

        DB::beginTransaction();

        try {
            // 1. Store Vehicle
            $vehicle = Vehicle::create([
                'registration_number'     => $data['registration_number'],
                'make'                    => $data['make'],
                'model'                   => $data['model'],
                'year_of_manufacture'     => $data['year_of_manufacture'],
                'colour'                  => $data['colour'],
                'body_type'               => $data['body_type'],
                'fuel_type'               => $data['fuel_type'],
                'engine_size'             => $data['engine_size'],
                'vin'                     => $data['vin'],
                'odometer_reading'        => $data['odometer_reading'],
                'first_registration_date' => $data['first_registration_date'],
                'vehicle_category'        => $data['vehicle_category'],
                'assigned_to'             => $data['assigned_to'],
            ]);

            // 2. Store Compliance
            VehicleCompliance::create(array_merge(
                Arr::only($data, [
                    'mot_certificate_number',
                    'mot_expiry_date',
                    'insurance_provider',
                    'insurance_policy_number',
                    'insurance_expiry_date',
                    'vehicle_tax_status',
                    'tax_expiry_date',
                    'tax_class',
                    'v5c_logbook_reference_number',
                    'lez_ulez_compliant',
                    'tachograph_certificate_number',
                    'tachograph_calibration_expiry'
                ]),
                ['vehicle_id' => $vehicle->id]
            ));

            // 3. Store Maintenance
            VehicleMaintenance::create(array_merge(
                Arr::only($data, [
                    'last_service_date',
                    'next_service_due_date',
                    'work_type',
                    'maintenance_date',
                    'garage_provider',
                    'reported_by',
                    'date_reported',
                    'resolution_status'
                ]),
                ['vehicle_id' => $vehicle->id]
            ));

            // 4. Store Roadworthiness Check
            RoadworthinessCheck::create(array_merge(
                Arr::only($data, [
                    'date_completed',
                    'checked_by',
                    'defects_found',
                    'corrective_action_taken'
                ]),
                ['vehicle_id' => $vehicle->id]
            ));

            // 5. Store Alerts
            AlertReminder::create(array_merge(
                Arr::only($data, [
                    'mot_due_date',
                    'insurance_renewal_date',
                    'tax_renewal_date',
                    'service_due_date',
                    'tachograph_calibration_date'
                ]),
                ['vehicle_id' => $vehicle->id]
            ));

            DB::commit();

            return response()->json(['message' => 'Vehicle and related data created successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $vehicle = Vehicle::findOrFail($id);

        $validator = Validator::make($request->all(), [
            // Required core vehicle fields
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

            // Compliance (optional)
            'mot_certificate_number'         => 'nullable|string|max:255',
            'mot_expiry_date'                => 'nullable|date',
            'insurance_provider'             => 'nullable|string|max:255',
            'insurance_policy_number'        => 'nullable|string|max:255',
            'insurance_expiry_date'          => 'nullable|date',
            'vehicle_tax_status'             => 'nullable|string|max:255',
            'tax_expiry_date'                => 'nullable|date',
            'tax_class'                      => 'nullable|string|max:255',
            'v5c_logbook_reference_number'   => 'nullable|string|max:255',
            'lez_ulez_compliant'             => 'nullable|boolean',
            'tachograph_certificate_number'  => 'nullable|string|max:255',
            'tachograph_calibration_expiry'  => 'nullable|date',

            // Maintenance (optional)
            'last_service_date'       => 'nullable|date',
            'next_service_due_date'   => 'nullable|date',
            'work_type'               => 'nullable|string|max:255',
            'maintenance_date'        => 'nullable|date',
            'garage_provider'         => 'nullable|string|max:255',
            'reported_by'             => 'nullable|string|max:255',
            'date_reported'           => 'nullable|date',
            'resolution_status'       => 'nullable|string|in:pending,resolved,in_progress',

            // Roadworthiness (optional)
            'date_completed'          => 'nullable|date',
            'checked_by'              => 'nullable|string|max:255',
            'defects_found'           => 'nullable|string',
            'corrective_action_taken' => 'nullable|string',

            // Alerts (optional)
            'mot_due_date'               => 'nullable|date',
            'insurance_renewal_date'     => 'nullable|date',
            'tax_renewal_date'           => 'nullable|date',
            'service_due_date'           => 'nullable|date',
            'tachograph_calibration_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        DB::beginTransaction();
        try {
            // Update vehicle core fields
            $vehicle->update(Arr::only($data, [
                'registration_number',
                'make',
                'model',
                'year_of_manufacture',
                'colour',
                'body_type',
                'fuel_type',
                'engine_size',
                'vin',
                'odometer_reading',
                'first_registration_date',
                'vehicle_category',
                'assigned_to'
            ]));

            // Update or create related records (all fields optional)
            VehicleCompliance::updateOrCreate(
                ['vehicle_id' => $vehicle->id],
                array_merge(
                    Arr::only($data, [
                        'mot_certificate_number',
                        'mot_expiry_date',
                        'insurance_provider',
                        'insurance_policy_number',
                        'insurance_expiry_date',
                        'vehicle_tax_status',
                        'tax_expiry_date',
                        'tax_class',
                        'v5c_logbook_reference_number',
                        'lez_ulez_compliant',
                        'tachograph_certificate_number',
                        'tachograph_calibration_expiry'
                    ]),
                    ['vehicle_id' => $vehicle->id]
                )
            );

            VehicleMaintenance::updateOrCreate(
                ['vehicle_id' => $vehicle->id],
                array_merge(
                    Arr::only($data, [
                        'last_service_date',
                        'next_service_due_date',
                        'work_type',
                        'maintenance_date',
                        'garage_provider',
                        'reported_by',
                        'date_reported',
                        'resolution_status'
                    ]),
                    ['vehicle_id' => $vehicle->id]
                )
            );

            RoadworthinessCheck::updateOrCreate(
                ['vehicle_id' => $vehicle->id],
                array_merge(
                    Arr::only($data, [
                        'date_completed',
                        'checked_by',
                        'defects_found',
                        'corrective_action_taken'
                    ]),
                    ['vehicle_id' => $vehicle->id]
                )
            );

            AlertReminder::updateOrCreate(
                ['vehicle_id' => $vehicle->id],
                array_merge(
                    Arr::only($data, [
                        'mot_due_date',
                        'insurance_renewal_date',
                        'tax_renewal_date',
                        'service_due_date',
                        'tachograph_calibration_date'
                    ]),
                    ['vehicle_id' => $vehicle->id]
                )
            );

            DB::commit();

            return response()->json(['message' => 'Vehicle and related data updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function edit($id)
    {
        $vehicle = Vehicle::findOrFail($id);

        // fetch related records directly (doesn't rely on Vehicle relationships)
        $compliance = VehicleCompliance::where('vehicle_id', $vehicle->id)->first();
        $maintenance = VehicleMaintenance::where('vehicle_id', $vehicle->id)->first();
        $roadworthiness = RoadworthinessCheck::where('vehicle_id', $vehicle->id)->first();
        $alerts = AlertReminder::where('vehicle_id', $vehicle->id)->first();

        return response()->json([
            'vehicle' => $vehicle,
            'compliance' => $compliance,
            'maintenance' => $maintenance,
            'roadworthiness' => $roadworthiness,
            'alerts' => $alerts,
        ]);
    }

    public function delete($id)
    {
        DB::beginTransaction();
        try {
            $vehicle = Vehicle::findOrFail($id);

            // Delete related records
            VehicleCompliance::where('vehicle_id', $vehicle->id)->delete();
            VehicleMaintenance::where('vehicle_id', $vehicle->id)->delete();
            RoadworthinessCheck::where('vehicle_id', $vehicle->id)->delete();
            AlertReminder::where('vehicle_id', $vehicle->id)->delete();

            // Delete vehicle
            $vehicle->delete();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Vehicle and related data deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:vehicles,id',
        ]);

        DB::beginTransaction();
        try {
            $vehicleIds = $request->ids;

            // Delete related records first
            VehicleCompliance::whereIn('vehicle_id', $vehicleIds)->delete();
            VehicleMaintenance::whereIn('vehicle_id', $vehicleIds)->delete();
            RoadworthinessCheck::whereIn('vehicle_id', $vehicleIds)->delete();
            AlertReminder::whereIn('vehicle_id', $vehicleIds)->delete();

            // Delete vehicles
            Vehicle::whereIn('id', $vehicleIds)->delete();

            DB::commit();
            return response()->json(['message' => 'Selected vehicles and related data deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
