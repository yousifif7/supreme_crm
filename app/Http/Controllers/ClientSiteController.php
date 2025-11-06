<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Site;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controller as BaseController;

class ClientSiteController extends BaseController
{
    public function __construct()
    {
        $this->middleware(['auth','role:client']);
    }

    public function index()
    {
        $sites = Site::where('client_id', Auth::id())->get();
        return view('clients.sites.index', compact('sites'));
    }

    public function create()
    {
        return view('clients.sites.form', ['site' => new Site()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'site_name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'post_code' => 'nullable|string|max:50',
            'contact_number' => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:255',
        ]);

        $data['client_id'] = Auth::id();

        $site = Site::create($data);

        return redirect()->route('client.sites.index')->with('success', 'Site created');
    }

    public function edit($id)
    {
        $site = Site::findOrFail($id);
        if ($site->client_id !== Auth::id()) abort(403);
        return view('clients.sites.form', compact('site'));
    }

    public function update(Request $request, $id)
    {
        $site = Site::findOrFail($id);
        if ($site->client_id !== Auth::id()) abort(403);

        $data = $request->validate([
            'site_name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'post_code' => 'nullable|string|max:50',
            'contact_number' => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:255',
        ]);

        $site->update($data);

        return redirect()->route('client.sites.index')->with('success', 'Site updated');
    }

    public function show($id)
    {
        $site = Site::findOrFail($id);
        if ($site->client_id !== Auth::id()) abort(403);
        return view('clients.sites.show', compact('site'));
    }
}
