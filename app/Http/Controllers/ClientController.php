<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Client;
use App\Helpers\Logger;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\DataTables\ClientsDataTable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    public function index(ClientsDataTable $dataTable, Request $request)
    {
        $companys = Company::all();
        $staffs = User::role('security_staff')->get();

        return $dataTable->render('clients.index', compact('companys', 'staffs'));
        // view('clients.index');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_name'     => 'required|string|max:255',
            'address'         => 'nullable|string|max:355',
            'contact_number' => [
                'nullable',
                'string',
                'min:9',
                'max:50',
                'regex:/^(\+?\d{1,3})?[-.\s]?\(?\d+\)?([-.\s]?\d+)*$/'
            ],
            'contact_person'             => 'nullable|string|max:255',
            'email' => 'required|email:dns|max:255|unique:users,email',
            'invoice_terms'   => 'nullable|string|max:255',
            'payment_terms'   => 'nullable|string|max:255',
            'doc_1'           => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,excel,csv|max:20048',
            'doc_2'           => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,excel,csv|max:20048',
            'doc_3'           => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,excel,csv|max:20048',
            'contract_start'   => 'nullable|date',
            'contract_end'     => 'nullable|date|after_or_equal:contact_start',
            'company_id'      => 'nullable|integer',
            'guard_rate'      => 'nullable|numeric',
            'office_rate'     => 'nullable|numeric',
            'vat'  => 'nullable',
            // 'username' => 'required|email|unique:users,username',
            'password' => [
                'required',
                'string',
                'min:8', // minimum 8 characters
                'regex:/[A-Z]/', // at least one uppercase
                'regex:/[a-z]/', // at least one lowercase
                'regex:/[0-9]/', // at least one number
                'regex:/[@$!%*?&#]/', // at least one special char
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
            'username' => $data['email'],
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
        unset($clientData['password']);
        // unset($clientData['username'], $clientData['password']);

        // Create client
        $client= Client::create($clientData);

        Logger::log(Auth::user(), 'Created', 'A new client has been added');

        return response()->json(['message' => 'Client created successfully']);
    }
    public function update(Request $request, $id)
    {
        $client = Client::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'client_name'     => 'required|string|max:255',
            'address'         => 'nullable|string|max:355',
            'contact_number' => [
                'nullable',
                'string',
                'min:9',
                'max:50',
                'regex:/^(\+?\d{1,3})?[-.\s]?\(?\d+\)?([-.\s]?\d+)*$/'
            ],
            'contact_person'             => 'nullable|string|max:255',
            'email' => 'required|email:dns|max:255',
            'invoice_terms'   => 'nullable|string|max:255',
            'payment_terms'   => 'nullable|string|max:255',
            'doc_1'           => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
            'doc_2'           => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
            'doc_3'           => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
            'contract_start'   => 'nullable|date',
            'contract_end'     => 'nullable|date|after_or_equal:contact_start',
            'company_id'      => 'nullable|integer',
            'guard_rate'      => 'nullable|numeric',
            'office_rate'     => 'nullable|numeric',
            'vat'  => 'nullable',
            'password' => [
                'required',
                'string',
                'min:8', // minimum 8 characters
                'regex:/[A-Z]/', // at least one uppercase
                'regex:/[a-z]/', // at least one lowercase
                'regex:/[0-9]/', // at least one number
                'regex:/[@$!%*?&#]/', // at least one special char
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

        if ($request->email || $request->password) {
            $user = User::role('client')->where('id', $client->user->id)->first();

            if ($user) {
                if ($request->email) {
                    $user->email = $request->email;
                    $client->email = $request->email;
                }
                if (!$request->email) {
                    $user->email = $user->email;
                    $client->email = $client->email;
                }

                if ($request->password) {
                    // Use Hash facade to hash the password
                    $user->password = Hash::make($request->password);
                }
                if (!$request->password) {
                    // Use Hash facade to hash the password
                    $user->password = $user->password;
                }

                $user->save();
            }
        }
        unset($data['password']);
        // update client
        $client->update($data);

        Logger::log(Auth::user(), 'Updated', 'Client'.$client->client_name.' Updated');

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
        $empClient = User::role('client')->find($client->user_id);

        Logger::log(Auth::user(), 'Delete', 'Client '.$client->client_name.' Deleted');
        $empClient->forceDelete();
        $client->forceDelete();

        return response()->json(['success' => true]);
    }
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:clients,id',
        ]);

        $clients=Client::whereIn('id', $request->ids)->get();
        foreach($clients as $client){
            $empClient = User::role('client')->find($client->user_id);
            Logger::log(Auth::user(), 'Delete', 'Client '.$client->client_name.' Deleted');
            
            $empClient->forceDelete();
            $client->forceDelete();
        }

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
            'company' => optional($client->company)->name ?? '',
            'manager' => optional($client->manager)->fore_name ?? '',
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
        
        Logger::log(Auth::user(), 'Update', 'Client '.$client->client_name.' Assigned a manager');

        return back()->with(['success' => 'Manager assigned successfully.']);
    }
}
