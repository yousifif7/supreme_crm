<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\LoginActivity;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        // Record login activity
        try {
            LoginActivity::create([
                'admin_id' => $user?->admin_id,
                'user_id' => $user?->id,
                'login_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (\Exception $e) {
            // don't break login on logging errors
        }

        if ($user && $user->hasRole('client')) {
            return redirect()->route('client.dashboard');
        }

        return redirect()->route('dashboard');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Attempt to stamp logout time for user's latest login activity
        try {
            $user = $request->user();
            if ($user) {
                $last = LoginActivity::where('user_id', $user->id)
                    ->whereNull('logout_at')
                    ->latest('login_at')
                    ->first();
                if ($last) {
                    $last->update(['logout_at' => now()]);
                }
            }
        } catch (\Exception $e) {
            // ignore logging failures
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
