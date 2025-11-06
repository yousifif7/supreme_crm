<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\User;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\ShiftDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controller as BaseController;

class ClientDashboardController extends BaseController
{
    public function __construct()
    {
        $this->middleware(['auth','role:client']);
    }

    public function index(Request $request)
    {
        $clientId = Auth::id();

        $invoicesCount = Invoice::where('client_id', $clientId)->count();
        $outstanding = Invoice::where('client_id', $clientId)
            ->sum(DB::raw('net_amount'));
        $sitesCount = Site::where('client_id', $clientId)->count();
        $upcomingShifts = ShiftDate::whereHas('shift', function($q) use ($clientId) {
            $q->where('client_id', $clientId);
        })->whereDate('shift_date', '>=', now()->toDateString())
          ->orderBy('shift_date')
          ->limit(5)
          ->get();

        $clientId = auth()->id(); // the logged-in client's user ID

        // --- Get latest user locations (only for users in client's sites) ---
        $userLocations = Location::with([
            'user:id,first_name,last_name',
            'user.employee:id,user_id,service_type',
        ])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereIn('id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('locations')
                    ->whereNotNull('user_id')
                    ->groupBy('user_id');
            })
            ->whereIn('user_id', function ($query) use ($clientId) {
                $query->select('staff_id')
                    ->from('shift_dates')
                    ->whereIn('shift_id', function ($subQuery) use ($clientId) {
                        $subQuery->select('id')
                            ->from('shifts')
                            ->whereIn('site_id', function ($siteQuery) use ($clientId) {
                                $siteQuery->select('id')
                                    ->from('sites')
                                    ->where('client_id', $clientId);
                            });
                    });
            })
            ->get()
            ->map(function ($l) {
                return [
                    'id' => 'user-' . $l->user_id,
                    'latitude' => (float) $l->latitude,
                    'longitude' => (float) $l->longitude,
                    'name' => optional($l->user)->first_name . ' ' . optional($l->user)->last_name,
                    'type' => 'user',
                    'service_type_id' => optional(optional($l->user)->employee)->service_type,
                    'accuracy' => $l->accuracy,
                    'on_duty' => (bool) $l->on_duty,
                    'timestamp' => optional($l->created_at)->toDateTimeString(),
                ];
            });

        // --- Sites belonging to this client ---
        $sites = Site::where('client_id', $clientId)
            ->whereHas('shifts', function ($query) {
                $query->whereHas('shiftDates', function ($query) {
                    $query->whereNotNull('staff_id');
                });
            })
            ->select('id', 'site_name', 'post_code')
            ->get();

        $siteLocations = $sites->map(function ($site) {
            return [
                'id' => 'site-' . $site->id,
                'name' => $site->site_name,
                'postalcode' => $site->post_code,
                'type' => 'site',
            ];
        });


        $apiKey = env('GOOGLE_MAPS_API_KEY');

        return view('clients.dashboard', compact('apiKey','invoicesCount','outstanding','sitesCount','upcomingShifts','siteLocations','userLocations'));
    }

    public function rota()
    {
        $clientId = Auth::id();

        $shifts = ShiftDate::with('shift.site','staff')
            ->whereHas('shift', function($q) use ($clientId) {
                $q->where('client_id', $clientId);
            })->orderBy('shift_date')->get();

        return view('clients.rota', compact('shifts'));
    }

    /**
     * Show client profile for the currently authenticated client user.
     */
    public function profile()
    {
        $userId = Auth::id();

        $client = Client::where('user_id', $userId)->first();

        return view('clients.profile', compact('client'));
    }

    /**
     * Update client profile and sync matched user fields (email/password).
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $client = Client::firstOrCreate(['user_id' => $user->id]);

        $validator = Validator::make($request->all(), [
            'client_name' => 'required|string|max:255',
            'address' => 'nullable|string|max:355',
            'contact_number' => [
                'nullable','string','min:9','max:50',
                'regex:/^(\+?\d{1,3})?[-.\s]?\(?\d+\)?([-.\s]?\d+)*$/'
            ],
            'contact_person' => 'nullable|string|max:255',
            'email' => 'required|email:dns|max:255|unique:users,email,'. $user->id,
            'invoice_terms' => 'nullable|string|max:255',
            'payment_terms' => 'nullable|string|max:255',
            'contract_start' => 'nullable|date',
            'contract_end' => 'nullable|date|after_or_equal:contract_start',
            'company_id' => 'nullable|integer',
            'guard_rate' => 'nullable|numeric',
            'office_rate' => 'nullable|numeric',
            'vat' => 'nullable',
            'password' => [
                'nullable','string','min:8',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*?&#]/',
            ],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        // Sync user email/password if provided
        if (!empty($data['email'])) {
            $user->email = $data['email'];
        }
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        // remove password before saving to client
        unset($data['password']);

        // ensure user_id present on client
        $data['user_id'] = $user->id;

        $client->fill($data);
        $client->save();

        return redirect()->back()->with('success', 'Profile updated successfully');
    }
}
