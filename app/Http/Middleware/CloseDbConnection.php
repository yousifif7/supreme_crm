<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CloseDbConnection
{
    /**
     * Handle an incoming request and ensure DB connection is closed after response
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Disconnect database after sending response
        try {
            DB::disconnect();
        } catch (\Exception $e) {
            \Log::warning('Middleware DB disconnect failed: ' . $e->getMessage());
        }
        
        return $response;
    }

    /**
     * Handle tasks after the response has been sent to the browser
     */
    public function terminate(Request $request, Response $response): void
    {
        try {
            DB::disconnect();
        } catch (\Exception $e) {
            // Silently fail
        }
    }
}
