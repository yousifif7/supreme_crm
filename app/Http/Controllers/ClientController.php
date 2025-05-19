<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::orderBy('id', 'desc')->paginate(15);
        $companys = Company::all();
        return view('clients.index', compact('clients', 'companys'));
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_name'     => 'required|string|max:255',
            'address'         => 'required|string|max:255',
            'contact_number'  => 'required|string|max:50',
            'fax'             => 'required|string|max:50',
            'email'           => 'required|email|max:255',
            'invoice_terms'   => 'required|string|max:255',
            'payment_terms'   => 'required|string|max:255',
            'doc_1'           => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
            'doc_2'           => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
            'doc_3'           => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
            'contract_start'   => 'nullable|date',
            'contract_end'     => 'nullable|date|after_or_equal:contact_start',
            'company_id'      => 'nullable|integer',
            'guard_rate'      => 'required|numeric',
            'office_rate'     => 'required|numeric',
            'vat'  => 'nullable',
            'username'        => 'required|email',          // Assuming username is email for user
            'password'        => 'required|string|min:6',   // Add password validation
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
            'username' => $data['client_name'],
            'email' => $data['username'],
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

        // Now create client with cleaned data
        Client::create($clientData);

        return response()->json(['message' => 'Client created successfully']);
    }
    public function update(Request $request, $id)
    {
        $client = Client::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'client_name'     => 'required|string|max:255',
            'address'         => 'required|string|max:255',
            'contact_number'  => 'required|string|max:50',
            'fax'             => 'required|string|max:50',
            'email'           => 'required|email|max:255',
            'invoice_terms'   => 'required|string|max:255',
            'payment_terms'   => 'required|string|max:255',
            'doc_1'           => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
            'doc_2'           => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
            'doc_3'           => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
            'contract_start'   => 'nullable|date',
            'contract_end'     => 'nullable|date|after_or_equal:contact_start',
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
}
