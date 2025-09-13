<?php

namespace App\Http\Controllers;

use Notify;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Employee;
use App\Models\ShiftDate;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use App\Models\EmployeeLeave;
use App\DataTables\LeavesDataTable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class EmployeeLeaveController extends Controller
{
    public function index(LeavesDataTable $dataTable)
    {
        $status = [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ];
        $employees = \App\Models\Employee::selectRaw("CONCAT(fore_name, ' ', sur_name) as full_name, id")->pluck('full_name', 'id')->toArray();

        return $dataTable->render('leave_management.leaves', compact('status', 'employees'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:255',
            'employee_id' => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:from_date',
            'type' => 'required',
            'user_id' => 'mullable',
        ]);

        $validator->after(function ($validator) use ($request) {
            $conflictExists = \App\Models\LeaveRequest::where('employee_id', $request->employee_id)
                ->where('status', '!=', 'rejected')
                ->where(function ($query) use ($request) {
                    $query->whereBetween('start_date', [$request->start_date, $request->start_date])
                        ->orWhereBetween('end_date', [$request->end_date, $request->end_date])
                        ->orWhere(function ($query) use ($request) {
                            $query->where('start_date', '<=', $request->start_date)
                                ->where('end_date', '>=', $request->end_date);
                        });
                })
                ->exists();

            if ($conflictExists) {
                $validator->errors()->add('start_date', 'This employee already has a leave request overlapping with the selected dates.');
            }
        });

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            } else {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }

        $employee = Employee::find($request->employee_id);
        $user = User::find($employee->user_id);

        $leave = LeaveRequest::create([
            'user_id' => $user->id,
            'employee_id' => $employee->id,
            'start_date' => $request['start_date'],
            'end_date' => $request['end_date'],
            'reason' => $request['reason'],
            'type' => $request['type'],
        ]);


        return response()->json(['message' => 'Leave created successfully']);
    }

    public function edit($id)
    {
        $leave = LeaveRequest::find($id);
        $status = [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ];
        return response()->json(['leave' => $leave, 'status' => $status]);
    }

    public function update(Request $request, $id)
    {
        $leave = LeaveRequest::find($id);
        if (!$leave) {
            return response()->json(['error' => 'Leave not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'reason'        => 'required|string|max:255',
            'employee_id'   => 'required|exists:employees,id',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'status'        => 'required|in:pending,approved,rejected,denied',
            'hours'         => 'nullable|numeric|min:0',
            'reject_reason' => 'required_if:status,rejected|required_if:status,denied|nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $employee = Employee::find($validated['employee_id']);
        if (!$employee) {
            return response()->json(['error' => 'Employee not found.'], 404);
        }

        // calculate totals (same as before)
        $start = Carbon::parse($validated['start_date']);
        $end   = Carbon::parse($validated['end_date']);
        $totalDays = $end->diffInDays($start) + 1;
        $hoursPerDay = $validated['hours'] ?? 8;
        $totalHours  = $totalDays * $hoursPerDay;

        $paid = false;
        $sspPaidDays = 0;
        $holidayHours = 0;
        $unpaidHours = 0;
        $amountPaid = 0;

        if ($leave->type === 'sick_leave') {
            $sspRate = 23.75;
            $waitingDays = 3;
            $sspPaidDays = max($totalDays - $waitingDays, 0);
            $unpaidHours = min($waitingDays, $totalDays) * $hoursPerDay;
            $paid = $sspPaidDays > 0;
            $amountPaid = $sspPaidDays * $sspRate;
        }

        if ($leave->type === 'annual_leave') {
            $holidayBalance = $employee->holiday_balance ?? 0;
            if ($totalHours > $holidayBalance) {
                $holidayHours = $holidayBalance;
                $unpaidHours  = $totalHours - $holidayBalance;
                $paid = $holidayBalance > 0;
            } else {
                $holidayHours = $totalHours;
            }
        }

        // Always set reject_reason from request
        $leave->reason          = $validated['reason'];
        $leave->employee_id     = $validated['employee_id'];
        $leave->start_date      = $validated['start_date'];
        $leave->end_date        = $validated['end_date'];
        $leave->status          = $validated['status'];
        $leave->reject_reason   = $request->reject_reason ?? null;

        if ($validated['status'] === 'approved') {
            $leave->approved_by = auth()->id();
            $leave->approved_at = now();
            $leave->reject_reason = null;
        }

        // calculated fields
        $leave->hours            = $totalHours;
        $leave->approved_hours   = $totalHours - $unpaidHours;
        $leave->paid             = $paid;
        $leave->ssp_paid_days    = $sspPaidDays;
        $leave->holiday_days_used = $holidayHours;
        $leave->unpaid_days      = $unpaidHours / $hoursPerDay;
        $leave->amount_paid      = $amountPaid;

        $leave->save();

        $userId = $leave->user_id;
        $employeeName = $employee->fore_name . ' ' . $employee->sur_name;

        if ($leave->status === 'approved') {
            Notify::toDashboard(
                null,
                'alert',
                'Leave Approved',
                "An admin Approved a leave request from {$leave->start_date} to {$leave->end_date} requested by $employeeName",
                "/leaves"
            );

            $shift = ShiftDate::find($leave->shift_id);
            if ($shift) {
                $shift->staff_id = null;
                $shift->status = 'cancelled';
                $shift->is_assign = 6;
                $shift->save();

                // $staff = User::role('security_staff')->where('id',$shift->staff_id)->first();
                send_push_notification(
                    $userId,
                    'Removed from shift',
                    "You have been removed from shift (ID: " . $shift->id . ' at ' . $shift->shift_date,
                    ['shift' => $shift]
                );

                Notify::toDashboard(
                    null,
                    'alert',
                    'Guard Removed from shift',
                    "Guard " . $employeeName . ' Has been removed from shift due to leave accepted, Reassign the shift before ' . $shift->start_time,
                    "/shift-dates/$shift->id/view",
                );
            }

            send_push_notification(
                $userId,
                'Leave Approved',
                "Your leave request has been approved by admin.",
                ['leave' => $leave]
            );
        }

        if ($leave->status === 'rejected' || $leave->status === 'denied') {
            Notify::toDashboard(
                null,
                'alert',
                'Leave Rejected',
                "An admin rejected a leave request from {$leave->start_date} to {$leave->end_date} requested by $employeeName . Reason: {$leave->reject_reason}",
                "/leaves"
            );

            send_push_notification(
                $userId,
                'Leave Rejected',
                "Your leave request was rejected. Reason: {$leave->reject_reason}",
                ['leave' => $leave]
            );
        }

        return response()->json(['message' => 'Leave updated successfully']);
    }

    public function destroy($leaveId)
    {
        $leave = LeaveRequest::findOrFail($leaveId);
        $leave->delete();

        return response()->json(['success' => true]);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:leave_requests,id',
        ]);

        LeaveRequest::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => 'Selected leaves deleted.']);
    }

    public function getLogs($id)
    {
        $leave = LeaveRequest::with('logs')->findOrFail($id);

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
        $leave = LeaveRequest::findOrFail($id);
        $user = Employee::where('user_id', $leave->user_id)->first();

        return response()->json([
            'user' => $user ? $user->fore_name . ' ' . $user->sur_name : 'N/A',
            'employee_id' => $leave->employee_id,
            'reason' => $leave->reason,
            'start_date' => $leave->start_date,
            'end_date' => $leave->end_date,
            'status' => ucfirst($leave->status),
            'type' => $leave->type,
            'paid' => $leave->paid ? 'Yes' : 'No',
            'hours' => $leave->hours,
            'approved_hours' => $leave->approved_hours,
            'auto_split' => $leave->auto_split ? 'Yes' : 'No',
            'ssp_paid_days' => $leave->ssp_paid_days,
            'unpaid_days' => $leave->unpaid_days,
            'amount_paid' => $leave->amount_paid,
            'reject_reason' => $leave->reject_reason,
        ]);
    }

    public function calendar()
    {
        return view('leave_management.calendar');
    }
}
