<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::orderBy('id', 'desc')->paginate(15);
        return view('clients.index', compact('clients'));
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_name'     => 'required|string|max:255',
            'address'         => 'nullable|string|max:255',
            'contact_number'  => 'nullable|string|max:50',
            'fax'             => 'nullable|string|max:50',
            'email'           => 'nullable|email|max:255',
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
        // Save client
        Client::create($data);

        return response()->json(['message' => 'Client created successfully']);
    }
    public function update(Request $request, $id)
    {
        $client = Client::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'client_name'     => 'required|string|max:255',
            'address'         => 'nullable|string|max:255',
            'contact_number'  => 'nullable|string|max:50',
            'fax'             => 'nullable|string|max:50',
            'email'           => 'nullable|email|max:255',
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
}
