<?php

namespace App\Http\Controllers;

use App\DataTables\ClientsDataTable;
use App\Models\Client;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class ClientController extends Controller
{
    public function index(ClientsDataTable $dataTable, Request $request)
    {
        $companys = Company::all();
        $staffs = Employee::all();

        return $dataTable->render('clients.index', compact('companys', 'staffs'));
        // view('clients.index');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_name'     => 'required|string|max:255',
            'address'         => 'required|string|max:355',
            'contact_number' => [
                'required',
                'string',
                'min:9',
                'max:50',
                'regex:/^(\+?\d{1,3})?[-.\s]?\(?\d+\)?([-.\s]?\d+)*$/'
            ],
            'contact_person'             => 'required|string|max:255',
            'email' => 'required|email:dns|max:255|unique:users,email',
            'invoice_terms'   => 'required|string|max:255',
            'payment_terms'   => 'required|string|max:255',
            'doc_1'           => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,excel,csv|max:20048',
            'doc_2'           => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,excel,csv|max:20048',
            'doc_3'           => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,excel,csv|max:20048',
            'contract_start'   => 'required|date',
            'contract_end'     => 'required|date|after_or_equal:contact_start',
            'company_id'      => 'nullable|integer',
            'guard_rate'      => 'required|numeric',
            'office_rate'     => 'required|numeric',
            'vat'  => 'nullable',
            'username' => 'required|email|unique:users,username',
            'password' => [
                'required',
                'string',
                'min:6',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/'
            ],
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            } else {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }

        $data = $validator->validated();
        // Handle document uploads
        foreach (['doc_1', 'doc_2', 'doc_3'] as $docField) {
            if ($request->hasFile($docField)) {
                $uploadPath = public_path('uploads/docs');
                if (!File::exists($uploadPath)) {
                    File::makeDirectory($uploadPath, 0755, true); // true = recursive
                }
                $file = $request->file($docField);
                $fileName = time() . "_{$docField}." . $file->getClientOriginalExtension();
                $file->move($uploadPath, $fileName);
                $data[$docField] = $fileName;
            }
        }
        // Create user with hashed password
        $user = User::create([
            'name' => $data['client_name'],
            'first_name' => $data['client_name'],
            'last_name' => '',
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Check if 'client' role exists, if not create it
        $role = Role::firstOrCreate(['name' => 'client']);

        // Assign role to user
        $user->assignRole($role);
        $data['user_id'] = $user->id;
        // Save client data (assuming Client model and $data matches columns)
        // Prepare client data by excluding user-related fields
        $clientData = $data;
        unset($clientData['username'], $clientData['password']);

        // Create client
        Client::create($clientData);

        return response()->json(['message' => 'Client created successfully']);
    }
    public function update(Request $request, $id)
    {
        $client = Client::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'client_name'     => 'required|string|max:255',
            'address'         => 'required|string|max:355',
            'contact_number' => [
                'required',
                'string',
                'min:9',
                'max:50',
                'regex:/^(\+?\d{1,3})?[-.\s]?\(?\d+\)?([-.\s]?\d+)*$/'
            ],
            'contact_person'             => 'required|string|max:255',
            'email' => 'required|email:dns|max:255',
            'invoice_terms'   => 'required|string|max:255',
            'payment_terms'   => 'required|string|max:255',
            'doc_1'           => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
            'doc_2'           => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
            'doc_3'           => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
            'contract_start'   => 'required|date',
            'contract_end'     => 'required|date|after_or_equal:contact_start',
            'company_id'      => 'nullable|integer',
            'guard_rate'      => 'required|numeric',
            'office_rate'     => 'required|numeric',
            'vat'  => 'nullable',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            } else {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }

        $data = $validator->validated();
        // Handle document uploads
        foreach (['doc_1', 'doc_2', 'doc_3'] as $docField) {
            if ($request->hasFile($docField)) {
                $uploadPath = public_path('uploads/docs');
                if (!File::exists($uploadPath)) {
                    File::makeDirectory($uploadPath, 0755, true); // true = recursive
                }
                $file = $request->file($docField);
                $fileName = time() . "_{$docField}." . $file->getClientOriginalExtension();
                $file->move($uploadPath, $fileName);
                $data[$docField] = $fileName;
            }
        }
        // update client
        $client->update($data);

        return response()->json(['message' => 'Client Update successfully']);
    }
    public function edit($id)
    {
        $client = Client::find($id);
        return response()->json(['client' => $client]);
    }
    public function delete($id)
    {
        $client = Client::findOrFail($id);
        $client->delete();

        return response()->json(['success' => true]);
    }
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:clients,id',
        ]);

        Client::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => 'Selected clients deleted.']);
    }
    public function getLogs($id)
    {
        $client = Client::with('logs')->findOrFail($id);

        return response()->json([
            'logs' => $client->logs->map(function ($log) {
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
        $client = Client::with('company', 'manager')->findOrFail($id);

        return response()->json([
            'client_name' => $client->client_name,
            'contact_person' => $client->contact_person,
            'address' => $client->address,
            'contact_number' => $client->contact_number,
            'email' => $client->email,
            'invoice_terms' => $client->invoice_terms,
            'payment_terms' => $client->payment_terms,
            'vat_registered' => $client->vat_registered ? 'Yes' : 'No',
            'guard_rate' => $client->guard_rate,
            'supervisor_rate' => $client->office_rate,
            'contract_period' => $client->contract_start . ' - ' . $client->contract_end,
            'documents' => collect([$client->doc_1, $client->doc_2, $client->doc_3])->filter()->implode(', '),
            'company' => optional($client->company)->name ?? 'N/A',
            'manager' => optional($client->manager)->fore_name ?? 'N/A',
        ]);
    }
    public function assignManager(Request $request, $id)
    {
        $request->validate([
            'manager_id' => 'required|exists:users,id', // assuming managers are users
        ]);

        $client = Client::findOrFail($id);
        $client->manager_id = $request->manager_id;
        $client->save();
        return back()->with(['success' => 'Manager assigned successfully.']);
    }
}
