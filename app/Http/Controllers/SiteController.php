<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SiteController extends Controller
{
    public function index()
    {
        $clients = Client::get();
        $sites = Site::with('client')->orderBy('id', 'desc')->paginate(15);
        return view('sites.index', compact('sites', 'clients'));
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id'      => 'required|integer',
            'site_name'      => 'nullable|string|max:255',
            'site_group'     => 'nullable|string|max:255',
            'address'        => 'nullable|string|max:255',
            'post_code'      => 'nullable|string|max:50',
            'site_code'      => 'nullable|string|max:50',
            'contact_number' => 'nullable|string|max:50',
            'note'           => 'nullable|string|max:1000',
            'manager_1_id'   => 'nullable|integer',
            'manager_2_id'   => 'nullable|integer',
            'start_time'     => 'nullable|string',
            'end_time'       => 'nullable|string',
            'break_time'     => 'nullable|string',
            'guard_rate'     => 'nullable|numeric',
            'office_rate'    => 'nullable|numeric',
            'billable_rate'  => 'nullable|numeric',
            'payable_rate'   => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            } else {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }

        $data = $validator->validated();

        // Save site
        Site::create($data);

        return response()->json(['message' => 'Site created successfully']);
    }
    public function update(Request $request, $id)
    {
        $site = Site::find($id);
        $validator = Validator::make($request->all(), [
            'client_id'      => 'required|integer',
            'site_name'      => 'nullable|string|max:255',
            'site_group'     => 'nullable|string|max:255',
            'address'        => 'nullable|string|max:255',
            'post_code'      => 'nullable|string|max:50',
            'site_code'      => 'nullable|string|max:50',
            'contact_number' => 'nullable|string|max:50',
            'note'           => 'nullable|string|max:1000',
            'manager_1_id'   => 'nullable|integer',
            'manager_2_id'   => 'nullable|integer',
            'start_time'     => 'nullable|string',
            'end_time'       => 'nullable|string',
            'break_time'     => 'nullable|string',
            'guard_rate'     => 'nullable|numeric',
            'office_rate'    => 'nullable|numeric',
            'billable_rate'  => 'nullable|numeric',
            'payable_rate'   => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            } else {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }

        $data = $validator->validated();

        // Update site
        $site->update($data);

        return response()->json(['message' => 'Site Updated successfully']);
    }
    public function edit($id)
    {
        $site = Site::find($id);
        return response()->json(['site' => $site]);
    }
    public function delete($id)
    {
        $site = Site::findOrFail($id);
        $site->delete();

        return response()->json(['success' => true]);
    }
}
