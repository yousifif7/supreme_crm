<?php

namespace App\Http\Controllers;

use App\DataTables\SubcontractorsDataTable;
use App\Models\Subcontractor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class SubContractorController extends Controller
{
    public function index(SubcontractorsDataTable $dataTable)
    {
        return $dataTable->render('employees.sub_contractors');
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_name'       => 'required|string|max:255|unique:users,username',
            'company_address'    => 'nullable|string|max:355',
            'contact_number'     => [
                'nullable',
                'string',
                'min:9',
                'max:50',
                'regex:/^(\+?\d{1,3})?[-.\s]?\(?\d+\)?([-.\s]?\d+)*$/'
            ],
            'contact_person'     => 'nullable|string|max:255',
            // 'email'              => 'required|email:dns|max:255',
            'email'           => 'required|email|max:255|unique:users,email',
            'password'           => [
                'required',
                'string',
                'min:6',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/'
            ],
            'invoice_terms'      => 'nullable|string|max:255',
            'payment_terms'      => 'nullable|string|max:255',
            'department'         => 'nullable|string|max:255',
            'vat_registered'     => 'nullable',
            'vat_number'         => 'nullable|string|max:255',
            'pay_rate'           => 'nullable|numeric',
            'pmva_trained_officer' => 'nullable',
        ], [
            'contact_number.regex' => 'The contact number format is invalid. It should be a valid phone number.',
            'password.regex' => 'The password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // Create user for subcontractor
        $user = User::create([
            'name'     => $data['company_name'],
            'first_name'     => $data['company_name'],
            'last_name'     => '',
            'username' => $data['company_name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $role = Role::firstOrCreate(['name' => 'subcontractor']);
        $user->assignRole($role);

        $data['user_id'] = $user->id;
        unset($data['password']);
        // unset($data['username'], $data['password']);

        Subcontractor::create($data);

        return response()->json(['message' => 'Subcontractor created successfully']);
    }
    public function update(Request $request, $id)
    {
        $subcontractor = Subcontractor::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'company_name'       => 'required|string|max:255',
            'company_address'    => 'nullable|string|max:355',
            'contact_number'     => [
                'nullable',
                'string',
                'min:9',
                'max:50',
                'regex:/^(\+?\d{1,3})?[-.\s]?\(?\d+\)?([-.\s]?\d+)*$/'
            ],
            'contact_person'     => 'nullable|string|max:255',
            'email'              => 'required|email:dns|max:255',
            'invoice_terms'      => 'nullable|string|max:255',
            'payment_terms'      => 'nullable|string|max:255',
            'department'         => 'nullable|string|max:255',
            'vat_registered'     => 'nullable',
            'vat_number'         => 'nullable|string|max:255',
            'pay_rate'           => 'nullable|numeric',
            'pmva_trained_officer' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $subcontractor->update($data);

        return response()->json(['message' => 'Subcontractor updated successfully']);
    }
    public function edit($id)
    {
        $subcontractor = Subcontractor::find($id);
        return response()->json(['subcontractor' => $subcontractor]);
    }

    public function delete($id)
    {
        $subcontractor = Subcontractor::findOrFail($id);
        $subcontractor->delete();

        return response()->json(['success' => true]);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:sub_contractors,id',
        ]);

        Subcontractor::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => 'Selected subcontractors deleted.']);
    }

    public function getLogs($id)
    {
        $subcontractor = Subcontractor::with('logs')->findOrFail($id);

        return response()->json([
            'logs' => $subcontractor->logs->map(function ($log) {
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
        $subcontractor = Subcontractor::findOrFail($id);

        return response()->json([
            'company_name'       => $subcontractor->company_name,
            'contact_person'     => $subcontractor->contact_person,
            'company_address'    => $subcontractor->company_address,
            'contact_number'     => $subcontractor->contact_number,
            'email'              => $subcontractor->email,
            'username'           => $subcontractor->username,
            'invoice_terms'      => $subcontractor->invoice_terms,
            'payment_terms'      => $subcontractor->payment_terms,
            'department'         => $subcontractor->department,
            'pay_rate'           => $subcontractor->pay_rate,
            'pmva_trained_officer' => $subcontractor->pmva_trained_officer,
            'vat_registered'     => $subcontractor->vat_registered ? 'Yes' : 'No',
            'vat_number'         => $subcontractor->vat_number,
        ]);
    }
}
