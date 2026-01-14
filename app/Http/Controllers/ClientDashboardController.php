<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\User;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\ShiftDate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
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

        $cutoff24 = Carbon::now()->subDay();
$cacheKeyUsers = 'client_dashboard_user_locations_' . $clientId;

$userLocations = Cache::remember($cacheKeyUsers, 60, function () use ($cutoff24, $clientId) {
    return Location::with([
        'user:id,first_name,last_name',
        'user.employee:id,user_id,service_type',
    ])
        ->whereNotNull('latitude')
        ->whereNotNull('longitude')
        ->whereIn('id', function ($query) use ($cutoff24, $clientId) {
            $query->select(DB::raw('MAX(l.id)'))
                ->from('locations as l')
                ->join('shift_dates as sd', 'sd.staff_id', '=', 'l.user_id')
                ->join('shifts as sh', 'sh.id', '=', 'sd.shift_id')
                ->join('sites as si', 'si.id', '=', 'sh.site_id')
                ->where('si.client_id', $clientId)
                ->whereNotNull('l.user_id')
                ->where('l.created_at', '>=', $cutoff24)
                ->groupBy('l.user_id');
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
});

$sevenDaysAgo = Carbon::now()->subDays(7)->startOfDay();
$cacheKeySites = 'client_dashboard_site_locations_' . $clientId . '_' . now()->toDateString();

$siteLocations = Cache::remember($cacheKeySites, 300, function () use ($clientId, $sevenDaysAgo) {
    return Site::query()
        ->select('sites.id', 'sites.site_name', 'sites.post_code', 'sites.address')
        ->join('shifts', 'shifts.site_id', '=', 'sites.id')
        ->join('shift_dates', 'shift_dates.shift_id', '=', 'shifts.id')
        ->where('sites.client_id', $clientId)
        ->whereNotNull('shift_dates.staff_id')
        ->where('shift_dates.shift_date', '>=', $sevenDaysAgo)
        ->distinct()
        ->get()
        ->map(function ($site) {
            return [
                'id' => 'site-' . $site->id,
                'name' => $site->site_name,
                'postalcode' => $site->post_code,
                'address' => $site->address,
                'type' => 'site',
            ];
        });
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
            'email' => 'required|email:dns|max:255|unique:users,email,'. $user->id .',id,deleted_at,NULL',
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
