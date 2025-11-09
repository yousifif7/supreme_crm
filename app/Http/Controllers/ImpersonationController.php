<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    /**
     * Start impersonating a client (admin -> client)
     */
    public function start($clientId)
    {
        $admin = Auth::user();

        // Only allow admins/superadmins to impersonate
        if (! $admin || ! method_exists($admin, 'hasAnyRole') || ! $admin->hasAnyRole(['admin', 'superadmin'])) {
            abort(403);
        }

        $client = Client::findOrFail($clientId);
        if (! $client->user_id) {
            abort(404, 'Client user account not found');
        }

        $clientUser = User::findOrFail($client->user_id);

        // store original admin id and return URL
        session(['impersonator_id' => $admin->id, 'impersonator_return_url' => url()->previous()]);

        // login as client
        Auth::loginUsingId($clientUser->id);

        return redirect()->route('client.dashboard');
    }

    /**
     * Stop impersonation and return to original admin
     */
    public function stop()
    {
        $impersonatorId = session('impersonator_id');
        $returnUrl = session('impersonator_return_url', route('clients.index'));

        // clear session keys
        session()->forget(['impersonator_id', 'impersonator_return_url']);

        if ($impersonatorId) {
            Auth::loginUsingId($impersonatorId);
        } else {
            // fallback: logout
            Auth::logout();
        }

        return redirect($returnUrl);
    }
}
