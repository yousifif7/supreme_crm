<?php

namespace App\Http\Controllers;

use App\Helpers\Logger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::paginate(10);
        return view('user_management.permissions', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|unique:permissions,name']);
        Permission::create(['name' => $request->name]);

        Logger::log(Auth::user(), 'Create', 'Permission '.$request->name.' Created');

        return response()->json(['message' => 'Permission created successfully']);
    }

    public function edit($id)
    {
        $permission = Permission::findOrFail($id);
        return response()->json(['permission' => $permission]);
    }

    public function update(Request $request, $id)
    {
        $request->validate(['name' => 'required|unique:permissions,name,' . $id]);
        $permission = Permission::findOrFail($id);
        Logger::log(Auth::user(), 'Update', 'Permission '.$permission->name.' Updated to '. $request->name);
        $permission->update(['name' => $request->name]);


        return response()->json(['message' => 'Permission updated successfully']);
    }

    public function destroy($id)
    {
       $permission= Permission::findOrFail($id)->delete();
        Logger::log(Auth::user(), 'Delete', 'Permission '.$permission->name.' Deleted ');
        return response()->json(['message' => 'Permission deleted successfully']);
    }
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:permissions,id',
        ]);

        Permission::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => 'Selected permissions deleted.']);
    }
}
