<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Helpers\Logger;
use Illuminate\Http\Request;
use App\Models\Subcontractor;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\DataTables\SubcontractorsDataTable;
use Illuminate\Support\Str;

class SubContractorController extends Controller
{
    public function index(SubcontractorsDataTable $dataTable)
    {
        // $subcontractors = Subcontractor::all();

        // foreach ($subcontractors as $s) {

        //     $email = $s->email ?: Str::slug($s->company_name).'_'.$s->id.'@example.com';

        //     $user = User::where('email', $email)
        //                 ->orWhere('username', Str::slug($s->company_name).'_'.$s->id)
        //                 ->first();

        //     if (!$user) {
        //         $user = User::create([
        //             'name'       => $s->company_name,
        //             'first_name' => $s->company_name,
        //             'last_name'  => '',
        //             'username'   => Str::slug($s->company_name).'_'.$s->id,
        //             'email'      => $email,
        //             'password'   => Hash::make('password'),
        //         ]);

        //         $user->assignRole('subcontractor');
        //     }

        //     $s->user_id = $user->id;
        //     $s->email   = $user->email;
        //     $s->save();
        // }

        return $dataTable->render('subcontractors.index');
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_name'       => 'required|string|max:255|unique:users,username',
            'company_address'    => 'nullable|string|max:355',
            'contact_number'     => [
                'nullable',
                'string',
                'min:9',
                'max:50',
                'regex:/^(\+?\d{1,3})?[-.\s]?\(?\d+\)?([-.\s]?\d+)*$/'
            ],
            'contact_person'     => 'nullable|string|max:255',
            // 'email'              => 'required|email:dns|max:255',
            'email'           => 'required|email|max:255|unique:users,email,NULL,id,deleted_at,NULL',
            'password'           => [
                'required',
                'string',
                'min:6',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/'
            ],
            'invoice_terms'      => 'nullable|string|max:255',
            'payment_terms'      => 'nullable|string|max:255',
            'department'         => 'nullable|string|max:255',
            'vat_registered'     => 'nullable',
            'vat_number'         => 'nullable|string|max:255',
            'pay_rate'           => 'nullable|numeric',
            'commission'         => 'nullable|numeric|min:0|max:100',
            'pmva_trained_officer' => 'nullable',
        ], [
            'contact_number.regex' => 'The contact number format is invalid. It should be a valid phone number.',
            'password.regex' => 'The password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // Create user for subcontractor
        $user = User::create([
            'name'     => $data['company_name'],
            'first_name'     => $data['company_name'],
            'last_name'     => '',
            'username' => $data['company_name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $role = Role::firstOrCreate(['name' => 'subcontractor']);
        $user->assignRole($role);

        $data['user_id'] = $user->id;
        unset($data['password']);
        // unset($data['username'], $data['password']);

        Subcontractor::create($data);

        return response()->json(['message' => 'Subcontractor created successfully']);
    }
    public function update(Request $request, $id)
    {
        $subcontractor = Subcontractor::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'company_name'       => 'required|string|max:255',
            'company_address'    => 'nullable|string|max:355',
            'contact_number'     => [
                'nullable',
                'string',
                'min:9',
                'max:50',
                'regex:/^(\+?\d{1,3})?[-.\s]?\(?\d+\)?([-.\s]?\d+)*$/'
            ],
            'contact_person'     => 'nullable|string|max:255',
            'email'              => 'required|email:dns|max:255',
            'invoice_terms'      => 'nullable|string|max:255',
            'payment_terms'      => 'nullable|string|max:255',
            'department'         => 'nullable|string|max:255',
            'vat_registered'     => 'nullable',
            'vat_number'         => 'nullable|string|max:255',
            'pay_rate'           => 'nullable|numeric',
            'commission'         => 'nullable|numeric|min:0|max:100',
            'pmva_trained_officer' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $subcontractor->update($data);

        return response()->json(['message' => 'Subcontractor updated successfully']);
    }
    public function edit($id)
    {
        $subcontractor = Subcontractor::find($id);
        return response()->json(['subcontractor' => $subcontractor]);
    }

    // Delete a single subcontractor along with related user
    public function delete($id)
    {
        $subcontractor = Subcontractor::findOrFail($id);
        $empSubcontractor = User::role('subcontractor')->find($subcontractor->user_id);

        // Delete related user if exists
        if ($subcontractor->user_id) {
            User::where('id', $subcontractor->user_id)->delete();
        }

        Logger::log(Auth::user(), 'Delete', 'Subcontractor ' . $subcontractor->company_name . ' and related user deleted.');

        $empSubcontractor->delete();
        $subcontractor->forceDelete();

        return response()->json(['message' => 'Subcontractor and related user deleted successfully.']);
    }

    // Bulk delete subcontractors along with their related users
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:sub_contractors,id',
        ]);

        // Get subcontractors
        $subcontractors = Subcontractor::whereIn('id', $request->ids)->get();

        // Collect related user IDs
        $userIds = $subcontractors->pluck('user_id')->filter()->toArray();

        // Delete related users (users are hard-deleted)
        User::whereIn('id', $userIds)->delete();

        foreach ($subcontractors as $sc) {
            Logger::log(Auth::user(), 'Delete', 'Subcontractor ' . $sc->company_name . ' and related user deleted.');
        }

        // Delete subcontractors
        Subcontractor::whereIn('id', $request->ids)->forceDelete();

        return response()->json(['message' => 'Selected subcontractors and related users deleted successfully.']);
    }


    public function getLogs($id)
    {
        $subcontractor = Subcontractor::with('logs')->findOrFail($id);

        return response()->json([
            'logs' => $subcontractor->logs->map(function ($log) {
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
        $subcontractor = Subcontractor::findOrFail($id);

        return response()->json([
            'company_name'       => $subcontractor->company_name,
            'contact_person'     => $subcontractor->contact_person,
            'company_address'    => $subcontractor->company_address,
            'contact_number'     => $subcontractor->contact_number,
            'email'              => $subcontractor->email,
            'username'           => $subcontractor->username,
            'invoice_terms'      => $subcontractor->invoice_terms,
            'payment_terms'      => $subcontractor->payment_terms,
            'department'         => $subcontractor->department,
            'pay_rate'           => $subcontractor->pay_rate,
            'pmva_trained_officer' => $subcontractor->pmva_trained_officer,
            'vat_registered'     => $subcontractor->vat_registered ? 'Yes' : 'No',
            'vat_number'         => $subcontractor->vat_number,
            'commission'         => $subcontractor->commission,
        ]);
    }


    public function employees($id)
    {
        $employees = \App\Models\Employee::query();

                // Support both legacy scalar subcontractor values and JSON arrays.
                // Only use JSON_CONTAINS when the column holds valid JSON; check both numeric and string representations.
                $employees->where(function ($q) use ($id) {
                        $q->where('subcontractor', $id)
                            ->orWhereRaw(
                                    '(JSON_VALID(subcontractor) AND (JSON_CONTAINS(subcontractor, ?) OR JSON_CONTAINS(subcontractor, ?)))',
                                    [json_encode((int) $id), json_encode((string) $id)]
                            );
                });

        $userIds = $employees->pluck('user_id')->filter()->unique()->toArray();

        $users = [];
        if (!empty($userIds)) {
            $users = User::whereIn('id', $userIds)
                ->select('id', 'first_name', 'last_name', 'email')
                ->orderBy('first_name')
                ->get();
        }

        return response()->json(['data' => $users]);
    }

    /**
     * Given a user id (employee), return the subcontractors associated with that employee.
     * Handles legacy scalar, CSV string, and JSON/array storage in `subcontractor`.
     */
    public function subcontractorsForEmployee($userId)
    {
        $employee = \App\Models\Employee::where('user_id', $userId)->first();

        $subs = [];
        if ($employee) {
            $raw = $employee->subcontractor;

            // Normalize into array of ids (may be array, CSV, or single scalar)
            if (is_array($raw)) {
                $ids = array_filter($raw);
            } elseif (is_null($raw) || $raw === '') {
                $ids = [];
            } else {
                // Try to decode JSON first
                $decoded = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $ids = array_filter($decoded);
                } else {
                    // Fallback: comma-separated values or single value
                    $ids = array_filter(array_map('trim', explode(',', (string) $raw)));
                }
            }

            $ids = array_map('intval', $ids);

            if (!empty($ids)) {
                // First try matching sub_contractors.id
                $subs = Subcontractor::whereIn('id', $ids)
                    ->select('id', 'company_name', 'user_id', 'email')
                    ->orderBy('company_name')
                    ->get();

                // If nothing found, maybe the stored ids are actually user_ids referencing subcontractor.user_id
                if ($subs->isEmpty()) {
                    $subs = Subcontractor::whereIn('user_id', $ids)
                        ->select('id', 'company_name', 'user_id', 'email')
                        ->orderBy('company_name')
                        ->get();
                }
            }
        }

        return response()->json(['data' => $subs]);
    }
}
