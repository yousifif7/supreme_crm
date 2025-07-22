<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use App\DataTables\LeavesDataTable;
use Illuminate\Support\Facades\Validator;
use App\Models\EmployeeLeave;
use Carbon\Carbon;

class EmployeeLeaveController extends Controller
{
    public function index(LeavesDataTable $dataTable)
    {
        $status = [
            'applied' => 'Applied',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ];
        $employees = \App\Models\Employee::selectRaw("CONCAT(fore_name, ' ', sur_name) as full_name, id")->pluck('full_name', 'id')->toArray();

        return $dataTable->render('leave_management.leaves', compact('status', 'employees'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'leave_entitlement' => 'required|string|max:255',
            'employee_id' => 'required|exists:employees,id',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        $validator->after(function ($validator) use ($request) {
            $conflictExists = \App\Models\EmployeeLeave::where('employee_id', $request->employee_id)
                ->where('status', '!=', 'rejected')
                ->where(function ($query) use ($request) {
                    $query->whereBetween('from_date', [$request->from_date, $request->to_date])
                          ->orWhereBetween('to_date', [$request->from_date, $request->to_date])
                          ->orWhere(function ($query) use ($request) {
                              $query->where('from_date', '<=', $request->from_date)
                                    ->where('to_date', '>=', $request->to_date);
                          });
                })
                ->exists();

            if ($conflictExists) {
                $validator->errors()->add('from_date', 'This employee already has a leave request overlapping with the selected dates.');
            }
        });

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            } else {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }

        $validated = $validator->validated();

        $leave = EmployeeLeave::create($validated);

        return response()->json(['message' => 'Leave created successfully']);
    }

    public function edit($id)
    {
        $leave = EmployeeLeave::find($id);
        $status = [
            'applied' => 'Applied',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ];
        return response()->json(['leave' => $leave, 'status' => $status]);
    }

    public function update(Request $request, $id)
    {
        $leave = EmployeeLeave::find($id);
        if (!$leave) {
            return response()->json(['error' => 'Leave not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'leave_entitlement' => 'required|string|max:255',
            'employee_id' => 'required',
            'from_date' => 'required|date',
            'to_date' => 'required|date',
            'status' => 'required',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            } else {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }

        $validated = $validator->validated();

        if($validated['status'] == 'approved')
        {
            $validated['approved_by'] = auth()->id();
            $validated['approved_at'] = now();
        }

        $leave->update($validated);

        return response()->json(['message' => 'Leave updated successfully']);
    }

    public function destroy($leaveId)
    {
        $leave = EmployeeLeave::findOrFail($leaveId);
        $leave->delete();

        return response()->json(['success' => true]);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:employee_leaves,id',
        ]);

        EmployeeLeave::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => 'Selected leaves deleted.']);
    }

    public function getLogs($id)
    {
        $leave = EmployeeLeave::with('logs')->findOrFail($id);

        return response()->json([
            'logs' => $leave->logs->map(function ($log) {
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
        $leave = EmployeeLeave::findOrFail($id);

        return response()->json([
            'leave_entitlement' => $leave->leave_entitlement,
            'employee_id' => $leave->employee->fore_name .' '. $leave->employee->sur_name,
            'from_date' => $leave->from_date,
            'to_date' => $leave->to_date,
            'status' => ucfirst($leave->status),
        ]);
    }
}
