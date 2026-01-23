<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmployeeBan;
use App\Models\Site;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EmployeeBanController extends Controller
{
    public function indexForEmployee($employeeId)
    {
        $bans = EmployeeBan::where('employee_id', $employeeId)
            ->with(['site', 'client'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['bans' => $bans]);
    }

    public function formData()
    {
        $clients = User::role('client')->select('id', 'first_name', 'last_name')->orderBy('first_name')->get();
        $sites = Site::select('id', 'site_name')->orderBy('site_name')->get();

        return response()->json(['clients' => $clients, 'sites' => $sites]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|integer|exists:employees,id',
            'site_id' => 'nullable|integer|exists:sites,id',
            'client_id' => 'nullable|integer|exists:users,id',
            'reason' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['created_by'] = Auth::id() ?? null;

        $ban = EmployeeBan::create($data);

        return response()->json(['ban' => $ban], 201);
    }

    public function destroy($id)
    {
        $ban = EmployeeBan::findOrFail($id);
        $ban->delete();
        return response()->json(['success' => true]);
    }
}
