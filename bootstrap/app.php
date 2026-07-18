<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register Spatie middleware
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'deny.security_staff' => \App\Http\Middleware\DenySecurityStaffFromWeb::class,
            'web.permission' => \App\Http\Middleware\EnforceWebPermission::class,
        ]);

        // CRM web stack: block guards + enforce Spatie permissions on mapped routes
        $middleware->web(append: [
            \App\Http\Middleware\DenySecurityStaffFromWeb::class,
            \App\Http\Middleware\EnforceWebPermission::class,
        ]);
        
        // Add DB connection cleanup middleware globally
        $middleware->append(\App\Http\Middleware\CloseDbConnection::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
